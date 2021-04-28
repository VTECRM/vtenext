<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@91082 */

require_once('include/BaseClasses.php');
require_once('modules/Users/LoginHistory.php');

/**
 * Class which checks if the current user should be logged out according to some
 * advanced conditions. So far there are 2 implementation, one time based, and another for 
 * concurrent logins
 */
class SessionValidator extends SDKExtendableUniqueClass {

	// different types of checks that can be done
	
	// This value disables the session expiration (although standard PHP session expiration is
	// still in place)
	const VALIDATION_NONE = 0;
	
	// This check limits the maximum number of session a single user can have.
	// Different access point are counted separately, like web, webservice, app.
	// External automatic plugins are not considered
	// This is the default
	const VALIDATION_CONCURRENT_LOGIN = 1;
	
	// This check limits the session to a maximum time. Can be also achieved with 
	// just the session expiration time, but we wouldn't be able to show the special
	// login page when the session expires.
	const VALIDATION_EXPIRATION = 2;
	
	
	// the strategy to be used in the validation.
	public $strategy = self::VALIDATION_CONCURRENT_LOGIN;
	
	// default config for various validation
	public $validationConfig = array(
		1 => array(
			'allowed_logins' => array(				// max number of concurrent accesses (0 means unlimited)
			
				'web' => 2,							// web access
				'ws' => 5,							// webservice acccess (excluding automatic plugins)
				'app' => 3,							// app access, this is the maximum number of devices connected
				'other' => 0,						// this is for all not handled cases, which by default are forbidden
			),
			
			'cache_duration' => 10,					// to track different sessions, I have to uswe the database, to store the active sessions,
													// so to avoid a big overhead to read the db at each request, cache the informations 
													// for this number of minutes
		),
		2 => array(
			'expiration_time' => 30,				// time (in minutes) after which the session expires in case of inactivity
			'cookie_name' => 'last_user_logged',	// the name of the cookie to set when saving the old user
		)
	);
	
	// crmv@101201
	// these variables are preserved between session expirations
	// be careful not to save any sensitive data in here
	public $preserveSessionVars = array(
		'lvs',
	);
	
	public $preserveSessionPath = 'cache/';			// the path where to store the preserved session vars
	
	public $preserveSessionDuration = 60;			// duration, in minutes, of the session vars
	// crmv@101201e
	
	// these files don't trigger the session refresh
	public $skip_files = array(
		'ActivityReminderCallbackAjax',
		'src/Notifications/CheckChanges',
		'TraceIncomingCall', // crmv@185734
		'CheckSession',
		'Validate'
	);
	
	public $timer_files = array(
		'ActivityReminderCallbackAjax',
		'src/Notifications/CheckChanges',
		'TraceIncomingCall', // crmv@185734
	);
	
	public function getConfig($strategy) {
		return $this->validationConfig[$strategy];
	}
	
	/**
	 * Check if this is the first time we "check-in"
	 */
	public function isStarted() {
		global $current_user;
		
		$ret = false;
		
		
		if ($this->strategy == self::VALIDATION_NONE) {
		
		} elseif ($this->strategy == self::VALIDATION_CONCURRENT_LOGIN) {
			$key = 'last_operation_u_'.$current_user->id;
			$ret = !VteSession::isEmpty($key);
			/*if (!$ret) {
				$LH = LoginHistory::getInstance();
				$LH->set_expired($current_user->user_name);
				
			}*/
		} elseif ($this->strategy == self::VALIDATION_EXPIRATION) {
			$key = 'last_operation_u_'.$current_user->id;
			$ret = !VteSession::isEmpty($key);
		}
		
		return $ret;
	}
	
	/**
	 * Mark the current session as "never expiring" and not accounted in
	 * session limits
	 */
	public function setUnlimitedSession() {
		VteSession::set('session_unaccount', 1);
	}
	
	/**
	 * Remove the mark from the current session, making it expiring according
	 * to the regular rules.
	 */
	public function unsetUnlimitedSession() {
		VteSession::remove('session_unaccount');
	}
	
	/**
	 * Check if the current session is unlimited and unaccounted
	 */
	public function isUnlimitedSession() {
		return (VteSession::get('session_unaccount') == 1);
	}
	
	
	/**
	 * Check if the session is still valid
	 */
	public function isValid($options = array(), &$reason = '') {
		global $adb, $current_user;
		
		$ret = true;
		$cfg = $this->getConfig($this->strategy);
		
		if ($this->strategy == self::VALIDATION_NONE) {
			return true;
		}
		
		// crmv@106590
		if (!$current_user || !$current_user->id) {
			$reason = 'expired';
			return false;
		}
		
		if ($this->isUnlimitedSession()) return true;
		
		if ($this->strategy == self::VALIDATION_CONCURRENT_LOGIN) {
			// check how many other new connections of the same type has been opened
			// check only after the cache is expired
			//$keyTs = 'last_operation_ts_u_'.$current_user->id;
			//$checkTs = time() - $cfg['cache_duration']*60;
			//if (VteSession::isEmpty($keyTs) || VteSession::get($keyTs) < $checkTs) {
				$LH = LoginHistory::getInstance();
				$ltime = $LH->getLoginTime($current_user->user_name);
				$type = $LH->getLoginType();
				$count = $LH->countOtherOpenSessions($current_user->user_name, 'auto', $ltime);
				$limit = intval($cfg['allowed_logins'][$type]);
				if ($limit > 0 && $count >= $limit) {
					// if there are newer open sessions, bayond the allowed limit, destroy the current session
					$reason = 'concurrent';
					$ret = false;
				}
			//}
		} elseif ($this->strategy == self::VALIDATION_EXPIRATION) {
			$key = 'last_operation_u_'.$current_user->id;
			$tocheck = time() - $cfg['expiration_time']*60;
			$ret = (VteSession::isEmpty($key) || VteSession::get($key) > $tocheck);
			if (!$ret) $reason = 'expired';
		}
		// crmv@106590e
		
		if (!$ret) {
			$this->log("The session has expired");
		}
		
		return $ret;
	}
	
	
	/**
	 * Mark the session as active right now, resetting any timer
	 */
	public function refresh() {
		global $current_user;
		
		$ret = true;
		$cfg = $this->getConfig($this->strategy);
		
		if (!$current_user || !$current_user->id) return false;
		
		
		if ($this->strategy == self::VALIDATION_NONE) {
			// do nothing
		} elseif ($this->strategy == self::VALIDATION_CONCURRENT_LOGIN) {
			// write in the table the time of the last activity, but only if the cache is not expired
			$key = 'last_operation_u_'.$current_user->id;
			$keyTs = 'last_operation_ts_u_'.$current_user->id;
			$checkTs = time() - $cfg['cache_duration']*60;
			if (VteSession::isEmpty($keyTs) || VteSession::get($keyTs) < $checkTs) {
				$LH = LoginHistory::getInstance();
				$LH->user_activity($current_user->user_name);
				VteSession::set($keyTs, time());
			}
			VteSession::set($key, time());
		} elseif ($this->strategy == self::VALIDATION_EXPIRATION) {
			$key = 'last_operation_u_'.$current_user->id;
			VteSession::set($key, time());
		}
		
		$this->log("Current session refreshed");
		return $ret;
	}

	// crmv@101201

	/**
	 * Save some session variables for the next re-login (only used when doing ajax login)
	 */
	public function saveSessionVars($userid) {
		if (is_writable($this->preserveSessionPath)) {
			$vars = array_intersect_key($_SESSION, array_flip($this->preserveSessionVars));
			$filename = $this->preserveSessionPath.'session_'.$userid.'.tmp';
			@file_put_contents($filename, serialize($vars));
		}
	}
	
	/**
	 * Restore some previously saved session variables
	 */
	public function restoreSessionVars($userid) {
		$filename = $this->preserveSessionPath.'session_'.$userid.'.tmp';
		if (is_readable($filename)) {
			// check the time
			if (filemtime($filename) >= time() - $this->preserveSessionDuration*60) {
				$vars = @file_get_contents($filename);
				if ($vars) {
					$vars = unserialize($vars);
					if (is_array($vars)) {
						// crmv@128133
						VteSession::reopen(true);
						foreach ($vars as $key => $value) {
							VteSession::set($key, $value);
						}
						VteSession::close(true);
						// crmv@128133e
					}
				}
			}
			// remove it
			@unlink($filename);
		}
	}
	
	/**
	 * Clear the stored session variables
	 */
	public function clearSessionVars($userid) {
		$filename = $this->preserveSessionPath.'session_'.$userid.'.tmp';
		if (is_readable($filename)) {
			@unlink($filename);
		}
	}
	// crmv@101201e
	
	public function saveUser() {
		global $current_user;
		
		$ret = true;
		$cfg = $this->getConfig($this->strategy);
		
		if ($this->strategy == self::VALIDATION_NONE) return $ret;
		
		// crmv@146653
		if ($cfg['cookie_name']) {
			// this is not a risk, since the user have to login with the right credentials anyway
			$cookieurl = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']) ?: '/';
			$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
			setcookie($cfg['cookie_name'], $current_user->user_name, 0, $cookieurl, '', $isHttps, true);
		}
		// crmv@146653e
		
		$this->log("Current user saved");
		return $ret;
	}
	
	
	public function userChanged($focus) {
		$cfg = $this->getConfig($this->strategy);
		
		$ret = false;
		if ($this->strategy == self::VALIDATION_NONE) {
			// do nothing
			return null;
		} elseif ($this->strategy == self::VALIDATION_CONCURRENT_LOGIN) {
		
		} elseif ($this->strategy == self::VALIDATION_EXPIRATION) {
			$cname = $cfg['cookie_name'];
			$ret = ($_COOKIE[$cname] != $focus->column_fields["user_name"]);
		}
		
		if ($ret) {
			$this->log("The new user is different ({$_COOKIE[$cname]} -> {$focus->column_fields['user_name']})");
		} else {
			$this->log("The new user is the same ({$focus->column_fields['user_name']})");
		}
		
		return $ret;
	}
	
	public function ajaxOutput($output = array()) {
		echo Zend_Json::encode($output);
		die();
	}
	
	public function getSessionId() {
		return session_id();
	}
	
	protected function log($message) {
		global $current_user;
		$uname = ($current_user ? $current_user->column_fields['user_name'] : '');
		$sessid = $this->getSessionId();
		$msg = "[".date('Y-m-d H:i:s')."] [$sessid] $uname: $message\n";
		// output, uncomment if needed
		//file_put_contents('logs/session.log', $msg, FILE_APPEND);
	}
	
}
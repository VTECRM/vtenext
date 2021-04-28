<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@128133 */

/**
 * Handler for all interactions with the http session.
 * Scripts shouldn't use any of the php internal session_* functions,
 * but only methods of this class.
 * Every session can be opened multiple times, with a maximum set by $maxRequests,
 * above which the request will wait.
 * 
 */
class VteSession {

	public static $autoClose = true;			// if true, close the session after every write
												// setting it to false, disable the maxRequest parameter (behaves like the standard session_start)
	public static $sessionName = 'PHPSESSID';
	public static $enableLog = false;
	
	public static $maxRequests = 5;				// max number of concurrent open sessions, 
												// if grater, start will wait until a session is closed
	public static $maxWaitTimeout = 30;			// wait no more than these seconds if the limit is passed
	
	protected static $sessionStarted = false;
	protected static $sessionOpen = false;
	protected static $currentSessionId;
	protected static $requestId = null;			// simple id to identify requests in logs
	
	protected static $fakeSession = false; // crmv@181231
	
	protected static $shutdownMemory = null; // crmv@169814

	public static function start() {
	
		if (self::$sessionStarted) {
			self::log("Session already started");
			return;
		}
		
		self::$requestId = uniqid();
	
		self::log("Starting session...");
		self::log("for request: ".$_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_URI']);
		
		// crmv@181231
		if (php_sapi_name() === 'cli') {
			return self::startCli();
		}
		
		$VP = VTEProperties::getInstance(); // crmv@198545
		$handlerType = $VP->get('session.handler');
		if ($handlerType) {
			// beware, no locking available here!
			// if you disable autoclose, you can have race conditions
			VteSessionHandler::register($handlerType, $VP->get('session.handler.params'));
		}
		// crmv@181231e
		
		// keep a counter on how many sessions are open
		register_shutdown_function('VteSession::onShutdown');
		
		// 3MB of memory to be freed on shutdown (in case of memory exhausted)
		// this amount has been verified with PHP 5.3
		self::$shutdownMemory = str_repeat('.', 3*1024*1024); // crmv@169814

		$t0 = microtime(true);
		session_name(self::$sessionName);
		
		// Allow for the session information to be passed via the URL for printing.
		if(isset($_REQUEST[self::$sessionName])) {
			session_id($_REQUEST[self::$sessionName]);
		}
		
		// Create or reestablish the current session
		//crmv@27520 crmv@29377 crmv@80972
		$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');

		// set the cookie url relative to this vte
		if (substr($_SERVER['SCRIPT_NAME'], -12) == 'Touch/ws.php') {
			$cookieurl = str_ireplace('modules/Touch/ws.php', '', $_SERVER['SCRIPT_NAME']) ?: '/';
		} else {
			$cookieurl = dirname($_SERVER['SCRIPT_NAME']) ?: '/';
		}

		session_set_cookie_params(0, $cookieurl, null, $isHttps, true); // crmv@80972
		session_start();
		
		// crmv@149300
		if (!isset($_SESSION['session_count'])) $_SESSION['session_count'] = 0;
		
		self::$currentSessionId = session_id();
		self::$sessionStarted = true;
		self::$sessionOpen = true;
		
		if (self::$autoClose && $_SESSION['session_count'] >= self::$maxRequests) {
			self::log("Waiting for another session to be released...");
			self::close();
			$released = false;
			$waitTs = $pollTs = microtime(true);
			while ($pollTs - $waitTs < self::$maxWaitTimeout) {
				usleep(500000); // poll every 0.5s
				// check if the session has been freed
				self::reopen();
				if ($_SESSION['session_count'] < self::$maxRequests) {
					$released = true;
					break;
				}
				self::close();
				$pollTs = microtime(true);
			}
			if (!$released) {
				self::log("Timeout waiting for session release, continuing anyway...");
				self::reopen();
			}
		}
		
		++$_SESSION['session_count'];
		// crmv@149300e
		
		$t1 = microtime(true);
		self::log("Session started in ".round($t1-$t0, 3)."s (sessionid: ".self::$currentSessionId.")");
		
		if (self::$autoClose) self::close();
	}
	
	// crmv@181231
	/**
	 * Start a fake session for CLI invocations
	 */
	protected static function startCli() {
		self::$autoClose = false; // no need, since the session is for the current script only
		
		self::$currentSessionId = 'cli_'.self::$requestId;
		self::$sessionStarted = true;
		self::$sessionOpen = true;
		self::$fakeSession = true;
		
		self::log("CLI session started");
	}
	// crmv@181231e
	
	/**
	 * Get the current session id
	 */
	public static function getId() {
		return self::$currentSessionId;
	}
	
	// remember, here the root dir might change, thus logging is disabled
	public static function onShutdown() {
		// crmv@169814 - free memory
		global $adb;
		self::$shutdownMemory = null;
		unset($adb);
		// crmv@169814e
		
		self::$enableLog = false;
		self::reopen();
		--$_SESSION['session_count'];
	}
	
	/**
	 * Suspend the current session, so the session file doesn't lock concurrent requests
	 */
	public static function close($autoClose = false) {
		if ($autoClose) self::$autoClose = true;
		if (self::$sessionStarted && self::$sessionOpen && !self::$fakeSession) { // crmv@181231
			self::$currentSessionId = session_id();
			self::$sessionOpen = false;
			session_write_close();
			self::log("Session closed");
		}
	}
	
	/**
	 * Reopen the current session, in order to write in it
	 */
	public static function reopen($keepOpen = false) {
		
		// check if started
		if (!self::$sessionStarted) return false;
		
		// check if I need to keep it open
		if ($keepOpen) self::$autoClose = false;
		
		// check if already open
		if (self::$sessionOpen) return false;
		
		// avoid headers to be sent
		@ini_set('session.use_only_cookies', false);
		@ini_set('session.use_cookies', false);
		@ini_set('session.use_trans_sid', false);
		@ini_set('session.cache_limiter', null);
		
		// reopen the session
		@session_start();
		
		self::$sessionOpen = true;
		
		self::log("Session reopened");
		return true;
	}
	
	/**
	 * Change the current session id
	 */
	public static function reset() {
	
		if (self::$fakeSession) return; // crmv@181231 - no-op in cli mode
		
		self::reopen();
		if (function_exists('session_status')) {
			// PHP >= 5.4.0
			if (session_status() === PHP_SESSION_ACTIVE) {
				session_regenerate_id(true);
			}
		} else {
			// PHP < 5.4.0
			if (session_id() != '') {
				session_regenerate_id(true);
			}
		}
		self::$currentSessionId = null;
		self::$sessionOpen = false;
		
		self::log("Session reset");
	}
	
	/**
	 * Destroy the current session
	 */
	public static function destroy() {
		self::reopen();
		$sessid = session_id();
		// crmv@181231
		if (self::$fakeSession) {
			$_SESSION = array();
		} elseif ($sessid != '') {
		// crmv@181231e
		
			// activate some values to send the updated cookie to the browser
			@ini_set('session.use_only_cookies', true);
			@ini_set('session.use_cookies', true);
			@ini_set('session.use_trans_sid', true);
			@ini_set('session.cache_limiter', 'nocache');
		
			session_regenerate_id(true);
			session_unset();
			session_destroy();
		}
		self::$currentSessionId = null;
		self::$sessionOpen = false;
		
		self::log("Session destroyed");
	}
	
	/**
	 * Set a single value or a set of values in the session
	 */
	public static function set($key, $value = '') {
		if (is_array($key)) return self::setMulti($key);
		self::reopen();
		$_SESSION[$key] = $value;
		self::log("Set value for key $key");
		if (self::$autoClose) self::close();
	}
	
	public static function increment($key, $increment = 1) {
		self::reopen();
		$_SESSION[$key] += $increment;
		self::log("Increment value for key $key");
		if (self::$autoClose) self::close();
	}
	
	public static function decrement($key, $increment = 1) {
		self::reopen();
		$_SESSION[$key] -= $increment;
		self::log("Decrement value for key $key");
		if (self::$autoClose) self::close();
	}
	
	public static function concat($key, $text) {
		self::reopen();
		$_SESSION[$key] .= $text;
		self::log("Concatenated value for key $key");
		if (self::$autoClose) self::close();
	}
	
	/**
	 * Append a single value to an array in the session
	 */
	public static function append($key, $value = '') {
		self::reopen();
		$_SESSION[$key][] = $value;
		self::log("Append value for key $key");
		if (self::$autoClose) self::close();
	}
	
	/**
	 * Get a single value in the session
	 */
	public static function get($key) {
		return $_SESSION[$key];
	}
	
	/**
	 * Check if the value is empty (workaround for PHP < 5.5 which doesn't support empty with functions)
	 */
	public static function isEmpty($key) {
		return empty($_SESSION[$key]);
	}
	
	/**
	 * Check if the key is set (PHP doesn't support isset with functions)
	 */
	public static function hasKey($key) {
		return isset($_SESSION[$key]);
	}
	
	/**
	 * Remove a single value from the session
	 */
	public static function remove($key) {
		self::reopen();
		unset($_SESSION[$key]);
		self::log("Removed key $key");
		if (self::$autoClose) self::close();
	}
	
	/**
	 * Set multiple values at once
	 */
	public static function setMulti($values) {
		self::reopen();
		foreach ($values as $key => $value) {
			$_SESSION[$key] = $value;
		}
		self::log("Set multiple keys (".count($values)." items: ".implode(",", array_keys($values)).")");
		if (self::$autoClose) self::close();
	}
	
	/**
	 * Remove multiple values at once
	 */
	public static function removeMulti($keys) {
		self::reopen();
		foreach ($keys as $key) {
			unset($_SESSION[$key]);
		}
		self::log("Removed multiple keys (".count($keys)." items: ".implode(",", $keys).")");
		if (self::$autoClose) self::close();
	}
	
	/**
	 * Set a single value in the session using a nested array as key
	 */
	public static function setArray($keys, $value) {
		self::reopen();
		if (!is_array($keys)) $keys = array($keys);
		$temp = &$_SESSION;
		foreach($keys as $key) {
			$temp = &$temp[$key];
		}
		$temp = $value;
		self::log("Set nested value for key ".implode(',', $keys));
		if (self::$autoClose) self::close();
	}
	
	/**
	 * Append a single value to and array in the session using a nested array as key
	 */
	public static function appendArray($keys, $value) {
		self::reopen();
		if (!is_array($keys)) $keys = array($keys);
		$temp = &$_SESSION;
		foreach($keys as $key) {
			$temp = &$temp[$key];
		}
		$temp[] = $value;
		self::log("Append nested value for key ".implode(',', $keys));
		if (self::$autoClose) self::close();
	}
	
	/**
	 * Get a single value from the session using a nested array as key
	 */
	public static function getArray($keys) {
		if (!is_array($keys)) $keys = array($keys);
		$temp = &$_SESSION;
		foreach($keys as $key) {
			if (!isset($temp[$key])) return null; // crmv@147333
			$temp = &$temp[$key];
		}
		return $temp;
	}
	
	/**
	 * Check for emptiness a value from the session using a nested array as key
	 */
	public static function isEmptyArray($keys) {
		if (!is_array($keys)) $keys = array($keys);
		$temp = &$_SESSION;
		foreach($keys as $key) {
			if (!isset($temp[$key])) return true; // crmv@147333
			$temp = &$temp[$key];
		}
		return empty($temp);
	}
	
	/**
	 * Check for emptiness a value from the session using a nested array as key
	 */
	public static function hasKeyArray($keys) {
		if (!is_array($keys)) $keys = array($keys);
		$temp = &$_SESSION;
		foreach($keys as $key) {
			if (!isset($temp[$key])) return false; // crmv@147333
			$temp = &$temp[$key];
		}
		return isset($temp);
	}
	
	/**
	 * Remove a value from the session using a nested array as key
	 */
	public static function removeArray($keys) {
		self::reopen();
		if (!is_array($keys)) $keys = array($keys);
		$keys = array_values($keys);
		$nkeys = count($keys);
		switch ($nkeys) {
			case 1:
				unset($_SESSION[$keys[0]]); break;
			case 2:
				unset($_SESSION[$keys[0]][$keys[1]]); break;
			case 3:
				unset($_SESSION[$keys[0]][$keys[1]][$keys[2]]); break;
			case 4:
				unset($_SESSION[$keys[0]][$keys[1]][$keys[2]][$keys[3]]); break;
			case 5:
				unset($_SESSION[$keys[0]][$keys[1]][$keys[2]][$keys[3]][$keys[4]]); break;
			default:
				throw new Exception('Removing session values nested deeper than 5 levels is not yet supported');
		}
		self::log("Removed nested value for key ".implode(',', $keys));
		if (self::$autoClose) self::close();
	}
	
	protected static function log($message) {
		if (self::$enableLog) {
			$file = 'logs/vtesession.log';
			// logging the session id is a security risk! enable it only if needed!
			//$str = "[".date('Y-m-d H:i:s')."] [".self::$currentSessionId."] [".self::$requestId."] ".$message."\n";
			$str = "[".date('Y-m-d H:i:s')."] [".self::$requestId."] ".$message."\n";
			file_put_contents($file, $str, FILE_APPEND);
			//echo $str."<br>\n";
		}
	}
	
}
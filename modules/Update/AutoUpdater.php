<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181161 crmv@182073 crmv@183486 */

require_once('Update.php');

class AutoUpdater {

	const STATUS_IDLE = 0;
	const STATUS_WAIT_PACKAGE = 10;
	const STATUS_DOWNLOADING = 20;
	const STATUS_CHECK_FILES = 30;
	const STATUS_NOT_UPDATABLE = 40;
	const STATUS_UPDATABLE = 50;
	const STATUS_POSTPONED = 60;
	const STATUS_REFUSED = 70;
	const STATUS_SCHEDULED = 80;
	const STATUS_UPDATING = 90;
	const STATUS_SUCCESS = 100;
	const STATUS_FAILURE = 110;
	
	const REASON_FILES = 'files_changed';
	const REASON_NEED_PHP_70 = 'need_php_70';
	const REASON_OS_NOT_SUPPORTED = 'os_not_supported';
	
	public $table = '';
	public $statuses_table = '';
	public $remind_table = '';
	public $seen_table = '';
	
	public $updateServer = '';
	
	// if the user doesn't make any choice, show at the next login after this amount of time
	// set to 0 to disable
	public $popupQuietTime = 1800;
	
	// timeouts in seconds after which each state is reset
	public $status_timeouts = array(
		self::STATUS_WAIT_PACKAGE => array('timeout' => 86400, 'goto' => self::STATUS_IDLE),
		self::STATUS_DOWNLOADING => array('timeout' => 7200, 'goto' => self::STATUS_WAIT_PACKAGE),
		self::STATUS_CHECK_FILES => array('timeout' => 7200, 'goto' => self::STATUS_NOT_UPDATABLE),
	);
	
	public $hashes_file = 'modules/Update/md5_hashes.txt'; // crmv@183486
	
	public function __construct() {
		global $table_prefix;
		
		$this->table = $table_prefix.'_autoupdate';
		$this->statuses_table = $table_prefix.'_autoupdate_statuses';
		$this->remind_table = $table_prefix.'_autoupdate_reminder';
		$this->seen_table = $table_prefix.'_autoupdate_seen';
		
		eval(Users::m_de_cryption());
		eval($hash_version[23]);

		if (empty($this->updateServer)) {
			if ($this->isCommunity()) {
				$this->updateServer = 'https://autoupdatece.vtecrm.net/';
			} else {
				$this->updateServer = 'https://autoupdate.vtecrm.net/';
			}
		}
	}
	
	public function createTables() {
		global $adb;
		
		// crmv@183486
		if(!Vtecrm_Utils::CheckTable($this->table)) {
			$schema = '<?xml version="1.0"?>
						<schema version="0.3">
						<table name="'.$this->table.'">
							<opt platform="mysql">ENGINE=InnoDB</opt>
							<field name="id" type="I" size="11">
								<KEY/>
							</field>
							<field name="status" type="I" size="11">
								<NOTNULL/>
							</field>
							<field name="reason" type="C" size="31" />
							<field name="last_check_time" type="T">
								<DEFAULT value="0000-00-00 00:00:00"/>
							</field>
							<field name="new_revision" type="I" size="11"/>
							<field name="new_version" type="C" size="31"/>
							<field name="scheduled_time" type="T">
								<DEFAULT value="0000-00-00 00:00:00"/>
							</field>
							<field name="scheduled_users" type="XL"/>
							<field name="scheduled_message" type="XL"/>
							<field name="comments_ids" type="C" size="255"/>
							<field name="userid" type="I" size="19"/>
						</table>
						</schema>';
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
		}
		// crmv@183486e
		
		if(!Vtecrm_Utils::CheckTable($this->statuses_table)) {
			$schema = '<?xml version="1.0"?>
						<schema version="0.3">
						<table name="'.$this->statuses_table.'">
							<opt platform="mysql">ENGINE=InnoDB</opt>
							<field name="status" type="I" size="11">
								<KEY/>
							</field>
							<field name="last_change" type="T">
								<DEFAULT value="0000-00-00 00:00:00"/>
							</field>
						</table>
						</schema>';
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
		}
		
		if(!Vtecrm_Utils::CheckTable($this->remind_table)) {
			$schema = '<?xml version="1.0"?>
						<schema version="0.3">
						<table name="'.$this->remind_table.'">
							<opt platform="mysql">ENGINE=InnoDB</opt>
							<field name="userid" type="I" size="19">
								<KEY/>
							</field>
							<field name="revision" type="I" size="11"/>
							<field name="remind_after" type="T">
								<DEFAULT value="0000-00-00 00:00:00"/>
							</field>
						</table>
						</schema>';
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
		}
		
		if(!Vtecrm_Utils::CheckTable($this->seen_table)) {
			$schema = '<?xml version="1.0"?>
						<schema version="0.3">
						<table name="'.$this->seen_table.'">
							<opt platform="mysql">ENGINE=InnoDB</opt>
							<field name="userid" type="I" size="19">
								<KEY/>
							</field>
							<field name="seen_time" type="T">
								<DEFAULT value="0000-00-00 00:00:00"/>
							</field>
						</table>
						</schema>';
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
		}
	}
	
	// crmv@183486
	/**
	 * Create md5 hashes to be passed to vteUpdater to skip the overwritten files
	 */
	public function createFileHashes() {
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			exec('md5sum ./modules/Update/*.* > '.$this->hashes_file);
			exec('md5sum ./modules/Update/language/*.* >> '.$this->hashes_file);
			exec('md5sum ./Smarty/templates/modules/Update/*.tpl >> '.$this->hashes_file);
		}
	}
	// crmv@183486e
	
	public function isCommunity() {
		global $enterprise_mode;
		return ($enterprise_mode === 'VTENEXTCE');
	}
	
	// --------------------------------- DB functions ---------------------------------
	
	public function getStatus() {
		global $adb;
		
		$status = false;
		
		$res = $adb->query("SELECT status FROM {$this->table}");
		if ($res && $adb->num_rows($res) > 0) {
			$status = intval($adb->query_result_no_html($res, 0, 'status'));
		}
		
		return $status;
	}
	
	public function getInfo($field = null) {
		global $adb;
		
		$row = array();
		$res = $adb->query("SELECT ".($field ?: '*')." FROM {$this->table}");
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
		}
		
		if ($field) $row = $row[$field];
		
		return $row;
	}
	
	public function getRemindInfo($userid) {
		global $adb;
		
		$row = array();
		$res = $adb->pquery("SELECT * FROM {$this->remind_table} WHERE userid = ?", array($userid));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
		}
		
		return $row;
	}
	
	public function getSeenTime($userid) {
		global $adb;
		
		$time = null;
		$res = $adb->pquery("SELECT seen_time FROM {$this->seen_table} WHERE userid = ?", array($userid));
		if ($res && $adb->num_rows($res) > 0) {
			$time = $adb->query_result_no_html($res, 0, 'seen_time');
		}
		
		return $time;
	}
	
	public function setLastCheck($date = null) {
		global $adb;
		
		if (empty($date)) $date = date('Y-m-d H:i:s');
		$adb->pquery("UPDATE {$this->table} SET last_check_time = ?", array($date));
	}
	
	public function setStatus($status, $fields = array()) {
		global $adb;
		
		unset($fields['status']);
		$r = $adb->pquery("UPDATE {$this->table} SET status = ? WHERE status != ?", array($status, $status));
		
		if ($adb->getAffectedRowCount($r) > 0) {
			// ok, the status changed, set the changed time
			$this->setStatusChangeTime($status);
		}
		
		if (count($fields) > 0) {
			$update = array();
			foreach ($fields as $field => $val) {
				$update[] = $field .' = ?';
			}
			$adb->pquery("UPDATE {$this->table} SET ".implode(', ', $update), $fields);
		}
	}
	
	public function getStatusDuration($status) {
		global $adb;
		
		$now = time();
		
		$r = $adb->pquery("SELECT last_change FROM {$this->statuses_table} WHERE status = ?", array($status));
		if ($r && $adb->num_rows($r) > 0) {
			$lc = $adb->query_result_no_html($r, 0, 'last_change');
			
			return ($now - strtotime($lc));
		}
		
		return false;
	}
	
	protected function setStatusChangeTime($status) {
		global $adb;
		
		$time = date('Y-m-d H:i:s');
		
		$r = $adb->pquery("UPDATE {$this->statuses_table} SET last_change = ? WHERE status = ?", array($time, $status));
		if ($adb->getAffectedRowCount($r) == 0) {
			// no row present, insert!
			$adb->pquery("INSERT INTO {$this->statuses_table} (status, last_change) VALUES (?,?)", array($status, $time));
		}
	}
	
	protected function insertFirstStatus() {
		global $adb;
		
		$params = array(1, self::STATUS_IDLE);
		$adb->pquery("INSERT INTO {$this->table} (id, status) VALUES (".generateQuestionMarks($params).")", $params);
	}
	
	// --------------------------------- Utility functions ---------------------------------
	
	// crmv@199352
	/**
	 * Reset the cron lastrun time to have it run now
	 */
	public function forceCron() {
		global $adb, $table_prefix;
		
		$time = date('Y-m-d 00:00:00', time()-3600*24*7); // 7 days ago
		$res = $adb->pquery("UPDATE {$table_prefix}_cronjobs SET lastrun = ? WHERE cronname = ?", array($time, 'CheckUpdates'));
		
		if ($res && $adb->getAffectedRowCount($res) > 0) {
			return true;
		}
		
		return false;
	}
	// crmv@199352e
	
	/**
	 * Check whether autoupdate can run on this system
	 */
	public function canAutoupdate() {
		global $adb;
		
		// updateServer must be populated correctly
		if (empty($this->updateServer)) {
			$this->logWarning("Update server is empty");
			return false;
		}

		// only with mysql I can autoupdate
		$dbType = $adb->dbType;
		if ($dbType != 'mysql' && $dbType != 'mysqli') {
			return false;
		}
		
		// Windows is not supported in Business
		// in Community, let notify and show instructions for manual update
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && !$this->isCommunity()) {
			return false;
		}
		
		// VTE dir must be writable
		if (!is_writable('./') || !is_writable('modules/') || !is_writable('config.inc.php')) {
			return false;
		}
		
		return true;
	}
	
	public function shouldShowPopup($user) {
		$status = $this->getStatus();
		
		if ($status == self::STATUS_UPDATABLE || $status == self::STATUS_NOT_UPDATABLE) {
			if ($this->popupQuietTime > 0) {
				// check if the user has seen recently the popup
				// if he never answered, show again at the next login, but after some time
				$lastSeen = $this->getSeenTime($user->id);
				if ($lastSeen) {
					$limit = date('Y-m-d H:i:s', time() - $this->popupQuietTime);
					if ($lastSeen > $limit) {
						return false;
					}
				}
			}
			return true;
		} elseif ($status == self::STATUS_POSTPONED) {
			$rinfo = $this->getRemindInfo($user->id);
			$time = $rinfo['remind_after'];
			if ($time) {
				$now = date('Y-m-d H:i:s');
				if ($now >= $time) {
					return true;
				}
			} else {
				// reminded by someone else, don't show anything
			}
		}
		
		return false;
	}
	
	public function shouldUpdateNow() {
		$status = $this->getStatus();
		
		if ($status === self::STATUS_SCHEDULED) {
			$now = date('Y-m-d H:i:s');
			$when = $this->getInfo('scheduled_time');
			return ($when <= $now);
		}
		
		return false;
	}
	
	public function getReminderOptions() {
		$list = array(
			'in_4_hours' => getTranslatedString('LBL_REMIND_IN_4_HOURS', 'Update'), // crmv@183486
			'tomorrow' => getTranslatedString('LBL_REMIND_TOMORROW', 'Update'),
			'next_week' => getTranslatedString('LBL_REMIND_NEXT_WEEK', 'Update'),
		);
		return $list;
	}
	
	public function canIgnoreUpdate($user) {
		$status = $this->getStatus();
		
		return in_array($status, array(self::STATUS_UPDATABLE, self::STATUS_POSTPONED, self::STATUS_NOT_UPDATABLE));
	}
	
	public function canRemindUpdate($user) {
		return $this->canIgnoreUpdate($user);
	}
	
	public function canScheduleUpdate($user) {
		return $this->canIgnoreUpdate($user);
	}
	
	public function canCancelUpdate($user) {
		$status = $this->getStatus();
		return ($status === self::STATUS_SCHEDULED);
	}
	
	public function setPopupSeen($user) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		$res = $adb->pquery("UPDATE {$this->seen_table} SET seen_time = ? WHERE userid = ?", array($now, $user->id));
		if ($res && $adb->getAffectedRowCount($res) == 0) {
			$res = $adb->pquery("INSERT INTO {$this->seen_table} (userid, seen_time) VALUES (?,?)", array($user->id, $now));
		}
	}
	
	public function ignoreUpdate($user) {
		$this->setStatus(AutoUpdater::STATUS_REFUSED);
	}
	
	public function remindUpdate($user, $when) {
		global $adb, $table_prefix;
		
		$list = $this->getReminderOptions();
		
		if (!array_key_exists($when, $list)) throw new Exception("Invalid value for when parameter");
		
		$info = $this->getInfo();
		$revision = $info['new_revision'];
		
		switch ($when) {
			case 'in_4_hours': // crmv@183486
				$after = date('Y-m-d H:i:s', time()+3600*4); // crmv@183486
				break;
			case 'tomorrow':
				// after the user's start hour
				$startHour = getSingleFieldValue($table_prefix.'_users', 'start_hour', 'id', $user->id) ?: '08:00';
				$after = date('Y-m-d ', strtotime('+1 day')).substr(trim($startHour), 0, 5).':00';
				break;
			case 'next_week':
				// using first day of the week and start hour
				$startHour = getSingleFieldValue($table_prefix.'_users', 'start_hour', 'id', $user->id) ?: '08:00';
				$startDay = getSingleFieldValue($table_prefix.'_users', 'weekstart', 'id', $user->id);
				if ($startDay === '') $startDay = '1';
				$startDayName = ($startDay == '1' ? 'Monday' : 'Sunday');
				$after = date('Y-m-d ', strtotime('next '.$startDayName)).substr(trim($startHour), 0, 5).':00';
				break;
			default:
				throw new Exception("Unknown value for when parameter");
		}
		
		$params = array($user->id, $revision, $after);
		
		$adb->pquery("DELETE FROM {$this->remind_table} WHERE userid = ?", array($user->id));
		$adb->pquery("INSERT INTO {$this->remind_table} (userid, revision, remind_after) VALUES (?,?,?)", $params);
		
		$this->setStatus(AutoUpdater::STATUS_POSTPONED);
	}
	
	public function scheduleUpdate($user, $data) {
		global $adb;
		
		// delete all reminders from remind table
		$adb->query("DELETE FROM {$this->remind_table}");
		
		// set scheduling info
		$adb->pquery("UPDATE {$this->table} SET scheduled_time = ?, userid = ?", array($data['date'], $user->id));
		$adb->updateClob($this->table, 'scheduled_message', 'id = 1', $data['message']);
		$adb->updateClob($this->table, 'scheduled_users', 'id = 1', Zend_Json::encode($data['alert']));
		
		// crmv@183486
		$commentids = $this->sendUpdateAlert($user, $data);
	
		$this->setStatus(AutoUpdater::STATUS_SCHEDULED, array('comments_ids' => Zend_Json::encode($commentids)));
		// crmv@183486e
	}
	
	public function getDiffFile() {
		global $enterprise_current_build;
		
		$path = "vte_updater/$enterprise_current_build/differences.log";
		
		if (file_exists($path)) return $path;
		return null;
	}
	
	// crmv@183486
	public function cancelUpdate($user) {
		global $adb;
		
		// find previous state
		$res = $adb->limitpQuery(
			"SELECT status FROM {$this->statuses_table} WHERE status IN (?,?) ORDER BY last_change DESC",
			0, 1,
			array(self::STATUS_UPDATABLE, self::STATUS_NOT_UPDATABLE)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$prevStatus = $adb->query_result_no_html($res, 0, 'status');
		} else {
			// guess the safest one
			$prevStatus = self::STATUS_NOT_UPDATABLE;
		}
		
		$this->setStatus($prevStatus);
		
		$info = $this->getInfo();
		$schedulerId = $info['userid'];
		$users = Zend_Json::decode($info['scheduled_users']);
		
		$allUsers = $this->extractUsers($users);
		if (count($allUsers) == 0) return;
		
		$myname = getUserFullName($user->id);
		$email = getUserEmail($user->id);
		
		// extract emails
		$emails = array();
		foreach ($allUsers as $userid => $userinfo) {
			if ($userinfo['email']) $emails[] = $userinfo['email'];
		}
		
		list($sdate, $stime) = explode(' ', $info['scheduled_time']);
		
		$msg = getTranslatedString('LBL_CANCEL_BODY', 'Update');
		$msg = trim(str_replace(
			array(
				'{date}',
				'{hour}',
			),
			array(
				substr($sdate, 0, 10),
				substr($stime, 0, 5),
			), 
			$msg
		));
		
		// send the answer to the comment
		$commentids = Zend_Json::decode($info['comments_ids']) ?: array();
		foreach ($commentids as $commentid) {
			$focus = CRMEntity::getInstance('ModComments');
			$focus->retrieve_entity_info($commentid, 'ModComments');
			// add myself if I wasn't included and I wasn't the author
			if ($focus->column_fields['assigned_user_id'] != $user->id) {
				$focus->addUsers(array($user->id));
			}
			// and add reply!
			$focus = CRMEntity::getInstance('ModComments');
			$focus->column_fields['commentcontent'] = $msg;
			$focus->column_fields['assigned_user_id'] = $user->id;
			$focus->column_fields['parent_comments'] = $commentid;
			$focus->save('ModComments');
		}
		
		// send emails!
		if (count($emails) > 0) {
			// make the message more html like
			$msg = nl2br($msg);
			$r = send_mail('Update', $emails, $myname, $email, 'vtenext update canceled', $msg);
		}
	}
	
	protected function sendUpdateAlert($user, $data) {
		global $adb, $table_prefix, $site_URL;
		
		$users = $data['alert'];
		$msg = $data['message'];
		
		$allUsers = $this->extractUsers($users);
		if (count($allUsers) == 0) return;
		
		$myname = getUserFullName($user->id);
		$email = getUserEmail($user->id);
		
		// divide users in admin/non-admin and extract emails
		$adminUsers = $nonAdminUsers = array();
		$adminEmails = $nonAdminEmails = array();
		foreach ($allUsers as $userid => $userinfo) {
			if ($userinfo['is_admin']) {
				$adminUsers[] = $userid;
				if ($userinfo['email']) $adminEmails[] = $userinfo['email'];
			} else {
				$nonAdminUsers[] = $userid;
				if ($userinfo['email']) $nonAdminEmails[] = $userinfo['email'];
			}
		}
		
		$commentids = array();
		
		if (count($adminUsers) > 0) {
			$cancelUrl = $site_URL.'/index.php?module=Update&action=CancelUpdate&parenttab=Settings';
			$cancelText = getTranslatedString('LBL_UPDATE_DEFAULT_CANCEL_TEXT', 'Update');
			$cancelText = str_replace('%s', $cancelUrl, $cancelText);
			
			// now replace vars in the message (different for admin/non-admin)
			$msgAdmin = trim(str_replace(
				array(
					'{date}',
					'{hour}',
					'{cancel_text}'
				),
				array(
					substr($data['date'], 0, 10), // I know, I shoudld format according to the user, but then I'd have to send N messages...
					substr($data['date'], 11, 5),
					$cancelText
				), 
				$msg
			));
			
			// for notifications, text only messages!
			$msgAdminNot = strip_tags($msgAdmin);
			$msgAdminNot = preg_replace('/.$/', ': '.$cancelUrl.' .', $msgAdminNot);

			// alert by notification
			$focus = CRMEntity::getInstance('ModComments');
			$focus->column_fields['commentcontent'] = $msgAdminNot;
			$focus->column_fields['assigned_user_id'] = $user->id;
			$focus->column_fields['visibility_comm'] = 'Users';
			
			// remove myself from recipients now
			$k = array_search($user->id, $adminUsers);
			if ($k !== false) {
				unset($adminUsers[$k]);
			}
			$_REQUEST['users_comm'] = implode('|', $adminUsers); // uugh, ugly trick!!
			$focus->save('ModComments');
			$commentids[] = intval($focus->id);
			
			// alert by email
			if (count($adminEmails) > 0) {
				// make the message more html like
				$msgAdmin = nl2br($msgAdmin);
				$r = send_mail('Update', $adminEmails, $myname, $email, 'vtenext update', $msgAdmin);
			}
		}
		
		if (count($nonAdminUsers) > 0) {
			$msgNonAdmin = trim(str_replace(
				array(
					'{date}',
					'{hour}',
					'{cancel_text}'
				),
				array(
					substr($data['date'], 0, 10), // I know, I shoudld format according to the user, but then I'd have to send N messages...
					substr($data['date'], 11, 5),
					''
				), 
				$msg
			));

			// alert by notification
			$focus = CRMEntity::getInstance('ModComments');
			$focus->column_fields['commentcontent'] = $msgNonAdmin;
			$focus->column_fields['assigned_user_id'] = $user->id;
			$focus->column_fields['visibility_comm'] = 'Users';
			$_REQUEST['users_comm'] = implode('|', $nonAdminUsers); // uugh, ugly trick!!
			$focus->save('ModComments');
			$commentids[] = intval($focus->id);
			
			// alert by email
			if (count($nonAdminEmails) > 0) {
				// make the message more html like
				$msgNonAdmin = nl2br($msgNonAdmin);
				$r = send_mail('Update', $nonAdminEmails, $myname, $email, 'vtenext update', $msgNonAdmin);
			}
		}
		
		return $commentids;
	}
	
	/**
	 * Extract a list of all users from the users/group arrays
	 */
	protected function extractUsers($users) {
		global $adb, $table_prefix;
		
		$allUsers = array();
		if (is_array($users['users'])) {
			$allUsers = $users['users'];
		}
		if (in_array('all', $allUsers)) {
			$allUsers = array();
			$res = $adb->query("SELECT id, COALESCE(email1, email2) as email, is_admin FROM {$table_prefix}_users WHERE status = 'Active' AND deleted = 0");
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$id = intval($row['id']);
					$allUsers[$id] = array('email' => $row['email'], 'is_admin' => ($row['is_admin'] == 'on'));
				}
			}
		} else {
			if (is_array($users['groups'])) {
				foreach ($users['groups'] as $groupid) {
					$class = new GetGroupUsers();
					$class->getAllUsersInGroup($groupid, true, true);
					$allUsers = array_merge($allUsers, $class->group_users);
				}
			}
			
			$allUsers = array_filter(array_unique(array_map('intval', $allUsers)));
			
			if (count($allUsers) > 0) {
				$res = $adb->pquery("SELECT id, COALESCE(email1, email2) as email, is_admin FROM {$table_prefix}_users WHERE status = 'Active' AND id IN (".generateQuestionMarks($allUsers).")", $allUsers);
				if ($res && $adb->num_rows($res) > 0) {
					while ($row = $adb->fetchByAssoc($res, -1, false)) {
						$id = intval($row['id']);
						$allUsers[$id] = array('email' => $row['email'], 'is_admin' => ($row['is_admin'] == 'on'));
					}
				}
			}
		}
		
		return $allUsers;
	}
	// crmv@183486e
	
	
	public function validateSchedule($post, &$data = array(), &$error = '') {
		$status = $this->getStatus();
		$reason = $this->getInfo('reason');
		
		$date = substr($post['schedule_date'], 0, 10);
		$hour = substr($post['schedule_hour'], 0, 5);
		
		// TODO: translate errors!
		
		if ($status == self::STATUS_NOT_UPDATABLE && $reason == self::REASON_FILES) {
			if ($post['alert_changes'] !== 'on') {
				$error = "You must consent to overwrite files";
				return false;
			}
		}
		
		if (empty($date)) {
			$error = "Invalid date";
			return false;
		}
		
		if (empty($hour)) {
			$error = "Invalid hour";
			return false;
		}
		
		$dbDate = getValidDBInsertDateValue($date);
		if ($dbDate < date('Y-m-d')) {
			$error = getTranslatedString('LBL_DATE_IS_PAST', 'Update');
			return false;
		} elseif ($dbDate.' '.$hour.':00' <= date('Y-m-d H:i:s', time()+600)) {
			$error = getTranslatedString('LBL_DATE_TOO_CLOSE', 'Update');
			return false;
		}
		
		$data['date'] = $dbDate.' '.$hour.':00';
		
		if ($post['schedule_alert'] == 'on') {
			$users = Zend_Json::decode($post['schedule_users']);
			$msg = $post['schedule_message'];
			
			if (empty($users)) {
				$error = "You must select at least one user";
				return false;
			}
			
			if (empty($msg)) {
				$error = "You must provide a message";
				return false;
			}
			
			$data['alert'] = array();
			foreach ($users as $us) {
				list($type, $id) = explode('::', $us);
				$data['alert'][$type][] = ($id === 'all' ? $id : intval($id)); // crmv@183486
			}
			$data['message'] = $msg;
		}
		
		return true;
	}
	
	// --------------------------------- WS functions ---------------------------------
	
	protected function updateWSCall($service, $params = array()) {
		$url = $this->updateServer.'ws.php?wsname='.$service;
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$content = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);
		
		if ($errno > 0) {
			throw new Exception("Unable to communicate with update server: $error");
		}
		
		if (!$content) throw new Exception("Invalid answer from update server");
		
		$content = Zend_Json::decode($content);
		if (!$content) throw new Exception("Invalid answer from update server");
		
		if ($content['success'] !== true) throw new Exception("Update server returned error: ".$content['error']);
		
		return $content;
	}
	
	public function getLatestRevision() {
		$content = $this->updateWSCall('get_latest_revision');
		$newrev = intval($content['result']);

		return $newrev;
	}
	
	public function getUpdateRanges() {
		$ranges = $this->updateWSCall('get_safe_update_ranges');
		return $ranges['result'];
	}
	
	public function getRevisionInfo($revision) {
		$info = $this->updateWSCall('get_revision_info', array('revision' => $revision));
		return $info['result'];
	}
	
	
	// --------------------------------- Main logic ---------------------------------
	
	/**
	 * State-machine handler
	 */
	public function statusHandler() {
		$status = $this->getStatus();
		
		if ($status === false) {
			// never run before! set the default empty line
			$this->insertFirstStatus();
			return;
		}
		
		// timeout check
		if (array_key_exists($status, $this->status_timeouts)) {
			$timeinfo = $this->status_timeouts[$status];
			$duration = $this->getStatusDuration($status);
			if ($duration && $duration > $timeinfo['timeout']) { // crmv@201821
				$goto = $timeinfo['goto'];
				$this->logWarning("Status $status hit timeout limit, setting it back to $goto");
				$this->setStatus($goto);
				return;
			}
		}

		switch ($status) {
			case self::STATUS_IDLE:
			case self::STATUS_REFUSED:
				if ($this->canAutoupdate()) {
					$this->checkUpdates($status);
				}
				break;
			case self::STATUS_WAIT_PACKAGE:
				$info = $this->getInfo();
				$this->downloadAndCheck($info['new_revision']);
				break;
			case self::STATUS_NOT_UPDATABLE:
				// the user will be asked what to do
				$reason = $this->getInfo('reason');
				if ($reason == self::REASON_NEED_PHP_70) {
					$this->checkPhpVersion('7.0');
				}
				break;
			case self::STATUS_SUCCESS:
				// last update was ok, reset the status
				$this->resetStatus();
				break;
		}
	}
	
	public function checkPhpVersion($needed) {
		$ok = version_compare(phpversion(), $needed, '>=');
		if ($ok) {
			// good, reset the status so I can check again 
			$this->logInfo('PHP has been updated, resetting the status');
			$this->setStatus(self::STATUS_IDLE, array('reason' => ''));
		}
		return $ok;
	}
	
	/**
	 * Reset the status to 0, remove reminders/seen
	 */
	public function resetStatus() {
		global $adb;
		
		$adb->pquery("UPDATE {$this->table} SET reason = NULL, new_revision = 0, new_version = NULL, scheduled_time = ?, scheduled_users = NULL, scheduled_message = NULL, userid = 0, comments_ids = NULL", array('0000-00-00 00:00:00'));
		$adb->query("DELETE FROM {$this->seen_table}");
		$adb->query("DELETE FROM {$this->remind_table}");
		$this->setStatus(self::STATUS_IDLE);
	}

	public function checkUpdates($status = null) {
		global $adb;
		
		if (is_null($status)) {
			$status = $this->getStatus();
		}
		
		$this->setLastCheck();

		$newrev = $this->getLatestRevision();
		
		global $enterprise_current_build; 

		if ($newrev <= $enterprise_current_build) return false; // nothing to update
		
		$newrev = $this->decideNextRevision($newrev);
		
		$info = $this->getRevisionInfo($newrev);
		$newversion = $info['version'];
		
		// ok, set the new revision
		$adb->pquery("UPDATE {$this->table} SET new_revision = ?, new_version = ?", array($newrev, $newversion));
		
		// ok I have an update!!
		if ($status == self::STATUS_REFUSED) {
			// if I had refused, check if this update is newer than the old one
			$info = $this->getInfo();
			
			if ($newrev > $info['new_revision']) {
				$this->logInfo("Found new version ($newrev) after the refused one, checking for compatibility...");
				return $this->downloadAndCheck($newrev);
			}
		} else {
			// otherwise
			$this->logInfo("Found new version available ($newrev), checking for compatibility...");
			return $this->downloadAndCheck($newrev);
		}
		
		return true;
 	}
 	
 	public function downloadAndCheck($revision) {
		global $enterprise_current_build;
		
		// check if already downloaded
		$basename = "vte_updater/packages/vte{$enterprise_current_build}-{$revision}";
		if (is_readable($basename.'.tgz') && is_readable($basename.'src.tgz') && is_readable($basename.'del.tgz')) {
			$this->logInfo("Files already downloaded, using them");
			
		} else {
			$params = array(
				'start_revision' => $enterprise_current_build,
				'dest_revision' => $revision,
			);
			$content = $this->updateWSCall('is_package_available', $params);
			$avail = ($content['result'] == '1');
		
			if (!$avail) {
				$status = $this->getStatus();
				if ($status == self::STATUS_WAIT_PACKAGE || $this->isCommunity()) {
					$this->logInfo("Package is not available yet, keep waiting...");
					if ($status != self::STATUS_WAIT_PACKAGE) {
						$this->setStatus(self::STATUS_WAIT_PACKAGE, array('new_revision' => $revision));
					}
				} else {
					$this->logInfo("Package is not available, requesting it...");
					$this->requestPackage($revision);
				}
				return;
			}
		
		
			$this->logInfo("Package is available, downloading it...");
		
			$this->downloadPackage($revision);
		}
		
		// on Windows only manual update is supported
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->setStatus(self::STATUS_NOT_UPDATABLE, array('new_revision' => $revision, 'reason' => self::REASON_OS_NOT_SUPPORTED));
			$this->logInfo("On Windows only manual update is possible");
			$this->sendNotification();
		} else {
			$this->checkVteUpdater();
			$this->checkUpdatability($revision);
		}
 	}
 	
 	protected function requestPackage($revision) {
		global $site_URL, $application_unique_key, $enterprise_current_build;
		
		$this->setStatus(self::STATUS_WAIT_PACKAGE, array('new_revision' => $revision));
		
		$idlic = getMorphsuitNo();
		$lic = getSavedMorphsuit();
		$hash = hash('sha256', $lic.$application_unique_key);
		
		if (empty($idlic)) throw new Exception("This installation doesn't have a valid license");
		
		$params = array(
			'username' => 'LIC_'.$idlic,
			'hashedkey' => $hash,
			'start_revision' => $enterprise_current_build,
			'dest_revision' => $revision,
			'vte_url' => $site_URL,
			'email' => 'nomail@example.com',
		);
		$content = $this->updateWSCall('request_package', $params);
		
		return true;
 	}
 	
 	protected function downloadPackage($revision) {
		global $site_URL, $application_unique_key, $enterprise_current_build;
		
		$this->setStatus(self::STATUS_DOWNLOADING, array('new_revision' => $revision));
		
		if ($this->isCommunity()) {
		
			$params = array(
				'start_revision' => $enterprise_current_build,
				'dest_revision' => $revision,
				'vte_url' => $site_URL,
			);
			
		} else {
			$idlic = getMorphsuitNo();
			$lic = getSavedMorphsuit();
			$hash = hash('sha256', $lic.$application_unique_key);
		
			if (empty($idlic)) throw new Exception("This installation doesn't have a valid license");
		
			$params = array(
				'username' => 'LIC_'.$idlic,
				'hashedkey' => $hash,
				'start_revision' => $enterprise_current_build,
				'dest_revision' => $revision,
				'vte_url' => $site_URL,
			);
		}
		
		$answer = $this->updateWSCall('request_download', $params);
		$files = $answer['result'];
		
		if (empty($files)) throw new Exception("The update server didn't returned valid files");
		
		// make dest dirs
		if (!is_dir('vte_updater/packages')) {
			if (!mkdir('vte_updater/packages', 0755, true)) {
				throw new Exception("Unable to create the destination directory");
			}
		}
		
		$basename = "vte_updater/packages/vte{$enterprise_current_build}-{$revision}";
		
		// check if absolute url
		if (substr($files['upd'], 0, 4) == 'http') {
			$host = '';
		} else {
			$host = $this->updateServer;
		}
		$this->downloadFile($host.$files['upd'], $basename.'.tgz');
		$this->downloadFile($host.$files['src'], $basename.'src.tgz');
		if ($files['del']) {
			$this->downloadFile($host.$files['del'], $basename.'del.tgz');
		}
		
		$this->logInfo("Files downloaded");
		
		return true;
 	}
 	
 	public function checkUpdatability($revision)  {
 	
		$this->setStatus(self::STATUS_CHECK_FILES);
		
		$this->logInfo("Checking updatability of this VTE...");
		
		if (file_exists($this->hashes_file) && is_readable($this->hashes_file)) {
			$hashOpts = '--file-hashes='.$this->hashes_file;
		} else {
			$hashOpts = '';
		}
		
		$out = array();
		exec("./vteUpdater.sh -b -d $revision --only-file-check $hashOpts", $out, $ret);
		
		$log = implode("\n", $out);
		echo $log."\n";

		if (strpos($log, 'Some differences were found') !== false) {
			// not updatable
			$this->setStatus(self::STATUS_NOT_UPDATABLE, array('reason' => self::REASON_FILES));
			$this->logInfo("Update might overwrite files. User must decide what to do");
			$this->sendNotification();
		} elseif (strpos($log, 'No differences found') !== false) {
			// no diff
			$this->setStatus(self::STATUS_UPDATABLE);
			$this->logInfo("Update is applicable! A popup/notification will be shown to admin users");
			$this->sendNotification();
		} elseif (strpos($log, 'The destination revision supports only PHP >= 7.0') !== false) {
			// php too old to update!
			$this->setStatus(self::STATUS_NOT_UPDATABLE, array('reason' => self::REASON_NEED_PHP_70));
			$this->logInfo("Update cannot continue, but notify user anyway and later show alert");
			$this->sendNotification();
		} else {
			// unknown error
			throw new Exception("vteUpdater didn't answer as expected");
		}
		
 	}
 	
 	// notify admin users about the availability of an update
 	protected function sendNotification() {
		global $site_URL, $table_prefix; // crmv@183486
		global $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID; 
		
		// this works with the old class as well as with the new one
		require_once('modules/ModNotifications/ModNotifications.php');
		$notfocus = ModNotifications::getInstance('ModNotifications');
		
		// add notification type
		$notfocus->addNotificationType('UPDATE_AVAILABLE', 'UPDATE_AVAILABLE');
		
		// and add translation if missing (for old versions)
		$text = getTranslatedString('UPDATE_AVAILABLE', 'ModNotifications');
		$url = $site_URL.'/index.php?module=Update&action=ViewUpdate&parenttab=Settings';
		if (empty($text) || strpos($text, $url) === false) {
			$tpl = getTranslatedString('LBL_NOTIFICATION_TPL_TEXT', 'Update');
			$tpl = str_replace('{url}', $url, $tpl);
			SDK::setLanguageEntries('ModNotifications', 'UPDATE_AVAILABLE', array(
				'it_it' => $tpl,
				'en_us' => $tpl,
			));
			$text = $tpl; // there is a cache somewhere, so I have to use this value
		}
		
		$users = $this->getAllAdminIds();
		
		foreach ($users as $userid) {
			$this->logInfo("Notifying user #$userid");
			
			$subject = 'vtenext update available';
			$body = $text;
			$ret = $notfocus->saveFastNotification(
				array(
					'assigned_user_id' => $userid,
					'related_to' => '',
					'mod_not_type' => 'UPDATE_AVAILABLE',
					'subject' => $subject,
					'description' => $body,
				)
			);
			if (empty($ret)) {
				$this->logWarning("Unable to notify user #$userid");
			}
			
			// crmv@183486
			// and send also and email if it was a vte notification
			$notifyType = getSingleFieldValue($table_prefix.'_users', 'notify_me_via', 'id', $userid);
			if ($notifyType != 'Emails') {
				$email = getUserEmail($userid);
				if ($email) {
					$r = send_mail('Update', $email, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $subject, $body);
					if ($r != 1) {
						$this->logWarning("Unable to notify user #$userid by email");
					}
				}
			}
			// crmv@183486e
		}

 	}
 	
 	protected function getAllAdminIds() {
		global $adb, $table_prefix;
		
		$ids = array();
		$res = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE status = ? AND is_admin = ?", array('Active', 'on'));
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$ids[] = $row['id'];
		}
		
		return $ids;
 	}
 	
 	protected function checkVteUpdater() {
		if (is_file('vteUpdater.sh')) {
			// update it!
			$this->logInfo("Updating vteUpdater.sh...");
			
			// make it executable, just in case!
			chmod('vteUpdater.sh', 0755);
			
			// check version
			$out = exec('./vteUpdater.sh --version');
			if (preg_match('/version ([0-9.-]+)/', $out, $matches)) {
				$version = $matches[1];
			}
			if (empty($version)) throw new Exception('Unable to determine vteUpdater version');
			
			if (version_compare($version, '1.28') < 0) {
				// doesn't support only-upgrade flag and community version, download it from scratch!
				$this->downloadUpdater();
			} else {
				// ok, self check!
				exec('./vteUpdater.sh --only-upgrade');
			}
		} else {
			$this->logInfo("vteUpdater.sh missing, downloading it...");
			// download vte updater!!
			$this->downloadUpdater();
		}
		
		return true;
 	}
 	
 	protected function downloadUpdater() {
		$answer = $this->updateWSCall('request_script_download', array('name' => 'main'));
		$url = $answer['result']['url'];
		$this->downloadFile($this->updateServer.$url, 'vteUpdater.tgz');
		
		$output = array();
		exec('tar -xf vteUpdater.tgz', $output, $ret);
		if ($ret != 0) {
			throw new Exception("Unable to decompress vteUpdater: ".implode("\n", $output));
		}
		unlink('vteUpdater.tgz');
		
		if (!is_file('vteUpdater.sh')) throw new Exception('Unable to decompress vteUpdater');
			
		chmod('vteUpdater.sh', 0755);
		return true;
 	}
 	
 	protected function downloadFile($url, $dest) {
		$src = fopen($url, 'r');
		$dest_res = fopen($dest, 'w');

		$r = stream_copy_to_stream($src, $dest_res);
		fclose($src);
		fclose($dest_res);
		if ($r === false) throw new Exception("Unable to download file ".$url);
		
		return true;
 	}
 	
 	protected function decideNextRevision($latest) {
		global $enterprise_current_build;
		
		$ranges = $this->getUpdateRanges();
		
		// check the update ranges and choose the best destination revision
		foreach ($ranges as $range) {
			if ($range['rev_start'] <= $enterprise_current_build && $range['rev_end'] >= $enterprise_current_build) {
				return $range['rev_dest'];
			}
		}
		
		return $latest;
 	}
 	
 	public function getFreePackageUrl() {
		global $enterprise_current_build, $site_URL;
		
		$revision = $this->getInfo('new_revision');
		
		$params = array(
			'start_revision' => $enterprise_current_build,
			'dest_revision' => $revision,
			'vte_url' => $site_URL,
			'format' => 'zip', // crmv@183486
		);
		
		$answer = $this->updateWSCall('request_download', $params);
		$files = $answer['result'];
		
		if (substr($files['upd'], 0, 4) == 'http') {
			$host = '';
		} else {
			$host = $this->updateServer;
		}
		
		$url = $host.$files['upd'];
		
		return $url;
 	}
 	
 	public function startUpdate() {
		global $enterprise_current_build;
		$oldVersion = $enterprise_current_build;
		
		$this->setStatus(self::STATUS_UPDATING);
		
		$revision = $this->getInfo('new_revision');
		$user = $this->getInfo('userid');
		
		$this->logInfo('Starting vte update');
		
		$cj = CronJob::getByName('DoUpdate');
		
		// wait for cron to finish
		$this->cronFreezeAll();
		$this->unfreezeCron('DoUpdate'); // and unfreeze myself
		
		$r = $this->waitForAllCron(120, array($cj->getId())); // wait 2 mins, excluding myself!
		if (!$r) $this->logWarning("There seems to be cron running, but updating anyway...");
		
		$emailTabid = getTabid('Emails'); // trick to avoid new tabdata cache problem
		
		$retcode = 0;
		passthru("./vteUpdater.sh -b -d $revision --skip-upgrade --skip-file-check --skip-cron", $retcode);
		
		if ($retcode == 0) {
			// all ok!
			$this->setStatus(self::STATUS_SUCCESS);
			$this->logInfo('Update succesful!');
			$this->cronUnfreezeAll();
			$outcome = 'ok';
		} elseif ($retcode == 3) {
			// fail, rollback ok
			$this->setStatus(self::STATUS_FAILURE);
			$this->cronUnfreezeAll();
			$outcome = 'fail_rollback';
		} else {
			// fail, vte currupted! manual action needed!
			$this->setStatus(self::STATUS_FAILURE);
			$this->cronUnfreezeAll();
			
			// set maintenance mode anyway!
			try {
				$this->setMaintenanceMode();
			} catch (Exception $e) {
				$this->logError($e->getMessage());
			}
			
			$outcome = 'fail';
		}
		
		$this->notifyOutcome($outcome, $user, $oldVersion);
		
		// now forcefully terminate cron to avoid executing other crons with old files in memory
		$cj->clearPid();
		$cj->setStatus(CronJob::$STATUS_EMPTY);
		die();
 	}
 	
 	protected function notifyOutcome($outcome, $userid, $oldVersion) {
		$email = getUserEmail($userid);
		
		if ($email) {
			global $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID;
			
			if ($outcome == 'ok') {
				$msg = getTranslatedString('LBL_UPDATE_MESSAGE_OK', 'Update');
			} elseif ($outcome == 'fail_rollback') {
				$msg = getTranslatedString('LBL_UPDATE_MESSAGE_FAIL_RB', 'Update');
			} elseif ($outcome == 'fail') {
				$msg = getTranslatedString('LBL_UPDATE_MESSAGE_FAIL', 'Update');
			}
			$msg = str_replace('{name}', getUserFullName($userid), $msg);
			
			// find notes log
			$attachments = array();
			
			$workdir = "vte_updater/$oldVersion/";
			
			$updatelogs = glob($workdir.'*_vteupdate.log');
			if (is_array($updatelogs) && count($updatelogs)) {
				$updatelog = array_pop($updatelogs);
				$attachments[] = array(
					'sourcetype' => 'file',
					'content' => $updatelog,
					'filename' => 'update.log',
				);
			}
			
			$noteslogs = glob($workdir.'*_update_notes.log');
			if (is_array($noteslogs) && count($noteslogs)) {
				$notelog = array_pop($noteslogs);
				$attachments[] = array(
					'sourcetype' => 'file',
					'content' => $notelog,
					'filename' => 'notes.log',
				);
			}

			$r = send_mail('Update', $email, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, 'vtenext update result', $msg, '', '', $attachments);
			
			if ($r != 1) {
				$this->logWarning('Unable to send outcome email');
			}
		}
 	}
 	
 	protected function setMaintenanceMode() {
		$file = 'maintenance.php';
		
		if (!is_writable($file)) {
			throw new Exception('Maintanance file is not writable');
		}
		
		$maint = file_get_contents($file);
		$maint = preg_replace('/\$vte_maintenance\s*=.*/', '$vte_maintenance = true;');
		if (!file_put_contents($file, $maint)) { // false or 0 is an error
			throw new Exception('Unable to write maintenance file');
		}
		
		$this->logInfo('Maintenance mode activated');
 	}
 	
 	// --------------------------------- Cron compatibility --------------------------------- 
 	// these are needed only when backporting the AutoUpdater to a vte that doesn't have the
 	// needed patches in CronUtils
 	
 	protected function cronFreezeAll() {
		
		$CU = CronUtils::getInstance();
		if (method_exists($CU, 'freezeAllActive')) {
			$CU->freezeAllActive();
		} else {
			global $adb, $table_prefix;
			$adb->query("UPDATE {$table_prefix}_cronjobs SET active = 2 WHERE active = 1");
		}
 	}
 	
 	protected function cronUnfreezeAll() {
		$CU = CronUtils::getInstance();
		if (method_exists($CU, 'unfreezeAll')) {
			$CU->unfreezeAll();
		} else {
			global $adb, $table_prefix;
			$adb->query("UPDATE {$table_prefix}_cronjobs SET active = 1 WHERE active = 2");
		}
 	}
 	
 	protected function unfreezeCron($cronname) {
		$cj = CronJob::getByName('DoUpdate');
		if ($cj) {
			if (method_exists($cj, 'unfreeze')) {
				$cj->unfreeze();
			} else {
				global $adb, $table_prefix;
				$adb->pquery("UPDATE {$table_prefix}_cronjobs SET active = 2 WHERE cronid = ?", array($cj->getId()));
			}
		}
 	}
 	
	protected function waitForAllCron($timeout = 300, $skipids = array()) {
	
		$CU = CronUtils::getInstance();
		
		if (method_exists($CU, 'waitForAllCron')) {
			return $CU->waitForAllCron($timeout, $skipids);
		} else {
			global $adb, $table_prefix;
			
			$sql = "SELECT COUNT(*) as count FROM {$table_prefix}_cronjobs WHERE active = 1 AND status = ?";
			$params = array(CronJob::$STATUS_PROCESSING);
			
			if (count($skipids) > 0) {
				$sql .= " AND cronid NOT IN (".generateQuestionMarks($skipids).")";
				$params = array_merge($params, $skipids);
			}
			
			$time = microtime(true);
			$res = $adb->pquery($sql, $params);
			$count = $adb->query_result_no_html($res, 0, 'count');
			
			while ($count > 0) {
				$t = microtime(true);
				if ($t-$time > $timeout) return false;
				
				sleep(3);
				
				$res = $adb->pquery($sql, $params);
				$count = $adb->query_result_no_html($res, 0, 'count');
			}
			
			return true;
		}
 	}
 	
 	
 	// --------------------------------- Log functions ---------------------------------
 	
 	protected function logInfo($msg) {
		echo "[INFO] ".$msg."\n";
		return true;
 	}
 	
 	protected function logWarning($msg) {
		echo "[WARNING] ".$msg."\n";
		return true;
 	}
 	
 	protected function logError($msg) {
		echo "[ERROR] ".$msg."\n";
		return false;
 	}
 	
}
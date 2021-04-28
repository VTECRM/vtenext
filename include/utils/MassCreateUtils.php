<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@202577 */

require_once('include/utils/MassQueueBase.php');

class MassCreateUtils extends MassQueueBase {
	
	public function __construct() {
		global $table_prefix;

		$this->table = $table_prefix.'_masscreate';
		$this->queueTable = $table_prefix.'_masscreate_queue';

		parent::__construct();
	}
	
	public function enqueue($userid, $module, $records, $useWf = true) {
		global $adb;
		
		if (empty($records)) return true;
		
		// get the masscreate-id
		$massid = $adb->getUniqueID($this->table);
		
		// save in the main table
		$sql = "INSERT INTO {$this->table} (massid, userid, module, inserttime, workflows, status) VALUES (?,?,?,?,?,?)";
		$params = array($massid, $userid, $module, date('Y-m-d H:i:s'), intval($useWf), self::MASS_STATUS_IDLE);
		$res = $adb->pquery($sql, $params);
		
		// get the list of ids
		$inserts = array();
		foreach ($records as $record) {
			if (!empty($record)) {
				$queueid = $adb->getUniqueID($this->queueTable);
				$inserts[] = array($queueid, $massid, Zend_Json::encode($record));
			}
		}
		
		// and quickly insert into the db
		$adb->bulkInsert($this->queueTable, array('queueid', 'massid', 'record'), $inserts);
		
		return true;
	}
	
	public function notifyUser($massinfo, $result) {
		global $current_user;
		
		$success = ($result['error'] == 0);
		
		$focus = ModNotifications::getInstance(); // crmv@164122
		if ($success) {
			$desc = "\n".getTranslatedString('LBL_MASSCREATE_OK_DESC', 'APP_STRINGS');
			$desc = str_replace(
				array('{num_records}', '{num_fail_records}', '{module}'), 
				array($result['processed'], $result['error'], getTranslatedString($massinfo['module'], $massinfo['module'])), 
				$desc
			);
			$notifInfo = array(
				'assigned_user_id' => $massinfo['userid'],
				'mod_not_type' => 'MassCreate',
				'related_to' => $massinfo['massid'],
				'subject' => getTranslatedString('LBL_MASSCREATE_OK_SUBJECT', 'APP_STRINGS'),
				'description' => $desc,
				'from_email' => $current_user->email1 ?: $current_user->email2,
				'from_email_name' => getUserFullName($current_user->id),
			);
		} else {
			$desc = "\n".getTranslatedString('LBL_MASSCREATE_ERROR_DESC', 'APP_STRINGS');
			$desc = str_replace(
				array('{num_records}', '{num_fail_records}', '{module}'), 
				array($result['processed'], $result['error'], getTranslatedString($massinfo['module'], $massinfo['module'])), 
				$desc
			);
			$notifInfo = array(
				'assigned_user_id' => $massinfo['userid'],
				'mod_not_type' => 'MassCreateError',
				'related_to' => $massinfo['massid'],
				'subject' => getTranslatedString('LBL_MASSCREATE_ERROR_SUBJECT', 'APP_STRINGS'),
				'description' => $desc,
				'from_email' => $current_user->email1 ?: $current_user->email2,
				'from_email_name' => getUserFullName($current_user->id),
			);
		}
		
		$focus->saveFastNotification($notifInfo);
		
		return true;
	}

	public function processJob($massid, $massinfo, $editjob, &$error = '') {
		return $this->processCreateJob($massid, $massinfo, $editjob, $error);
	}
	
	public function processCreateJob($massid, $massinfo, $editjob, &$error = '') {
		global $adb;
		
		$queueid = $editjob['queueid'];
		$module = $massinfo['module'];
		$record = Zend_Json::decode($editjob['record']);
		$useWf = ($massinfo['workflows'] == '1');
		
		$adb->pquery("UPDATE {$this->queueTable} SET status = ? WHERE massid = ? AND queueid = ?", array(self::MASSQUEUE_STATUS_PROCESSING, $massid, $queueid));
		
		$error = '';
		$r = true;
		try {
			$r = $this->saveRecord($module, $record, $useWf, $error);
		} catch (Exception $e) {
			$r = false;
			$error = 'EXCEPTION: '.$e->getMessage();
		}
		
		if ($r) {
			$adb->pquery("UPDATE {$this->queueTable} SET status = ? WHERE massid = ? AND queueid = ?", array(self::MASSQUEUE_STATUS_COMPLETE, $massid, $queueid));
		} else {
			$adb->pquery("UPDATE {$this->queueTable} SET status = ?, info = ? WHERE massid = ? AND queueid = ?", array(self::MASSQUEUE_STATUS_ERROR, $error, $massid, $queueid));
			// error log
			$this->error("Error while saving record with queue id $queueid ($module):");
			$this->error($error);
		}
		
		return $r;
	}
	
	public function saveRecord($module, $values, $useWf = true, &$error = '') {
		global $adb, $table_prefix, $current_user;

		$focus = $this->getModuleInstance($module);
		$focus->mode = '';
		
		foreach ($focus->column_fields as $fieldname => $val) {
			if (array_key_exists($fieldname, $values)) {
				$focus->column_fields[$fieldname] = $values[$fieldname];
			}
		}

		if (empty($focus->column_fields['assigned_user_id'])) {
			$focus->column_fields['assigned_user_id'] = $current_user->id;
		}

		if (isInventoryModule($module)) {
			$_REQUEST['action'] = 'MassEditSave'; // Keep it, it's an harcoded value
		}

		if ($useWf) {
			TriggerQueueManager::activateBatchSave();
			$focus->save($module);
		} else {
			$focus->save($module, false, false, false);
		}
		
		return true;
	}
	
	public function getNotificationHtml($massid, $html = '') {

		$info = $this->getNotificationInfo($massid);
		
		if ($info['status'] == self::MASS_STATUS_ERROR) {
			$desc = getTranslatedString('LBL_MASSCREATE_ERROR', 'APP_STRINGS');
			$desc = str_replace(array('{num_records}', '{num_fail_records}'), array($info['num_records'], $info['num_fail_records']), $desc);
			$html = "<b>MassCreate Error</b> ".$desc;
		} else {
			$desc = getTranslatedString('LBL_MASSCREATE_OK', 'APP_STRINGS');
			$desc = str_replace('{num_records}', $info['num_ok_records'], $desc);
			$html = "<b>MassCreate</b> ".$desc;
		}
		
		return $html;
	}
	
}
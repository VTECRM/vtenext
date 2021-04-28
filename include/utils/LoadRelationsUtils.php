<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@91571 crmv@202577 crmv@204673 */

require_once('include/utils/MassQueueBase.php');

class LoadRelationsUtils extends MassQueueBase {
	
	public function __construct() {
		global $table_prefix;

		$this->table = $table_prefix.'_load_relations';
		$this->queueTable = $table_prefix.'_load_relations_queue';

		parent::__construct();
	}
	
	public function enqueue($userid, $forModule, $forRecord, $withModule, $withRecords) {
		global $adb;
		
		if (empty($withRecords)) return true;
		
		// get the loadrelation-id
		$massid = $adb->getUniqueID($this->table);
		
		// save in the main table
		$sql = "INSERT INTO {$this->table} (massid, userid, module, crmid, inserttime, status) VALUES (?,?,?,?,?,?)";
		$params = array($massid, $userid, $forModule, $forRecord, date('Y-m-d H:i:s'), self::MASS_STATUS_IDLE);
		$res = $adb->pquery($sql, $params);
		
		// get the list of ids
		$inserts = array();
		foreach ($withRecords as $crmid) {
			if (!empty($crmid)) {
				$queueid = $adb->getUniqueID($this->queueTable);
				$inserts[] = array($queueid, $massid, $withModule, intval($crmid));
			}
		}
		
		// and quickly insert into the db
		$adb->bulkInsert($this->queueTable, array('queueid', 'massid', 'with_module', 'with_crmid'), $inserts);
		
		return true;
	}
	
	public function notifyUser($massinfo, $result) {
		global $current_user;

		$success = ($result['error'] == 0);

		$focus = ModNotifications::getInstance(); // crmv@164122
		if ($success) {
			$desc = "\n".getTranslatedString('LBL_LOAD_RELATIONS_OK_DESC', 'APP_STRINGS');
			$desc = str_replace(
				array('{num_records}', '{num_fail_records}', '{module}'),
				array($result['processed'], $result['error'], getTranslatedString($massinfo['module'], $massinfo['module'])),
				$desc
			);
			$notifInfo = array(
				'assigned_user_id' => $massinfo['userid'],
				'mod_not_type' => 'Generic',
				'related_to' => $massinfo['crmid'],
				'subject' => getTranslatedString('LBL_LOAD_RELATIONS_OK_SUBJECT', 'APP_STRINGS'),
				'description' => $desc,
				'from_email' => $current_user->email1 ?: $current_user->email2,
				'from_email_name' => getUserFullName($current_user->id),
			);
		} else {
			$desc = "\n".getTranslatedString('LBL_LOAD_RELATIONS_ERROR_DESC', 'APP_STRINGS');
			$desc = str_replace(
				array('{num_records}', '{num_fail_records}', '{module}'),
				array($result['processed'], $result['error'], getTranslatedString($massinfo['module'], $massinfo['module'])),
				$desc
			);
			$notifInfo = array(
				'assigned_user_id' => $massinfo['userid'],
				'mod_not_type' => 'Generic',
				'related_to' => $massinfo['crmid'],
				'subject' => getTranslatedString('LBL_LOAD_RELATIONS_ERROR_SUBJECT', 'APP_STRINGS'),
				'description' => $desc,
				'from_email' => $current_user->email1 ?: $current_user->email2,
				'from_email_name' => getUserFullName($current_user->id),
			);
		}

		$focus->saveFastNotification($notifInfo);

		return true;
	}

	public function processJob($massid, $massinfo, $editjob, &$error = '') {
		global $adb;
		
		$queueid = $editjob['queueid'];

		$module = $massinfo['module'];
		$crmid = intval($massinfo['crmid']);
		$withModule = $editjob['with_module'];
		$withCrmid = intval($editjob['with_crmid']);
		
		$adb->pquery("UPDATE {$this->queueTable} SET status = ? WHERE massid = ? AND queueid = ?", array(self::MASSQUEUE_STATUS_PROCESSING, $massid, $queueid));
		
		$error = '';
		$r = true;
		try {
			$r = $this->saveRecord($module, $crmid, $withModule, $withCrmid, $error);
		} catch (Exception $e) {
			$r = false;
			$error = 'EXCEPTION: '.$e->getMessage();
		}
		
		if ($r) {
			$adb->pquery("UPDATE {$this->queueTable} SET status = ? WHERE massid = ? AND queueid = ?", array(self::MASSQUEUE_STATUS_COMPLETE, $massid, $queueid));
		} else {
			$adb->pquery("UPDATE {$this->queueTable} SET status = ?, info = ? WHERE massid = ? AND queueid = ?", array(self::MASSQUEUE_STATUS_ERROR, $error, $massid, $queueid));
			$this->error("Error while saving record:");
			$this->error($error);
		}
		
		return $r;
	}

	public function saveRecord($module, $crmid, $withModule, $withCrmid, &$error = '') {
		if (empty($module) ||  empty($crmid) ||  empty($withModule) ||  empty($withCrmid)) {
			$error = 'Invalid parameters.';
			return false;
		}

		$focus = CRMEntity::getInstance($module);
		$focus->save_related_module($module, $crmid, $withModule, $withCrmid);

		return true;
	}
	
	public function getNotificationHtml($massid, $html = '') {

		$info = $this->getNotificationInfo($massid);

		if ($info['status'] == self::MASS_STATUS_ERROR) {
			$desc = getTranslatedString('LBL_LOAD_RELATIONS_ERROR', 'APP_STRINGS');
			$desc = str_replace(array('{num_records}', '{num_fail_records}'), array($info['num_records'], $info['num_fail_records']), $desc);
			$html = "<b>LoadingRelations Error</b> ".$desc;
		} else {
			$desc = getTranslatedString('LBL_LOAD_RELATIONS_OK', 'APP_STRINGS');
			$desc = str_replace('{num_records}', $info['num_ok_records'], $desc);
			$html = "<b>LoadingRelations</b> ".$desc;
		}

		return $html;
	}
	
}
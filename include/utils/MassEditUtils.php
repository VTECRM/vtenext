<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@91571 crmv@202577 */

require_once('include/utils/MassQueueBase.php');

class MassEditUtils extends MassQueueBase {
	
	public function __construct() {
		global $table_prefix;

		$this->table = $table_prefix.'_massedit';
		$this->queueTable = $table_prefix.'_massedit_queue';

		parent::__construct();
	}
	
	public function enqueue($userid, $module, $fields, $records, $useWf = true) {
		global $adb;
		
		if (empty($records)) return true;
		
		// get the massedit-id
		$massid = $adb->getUniqueID($this->table);
		
		// save in the main table
		$sql = "INSERT INTO {$this->table} (massid, userid, module, inserttime, workflows, status) VALUES (?,?,?,?,?,?)";
		$params = array($massid, $userid, $module, date('Y-m-d H:i:s'), intval($useWf), self::MASS_STATUS_IDLE);
		$res = $adb->pquery($sql, $params);
		
		$adb->updateClob($this->table, 'fieldvalues', "massid = $massid", Zend_Json::encode($fields));
		
		// get the list of ids
		$inserts = array();
		foreach ($records as $crmid) {
			if (!empty($crmid)) $inserts[] = array($massid, intval($crmid));
		}
		
		// and quickly insert into the db
		$adb->bulkInsert($this->queueTable, array('massid', 'crmid'), $inserts);
		
		return true;
	}
	
	public function notifyUser($massinfo, $result) {
		global $current_user;
		
		$success = ($result['error'] == 0);
		
		$focus = ModNotifications::getInstance(); // crmv@164122
		if ($success) {
			$desc = "\n".getTranslatedString('LBL_MASSEDIT_OK_DESC', 'APP_STRINGS');
			$desc = str_replace(
				array('{num_records}', '{num_fail_records}', '{module}'), 
				array($result['processed'], $result['error'], getTranslatedString($massinfo['module'], $massinfo['module'])), 
				$desc
			);
			$notifInfo = array(
				'assigned_user_id' => $massinfo['userid'],
				'mod_not_type' => 'MassEdit',
				'related_to' => $massinfo['massid'],
				'subject' => getTranslatedString('LBL_MASSEDIT_OK_SUBJECT', 'APP_STRINGS'),
				'description' => $desc,
				'from_email' => $current_user->email1 ?: $current_user->email2,
				'from_email_name' => getUserFullName($current_user->id),
			);
		} else {
			$desc = "\n".getTranslatedString('LBL_MASSEDIT_ERROR_DESC', 'APP_STRINGS');
			$desc = str_replace(
				array('{num_records}', '{num_fail_records}', '{module}'), 
				array($result['processed'], $result['error'], getTranslatedString($massinfo['module'], $massinfo['module'])), 
				$desc
			);
			$notifInfo = array(
				'assigned_user_id' => $massinfo['userid'],
				'mod_not_type' => 'MassEditError',
				'related_to' => $massinfo['massid'],
				'subject' => getTranslatedString('LBL_MASSEDIT_ERROR_SUBJECT', 'APP_STRINGS'),
				'description' => $desc,
				'from_email' => $current_user->email1 ?: $current_user->email2,
				'from_email_name' => getUserFullName($current_user->id),
			);
		}
		
		$focus->saveFastNotification($notifInfo);
		
		return true;
	}

	public function processJob($massid, $massinfo, $editjob, &$error = '') {
		return $this->processEditJob($massid, $massinfo, $editjob, $error);
	}
	
	public function processEditJob($massid, $massinfo, $editjob, &$error = '') {
		global $adb;
		
		$module = $massinfo['module'];
		$crmid = intval($editjob['crmid']);
		$values = Zend_Json::decode($massinfo['fieldvalues']);
		$useWf = ($massinfo['workflows'] == '1');
		
		$adb->pquery("UPDATE {$this->queueTable} SET status = ? WHERE massid = ? AND crmid = ?", array(self::MASSQUEUE_STATUS_PROCESSING, $massid, $crmid));
		
		$error = '';
		$r = true;
		try {
			$r = $this->saveRecord($module, $crmid, $values, $useWf, $error);
		} catch (Exception $e) {
			$r = false;
			$error = 'EXCEPTION: '.$e->getMessage();
		}
		
		if ($r) {
			$adb->pquery("UPDATE {$this->queueTable} SET status = ? WHERE massid = ? AND crmid = ?", array(self::MASSQUEUE_STATUS_COMPLETE, $massid, $crmid));
		} else {
			$adb->pquery("UPDATE {$this->queueTable} SET status = ?, info = ? WHERE massid = ? AND crmid = ?", array(self::MASSQUEUE_STATUS_ERROR, $error, $massid, $crmid));
			// error log
			if ($error == 'NOT_PERMITTED') {
				$this->error("User doesn't have the permission to edit the record $crmid");
			} elseif ($error == 'LBL_RECORD_DELETE') {
				$this->error("The record $crmid has been deleted");
			} elseif ($error == 'LBL_RECORD_NOT_FOUND') {
				$this->error("The record $crmid was not found");
			} else {
				$this->error("Error while saving record $crmid ($module):");
				$this->error($error);
			}
		}
		
		return $r;
	}

	public function countProcessingEditJobs($massid) {
		return $this->countProcessingJobs($massid);
	}
	
	public function extractValuesFromRequest($module, &$request) {
		$focus = $this->getModuleInstance($module);
		
		// crmv@148116
		$InventoryUtils = InventoryUtils::getInstance();
		if (isProductModule($module)) {
			$tax_details = $InventoryUtils->getAllTaxes();
		}
		// crmv@148116e
		
		$massValues = array();
		foreach($focus->column_fields as $fieldname => $val) {
			if(isset($request[$fieldname."_mass_edit_check"])) {
				if ($fieldname == 'assigned_user_id') { // crmv@187598
					if($request['assigntype'] == 'U')  {
						$value = $request['assigned_user_id'];
					} elseif($request['assigntype'] == 'T') {
						$value = $request['assigned_group_id'];
					}
				// crmv@148116
				} elseif ($fieldname == 'taxclass' && is_array($tax_details)) {
					$editTaxes = array();;
					foreach ($tax_details as $taxinfo) {
						$tax_name = $taxinfo['taxname'];
						$tax_checkname = $taxinfo['taxname']."_check";
						if($request[$tax_checkname] == 'on' || $request[$tax_checkname] == 1) {
							$editTaxes[$tax_name] = $request[$tax_name];
						}
					}
					$value = $editTaxes;
				// crmv@148116e
				} else {
					if(is_array($request[$fieldname]))
						$value = $request[$fieldname];
					else
						$value = trim($request[$fieldname]);
				}
				$massValues[$fieldname] = $value;
			}
		}

		// crmv@77878 fix for calendar
		if ($module == 'Calendar' && isset($request["date_start_mass_edit_check"])) {
			if (!empty($request['starthr']) && !empty($request['startmin'])) {
				$value = $request['starthr'].':'.$request['startmin'];
				$massValues['time_start'] = $value;
			}
		}
		// crmv@77878e 
		
		return $massValues;
	}
	
	// crmv@93052 crmv@108612
	public function saveRecord($module, $crmid, $values, $useWf = true, &$error = '') {
		global $adb, $table_prefix;
		
		if (isPermitted($module,'EditView',$crmid) != 'yes') {
			$error = 'NOT_PERMITTED';
			return false;
		}

		$focus = $this->getModuleInstance($module);

		$saveModule = $module;
		if ($module == 'Calendar') {
			$actType = getSingleFieldValue($table_prefix."_activity", 'activitytype', 'activityid', $crmid);
			if($actType == 'Task'){
				$saveModule = $actType;
			} else {
				$saveModule = 'Events';
			}
		}
		
		// Save each module record with update value.
		$r = $focus->retrieve_entity_info($crmid, $module, false);
		if (in_array($r, array('LBL_RECORD_DELETE', 'LBL_RECORD_NOT_FOUND'))) {
			$error = $r;
			return false;
		}
		
		// crmv@183699
		// I know this is a bad thing, but how can I pass stuff to 208.php ?
		global $massedit_fields;
		$massedit_fields = array_keys($values);
		// crmv@183699e
		
		$focus->mode = 'edit';
		$focus->id = $crmid;
		foreach($focus->column_fields as $fieldname => $val) {
			// change the status field for that stupid calendar!
			if ($fieldname == 'taskstatus' && $saveModule == 'Events'){
				$fieldname = 'eventstatus';
			}

			if (array_key_exists($fieldname, $values)) {
				$focus->column_fields[$fieldname] = $values[$fieldname];
			} else {
				$focus->column_fields[$fieldname] = decode_html($focus->column_fields[$fieldname]);
			}
		}

		//crmv@107307
		if(isInventoryModule($module)){
			$_REQUEST['action'] = 'MassEditSave';
		}
		//crmv@107307e
		
		// crmv@148116
		if (isProductModule($module)) {
			if (is_array($focus->column_fields['taxclass'])) {
				$taxinfo = $focus->column_fields['taxclass'];
				$focus->column_fields['taxclass'] = '';
				$_REQUEST['taxclass_mass_edit_check'] = true;
				foreach ($taxinfo as $taxname => $taxvalue) {
					$_REQUEST[$taxname] = $taxvalue;
					$_REQUEST[$taxname.'_check'] = true;
				}
			}
		}
		// crmv@148116e

		//crmv@27096
		if ($useWf) {
			TriggerQueueManager::activateBatchSave(); // crmv@199641
			$focus->save($module);
		} else {
			$focus->save($module,false,false,false);
		}
		//crmv@27096e
		
		return true;
	}
	// crmv@93052e crmv@108612e
	
	public function getNotificationHtml($massid, $html = '') {

		$info = $this->getNotificationInfo($massid);
		
		if ($info['status'] == self::MASS_STATUS_ERROR) {
			$desc = getTranslatedString('LBL_MASSEDIT_ERROR', 'APP_STRINGS');
			$desc = str_replace(array('{num_records}', '{num_fail_records}'), array($info['num_records'], $info['num_fail_records']), $desc);
			$html = "<b>MassEdit Error</b> ".$desc;
		} else {
			$desc = getTranslatedString('LBL_MASSEDIT_OK', 'APP_STRINGS');
			$desc = str_replace('{num_records}', $info['num_ok_records'], $desc);
			$html = "<b>MassEdit</b> ".$desc;
		}
		
		return $html;
	}
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Import_Queue_Controller {

	static $IMPORT_STATUS_NONE = 0;
	static $IMPORT_STATUS_SCHEDULED = 1;
	static $IMPORT_STATUS_RUNNING = 2;
	static $IMPORT_STATUS_HALTED = 3;
	static $IMPORT_STATUS_COMPLETED = 4;

	public function  __construct() {
	}

	// crmv@83878
	public static function add($userInputObject, $user) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();

		if (!Vtecrm_Utils::CheckTable($table_prefix.'_import_queue')) {
			Vtecrm_Utils::CreateTable(
							$table_prefix.'_import_queue',
							"importid I(11) NOTNULL PRIMARY,
								userid I(11) NOTNULL,
								tabid I(11) NOTNULL,
								field_mapping X,
								default_values X,
								fields_formats X,
								merge_type I(11),
								merge_fields X,
								status I(11) DEFAULT 0",
							true);
		}

		if($userInputObject->get('is_scheduled')) {
			$status = self::$IMPORT_STATUS_SCHEDULED;
		} else {
			$status = self::$IMPORT_STATUS_NONE;
		}

		$adb->pquery(
			"INSERT INTO {$table_prefix}_import_queue (importid, userid, tabid, field_mapping, default_values, fields_formats, merge_type, merge_fields, status) 
			VALUES (?,?,?,?,?,?,?,?,?)",
			array($adb->getUniqueID($table_prefix.'_import_queue'), 
				$user->id,
				getTabid($userInputObject->get('module')),
				Zend_Json::encode($userInputObject->get('field_mapping')),
				Zend_Json::encode($userInputObject->get('default_values')),
				Zend_Json::encode($userInputObject->get('fields_formats')),
				$userInputObject->get('merge_type'),
				Zend_Json::encode($userInputObject->get('merge_fields')),
				$status));
	}
	// crmv@83878e

	public static function remove($importId) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();
		if(Vtecrm_Utils::CheckTable($table_prefix.'_import_queue')) {
			$adb->pquery("DELETE FROM {$table_prefix}_import_queue WHERE importid=?", array($importId));
		}
	}

	public static function removeForUser($user) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();
		if(Vtecrm_Utils::CheckTable($table_prefix.'_import_queue')) {
			$adb->pquery("DELETE FROM {$table_prefix}_import_queue WHERE userid=?", array($user->id));
		}
	}

	public static function getUserCurrentImportInfo($user) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();

		if(Vtecrm_Utils::CheckTable($table_prefix.'_import_queue')) {
			$queueResult = $adb->limitpQuery("SELECT * FROM {$table_prefix}_import_queue WHERE userid=?",0,1, array($user->id));

			if($queueResult && $adb->num_rows($queueResult) > 0) {
				$rowData = $adb->raw_query_result_rowdata($queueResult, 0);
				return self::getImportInfoFromResult($rowData);
			}
		}
		return null;
	}
	
	public static function getImportInfo($module, $user) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();
		
		if(Vtecrm_Utils::CheckTable($table_prefix.'_import_queue')) {
			$queueResult = $adb->pquery("SELECT * FROM {$table_prefix}_import_queue WHERE tabid=? AND userid=?",
											array(getTabid($module), $user->id));

			if($queueResult && $adb->num_rows($queueResult) > 0) {
				$rowData = $adb->raw_query_result_rowdata($queueResult, 0);
				return self::getImportInfoFromResult($rowData);
			}
		}
		return null;
	}

	public static function getImportInfoById($importId) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();

		if(Vtecrm_Utils::CheckTable($table_prefix.'_import_queue')) {
			$queueResult = $adb->pquery("SELECT * FROM {$table_prefix}_import_queue WHERE importid=?", array($importId));

			if($queueResult && $adb->num_rows($queueResult) > 0) {
				$rowData = $adb->raw_query_result_rowdata($queueResult, 0);
				return self::getImportInfoFromResult($rowData);
			}
		}
		return null;
	}

	public static function getAll($status=false) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();

		$query = "SELECT * FROM {$table_prefix}_import_queue";
		$params = array();
		if($status !== false) {
			$query .= ' WHERE status = ?';
			array_push($params, $status);
		}
		$result = $adb->pquery($query, $params);

		$noOfImports = $adb->num_rows($result);
		$scheduledImports = array();
		for ($i = 0; $i < $noOfImports; ++$i) {
			$rowData = $adb->raw_query_result_rowdata($result, $i);
			$scheduledImports[$rowData['importid']] = self::getImportInfoFromResult($rowData);
		}
		return $scheduledImports;
	}

	// crmv@83878
	static function getImportInfoFromResult($rowData) {
		return array(
			'id' => $rowData['importid'],
			'module' => getTabModuleName($rowData['tabid']),
			'field_mapping' => Zend_Json::decode($rowData['field_mapping']),
			'default_values' => Zend_Json::decode($rowData['default_values']),
			'fields_formats' => Zend_Json::decode($rowData['fields_formats']),
			'merge_type' => $rowData['merge_type'],
			'merge_fields' => Zend_Json::decode($rowData['merge_fields']),
			'user_id' => $rowData['userid'],
			'status' => $rowData['status']
		);
	}
	// crmv@83878e

	static function updateStatus($importId, $status) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();

		$adb->pquery("UPDATE {$table_prefix}_import_queue SET status=? WHERE importid=?", array($status, $importId));
	}

}

?>
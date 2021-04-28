<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Import_Lock_Controller {
	

	public function  __construct() {
	}

	public static function lock($importId, $module, $user) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();

		if(!Vtecrm_Utils::CheckTable($table_prefix.'_import_locks')) {
			Vtecrm_Utils::CreateTable(
				$table_prefix.'_import_locks',
				"vte_import_lock_id I(11) NOTNULL PRIMARY,
				userid I(11) NOTNULL,
				tabid I(11) NOTNULL,
				importid I(11) NOTNULL,
				locked_since T",
				true);
		}

		$adb->pquery("INSERT INTO {$table_prefix}_import_locks VALUES(?,?,?,?,?)",
						array($adb->getUniqueID($table_prefix.'_import_locks'), $user->id, getTabid($module), $importId, date('Y-m-d H:i:s')));
	}

	public static function unLock($user, $module=false) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();
		if(Vtecrm_Utils::CheckTable($table_prefix.'_import_locks')) {
			$query = "DELETE FROM {$table_prefix}_import_locks WHERE userid=?";
			$params = array($user->id);
			if($module != false) {
				$query .= ' AND tabid=?';
				array_push($params, getTabid($module));
			}
			$adb->pquery($query, $params);
		}
	}

	public static function isLockedForModule($module) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();
		
		if(Vtecrm_Utils::CheckTable($table_prefix.'_import_locks')) {
			$lockResult = $adb->pquery("SELECT * FROM {$table_prefix}_import_locks WHERE tabid=?",array(getTabid($module)));

			if($lockResult && $adb->num_rows($lockResult) > 0) {
				$lockInfo = $adb->query_result_rowdata($lockResult, 0);
				return $lockInfo;
			}
		}

		return null;
	}
}

?>
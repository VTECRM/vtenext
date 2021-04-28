<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $table_prefix;

class iCalLastImport {

	var $tableName = '';
	var $fields = array('id', 'userid', 'entitytype', 'crmid');
	var $fieldData = array();
	
	function  __construct() {
		global $table_prefix;
		$this->tableName = $table_prefix.'_ical_import';
	}

	function clearRecords($userId) {
		$adb = PearDatabase::getInstance();
		if(Vtecrm_Utils::CheckTable($this->tableName)) {
			$adb->pquery('DELETE FROM '.$this->tableName .' WHERE userid = ?', array($userId));
		}
	}

	function setFields($data) {
		if(!empty($data)) {
			foreach($data as $name => $value) {
				$this->fieldData[$name] = $value;
			}
		}
	}

	function save() {
		$adb = PearDatabase::getInstance();

		if(count($this->fieldData) == 0) return;
		
		if(!Vtecrm_Utils::CheckTable($this->tableName)) {
			// crmv@81728
			Vtecrm_Utils::CreateTable(
				$this->tableName,
				"userid I(11) NOTNULL PRIMARY,
					id I(11) NOTNULL PRIMARY,
					entitytype C(200) NOTNULL,
					crmid I(11) NOTNULL",
				true);
			// crmv@81728e
		}

		$fieldNames = array_keys($this->fieldData);
		$fieldValues = array_values($this->fieldData);
		$adb->pquery('INSERT INTO '.$this->tableName.'('. implode(',',$fieldNames) .') VALUES ('. generateQuestionMarks($fieldValues) .')',
				array($fieldValues));
	}

	function undo($moduleName, $userId) {
		global $table_prefix;
		$adb = PearDatabase::getInstance();
		if(Vtecrm_Utils::CheckTable($this->tableName)) {
			$result = $adb->pquery('UPDATE '.$table_prefix.'_crmentity SET deleted=1 WHERE crmid IN
								(SELECT crmid FROM '.$this->tableName .' WHERE userid = ? AND entitytype = ?)',
						array($userId, $moduleName));
			return $adb->getAffectedRowCount($result);
		}
	}
}
?>
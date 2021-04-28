<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 cmrv@163697

require_once('include/BaseClasses.php');

class PrivacyPolicyUtils extends SDKExtendableUniqueClass {
	
	protected $table = null;
	
	public function __construct() {
		global $adb, $table_prefix;
		
		$this->table = $table_prefix.'_privacy_policy';
	}
	
	public function checkTables() {
		global $adb, $table_prefix;
		
		$schema_table = '<schema version="0.3">
			<table name="' . $this->table. '">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="id" type="I" size="3">
					<KEY/>
				</field>
				<field name="type" type="C" size="50">
					<KEY/>
				</field>
				<field name="privacy_policy" type="XL" />
				<index name="type_idx">
					<col>type</col>
				</index>
			</table>
		</schema>';
		
		if (!Vtecrm_Utils::CheckTable($this->table)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
	}
	
	public function install() {
		$this->checkTables();
	}
	
	public function get($id, $type) {
		global $adb, $table_prefix;
		
		$privacyPolicy = null;
		
		$sql = "SELECT * FROM {$table_prefix}_privacy_policy WHERE id = ? AND type = ?";
		$result = $adb->pquery($sql, array($id, $type));
		
		if ($result && $adb->num_rows($result)) {
			$row = $adb->fetchByAssoc($result, -1, false);
			$privacyPolicy = $row['privacy_policy'];
		}
		
		return $privacyPolicy;
	}
	
	public function save($id, $type, $privacyPolicy) {
		global $adb, $table_prefix;
		
		if (empty($privacyPolicy)) return false;

		$sql = "SELECT 1 FROM {$table_prefix}_privacy_policy WHERE id = ? AND type = ?";
		$result = $adb->pquery($sql, array($id, $type));
		
		if ($result) {
			if ($adb->num_rows($result) > 0) {
				return $this->update($id, $type, $privacyPolicy);
			} else {
				return $this->create($id, $type, $privacyPolicy);
			}
		}
		
		return false;
	}
	
	public function create($id, $type, $privacyPolicy) {
		global $adb, $table_prefix;
		
		if (empty($privacyPolicy)) return false;
		
		$params = array(
			'id' => $id,
			'type' => $type,
			'privacy_policy' => $privacyPolicy
		);
		
		$columns = array_keys($params);
		$adb->format_columns($columns);
		
		$adb->pquery("INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES (" . generateQuestionMarks($params) . ")", $params);
		
		return true;
	}
	
	public function update($id, $type, $privacyPolicy) {
		global $adb, $table_prefix;
		
		if (empty($privacyPolicy)) return false;
		
		$adb->pquery("UPDATE {$table_prefix}_privacy_policy SET privacy_policy = ? WHERE id = ? AND type = ?", array($privacyPolicy, $id, $type));
		
		return true;
	}
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@157490 */

require_once('include/BaseClasses.php');

class ServerConfigUtils extends SDKExtendableUniqueClass {
	
	public function encryptAll() {
		global $adb, $table_prefix;
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$result = $adb->query("select id, server_password from {$table_prefix}_systems");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!empty($row['server_password'])) {
					$server_password = $encryption->encrypt($row['server_password']);
					$adb->pquery("update {$table_prefix}_systems set server_password = ? where id = ?", array($server_password, $row['id']));
				}
			}
		}
	}
	
	public function checkConfiguration($type) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select id from {$table_prefix}_systems where server_type = ?", array($type));
		return ($result && $adb->num_rows($result) > 0);
	}
	
	// return only one configuration
	public function getConfiguration($type, $fields=array(), $column_key='server_type', $single_result=false, $service_types=array()) {
		global $adb, $table_prefix;
		
		(empty($fields)) ? $select = '*' : $select = implode(',',$fields);
		$query = "select $select from {$table_prefix}_systems where $column_key = ?";
		$params = array($type);
		if (!empty($service_types)) {
			$query .= ' and service_type in ('.generateQuestionMarks($service_types).')';
			$params[] = $service_types;
		}
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			$row = $adb->fetchByAssoc($result);
			if (!empty($row['server_password'])) {
				require_once('include/utils/encryption.php');
				$encryption = new Encryption();
				$row['server_password'] = $encryption->decrypt($row['server_password']);
			}
			if ($single_result && count($fields) == 1) {
				return $row[$fields[0]];
			} else {
				return $row;
			}
		}
		return false;
	}
	
	// return an array of configurations
	public function getConfigurations($type, $fields=array(), $column_key='server_type') {
		global $adb, $table_prefix;
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$configurations = array();
		(empty($fields)) ? $select = '*' : $select = implode(',',$fields);
		$result = $adb->pquery("select $select from {$table_prefix}_systems where $column_key = ? order by id", array($type));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!empty($row['server_password'])) {
					$row['server_password'] = $encryption->decrypt($row['server_password']);
				}
				$configurations[] = $row;
			}
		}
		return $configurations;
	}
	
	public function removeConfiguration($type, $skip_ids=array()) {
		global $adb, $table_prefix;
		$query = "delete from {$table_prefix}_systems where server_type = ?";
		$params = array($type);
		if (!empty($skip_ids)) {
			$query .= ' and id not in ('.generateQuestionMarks($skip_ids).')';
			$params[] = $skip_ids;
		}
		$adb->pquery($query, $params);
	}
	
	public function saveConfiguration($id='', $values) {
		global $adb, $table_prefix;
		if (!empty($values['server_password'])) {
			require_once('include/utils/encryption.php');
			$encryption = new Encryption();
			$values['server_password'] = $encryption->encrypt($values['server_password']);
		}
		if (empty($id)) {
			$id = $adb->getUniqueID($table_prefix."_systems");
			$adb->pquery("insert into {$table_prefix}_systems (id,".implode(',',array_keys($values)).") values(?,".generateQuestionMarks($values).")", array($id,$values));
		} else {
			$set = array();
			foreach($values as $column => $value) $set[] = $column.' = ?';
			$adb->pquery("update {$table_prefix}_systems set ".implode(',',$set)." where id = ?", array($values,$id));
		}
		return $id;
	}
}
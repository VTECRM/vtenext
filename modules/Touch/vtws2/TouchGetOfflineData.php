<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* Webservice for Offline, only available as an extra package */

class TouchGetOfflineData extends TouchWSClass {
	
	public $table_name_prop;
	
	public function __construct($wsversion = null) {
		global $table_prefix;
		parent::__construct($wsversion);
		
		$this->table_name_prop = $table_prefix.'_touch_offline_prop';
	}
	
	public function process(&$request) {
		global $touchInst, $touchUtils, $current_user, $table_prefix;
		
		$this->checkTable();
		
		$active = $this->getProperty('active');
		if (!$active) return $this->error('Offline mode not active');
		
		$key = $this->getOfflineKey();
		
		return $this->success(array('key'=>$key));
	}
	
	public function isActive() {
		$active = $this->getProperty('active');
		if (!$active) return false;
		
		$key = $this->getOfflineKey();
		if (!$key) return false;
		
		return true;
	}
	
	public function activate($key = null) {
		$this->setProperty('active', 1);
		if (!is_null($key)) $this->setProperty('offlinekey', $key);
	}
	
	public function deactivate() {
		$this->setProperty('active', 0);
	}
	
	public function reset() {
		$this->deleteProperty('active');
		$this->deleteProperty('offlinekey');
	}
	
	public function getOfflineKey() {
		return $this->getProperty('offlinekey');
	}

	public function generateClientKey($username) {
		global $site_URL, $root_directory, $application_unique_key;
		$clientKey = base64_encode(Zend_Json::encode(array(
			'vte_url' => $site_URL,
			'vte_path' => $root_directory,
			'vte_appkey' => $application_unique_key,
			'req_username' => $username,
		)));
		return $clientKey;
	}
	
	// FALSE if not found
	protected function getProperty($property) {
		global $adb, $table_prefix;
		
		$r = $adb->pquery("SELECT value FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r && $adb->num_rows($r) > 0) {
			return $adb->query_result_no_html($r, 0, 'value');
		}
		return false;
	}
	
	protected function setProperty($property, $value) {
		global $adb, $table_prefix;
		
		$r = $adb->pquery("SELECT value FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r) {
			if ($adb->num_rows($r) > 0) {
				// update
				$r = $adb->pquery("UPDATE {$this->table_name_prop} SET value = ? WHERE property = ?", array($value, $property));
			} else {
				// insert
				$r = $adb->pquery("INSERT INTO {$this->table_name_prop} (property, value) VALUES (?,?)", array($property, $value));
			}
		}
	}
	
	protected function deleteProperty($property) {
		global $adb, $table_prefix;
		
		$r = $adb->pquery("DELETE FROM {$this->table_name_prop} WHERE property = ?", array($property));
	}
	
	public function checkTable() {
		global $adb;
		
		$schema_table =
		'<schema version="0.3">
			<table name="'.$this->table_name_prop.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="property" type="C" size="63">
					<KEY/>
				</field>
				<field name="value" type="C" size="1023" />
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($this->table_name_prop)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}

	}
	
}
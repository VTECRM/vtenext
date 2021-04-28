<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@185894 */

require_once('include/BaseClasses.php');

class SlaveHandler extends SDKExtendableUniqueClass {
	
	private $debug = false;
	private $active = false;
	private $functions = array();
	private $connection = array();
	private $adb = null;
	private $adb_cache = null;
	
	function __construct() {
		if ($this->debug) echo "SlaveHandler::__construct()<br>";
		$VTEP = VTEProperties::getInstance();
		$this->active = $VTEP->getProperty('performance.slave_handler');
		$this->functions = $VTEP->getProperty('performance.slave_functions');
		$this->connection = $VTEP->getProperty('performance.slave_connection');
	}
	
	function isActive(string $function) {
		if ($this->debug) echo "isActive($function) ";
		static $is_active = array();
		if (!isset($is_active[$function])) {
			$is_active[$function] = ($this->active && in_array($function,$this->functions));
			if ($this->debug) { echo '| '; var_dump($is_active[$function]); echo '<br>'; }
		}
		return $is_active[$function];
	}
	
	function checkDatabaseConnection() {
		if ($this->debug) echo "checkDatabaseConnection() ";
		if (empty($this->adb)) {
			if ($this->debug) echo "| load adb ";

			$this->adb = new PearDatabase();
			$this->adb->resetSettings($this->connection['db_type'],$this->connection['db_server'].$this->connection['db_port'],$this->connection['db_name'],$this->connection['db_username'],$this->connection['db_password']);
			$this->adb->connect();
			
		} elseif ($this->debug) echo "| adb loaded ";
		
		if (empty($this->adb->database->_connectionID)) {
			if ($this->debug) echo "| Connection Failed<br>";
			return false;
		} else {
			if ($this->debug) echo "| Connected<br>";
			return true;
		}
	}
	
	function getPearDatabaseObject() {
		return $this->adb;
	}

	function getCacheDbName() {
		return $this->connection['db_name_cache'];
	}
}
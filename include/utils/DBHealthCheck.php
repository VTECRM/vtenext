<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class DBHealthCheck {
	
	var $db;	
	var $dbType;
	var $dbName;
	var $dbHostName;
	var $recommendedEngineType = 'InnoDB';
	
	function __construct($db) {
		$this->db = $db;
		$this->dbType = $db->databaseType;
		$this->dbName = $db->databaseName;
		$this->dbHostName = $db->host;
	}
	 
	function isMySQL() { return (stripos($this->dbType ,'mysql') === 0);}
    function isOracle() { return $this->dbType=='oci8'; }
    
	function isDBHealthy() {
		$tablesList = $this->getUnhealthyTablesList();
		if (count($tablesList) > 0) {
			return false;
		}
		return true;
	}
	
	function getUnhealthyTablesList() {
		$tablesList = array();
		if($this->isMySql()) {
			$tablesList = $this->_mysql_getUnhealthyTables();
		}
		return $tablesList;
	}
	
	function updateTableEngineType($tableName) {
		if($this->isMySql()) {
			$this->_mysql_updateEngineType($tableName);
		}
	}
	
	function updateAllTablesEngineType() {
		if($this->isMySql()) {
			$this->_mysql_updateEngineTypeForAllTables();
		}
	}
	
	function _mysql_getUnhealthyTables() {
		$tablesResult = $this->db->_Execute("SHOW TABLE STATUS FROM `$this->dbName`");
		$numberOfRows = $tablesResult->NumRows($tablesResult);
		$unHealthyTables = array();
		$i=0;
		for($j=0; $j<$numberOfRows; ++$j) {
			$tableInfo = $tablesResult->GetRowAssoc(0);
			$tableNameParts = explode("_",$tableInfo['name']);
			$tableNamePartsCount = count($tableNameParts);
			$isHealthy = false;
			// If already InnoDB type, or view skip it.
			//crmv@25240
			if ($tableInfo['engine'] == 'InnoDB' || $tableInfo['comment'] == 'VIEW') {
				$isHealthy = true;
			}			
			//crmv@25240e
			// If table is a sequence table, then skip it.			
			else if ($tableNameParts[$tableNamePartsCount-1] == 'seq') {
				$isHealthy = true;
			}
			if(!$isHealthy) {
				$unHealthyTables[$i]['name'] = $tableInfo['name'];
				$unHealthyTables[$i]['engine'] = $tableInfo['engine'];
				$unHealthyTables[$i]['autoincrementValue'] = $tableInfo['auto_increment'];
				$tableCollation = $tableInfo['collation'];
				$unHealthyTables[$i]['characterset'] = substr($tableCollation, 0, strpos($tableCollation,'_'));
				$unHealthyTables[$i]['collation'] = $tableCollation;
				$unHealthyTables[$i]['createOptions'] = $tableInfo['create_options'];
				++$i;
			}
			$tablesResult->MoveNext();
		}
		return $unHealthyTables;
	}
	
	function _mysql_updateEngineType($tableName) {
		$this->db->_Execute("ALTER TABLE $tableName ENGINE=$this->recommendedEngineType");
	}
	
	function _mysql_updateEngineTypeForAllTables() {
		$unHealthyTables = $this->_mysql_getUnhealthyTables();
		$numberOfRows = count($unHealthyTables);
		for($i=0; $i<$numberOfRows; ++$i) {
			$tableName = $unHealthyTables[$i]['name'];
			$this->db->_Execute("ALTER TABLE $tableName ENGINE=$this->recommendedEngineType");
		}		
	}
}


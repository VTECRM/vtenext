<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Storage;

/* crmv@120777 */

class DatabaseUtils {
	
	protected $db;
	protected $log;
	
	public function __construct($db) {
		$this->db = $db;
		$this->log = new \VteSyncLib\Logger();
		$this->log->setPrefix('[DB]');
	}
	
	public function getUniqueId($seqname) {
        return $this->db->GenID($seqname."_seq", 1);
	}
	
	public function getTableColumns($tablename) {
		$colNames = array();
		$adoflds = $this->db->MetaColumns($tablename);
		foreach($adoflds as $fld) {
		    $colNames[] = $fld->name;
		}
		return $colNames;
	}
	
	public function addColumnToTable($tablename, $columnname, $type, $extra = '') {
		$colNames = $this->getTableColumns($tablename);

		// check if already present
		if (in_array($columnname, $colNames)) return true;
		
		$col = $columnname.' '.$type.' '.$extra;
		$dict = NewDataDictionary($this->db);

		$sqlarray = $dict->AddColumnSQL($tablename, $col);
		$r = $dict->ExecuteSQLArray($sqlarray);
		if (!$r) return $this->log->error("Error adding column $columnname to table $tablename");
		
		return true;
	}
	
	public function addIndexToTable($tablename, $indexname, $columns) {
		$dict = NewDataDictionary($this->db);
		$sql = (Array)$dict->CreateIndexSQL($indexname, $tablename, $columns);
		if (!$sql) {
			return $this->log->error("Unable to create index sql for index $indexname");
		}
		$r = $dict->ExecuteSQLArray($sql);
		if (!$r) {
			$this->log->warning("Unable to create index $indexname");
		}
		
		return true;
	}
	
}
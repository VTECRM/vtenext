<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@168297 */
 
/**
 * Class to add and remove new SOAP webservices, used in the customer portal
 */
class SOAPWSManager extends SDKExtendableUniqueClass {

	protected $table;
	protected $params_table;
	
	public function __construct() {
		global $table_prefix;
		
		$this->table = $table_prefix.'_soapws_operation';
		$this->params_table = $table_prefix.'_soapws_operation_params';
	}
	
	public function getWebserviceId($name) {
		global $adb;
		
		$r = $adb->pquery("SELECT operationid FROM {$this->table} WHERE name = ?", array($name));
		return $adb->query_result_no_html($r, 0, 'operationid') ?: null;
	}

	public function getWebservice($name) {
		global $adb;
		
		$r = $adb->pquery("SELECT * FROM {$this->table} WHERE name = ?", array($name));
		if ($adb->num_rows($r) > 0) {
			$row = $adb->FetchByAssoc($r, -1, false);
			$row['params'] = $this->getParameters($row['operationid']);
			return $row;
		}
		
		return null;
	}
	
	public function getAllWebservices() {
		global $adb;
		
		$list = array();
		$r = $adb->query("SELECT * FROM {$this->table}");
		if ($adb->num_rows($r) > 0) {
			while ($row = $adb->FetchByAssoc($r, -1, false)) {
				$row['params'] = $this->getParameters($row['operationid']);
				$list[] = $row;
			}
		}
		
		return $list;
	}
	
	public function addWebservice($name, $path, $class, $return_type, $parameters = array()) {
		global $adb;
		
		// check if exists
		$id = $this->getWebserviceId($name);
		if ($id > 0) return false;
		
		$id = $adb->GetUniqueId($this->table);
		
		$params = array($id, $name, $path, $class, $return_type);
		$r = $adb->pquery("INSERT INTO {$this->table} (operationid, name, handler_path, handler_class, return_type) VALUES (".generateQuestionMarks($params).")", $params);
		
		// now add the parameters
		if (count($parameters) > 0) {
			foreach ($parameters as $param) {
				$this->addParameter($id, $param['name'], $param['type'], $param['sequence'] ?: -1);
			}
		}
		
		return $id;
	}
	
	public function removeWebservice($name) {
		global $adb;
		
		$id = $this->getWebserviceId($name);
		if ($id > 0) {
			$adb->pquery("DELETE FROM {$this->table} WHERE operationid = ?", array($id));
			$adb->pquery("DELETE FROM {$this->params_table} WHERE operationid = ?", array($id));
		}
	}
	
	
	public function getParameter($wsnameorid, $pname) {
		global $adb;
	
		if (!is_numeric($wsnameorid)) {
			$wsnameorid = $this->getWebserviceId($wsnameorid);
			if (!$wsnameorid) return false;
		}
		
		$r = $adb->pquery("SELECT * FROM {$this->params_table} WHERE operationid = ? AND name = ?", array($wsnameorid, $pname));
		if ($adb->num_rows($r) > 0) {
			$row = $adb->FetchByAssoc($r, -1, false);
			return $row;
		}
		
		return null;
	}
	
	public function getParameters($wsnameorid) {
		global $adb;
		
		if (!is_numeric($wsnameorid)) {
			$wsnameorid = $this->getWebserviceId($wsnameorid);
			if (!$wsnameorid) return false;
		}
		
		$list = array();
		$r = $adb->pquery("SELECT * FROM {$this->params_table} WHERE operationid = ? ORDER BY sequence ASC", array($wsnameorid));
		if ($adb->num_rows($r) > 0) {
			while ($row = $adb->FetchByAssoc($r, -1, false)) {
				$list[] = $row;
			}
		}
		
		return $list;
	}
	
	public function addParameter($wsnameorid, $pname, $ptype, $sequence = -1) {
		global $adb;
		
		if (!is_numeric($wsnameorid)) {
			$wsnameorid = $this->getWebserviceId($wsnameorid);
			if (!$wsnameorid) return false;
		}
		
		if ($sequence == -1) {
			// get last sequence
			$r = $adb->pquery("SELECT MAX(sequence) as maxseq FROM {$this->params_table} WHERE operationid = ?", array($wsnameorid));
			if ($adb->num_rows($r) > 0) {
				$sequence = $adb->query_result_no_html($r, 0, 'maxseq') + 1;
			} else {
				$sequence = 1;
			}
		}
		
		$params = array($wsnameorid, $pname, $ptype, $sequence);
		$r = $adb->pquery("INSERT INTO {$this->params_table} (operationid, name, param_type, sequence) VALUES (".generateQuestionMarks($params).")", $params);

	}
	
	public function removeParameter($wsnameorid, $pname) {
		global $adb;
		
		if (!is_numeric($wsnameorid)) {
			$wsnameorid = $this->getWebserviceId($wsnameorid);
			if (!$wsnameorid) return false;
		}
		
		$adb->pquery("DELETE FROM {$this->table} WHERE operationid = ? AND name = ?", array($wsnameorid, $pname));
	}
}
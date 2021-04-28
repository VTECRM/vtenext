<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */

require_once('include/utils/CronUtils.php');
require_once('modules/Settings/DataImporter/DataImporterCron.php');

class DataImporterUtils {

	public $table_name = '';
	
	// where to look for CSV files
	public $import_folder = 'dataimport';
	// accept only files with these extensions
	public $import_extensions = array('txt', 'csv');
	
	// this is where the temporary files will be saved
	public $working_folder = 'plugins/dataimporter';
	
	public $can_run_manually = true;
	

	private $adbCheck;
	
	public function __construct() {
		global $table_prefix;
		
		$this->table_name = $table_prefix.'_dataimporter';

		eval(Users::m_de_cryption());
		eval($hash_version[20]);
	}
	
	public function canEditQuery() {
		return $this->can_edit_query;
	}
	
	public function canRunManually() {
		return $this->can_run_manually;
	}
	
	/**
	 * Get the list of importers configured
	 */
	public function getList() {
		global $adb, $table_prefix;
		
		$ret = array();
		$res = $adb->query("SELECT * FROM {$this->table_name}");
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				$orow = $this->transformRowFromDb($row);
				$nextStart = $this->calculateNextStart($orow['scheduling']);
				if ($nextStart > 0) {
					$orow['next_start'] = date('Y-m-d H:i', $nextStart);
				}
				$ret[] = $orow;
			}
		}
		return $ret;
	}
	
	/**
	 * Save a new importer
	 */
	public function insertImporter($data) {
		global $adb, $table_prefix;

		$now = date('Y-m-d H:i:s');
		$id = $adb->getUniqueID($this->table_name);
		
		$data = $this->transformRowToDb($data);
		$params = array(
			'id' => $id,
			'module' => $data['module'],
			'invmodule' => $data['invmodule'],			
			'createdtime' => $now,
			'modifiedtime' => $now,
			'enabled' => 1,
			'running' => 0,
			'notifyto' => intval($data['notifyto']),
		);
		$q = "INSERT INTO {$this->table_name} (".implode(',', array_keys($params)).") VALUES (".generateQuestionMarks($params).")";

		// insert the row
		$res = $adb->pquery($q, $params);
		
		// update the long text fields
		if ($res) {
			$jsonFields = array('srcinfo', 'mapping', 'scheduling');
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table_name, $f, "id = $id", $data[$f]);
					if ($f == 'scheduling') $this->updateCronJob($id, Zend_Json::decode($data[$f])); // crmv@205568
				}
			}
		} else {
			return false;
		}
		
		return $id;
	}
	
	/**
	 * Update an existing entry in the importers list.
	 * The modulename cannot be changed
	 */
	public function updateImporter($id, $data) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		$data = $this->transformRowToDb($data);
		$params = array(
			'modifiedtime' => $now,
			//'enabled' => ($data['enabled'] ? 1 : 0),
			'notifyto' => intval($data['notifyto']),
			'id' => $id,
		);
		$q = "UPDATE {$this->table_name} SET modifiedtime = ?, notifyto = ? WHERE id = ?";
		
		// update the row
		$res = $adb->pquery($q, $params);
		
		// update the long text fields
		if ($res) {
			$jsonFields = array('srcinfo', 'mapping', 'scheduling');
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table_name, $f, "id = $id", $data[$f]);
					if ($f == 'scheduling') $this->updateCronJob($id, Zend_Json::decode($data[$f])); // crmv@205568
				}
			}
		} else {
			return false;
		}
		
		return true;
	}
	
	public function updateSingleField($id, $field, $value) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		$params = array(
			'modifiedtime' => $now,
			$field => $value,
			'id' => $id,
		);
		$q = "UPDATE {$this->table_name} SET modifiedtime = ?, {$field} = ? WHERE id = ?";
		
		// update the row
		$res = $adb->pquery($q, $params);
	}
	
	protected function getCronjobParameters($scheduling) {
		$every = intval($scheduling['dimport_sched_every']);
		$what = $scheduling['dimport_sched_everywhat'];
		$at = $scheduling['dimport_sched_at'];

		// defaults
		$timeout = 3600*6;	// 6 hours
		$repeat = 60;		// every minute
		
		// try to lower the frequency of the cron
		if ($what == 'minute') {
			$repeat = max(60 * $every, 60);
			$timeout = min($timeout, 60*$every*4);	// 4 times the interval
		}
		// TODO: other cases, are they even possible?
		
		$params = array(
			'timeout' => $timeout,
			'repeat' => $repeat,
		);
		return $params;
	}
	
	public function updateCronJob($importid, $scheduling) {
		
		$cronname = 'DataImporter_'.$importid;
		$params = $this->getCronjobParameters($scheduling);
		$CU = CronUtils::getInstance();

		// install cronjob
		$cj = CronJob::getByName($cronname);
		if (empty($cj)) {
			$cj = new CronJob();
			$cj->name = $cronname;
			$cj->active = 1;
			$cj->singleRun = false;
		}
		$cj->timeout = $params['timeout'];
		$cj->repeat = $params['repeat'];
		$cj->fileName = 'cron/modules/DataImporter/DataImporter.service.php?importid='.$importid;

		$CU->insertCronJob($cj);
	}
	
	public function deleteCronJob($importid) {
		$cronname = 'DataImporter_'.$importid;
		
		$cronjob = CronJob::getByName($cronname);
		if ($cronjob) {
			$cronjob->delete();
		}
	}
	
	public function setEnabled($id, $value = 1) {
		// [de]activate the cronjob
		$cronname = 'DataImporter_'.$id;
		$cronjob = CronJob::getByName($cronname);
		if ($cronjob) {
			$cronjob->activate($value);
		}
		// change the flag
		return $this->updateSingleField($id, 'enabled', ($value ? 1 : 0));
	}
	
	public function setRunning($id, $value = 1) {
		return $this->updateSingleField($id, 'running', ($value ? 1 : 0));
	}
	
	public function isEnabled($id) {
		global $adb, $table_prefix;
		
		$q = "SELECT enabled FROM {$this->table_name} WHERE id = ?";
		$params = array($id);
		
		// update the row
		$res = $adb->pquery($q, $params);
		$enabled = $adb->query_result_no_html($res, 0, 'enabled') ? true : false;
		return $enabled;
	}
	
	public function setOverride($id, $override, $value = 1) {
		if ($override == 'runnow' || $override == 'abort') {
			$this->updateSingleField($id, 'override_'.$override, $value);
		}
	}
	
	/**
	 * Get informations about a single importer. Returns false if the id is not found.
	 */
	public function getImporterInfo($id) {
		global $adb, $table_prefix;
		
		$ret = false;
		$res = $adb->pquery("SELECT * FROM {$this->table_name} WHERE id = ?", array($id));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				$ret = $this->transformRowFromDb($row);
			}
		}
		return $ret;
	}
	
	protected function transformRowFromDb($row) {
		$jsonFields = array('srcinfo', 'mapping', 'scheduling');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::decode($row[$f]);
			}
		}
		return $row;
	}
	
	protected function transformRowToDb($row) {
		$jsonFields = array('srcinfo', 'mapping', 'scheduling');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::encode($row[$f]);
			}
		}
		return $row;
	}
	
	/**
	 * Remove an importer definition from the list
	 */
	public function deleteImporter($id) {
		global $adb;
				
		// remove the saved line
		$adb->pquery("DELETE FROM {$this->table_name} WHERE id = ?", array($id));
		
		// and the associated cronjob
		$this->deleteCronJob($id);
		
		return true;
	}
		
	/*
	protected function rrmdir($dir) { 
		if (is_dir($dir)) { 
			$objects = scandir($dir); 
			foreach ($objects as $object) { 
				if ($object != "." && $object != "..") { 
					$subpath = $dir.DIRECTORY_SEPARATOR.$object;
					if (filetype($subpath) == "dir") $this->rrmdir($subpath); else unlink($subpath); 
				} 
			} 
			reset($objects); 
			rmdir($dir); 
		} elseif (is_file($dir)) {
			unlink($dir);
		}
	}
	*/
	
	public function calculateNextStart($schedInfo) {
		$every = intval($schedInfo['dimport_sched_every']);
		$what = $schedInfo['dimport_sched_everywhat'];
		$at = $schedInfo['dimport_sched_at'];
		$now = time();
		if ($what == 'day') {
			$period = 3600*24;
			$d = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d', $now).' '.$at);
			$start = $d->getTimestamp();
		} elseif ($what == 'hour') {
			$period = 3600*$every;
			$d = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d', $now).' 00:'.str_pad($at, 2, '0', STR_PAD_LEFT));
			$start = $d->getTimestamp();
		} elseif ($what == 'minute') {
			$period = 60*$every;
			$d = DateTime::createFromFormat('Y-m-d', date('Y-m-d', $now));
			$start = $d->getTimestamp();
		}
		if (!$start) return 0;
		while ($start < $now) {
			$start += $period;
		}
		
		return $start;
	}
	
	public function getImporterModules() {
		global $adb, $table_prefix;
		
		$skipMods = array('PBXManager', 'Messages', 'ModComments', 'Charts', 'Emails', 'MyNotes', 'Sms', 'Fax', 'Calendar'); // crmv@164120 crmv@164122
		
		$list = array();
		$res = $adb->pquery("SELECT name FROM {$table_prefix}_tab WHERE presence = 0 AND isentitytype = 1 AND name NOT IN (".generateQuestionMarks($skipMods).")", $skipMods);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$list[$row['name']] = getTranslatedString($row['name'], $row['name']);
			}
		}
		// add a fake module to import product rows for inventory modules
		$list['ProductRows'] = getTranslatedString('LBL_RELATED_PRODUCTS', 'Settings');
		asort($list);
		$list['ProductRows'] = '['.$list['ProductRows'].']';
		
		return $list;
	}
	
	// get the inventory modules only if they are in other imports
	public function getImporterInventoryModules() {
		$list = array();
		$imports = $this->getList();
		foreach ($imports as $import) {
			$mod = $import['module'];
			if (isInventoryModule($mod)) {
				$list[$mod] = getTranslatedString($mod, $mod);
			}
		}
		return $list;
	}
	
	public function getSupportedDbType() {
		require_once('include/install/resources/utils.php');
		$dbOptions = Installation_Utils::getDbOptions();
		return $dbOptions;
	}
	
	public function getCSVEncodings() {
		return array(
			'AUTO' => getTranslatedString('LBL_AUTOMATIC'),
			'UTF-8' => 'UTF8',
			'ISO-8859-1' => 'ISO-8859-1',
		);
	}
	
	public function getCSVDelimiters() {
		return array(
			'AUTO' => getTranslatedString('LBL_AUTOMATIC'),
			',' => getTranslatedString('comma', 'Import'),
			';' => getTranslatedString('semicolon', 'Import'),
		);
	}
	
	public function connectToExternalDb($importRow) {
		if ($this->adbCheck && $this->adbCheck->database->isConnected()) return $this->adbCheck;

		$dbtype = $importRow['srcinfo']['dimport_dbtype'];
		$dbhost = trim($importRow['srcinfo']['dimport_dbhost']);
		$dbport = trim($importRow['srcinfo']['dimport_dbport']);
		$dbuser = trim($importRow['srcinfo']['dimport_dbuser']);
		$dbpass = $importRow['srcinfo']['dimport_dbpass'];
		$dbname = trim($importRow['srcinfo']['dimport_dbname']);
		$host_separator = ':'; // crmv@166852

		// remove port for mysqli (PearDatabase doesn't support it)
		if ($dbtype == 'mysqli' && $dbport == '3306') $dbport = ''; // crmv@87579
		if (($dbtype == 'mssqlnative' || $dbtype == 'mssql') && $dbport == '1433') $dbport = ''; // crmv@155585 crmv@178746
		if ($dbtype == 'mssqlnative') $host_separator = ','; // crmv@166852

		// crmv@77830 - fix for ms sql instances
		if(strpos($dbhost,'\\') !== false){
			list($dbhost_ip,$instance) = explode('\\',$dbhost);
			$host = $dbhost_ip.(empty($dbport) ? '' : $host_separator.$dbport).'\\'.$instance; // crmv@166852
		} else {
			$host = $dbhost.(empty($dbport) ? '' : $host_separator.$dbport); // crmv@166852
		}
		// crmv@77830e
		
		// crmv@193619
		$ssl_connection = false;
		if (substr($host, 0, 6) === 'ssl://') {
			$ssl_connection = true;
			$host = substr($host,6);
		}
		// crmv@193619e
		
		$this->adbCheck = new PearDatabase($dbtype, $host, $dbname, $dbuser, $dbpass);
		$this->adbCheck->usePersistent = false;		// force a new connection, in case the host, user and pwd is the same
		$this->adbCheck->setDieOnError(false);		// disable die on error
		$this->adbCheck->setExceptOnError(true);	// but enable exception on error
		if ($ssl_connection) $this->adbCheck->setSSLconnection(); // crmv@193619
		@$this->adbCheck->connect();
		if (!$this->adbCheck->database->isConnected()) {
			$this->adbCheck = null;
			return false;
		}
		return $this->adbCheck;
	}
	
	private function getDbTableColumns($db, $table) {
		$colNames = array();
		$adoflds = $db->database->MetaColumns($table);
		foreach($adoflds as $fld) {
		    $colNames[] = strtolower($fld->name);
		}
		if (empty($colNames)) {
			return getTranslatedString('LBL_NO_QUERY_RESULT').' '.getTranslatedString('LBL_ALTER_QUERY_TO_GET_ROWS');
		} else {
			return $colNames;
		}
	}
	
	
	private function getSQLSelectColumns($sql) {
		// This is not supproted at the moment, since it requires a full sql parsing
		return getTranslatedString('LBL_NO_QUERY_RESULT').' '.getTranslatedString('LBL_ALTER_QUERY_TO_GET_ROWS');
	}
	
	public function cleanImportQuery($sql) {
		$sql = preg_replace('/;$/', '', trim($sql));
		return $sql;
	}
	
	public function getImportColumns($importRow) {
		global $current_user;
		// get the columns either from CSV, from the table or from the query
		
		$type = $importRow['srcinfo']['dimport_sourcetype'];
		$table = $importRow['srcinfo']['dimport_dbtable'];
		$query = $this->cleanImportQuery($importRow['srcinfo']['dimport_dbquery']);
		
		if ($type == 'database') {
			$adbExt = $this->connectToExternalDb($importRow);
			if (!empty($table)) {
				// get columns for table
				$cols = $this->getDbTableColumns($adbExt, $table);
			} elseif (!empty($query)) {
				try {
					$res = $adbExt->limitpQuery($query, 0, 1);
					if ($adbExt->num_rows($res) > 0) {
						$row = $adbExt->FetchByAssoc($res, -1, false);
						if (empty($row)) {
							return getTranslatedString('LBL_NO_QUERY_RESULT').' '.getTranslatedString('LBL_ALTER_QUERY_TO_GET_ROWS');
						} else {
							$this->firstImportRow = $row;
							$cols = array_keys($row);
						}
					} else {
						// no results, parse the query
						$cols = $this->getSQLSelectColumns($query);
					}
					
				} catch (Exception $e) {
					$cols = $e->getMessage();
				}
			}
		} elseif ($type == 'csv') {
			// read or count first row from CSV
			$hasHeader = $importRow['srcinfo']['dimport_csvhasheader'];
			$file = $this->getOneCSVFile($importRow['srcinfo']['dimport_csvpath']);
			$cfg = array(
				'file' => $this->import_folder.$file,
				'delimiter' => $importRow['srcinfo']['dimport_csvdelimiter'],
				'file_encoding' => $importRow['srcinfo']['dimport_csvencoding'],
				'has_header' => !!$hasHeader,
			);
			FSUtils::removeBOM($cfg['file']); // crmv@138011
			$reader = new DataImporterCSVReader($current_user, $cfg);
			$row = $reader->getFirstRowData($hasHeader);
			if (!is_array($row) || count($row) == 0) return getTranslatedString('LBL_INVALID_FILE', 'Import');
			$cols = array_keys($row);
		}

		return $cols;
	}
	
	public function getFirstImportRow($importRow) {
		global $curent_user;
		
		// check if I already have it
		if (!empty($this->firstImportRow)) return $this->firstImportRow;
	
		$type = $importRow['srcinfo']['dimport_sourcetype'];
		$table = $importRow['srcinfo']['dimport_dbtable'];
		$query = $this->cleanImportQuery($importRow['srcinfo']['dimport_dbquery']);

		$row = 'ERROR';
		if ($type == 'database') {
			$adbExt = $this->connectToExternalDb($importRow);
			if (!empty($table)) {
				$adbExt->format_columns($table);
				$sql = "SELECT * FROM $table";
			} elseif (!empty($query)) {
				$sql = $query;
			}
			try {
				$res = $adbExt->limitpQuery($sql, 0, 1);
				if ($res && $adbExt->num_rows($res) > 0) {
					$row = $adbExt->FetchByAssoc($res, -1, false);
				} else {
					return getTranslatedString('LBL_NO_QUERY_RESULT').' '.getTranslatedString('LBL_ALTER_QUERY_TO_GET_ROWS');
				}
			} catch (Exception $e) {
				return $e->getMessage();
			}
		} elseif ($type == 'csv') {
			// read or count first row from CSV
			$hasHeader = $importRow['srcinfo']['dimport_csvhasheader'];
			$file = $this->getOneCSVFile($importRow['srcinfo']['dimport_csvpath']);
			$cfg = array(
				'file' => $this->import_folder.$file,
				'delimiter' => $importRow['srcinfo']['dimport_csvdelimiter'],
				'file_encoding' => $importRow['srcinfo']['dimport_csvencoding'],
				'has_header' => !!$hasHeader,
			);
			$reader = new DataImporterCSVReader($current_user, $cfg);
			$row = $reader->getFirstRowData($hasHeader);
			if (!is_array($row) || count($row) == 0) return getTranslatedString('LBL_INVALID_FILE', 'Import');
		}

		return $row;
	}
	
	public function getMappableFields($importRow) {
		global $current_user;
		$module = $importRow['module'];
		
		if ($module == 'ProductRows') return $this->getMappableProductsFields($importRow);
		$focus = CRMEntity::getInstance($module);
		$moduleHandler = vtws_getModuleHandlerFromName($module, $current_user);
		$moduleMeta = $moduleHandler->getMeta();
		//$moduleObjectId = $moduleMeta->getEntityId();
		$moduleFields = $moduleMeta->getModuleFields();
		
		// can return raw objects
		if ($objects) return $moduleFields;
		
		// now transform this array of objects in a normal array with useful properties
		$fields = array();
		foreach ($moduleFields as $finfo) {
			$fname = $finfo->getFieldName();
			$field = array(
				'fieldid' => $finfo->getFieldId(),
				'blockid' => $finfo->getBlockId(),
				'fieldname' => $fname,
				'tablename' => $finfo->getTableName(),
				'columnname' => $finfo->getColumnName(),
				'fieldlabel' => $finfo->getFieldLabelKey(),
				'label' => getTranslatedString($finfo->getFieldLabelKey(), $module),
				'uitype' => intval($finfo->getUitype()),
				'type' => $finfo->getFieldDataType(),
				'mandatory' => $finfo->isMandatory(),
				'references' => $finfo->getReferenceList() ?: null,	// crmv@90287
			);
			if ($field['type'] == 'picklist' || $field['type'] == 'multipicklist') {
				$field['picklistdetails'] = $finfo->getPicklistDetails();
			}
			//crmv@93655
			if($field['type'] == 'reference' && $fname == 'currency_id'){
				$field['type'] = 'integer';
			}
			//crmv@93655e
			// crmv@203591
			// disable direct import of prod attributes, use a separate import for them!
			if ($field['type'] == 'table' /* && !($module == 'ConfProducts' && $fname == 'mlProdAttr') */) {
				continue;
			}
			// crmv@203591e
			$fields[$fname] = $field;
		}
		
		// crmv@203591
		if ($module == 'Products' && vtlib_isModuleActive('ConfProducts')) {
			$cprods = CRMEntity::getInstance('ConfProducts');
			$attrs = $cprods->getAllAttributes();
			foreach ($attrs as $attrField) {
				$attrField['label'] = $attrField['productname'].': '.$attrField['fieldlabel'];
				$fields[$attrField['fieldname']] = $attrField;
			}
		}
		// crmv@203591e
		
		// TODO: prepopulate the default value with something useful...
		
		return $fields;
	}
	
	// fake fields for the products block
	public function getMappableProductsFields($importRow) {
		global $adb, $table_prefix;
		
		$invlabel = '';
		$invmod = $importRow['invmodule'];
		$keys = $this->getOtherImportKeys($importRow);
		foreach ($keys as $k) {
			if ($k['module'] == $invmod) {
				$invlabel = getTranslatedString('SINGLE_'.$k['module'], $k['module']);
				break;
			}
		}

		$fields = array(
			'inventoryid' => array(
				'fieldname' => 'inventoryid',
				'fieldlabel' => 'Inventory Name',
				'label' => $invlabel,
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'id',
				'mandatory' => true,
				'uitype' => 10,
				'type' => 'reference',
			),
			'productid' => array(
				'fieldname' => 'productid',
				'fieldlabel' => 'Product Name',
				'label' => getTranslatedString('Product Name', $invmod),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'productid',
				'mandatory' => true,
				'uitype' => 10,
				'type' => 'reference',
			),
			'quantity' => array(
				'fieldname' => 'quantity',
				'fieldlabel' => 'Quantity',
				'label' => getTranslatedString('Quantity', $invmod),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'quantity',
				'mandatory' => true,
				'uitype' => 7,
				'type' => 'double',
			),
			'listprice' => array(
				'fieldname' => 'listprice',
				'fieldlabel' => 'List Price',
				'label' => getTranslatedString('List Price', $invmod),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'listprice',
				'mandatory' => true,
				'uitype' => 7,
				'type' => 'double',
			),
			'discount_percent' => array(
				'fieldname' => 'discount_percent',
				'fieldlabel' => 'Discount Percent',
				'label' => getTranslatedString('Discount Percent', 'Quotes'),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'discount_percent',
				'mandatory' => false,
				'uitype' => 7,
				'type' => 'double',
			),
			'discount_amount' => array(
				'fieldname' => 'discount_amount',
				'fieldlabel' => 'Discount Amount',
				'label' => getTranslatedString('Discount Amount', 'Quotes'),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'discount_amount',
				'mandatory' => false,
				'uitype' => 7,
				'type' => 'double',
			),
			'sequence_no' => array(
				'fieldname' => 'sequence_no',
				'fieldlabel' => 'LBL_PRODUCT_POSITION',
				'label' => getTranslatedString('LBL_PRODUCT_POSITION', 'PDFMaker'),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'sequence_no',
				'mandatory' => false,
				'uitype' => 7,
				'type' => 'integer',
			),
			'comment' => array(
				'fieldname' => 'comment',
				'fieldlabel' => 'Comments',
				'label' => getTranslatedString('Comments', $invmod),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'comment',
				'mandatory' => false,
				'uitype' => 19,
				'type' => 'text',
			),
			'linetotal' => array(
				'fieldname' => 'linetotal',
				'fieldlabel' => 'Line Total',
				'label' => getTranslatedString('Line Total', 'Settings'),
				'tablename' => $table_prefix.'_inventoryproductrel',
				'columnname' => 'linetotal',
				'mandatory' => false,
				'uitype' => 7,
				'type' => 'double',
			),
			// TODO: taxes and discounts
		);
		return $fields;
	}	
	
	public function getOtherImportKeys($importRow = null) {
		global $adb, $table_prefix;
		$importid = $importRow['id'];
		$module = $importRow['module'];
		$invmodule = $importRow['invmodule'];
		
		$keyfields = array();
		$imports = $this->getList();
		foreach ($imports as $import) {
			// exclude myself
			if ($import['id'] == $importid) continue;
			$imodule = $import['module'];
			// exclude other modules if is a product block
			if ($module == 'ProductRows') {
				if (!isProductModule($imodule) && $imodule != $invmodule) continue;
			}
			
			$keycol = $import['mapping']['dimport_mapping_keycol'];
			if (!empty($keycol) && is_array($import['mapping']['fields'])) {
				$keycol = $this->fixSpaceNames($keycol, false);
				if (!empty($import['mapping']['fields'][$keycol])) {
					$keydata = array(
						'importid' => $import['id'],
						'module' => $import['module'],
						'modulelabel' => getTranslatedString($import['module'], $import['module']),
						'srccolumn' => $keycol,
						'keyfield' => $import['mapping']['fields'][$keycol],
					);
					//crmv@93027 exclude duplicates
					if (!empty($keyfields)) {
						foreach($keyfields as $tmp) {
							if ($keydata['module'] == $tmp['module'] && $keydata['keyfield']['field'] == $tmp['keyfield']['field']) continue(2);
						}
					}
					//crmv@93027e
					// get some more info about that field
					$res = $adb->pquery("SELECT fieldlabel FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?", array(getTabid($keydata['module']), $keydata['keyfield']['field']));
					if ($res && $adb->num_rows($res) > 0) {
						$row = $adb->FetchByAssoc($res, -1, false);
						$keydata['keyfield']['fieldlabel'] = $row['fieldlabel'];
						$keydata['keyfield']['label'] = getTranslatedString($keydata['keyfield']['fieldlabel'], $keydata['module']);
					}
					$keyfields[] = $keydata;
				}
			}
		}
		return $keyfields;
	}
	
	public function getAvailableFormats() {
		return array(
			'EMAIL_REGEX' => array(
				'name' => 'EMAIL_REGEX',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_EMAIL'),
				'fortypes' => array('email'),
				'foruitypes' => '',
				'regex' => '',
				'phpfilter' => array(
					'filter' => FILTER_VALIDATE_EMAIL
				),
			),
			/*'URL_REGEX' => array(
				'name' => 'URL_REGEX',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_URL'),
				'fortypes' => array('url'),
				'foruitypes' => '',
				//'regex' => '^(f|h)ttps?:',	// TODO: www.** also?
				//'phpfilter' => array(
				//	'filter' => FILTER_VALIDATE_URL
				//),
			),*/
			'PHONE_REGEX' => array(
				'name' => 'PHONE_REGEX',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_PHONE'),
				'fortypes' => array('phone', 'fax'),
				'foruitypes' => array(1013),
				'regex' => '/^\+?[0-9 .\-)(]+$/',	// this is a veeeery basic phone format regexp
			),
			'BOOL_INT_REGEX' => array(
				'name' => 'BOOL_INT_REGEX',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_INTEGER_01'),
				'fortypes' => array('boolean'),
				'foruitypes' => '',
				'regex' => '/^[01]$/',
			),
			'BOOL_NULL_REGEX' => array(
				'name' => 'BOOL_NULL_REGEX',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_INTEGER_NULL'),
				'fortypes' => array('boolean'),
				'foruitypes' => '',
				'regex' => '',
			),
			'DATE_TIME_REGEX' => array(
				'name' => 'DATE_TIME_REGEX',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_DATETIME'),
				'fortypes' => array('datetime', 'date'),
				'foruitypes' => '',
				'hasvalue' => true,
				'value' => 'Y-m-d H:i:s',
			),
			// crmv@117880
			'NUMBER_REGEX' => array(
				'name' => 'NUMBER_REGEX',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_NUMBER'),
				'fortypes' => array('number', 'currency'),
				'foruitypes' => array(7,9,71,72),
				'hasvalue' => false,
				'hasvalues' => true,
				'values' => array(
					'' => '',
					'EMPTY:PERIOD' => '1234.56',
					'EMPTY:COMMA' => '1234,56',
					'PERIOD:COMMA' => '1.234,56',
					'COMMA:PERIOD' => '1,234.56',
					'SPACE:PERIOD' => '1 234.56',
					'SPACE:COMMA' => '1 234,56',
					'QUOTE:PERIOD' => '1\'234.56',
				)
			),
			// crmv@117880e
			// crmv@203591
			// if you enable this, you'll be able to import attributes with the ConfigProd import
			// attributes should be in a single column, with only names separated by a delimiter
			// or in json format as {"attrname" => ["value1", "value2", ...], ...}
			/*
			'ATTRIBUTES_SEPARATOR' => array(
				'name' => 'ATTRIBUTES_SEPARATOR',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_ATTRIB_NAMES'),
				'fortypes' => array('table'),
				'foruitypes' => array(220),
				'hasvalue' => false,
				'hasvalues' => true,
				'values' => array(
					'SPACE' => getTranslatedString('LBL_DIMPORT_SEP_SPACE'),
					'COMMA' => getTranslatedString('LBL_DIMPORT_SEP_COMMA'),
					'COLON' => getTranslatedString('LBL_DIMPORT_SEP_COLON'),
					'SEMICOLON' => getTranslatedString('LBL_DIMPORT_SEP_SEMICOLON'),
				)
			),
			'ATTRIBUTES_TABLE' => array(
				'name' => 'ATTRIBUTES_TABLE',
				'label' => getTranslatedString('LBL_DIMPORT_FORMAT_ATTRIB_TABLE'),
				'fortypes' => array('table'),
				'foruitypes' => array(220),
				'hasvalue' => false,
				'hasvalues' => true,
				'values' => array(
					'JSON' => 'JSON (object + array)',
				)
			),
			*/
			// crmv@203591e
		);
	}
	
	public function getAvailableFormulas() {
		return array(
			'PREPEND' => array(
				'name' => 'PREPEND',
				'label' => getTranslatedString('LBL_DIMPORT_FORMULA_PREPEND'),
				'fortypes' => array('string', 'text'),
				'foruitypes' => '',
				'hasvalue' => true,
				'value' => '',
			),
			'APPEND' => array(
				'name' => 'APPEND',
				'label' => getTranslatedString('LBL_DIMPORT_FORMULA_APPEND'),
				'fortypes' => array('string', 'text'),
				'foruitypes' => '',
				'hasvalue' => true,
				'value' => '',
			),
			'ADD' => array(
				'name' => 'ADD',
				'label' => getTranslatedString('LBL_DIMPORT_FORMULA_ADD'),
				'fortypes' => array('number', 'integer', 'currency'),
				'foruitypes' => '',
				'hasvalue' => true,
				'value' => '',
			),
			'SUBTRACT' => array(
				'name' => 'SUBTRACT',
				'label' => getTranslatedString('LBL_DIMPORT_FORMULA_SUBTRACT'),
				'fortypes' => array('number', 'integer', 'currency'),
				'foruitypes' => '',
				'hasvalue' => true,
				'value' => '',
			),
			'YEAR' => array(
				'name' => 'YEAR',
				'label' => getTranslatedString('LBL_DIMPORT_FORMULA_YEAR'),
				'forformats' => array('DATE_TIME_REGEX'),
				'fortypes' => '',
				'foruitypes' => '',
				'value' => '',
			),
			'YEARMONTH' => array(
				'name' => 'YEARMONTH',
				'label' => getTranslatedString('LBL_DIMPORT_FORMULA_YEARMONTH'),
				'forformats' => array('DATE_TIME_REGEX'),
				'fortypes' => '',
				'foruitypes' => '',
				'value' => '',
			),
			
			// TODO: custom functions
		);
	}
	
	public function getAvailableUsers($module) {
		require_once('modules/Import/resources/Utils.php');
		$list = Import_Utils::getAssignedToUserList($module);
		return $list;
	}
	
	public function getAvailableGroups($module) {
		require_once('modules/Import/resources/Utils.php');
		$list = Import_Utils::getAssignedToGroupList($module);
		return $list;
	}
	
	public function extractFieldsProperties($fields) {
		$list = array();
		foreach ($fields as $finfo) {
			$fname = $finfo['fieldname'];
			$def = array(
				'uitype' => $finfo['uitype'],
				'type' => $finfo['type'],
				'mandatory' => $finfo['mandatory'],
				// crmv@90287
				'label' => $finfo['fieldlabel'],
				'references' => $finfo['references'],
				// crmv@90287e
			);
			$list[$fname] = $def;
		}
		return $list;
	}
	
	public function getSchedulingVars() {
		$vars = array(
			'labels' => array(
				'day' => getTranslatedString('LBL_DAY'),
				'days' => getTranslatedString('LBL_DAYS'),
				'hour' => getTranslatedString('LBL_HOUR'),
				'hours' => getTranslatedString('LBL_HOURS'),
				'minute' => getTranslatedString('LBL_MINUTE'),
				'minutes' => getTranslatedString('LBL_MINUTES'),
				'at_hour' => getTranslatedString('LBL_AT_HOUR'),
				'at_minute' => getTranslatedString('LBL_AT_MINUTE'),
			),
			'default_hour' => '22:00',
			'default_minute' => '0',
		);
		return $vars;
	}
	
	public function getOneCSVFile($name) {
		global $root_directory;
		//check for jolly chars
		if (preg_match('/\*|\?/', $name)) {
			// get the first matching file
			
			if (substr($this->import_folder, -1) != '/') $this->import_folder .= '/';
			
			chdir($this->import_folder);
			$list = glob($name);
			chdir($root_directory);
			if (!$list) return false;
			foreach ($list as $file) {
				$path = $this->import_folder.$file;
				if ($file != '.' && $file != '..' && is_readable($path) && is_file($path)) {
				
					// check the extension
					$epos = strrpos($file, '.');
					if ($epos !== false) {
						$ext = strtolower(substr($file, $epos+1));
					} else {
						$ext = '';
					}
					if (is_array($this->import_extensions) && count($this->import_extensions) > 0) {
						if (!in_array($ext, $this->import_extensions)) return false;
					}
					return $file;
				}
			}
			return false;
		} else {
			return $name;
		}
	}
	// returns the full patch of all csv files eligible for import
	public function getAllCSVFiles($name) {
		global $root_directory;
		
		$filesList = array();
		if (substr($this->import_folder, -1) != '/') $this->import_folder .= '/';
		
		//check for jolly chars
		if (preg_match('/\*|\?/', $name)) {
			// get the first matching file
			
			chdir($this->import_folder);
			$list = glob($name);
			chdir($root_directory);
			if (!$list) return false;
			foreach ($list as $file) {
				$path = $this->import_folder.$file;
				if ($file != '.' && $file != '..' && is_readable($path) && is_file($path)) {
				
					// check the extension
					$epos = strrpos($file, '.');
					if ($epos !== false) {
						$ext = strtolower(substr($file, $epos+1));
					} else {
						$ext = '';
					}
					if (is_array($this->import_extensions) && count($this->import_extensions) > 0) {
						if (!in_array($ext, $this->import_extensions)) return false;
					}
					$filesList[] = $path;
				}
			}
		} else {
			$filesList[] = $this->import_folder.$name;
		}
		return $filesList;
	}
	
	public function getLastLogPath($importid) {
		$path = $this->working_folder."/import_{$importid}/logs/last.log";
		return $path;
	}
	
	public function run($importid, $inBackground = false) {
		if ($inBackground) {
			// set the override flag, then the cron will start it
			$this->setOverride($importid, 'runnow', 1);
			return true;
		} else {
			$dcron = new DataImporterCron($importid);
			return $dcron->run();
		}
	}
	
	public function abort($importid) {
		$info = $this->getImporterInfo($importid);
		if (!$info['running']) {
			// I can just remove the start flag
			$this->setOverride($importid, 'runnow', 0);
			$this->setOverride($importid, 'abort', 0);
		} else {
			// I have to stop it while running
			$this->setOverride($importid, 'abort', 1);
		}
		return true;
	}
	
	public function isAborted($importid) {
		global $adb;
		$q = "SELECT override_abort FROM {$this->table_name} WHERE id = ?";
		$params = array($importid);
		
		// update the row
		$res = $adb->pquery($q, $params);
		$enabled = $adb->query_result_no_html($res, 0, 'override_abort') ? true : false;
		return $enabled;
	}
	
	public function resetFailedImport($importid) {
		$this->setOverride($importid, 'runnow', 0);
		$this->setOverride($importid, 'abort', 0);
		$this->setRunning($importid, 0);
		// resetting the cron is done in another class
	}
	
	public function sendFailNotification($importid, $error = '') {
		global $current_user,$site_URL; // crmv@178322
		$info = $this->getImporterInfo($importid);
		
		$logFile = $this->working_folder.'/import_'.$importid.'/logs/last.log';
		if (is_readable($logFile)) {
			$logContent = file_get_contents($logFile);
		}
		$dataimporter_url = $site_URL.'/index.php?module=Settings&action=DataImporter'; // crmv@178322
		$this->updateSingleField($importid, 'errors', 1);
		$focus = ModNotifications::getInstance(); // crmv@164122
		$focus->saveFastNotification(
			array(
				'assigned_user_id' => $info['notifyto'],
				'mod_not_type' => 'Import Error',
				'subject' => getTranslatedString('LBL_IMPORT_ERROR_SUBJECT', 'Settings'),
				'description' => "\n".sprintf(getTranslatedString('LBL_IMPORT_ERROR_NOTIF_DESC', 'Settings'),$dataimporter_url)."<br>\n<br>\nError: $error<br>\n<br>\nLog:<br>\n<pre>\n$logContent\n</pre>", // crmv@178322
				'from_email' => $current_user->email1,
				'from_email_name' => getUserFullName($current_user->id),
			)
		);

	}
	
	// return true if no action needed, false if it should be activated
	public function checkMysqlLocalInfile() {
		global $adb, $dbconfig;

		if ($dbconfig['db_type'] == 'mysql') {
			$res = $adb->query("show variables like 'local_infile'");
			if ($res && $adb->num_rows($res) > 0) {
				$value = $adb->query_result_no_html($res, 0, 'value');
				$okArray = array('ON', 'on', 'true', 'TRUE', '1');
				return in_array($value, $okArray);
			}
		}
		
		return true;
	}

	// crmv@71496 crmv@105144
	public function fixSpaceNames($mapping, $toHtml = true) {
		$newMapping = array();
		
		if ($toHtml) {
			$from = array(' ', "'", '.', '(', ')');
			$to = array('0SPC0', '0QUO0', '0DOT0', '0LRP0', '0RRP0');
		} else {
			$from = array('0SPC0', '0QUO0', '0DOT0', '0LRP0', '0RRP0');
			$to = array(' ', "'", '.', '(', ')');
		}
		if (is_array($mapping)) {
			foreach ($mapping as $key => $field) {
				$newMapping[str_replace($from, $to, $key)] = $field;
			}
		} else {
			return str_replace($from, $to, $mapping);
		}
		
		return $newMapping;
	}
	// crmv@71496e crmv@105144e
	
}


// extends the classic CSV reader
require_once('modules/Import/api/UserInput.php');
require_once('modules/Import/readers/CSVReader.php');

class DataImporterCSVReader extends Import_CSV_Reader {

	public function __construct($user, $config=array()) {
		$uinput = new Import_API_UserInput();
		foreach ($config as $cfg=>$val) {
			$uinput->set($cfg, $val);
		}
		parent::__construct($uinput, $user);
	}
	
	public function getFilePath() {
		return $this->userInputObject->get('file');
	}
}

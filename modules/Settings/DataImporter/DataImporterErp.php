<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 - reviewed version of the erpConnector classes */
/* crmv@90287 - support for same-module reference fields */

/*
	If you want to test an import directly, use this code:

	require_once('modules/Settings/DataImporter/DataImporterCron.php');
	$dcron = new DataImporterCron(3);	// 3 is the importid
	$r = $dcron->run();

	inside a standard script file
*/

require_once('include/BaseClasses.php');
require_once('include/VTEBaseLogger.php');
require_once('modules/Settings/DataImporter/DataImporterUtils.php');
require_once('modules/Settings/DataImporter/DataImporterErpClasses.php');


class DataImporterErp extends SDKExtendableClass {
	
	public $logConfig = array(
		'level' => 5,					// 5 = debug, 4 = info, 3 = warning, 2 = error, 1 = fatal
		'name' => 'DataImporter',
	);
	public $config = array(
		'log_memory_rows' => 1000,		// log memory usage statistics every N rows, only if debug level >= 5, set to 0 do deactivate
		'trim_csv_values' => true,		// if true, values read from CSV are trimmed (space characters are removed from beginning and end)
		'trim_db_values' => false,		// if true, values read from database are trimmed
		'on_invalid_cell' => 'skip',	// "skip", "blank" or "ignore": when a cell contains an invalid value, you can skip the whole line, empty the cell
										//   or ignore it and try to continue (there may be problems later)
		'log_statistics' => true,		// if true, print out some statistics about the import process
		'keep_import_files' => false,	// if true, import files are kept (warning: they might use a lot of space!!)
		'autoclean_import_files' => 30,	// number of days after which automatically delete old import files (0 to disable)
		'autoclean_log_files' => 90,	// number of days after which automatically delete old log files (0 to disable)
		'import_user' => 'ADMIN',		// during the import, impersonate this user. Can be a userid, a username or "ADMIN" to use the first valid admin user. 
										//   Empty to disable the switch. Using a non-admin user can give problems.
		'abort_interval' => 30,			// Every X seconds, check if the import should be aborted, only used in the first reading phase
		'auto_create_index' => true,	// If true, automatically creates indexes for columns used as external codes
		'fast_mysql_insert' => true,	// If true, when the VTE database is MySql, insert records really fast by using local-infile. Disabled when importing the product block
	);
	
	public $simulate = false;			// if true, no changes are made to VTE (only some crmid increments might happen)
	
	//crmv@136410
	public $launch_workflows = false;
	public $launch_processes = false;
	public $enable_process_modify_action = true;	// if false dataimport do not trigger the event "on change" but only "on create", "on create / change" and "every time the condition is true", if true all triggers are enabled
	//crmv@136410e
	
	public $pass;
	public $module;
	public $invmodule;
	public $logTable;
	public $erpdir;
	public $sqldir;
	public $logdir;
	public $object;
	public $log;
	
	public $entity_created = array();
	public $entity_updated = array();
	public $entity_deleted = array();
	public $entity_2ndpass = array();
	
	// some internal vars
	protected $importId;
	protected $importInfo;
	protected $time_start;
	protected $diutils;
	protected $iutils;
	protected $allFormats;
	protected $allFormulas;
	
	// mapping related stuff
	protected $mapping;
	protected $mapping_inverse;
	protected $external_code;
	protected $fields = Array();
	protected $fields_functions = Array();
	protected $sequence_field = null;
	protected $fields_auto_create = Array();
	protected $fields_auto_update = Array();
	protected $fields_runtime = Array();
	protected $selfReferencies = array();
	
	// vars used for database connection
	private $extdb;
	private $extdb_charset; // crmv@109628
	private $qresult;
	private $query = '';
	private $selfRefUpdateQueries = '';
	private $prodAttrCreated = false; // crmv@203591
	private $prodAttrValues = []; // crmv@203591
	
	// vars used by csv
	private $csvFiles = array();
	private $currentFileIdx = -1;
	private $fhandle;
	private $csvreader;
	private $csv_rows = 0;
	
	// statistics vars
	private $row_retrieved = 0;
	private $row_discarded = 0;
	private $time_readsrc = '';
	private $time_saverecords = '';
	private $time_total = '';
	
	// other internal vars, for product block
	private $currentInventoryId = null;
	private $currentTotals = array();
	
	
	public function __construct($importid, $importInfo) {
		global $table_prefix, $root_directory;
		
		$this->importId = $importid;
		$this->importInfo = $importInfo;
		$this->logTable = $table_prefix.'_dataimporter_log';
		
		$this->module = $importInfo['module'];
		$this->invmodule = $importInfo['invmodule'];
		
		// data importer utils
		$this->diutils = new DataImporterUtils();
		
		// inventory utils
		if ($this->module == 'ProductRows') {
			$this->config['fast_mysql_insert'] = false;
			$this->iutils = InventoryUtils::getInstance();
			$this->iutils->workingPrecision = $this->iutils->outputPrecision = 3;
		}
		
		$this->erpdir = rtrim($root_directory, '/').'/'.$this->diutils->working_folder;
		$this->sqldir = $this->erpdir.'/import_'.$importid;
		$this->logdir = $this->sqldir.'/logs';
		
		// log object
		$logCfg = array(
			'file_global' => array_merge($this->logConfig, array('type' => 'file', 'rotate_size' => 5)),
			'file_last' => array_merge($this->logConfig, array('type' => 'file', 'clean_on_start' => true)),
			'std' => $this->logConfig,
		);
		$logCfg['file_global']['file'] = $this->logdir.'/general.log';
		$logCfg['file_last']['file'] = $this->logdir.'/last.log';
		$this->log = new DataImporterLogger($logCfg);
		
		
		$this->allFormats = $this->diutils->getAvailableFormats();
		$this->allFormulas = $this->diutils->getAvailableFormulas();
		$this->initModuleObject();
		$this->prepareMapping();
		$this->getFieldsDesc();
		
	}
	
	public function __destruct() {
		if ($this->fhandle) fclose($this->fhandle);
	}
	
	public function initModuleObject() {
		global $table_prefix;
		
		if ($this->module == 'ProductRows' && $this->invmodule) {
			$this->object = CRMEntity::getInstance($this->invmodule);
		} else {
			$this->object = CRMEntity::getInstance($this->module);
		}
		if ($this->module == 'Users') {
			$this->object->tab_name = Array($table_prefix.'_users');
			$this->object->tab_name_index = Array($table_prefix.'_users'=>'id');
		}
		//check table: rimuovere tabelle che non fanno parte del "core" del modulo (tipo ticketcomments)
		foreach ($this->object->tab_name_index as $tablename=>$index){
			if (!in_array($tablename,$this->object->tab_name))
				unset($this->object->tab_name_index[$tablename]);
		}
	}
	
	public function prepareMapping() {
		$this->external_code = $this->diutils->fixSpaceNames($this->importInfo['mapping']['dimport_mapping_keycol'], false); // crmv@93582 crmv@105144
		
		foreach ($this->importInfo['mapping']['fields'] as $colname => $map) {
			if ($map['field']) {
				$this->mapping[$colname] = $map['field'];
			}
		}
		if (is_array($this->mapping)) {
			$this->mapping_inverse = array_flip($this->mapping);
		}

		// autocreate/update defaults. THe importer expect this to be a table->column array, but I have field names, so I need to translate them
		if (is_array($this->importInfo['mapping']['deffields'])) {
			foreach ($this->importInfo['mapping']['deffields']['create'] as $fld) {
				if (!empty($fld['field']) && isset($fld['default'])) {
					$finfo = $this->getFieldColumn($this->module, array($fld['field']));
					$finfo = $finfo[$fld['field']];
					$this->fields_auto_create[$finfo['tablename']][$finfo['columnname']] = $fld['default'];
				}
			}
			foreach ($this->importInfo['mapping']['deffields']['update'] as $fld) {
				if (!empty($fld['field']) && isset($fld['default'])) {
					$finfo = $this->getFieldColumn($this->module, array($fld['field']));
					$finfo = $finfo[$fld['field']];
					$this->fields_auto_update[$finfo['tablename']][$finfo['columnname']] = $fld['default'];
				}
			}
		}
	}
	
	// add some default for creation and update in case they are missing
	protected function addAutoFields() {
		global $table_prefix, $current_user;
		
		if (empty($this->time_start)) {
			$this->time_start = date('Y-m-d H:i:s');	// this is not right, since the import might require a long time... but ok let's keep it this way
		}
		$crmTable = $table_prefix.'_crmentity';
		$inventoryTable = $table_prefix.'_inventoryproductrel';
		
		if ($this->module == 'ProductRows') {
			$this->fields_auto_create[$inventoryTable]['relmodule'] = $this->invmodule;
			$this->fields_auto_update[$inventoryTable]['relmodule'] = $this->invmodule;
			$this->fields_auto_create[$inventoryTable]['incrementondel'] = 0;
			return;
		}

		if (in_array($crmTable,$this->object->tab_name)){
			if (!array_key_exists('creator', $this->mapping_inverse) && empty($this->fields_auto_create[$crmTable]['smcreatorid'])) {
				$this->fields_auto_create[$crmTable]['smcreatorid'] = $current_user->id;
			}
			if (!array_key_exists('assigned_user_id', $this->mapping_inverse) && empty($this->fields_auto_create[$crmTable]['smownerid'])) {
				$this->fields_auto_create[$crmTable]['smownerid'] = $current_user->id;
			}
			if (!array_key_exists('modifiedby', $this->mapping_inverse)) {
				if (empty($this->fields_auto_create[$crmTable]['modifiedby'])) {
					$this->fields_auto_create[$crmTable]['modifiedby'] = $current_user->id;
				}
				if (empty($this->fields_auto_update[$crmTable]['modifiedby'])) {
					$this->fields_auto_update[$crmTable]['modifiedby'] = $current_user->id;
				}
			}
			if (!array_key_exists('createdtime', $this->mapping_inverse) && empty($this->fields_auto_create[$crmTable]['createdtime'])) {
				$this->fields_auto_create[$crmTable]['createdtime'] = $this->time_start;
			}
			if (!array_key_exists('modifiedtime', $this->mapping_inverse)) {
				if (empty($this->fields_auto_create[$crmTable]['modifiedtime'])) {
					$this->fields_auto_create[$crmTable]['modifiedtime'] = $this->time_start;
				}
				if (empty($this->fields_auto_update[$crmTable]['modifiedtime'])) {
					$this->fields_auto_update[$crmTable]['modifiedtime'] = $this->time_start;
				}
			}
			$this->fields_auto_create[$crmTable]['setype'] = $this->module;
		}
	}
	
	// get the table, column and uitype for the specified field from the database
	// fieldname can be an array, in that case, all matching fields are returned
	// an array of fields is alwasy returned
	protected function getFieldColumn($module, $fieldname) {
		global $adb, $table_prefix;
		
		if (!is_array($fieldname)) $fieldname = array($fieldname);
		if (!is_array($this->field_cache)) $this->field_cache = array();
		if (!is_array($this->field_cache[$module])) $this->field_cache[$module] = array();
		
		$fieldsInCache = array_intersect_key($this->field_cache[$module], array_fill_keys($fieldname, 0));
		
		if (count($fieldsInCache) != count($fieldname)) {
			// some fields are missing, I need to retrieve them
			$fieldsToRetrieve = array_diff($fieldname, array_keys($fieldsInCache));
		
			// populate the cache
			if ($module == 'ProductRows') {
				$flds = $this->diutils->getMappableFields($this->importInfo);
				$flds = array_intersect_key($flds, array_flip($fieldsToRetrieve));
				$this->field_cache[$module] = array_merge($this->field_cache[$module], $flds);
				$fieldsInCache = array_merge($fieldsInCache, $flds);
			} else {
			
				// crmv@203591
				$hasProdInfo = false;
				if ($module == 'Products') {
					// check for attributes
					$prodInfoFields = [];
					foreach ($fieldsToRetrieve as $fieldname) {
						if (substr($fieldname, 0, 9) === 'prodattr_' || substr($fieldname, -8) === 'prodattr') {
							$hasProdInfo = true;
							$fieldsToRetrieve[] = 'confprodinfo';
							$prodInfoFields[] = $fieldname;
						}
					}
					$fieldsToRetrieve = array_unique($fieldsToRetrieve);
				}	
				// crmv@203591e
				
				$sql = "SELECT tablename,columnname,fieldname,uitype FROM {$table_prefix}_field WHERE fieldname IN (".generateQuestionMarks($fieldsToRetrieve).") AND tabid = ?";
				$params = $fieldsToRetrieve;
				$params[] = getTabid($module);
				$res = $adb->pquery($sql,$params);
				if ($res){
					while ($row = $adb->fetchByAssoc($res,-1,false)){
						$this->field_cache[$module][$row['fieldname']] = $row;
						$fieldsInCache[$row['fieldname']] = $row;
					}
				}
				
				// crmv@203591
				if ($hasProdInfo) {
					foreach ($prodInfoFields as $prodAttrField) {
						$prodAttr = [
							'tablename' => $this->field_cache[$module]['confprodinfo']['tablename'],
							'columnname' => $prodAttrField,
							'fieldname' => $prodAttrField,
							'uitype' => 1,
						];
						$this->field_cache[$module][$prodAttrField] = $prodAttr;
						$fieldsInCache[$prodAttrField] = $prodAttr;
					}
				}
				// crmv@203591e
				
			}
		}
		
		return $fieldsInCache;
	}
	
	public function getFieldsDesc() {
		$fields = $this->getFieldColumn($this->module, array_values($this->mapping));
		foreach ($fields as $fieldname=>$row) {
			$this->fields[$row['tablename']][] = $row['columnname'];
			$this->fields_name[$row['tablename']][$row['columnname']] = $row['fieldname']; // removed strtolower
			// TODO: sistema un po' questa roba
			switch($row['uitype']){
				case 99: //password
					$this->fields_functions[$row['fieldname']] = 'set_password';
					break;
				case 5: //data
					$this->fields_functions[$row['fieldname']] = 'get_date_short_value';
					break;
				case 117: //valuta
					$this->fields_functions[$row['fieldname']] = 'get_valuta';
					break;
				/*
				case 56: //checkbox
					$this->fields_functions[$row['fieldname']] = 'get_checkbox_value';
					break;
				case 53: //assegnatario
					$this->fields_functions[$row['fieldname']] = 'get_assigned_user';
					break;
				case 70: //data e ora
					$this->fields_functions[$row['fieldname']] = 'get_date_value';
					break;
				case 10: //uitype10 field
					$this->fields_functions[$row['fieldname']] = 'get_ui10_value';
					break;
				*/
				default:
					break;	
			}
		}
		$this->getSequenceField();
		
	}
	
	// catches only fatal errors
	static function errorHandler($logger = null) { // crmv@155585
		$error = error_get_last();
		$catchTypes = array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);

		// NOTICE: in case of die() or exit(), it's not possible to detect if there was an error or not, so the next code won't be executed
		if ($logger && $error !== null && in_array($error['type'], $catchTypes)) {
			$logger->fatal('A fatal error was encountered during the execution: '.print_r($error, true));
		}
	}
	
	public function run() {
		$this->log->info('Starting import...', true);
		
		// catch fatal errors
		register_shutdown_function(array($this, 'errorHandler'), $this->log);
		
		if ($this->config['autoclean_log_files'] > 0) $this->autocleanLogFiles();
		
		$r = $this->checkTables();
		if (!$r) return $r;
		
		$r = $this->checkDirectory();
		if (!$r) return $r;
		
		$r = $this->checkSource();
		if (!$r) return $r;
		
		if ($this->config['auto_create_index']) {
			$r = $this->checkKeyIndexes();
		}
		
		$this->pass = 1;
		
		// for statistics
		$this->log->tic('stat_main');
		$this->log->tic('stat_read');
		
		$this->importLog('IMPORT_START', 'Import started');

		// impersonate the import user
		if (!empty($this->config['import_user'])) {
			$r = $this->impersonateUser($this->config['import_user']);
			if (!$r) return $this->log->fatal("Unable to switch to user {$this->config['import_user']}");
		}

		// add special fields for the import
		$this->addAutoFields();
		
		// check for same-module referencies
		$this->checkSelfReferencies();
		
		$r = $this->preprocess();
		if (!$r) {
			if (!empty($this->config['import_user'])) $this->restoreUser();
			$this->log->info("Preprocess method returned false, so the import has been aborted");
			return true;
		}
		
		// create the files
		if ($this->config['autoclean_import_files'] > 0) $this->autocleanFiles();
		$this->make_create_files();

		// read the rows
		while ($row = $this->getNextRow()) {
			// apply early transformations
			$row = $this->preprocessRow($row);
			
			// validate it
			$valid = $this->validateRow($row);
			if (!$valid) {
				$this->log->debug("Row #{$this->row_retrieved} has been discarded");
				$this->row_discarded++;
				continue;
			}
			
			$row = $this->applyRowTransformations($row);

			// apply other transformations after the formulas
			$row = $this->postprocessRow($row);

			// populate the files
			$recordid = null;
			$exists = $this->checkExistance($row, $recordid);
			if ($exists) {
				$this->update($row,$recordid);
			} else {
				$this->create($row);
			}
		}
		
		if ($this->module == 'ProductRows') {
			// we have to update the last entity
			$this->updateInventoryTotals();
		}

		// close the files
		$this->close_files();
		
		// check again for abort
		if ($this->checkImportAbort()) {
			$this->row_retrieved = 0;
			$this->entity_created = 0;
			$this->entity_updated = 0;
			$this->entity_deleted = 0;
			$this->log->warn('Import has been aborted upon user request');
		}
		
		$this->importLog('IMPORT_MIDDLE', 'Import files populated');
		
		$this->time_readsrc = $this->log->tac('stat_read');
		$this->log->tic('stat_write');
		
		// now insert them in the vte
		if ($this->row_retrieved > 0) {
			$r = $this->execute();
			if (!$r) $this->log->error("Errors during the import");
		} else {
			$this->log->info('No rows for import');
		}
		
		// delete the files
		if (!$this->config['keep_import_files']) $this->delete_files();
		
		// execute the second pass, if needed
		if ($this->needSecondPass()) {
			$r = $this->processSecondPass();
			if (!$r) $this->log->error("Errors during the second pass");
		}
		
		// call the postprocess method
		$r = $this->postprocess();
		
		// restore the old user
		if (!empty($this->config['import_user'])) $this->restoreUser();
		
		$this->time_saverecords = $this->log->tac('stat_write');
		$this->time_total = $this->log->tac('stat_main');
		
		$logData = array(
			'records_created' => count($this->entity_created),
			'records_updated' => count($this->entity_updated),
			'records_deleted' => count($this->entity_deleted),
			'records_2ndpass' => count($this->entity_2ndpass),
		);
		$this->importLog('IMPORT_END', 'Import finished', $logData);
		
		$this->log->info('Import completed in {tac}');
		if ($this->config['log_statistics']) $this->logStatistics();
		
		CronManager::requestCronTermination(); // crmv@194059
		
		return true;
	}
	
	public function needSecondPass() {
		return (!empty($this->selfReferencies));
	}
	
	public function processSecondPass() {
		// only the self referencies are used for a second pass
		
		$this->pass++;
		$this->log->info('Starting second pass');
		
		$this->resetRowPointer();
			
		// read the rows
		while ($row = $this->getNextRow()) {
			
			// apply early transformations
			$row = $this->preprocessRow($row);
			
			// validate it
			$valid = $this->validateRow($row);
			if (!$valid) {
				$this->log->debug("Row #{$this->row_retrieved} has been discarded");
				continue;
			}
			
			$row = $this->applyRowTransformations($row);
			
			// apply other transformations after the formulas
			$row = $this->postprocessRow($row);
			
			$r = $this->updateSelfReferencies($row);
			if (!$r) {
				return $this->log->error('Error during update of same-module reference fields');
			}
		}
			
		$this->log->info('Second pass terminated');
		return true;
	}
	
	public function updateSelfReferencies($row) {
		global $adb, $table_prefix;
		
		$recordid = null;
		$exists = $this->checkExistance($row, $recordid);
		
		if (!$exists) {
			$name = $row[$this->external_code];
			$this->log->warning("The record '$name' was not found during the second pass, skipped.");
		} else {
			// get or generate the queries
			$qlist = $this->selfRefUpdateQueries;
			if (empty($qlist)) {
				$this->generateSelfRefUpdateQueries();
				$qlist = $this->selfRefUpdateQueries;
				if (empty($qlist)) {
					$this->log->error('The query to update the same-module reference fields couldn\'t be generated');
					return false;
				}
			}
			
			// execute them!
			foreach ($qlist as $qinfo) {
				$sql = $qinfo['sql'];
				$params = array();
				// intersect and sort
				foreach ($qinfo['columns'] as $col) {
					if ($col == 'RECORDID') {
						$params[] = $recordid;
					} elseif (array_key_exists($col, $row)) {
						$params[] = $row[$col];
					} 
				}
				if ($sql && count($params) >= 2) {
					$res = $adb->pquery($sql, $params);
					if (!$res) {
						$this->log->error('Query error while updating same-module reference field');
						return false;
					} else {
						$this->entity_2ndpass[$recordid] = $recordid;
					}
				}
				
			}
		}
		
		return true;
	}
	
	// check if there are reference fields in the mapping pointing to the
	// same module we are importing. In this case, these fields have to
	// be imported with a second pass, when all the other external keys
	// have been saved
	protected function checkSelfReferencies() {
		$map = $this->importInfo['mapping']['fields'];
		if (is_array($map)) {
			foreach ($map as $field) {
				$ref = $field['reference'];
				if (!empty($ref)) {
					list($refmod, $refField) = explode(':', $ref);
					if ($refmod == $this->module) {
						$this->selfReferencies[] = $field;
					}
				}
			}
		}
		if (count($this->selfReferencies) > 0) {
			$this->log->info('Found same-module reference fields, there will be a second pass');
		}
	}
	
	public function checkKeyIndexes() {
		$map = $this->importInfo['mapping']['fields'];
		if (is_array($map)) {
			foreach ($map as $field) {
				$ref = $field['reference'];
				if (!empty($ref)) {
					list($refmod, $refField) = explode(':', $ref);
					$finfo = $this->getFieldColumn($refmod, $refField);
					if ($finfo[$refField]['tablename'] && $finfo[$refField]['columnname']) {
						// check for indexes on that column
						$has = $this->tableHasIndexColumn($finfo[$refField]['tablename'], $finfo[$refField]['columnname']);
						if (!$has) $this->createSimpleIndex($finfo[$refField]['tablename'], $finfo[$refField]['columnname']);
					}
				}
			}
		}
		return true;
	}
	
	protected function createSimpleIndex($table, $column) {
		global $adb;
		
		$this->log->info("Creating an index for {$table}.{$column}...", true);
		$idxname = $table.'_'.$column.'_idx';
		$sql = $adb->datadict->CreateIndexSQL($idxname, $table, $column);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$this->log->info('Index created in {tac}');
	}
	
	// check if there's an index with it's first column as the specified one
	protected function tableHasIndexColumn($table, $column) {
		global $adb;
		$idxs = $adb->database->MetaIndexes($table);
		if (is_array($idxs)) {
			foreach ($idxs as $idxname => $idx) {
				if (is_array($idx['columns']) && $idx['columns'][0] == $column) return true;
			}
		}
		
		return false;
	}
	
	public function impersonateUser($user) {
		global $current_user, $adb, $table_prefix;
		if (empty($user)) return true;
		
		if (is_int($user) || is_numeric($user)) {
			$newid = intval($user);
			// check if it's a valid id
			$res = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE status = 'Active' AND deleted = 0 AND id = ?", array($user));
			if (!$res || $adb->num_rows($res) == 0) {
				return $this->log->error("No valid users found with ID $user");
			}
		} elseif (is_string($user)) {
			// find userid
			if ($user == 'ADMIN') {
				// find first admin
				$res = $adb->limitQuery("SELECT id FROM {$table_prefix}_users WHERE is_admin = 'on' AND status = 'Active' AND deleted = 0 ORDER BY id", 0, 1);
				if ($res && $adb->num_rows($res) > 0) {
					$newid = $adb->query_result_no_html($res, 0, 'id');
				} else {
					return $this->log->error("No valid admin users found");
				}
			} else {
				// match username
				$userFocus = CRMEntity::getInstance('Users');
				$newid = $userFocus->retrieve_user_id($user);
			}
			if (empty($newid)) {
				return $this->log->error("No users found with name $user");
			}
		}
		
		if ($newid > 0) {
			$this->oldUser = $current_user;
			$current_user = CRMEntity::getInstance('Users');
			$current_user->id = $newid;
			$current_user->retrieveCurrentUserInfoFromFile($newid);
			$this->log->debug("User switched to {$current_user->column_fields['user_name']}");
		}
		
		return true;
	}
	
	public function restoreUser() {
		global $current_user;
		$current_user = $this->oldUser;
		return true;
	}
	
	public function checkDirectory() {
		$this->log->debug('Checking directories');
		if (!is_dir($this->erpdir)) {
			return $this->log->fatal("Directory {$this->erpdir} doesn't exist, please provide the correct path");
		}
		if (!is_writable($this->erpdir)) {
			return $this->log->fatal("Directory {$this->erpdir} is not writable, please check permissions");
		}
		
		if (!is_dir($this->sqldir)) {
			mkdir($this->sqldir, 0755, true);
		}
		if (!is_dir($this->sqldir) || !is_writable($this->sqldir)) {
			return $this->log->fatal("Directory {$this->sqldir} is not writable, please check permissions");
		}
		
		if (!is_dir($this->logdir)) {
			mkdir($this->logdir, 0755, true);
		}
		if (!is_dir($this->logdir) || !is_writable($this->logdir)) {
			return $this->log->fatal("Directory {$this->logdir} is not writable, please check permissions");
		}
		
		return true;
	}
	
	public function checkTables() {
		global $adb;
		$this->log->debug('Checking tables');
		if(!Vtecrm_Utils::CheckTable($this->logTable)) {
			$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$this->logTable.'">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="id" type="I" size="19">
				    	<KEY/>
    				</field>
				    <field name="importid" type="I" size="19">
						<NOTNULL/>
				    </field>
				    <field name="module" type="C" size="63">
						<NOTNULL/>
					</field>
				    <field name="logdate" type="T">
						<NOTNULL/>
				    	<DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
				    <field name="action" type="C" size="63">
						<NOTNULL/>
					</field>
					<field name="logtext" type="C" size="255"/>
				    <field name="logdata" type="C" size="255"/>
				    <index name="importid_idx">
						<col>importid</col>
				    </index>
				    <index name="logdate_idx">
						<col>logdate</col>
				    </index>
				  </table>
				</schema>';
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
		}
		return true;
	}
	
	public function autocleanFiles() {
		$days = intval($this->config['autoclean_import_files']);
		if ($days > 0 && is_dir($this->sqldir)) {
			$this->log->debug('Starting autocleaning of old files');
			$list = glob($this->sqldir.'/*.{sql,csv}', GLOB_BRACE);
			$deleted = 0;
			$now = time();
			if (is_array($list)) {
				foreach ($list as $f) {
					$ftime = filemtime($f);
					if ($now - $ftime > ($days*3600*24)) {
						if (unlink($f)) ++$deleted;
					}
				}
			}
			$this->log->debug("Autoclean removed $deleted old files");
		}
	}
	
	public function autocleanLogFiles() {
		$days = intval($this->config['autoclean_log_files']);
		if ($days > 0 && is_dir($this->logdir)) {
			$this->log->debug('Starting autocleaning of old log files');
			$list = glob($this->logdir.'/*.log') ?: array();
			$list = array_merge($list, glob($this->logdir.'/*.log.*'));
			$deleted = 0;
			$now = time();
			if (is_array($list)) {
				foreach ($list as $f) {
					$ftime = filemtime($f);
					if ($now - $ftime > ($days*3600*24)) {
						if (unlink($f)) ++$deleted;
					}
				}
			}
			$this->log->debug("Autoclean removed $deleted old log files");
		}
	}
	
	public function importLog($action, $text = "", $data = null) {
		global $adb;
		$now = date('Y-m-d H:i:s');
		if (empty($action)) $action = 'UNKNOWN';
		
		$id = $adb->getUniqueID($this->logTable);
		$params = array(
			'id'=>$id, 
			'importid' => $this->importId,
			'module' => $this->module, 
			'logdate' => $now, 
			'action' => $action
		);
		if (!empty($text)) $params['logtext'] = $text;
		if (!empty($data)) $params['logdata'] = Zend_Json::encode($data);
		
		$q = "INSERT INTO {$this->logTable} (".implode(',', array_keys($params)).") VALUES(".generateQuestionMarks($params).")";
		$res = $adb->pquery($q, $params);
	}
	
	public function checkSource() {
		$this->log->debug('Checking source availability');
		
		$srcinfo = $this->importInfo['srcinfo'];
		$type = $srcinfo['dimport_sourcetype'];
		
		if ($type == 'database') {
			$db = $this->connectToDb($srcinfo);
			if (!$db) return $this->log->fatal("No database connection available");
		} elseif ($type == 'csv') {
			$file = $srcinfo['dimport_csvpath'];
			$file = $this->diutils->getOneCSVFile($file);
			if (!$file) return $this->log->fatal("No CSV file found for $file");
			
			$csvFolder = $this->diutils->import_folder;
			if (substr($csvFolder, -1) != '/') $csvFolder .= '/';
			$path = $csvFolder.$file;
			if (!is_readable($path)) return $this->log->fatal("The CSV file $path is not readable");
			if (filesize($path) == 0) return $this->log->fatal("The CSV file $path is empty");
		} else {
			return $this->log->fatal("Source is of unknown type: $type");
		}
		
		return true;
	}
	
	protected function connectToDb($srcinfo) {
	
		if ($this->extdb && $this->extdb->database->isConnected()) return $this->extdb;
		
		$dbtype = $srcinfo['dimport_dbtype'];
		$dbhost = trim($srcinfo['dimport_dbhost']);
		$dbport = trim($srcinfo['dimport_dbport']);
		$dbuser = trim($srcinfo['dimport_dbuser']);
		$dbpass = $srcinfo['dimport_dbpass'];
		$dbname = trim($srcinfo['dimport_dbname']);
		$host_separator = ':'; // crmv@166852

		// remove port for mysqli (PearDatabase doesn't support it)
		if ($dbtype == 'mysqli' && $dbport == '3306') $dbport = ''; // crmv@87579
		if (($dbtype == 'mssqlnative' || $dbtype == 'mssql') && $dbport == '1433') $dbport = ''; // crmv@155585 crmv@178746
		if ($dbtype == 'mssqlnative') $host_separator = ','; // crmv@166852

		//crmv@83676
		if (strpos($dbhost,'\\') !== false) {
			list($dbhost_ip,$instance) = explode('\\',$dbhost);
			$host = $dbhost_ip.(empty($dbport) ? '' : $host_separator.$dbport).'\\'.$instance; // crmv@166852
		} else {
		//crmv@83676e
			$host = $dbhost.(empty($dbport) ? '' : $host_separator.$dbport); // crmv@166852
		}
		
		// crmv@193619
		$ssl_connection = false;
		if (substr($host, 0, 6) === 'ssl://') {
			$ssl_connection = true;
			$host = substr($host,6);
		}
		// crmv@193619e
		
		$this->log->debug("Connecting to database ($dbtype) $host");
		
		$this->extdb = new PearDatabase($dbtype, $host, $dbname, $dbuser, $dbpass);
		$this->extdb->usePersistent = false;		// force a new connection, in case the host, user and pwd is the same
		$this->extdb->setDieOnError(false);		// disable die on error
		$this->extdb->setExceptOnError(true);	// but enable exception on error
		if ($ssl_connection) $this->extdb->setSSLconnection(); // crmv@193619
		@$this->extdb->connect();
		if (!$this->extdb->database->isConnected()) {
			$this->extdb = null;
			return $this->log->error("Unable to connect to database");
		}
		$this->log->debug('Connection successful');

		// crmv@109628
		// detect the external charset (only for mssql)
		$charset = $this->getDbCharset();
		if ($charset) $this->extdb_charset = $charset;
		// crmv@109628e
		
		return $this->extdb;
	}

	// crmv@109628
	protected function getDbCharset() {
		$charset = 'UTF8';
		if ($this->extdb->isMssql()) {
			$res = $this->extdb->pquery("SELECT DATABASEPROPERTYEX(?, 'Collation') as sql_collation", array($this->extdb->dbName));
			if ($res) {
				$collation = $this->extdb->query_result_no_html($res, 0, 'sql_collation'); // crmv@108648
				if (strpos($collation, 'Latin1') !== false) {
					$charset = 'Latin1';
				}
			}
		}
		// mysql doesn't need detection, since the mysql driver automatically converts the data
		return $charset;
	}
	// crmv@109628e
	
	protected function generateQuery() {
		if ($this->pass == 2) return $this->generateQuerySelfRef();
		
		$this->query = '';
		$table = $this->importInfo['srcinfo']['dimport_dbtable'];
		$query = $this->diutils->cleanImportQuery($this->importInfo['srcinfo']['dimport_dbquery']);
		if (!empty($table)) {
			// use the mapping to generate the query
			$select = array();
			$mapping = $this->importInfo['mapping'];
			foreach ($mapping['fields'] as $column => $map) {
				if ($map['field']) $select[] = $column;
			}
			$this->extdb->format_columns($select);
			$this->extdb->format_columns($table);
			$this->query = 'SELECT '.implode(',', $select)." FROM $table";
			
			// now add a orderby for products rows
			if ($this->module == 'ProductRows') {
				$orders = array();
				if (array_key_exists('inventoryid', $this->mapping_inverse)) {
					$orders[] = $this->mapping_inverse['inventoryid']." ASC";
				}
				if (array_key_exists('productid', $this->mapping_inverse)) {
					$orders[] = $this->mapping_inverse['productid']." ASC";
				}
				if (count($orders) > 0) {
					$this->query .= " ORDER BY ".implode(', ', $orders);
				}
			}
		} elseif (!empty($query)) {
			$this->query = $query;
		}
		$this->log->debug('Query being executed: '.$this->query);
		return true;
	}
	
	protected function generateQuerySelfRef() {
		$this->query = '';
		$table = $this->importInfo['srcinfo']['dimport_dbtable'];
		$query = $this->diutils->cleanImportQuery($this->importInfo['srcinfo']['dimport_dbquery']);
		if (!empty($table)) {
			// use the mapping to generate the query
			$select = array($this->external_code);
			$mapping = $this->importInfo['mapping'];
			foreach ($mapping['fields'] as $column => $map) {
				if ($map['field'] && $map['reference']) {
					list ($refmod, $reffield) = explode(':', $map['reference']);
					if ($refmod == $this->module) {
						$select[] = $column;
					}
				}
			}
			$this->extdb->format_columns($select);
			$this->extdb->format_columns($table);
			$this->query = 'SELECT '.implode(',', $select)." FROM $table";
			
		} elseif (!empty($query)) {
			$this->query = $query;
		}
		$this->log->debug('Query being executed: '.$this->query);
		return true;
	}
	
	protected function generateSelfRefUpdateQueries() {
		global $adb, $table_prefix;
		
		$this->selfRefUpdateQueries = array();
		
		$updateFields = array();
		$mapping = $this->importInfo['mapping'];
		if (is_array($mapping['fields'])) {
			foreach ($mapping['fields'] as $column => $map) {
				if ($map['field'] && $map['reference']) {
					list ($refmod, $reffield) = explode(':', $map['reference']);
					if ($refmod == $this->module) {
						$updateFields[$column] = $map['field'];
					}
				}
			}
		}
		
		$tables = array();
		$scols = array();
		foreach ($updateFields as $scol => $fieldname) {
			$finfo = $this->getFieldColumn($this->module, $fieldname);
			$ftable = $finfo[$fieldname]['tablename'];
			$fcol = $finfo[$fieldname]['columnname'];
			if ($ftable && $fcol) {
				$tables[$ftable][] = $fcol;
				$scols[$ftable][] = $scol;
			}
		}
		
		// now find the indexes
		$queries = array();
		foreach ($tables as $ftable => $columns) {
			if (!in_array($ftable, $this->object->tab_name) || !array_key_exists($ftable, $this->object->tab_name_index)) {
				$this->log->warn("Unable to find the table $ftable in the module class");
				continue;
			}
			
			$tableidx = $this->object->tab_name_index[$ftable];
			$colsql = array();
			foreach ($columns as $col) {
				$colsql[] = "$col = ?";
			}
			if (count($colsql) > 0) {
				$sql = "UPDATE {$ftable} SET ".implode(', ', $colsql)." WHERE {$tableidx} = ?";
				$cols = $scols[$ftable];
				$cols[] = 'RECORDID';
				$queries[] = array('sql' => $sql, 'columns' => $cols, 'tableidx' => $tableidx);
			}
		}
		
		$this->selfRefUpdateQueries = $queries;
		
		return true;
	}
	
	protected function openNextCsv() {
		if ($this->fhandle) fclose($this->fhandle);
		$this->fhandle = null;
		
		if (empty($this->csvFiles)) {
			// i have to retrieve the list of files
			$file = $this->importInfo['srcinfo']['dimport_csvpath'];
			$this->csvFiles = $this->diutils->getAllCSVFiles($file);
			if (empty($this->csvFiles)) return $this->log->error('No CSV files found');
			$this->currentFileIdx = -1;
		}
		
		// check if last one
		if ($this->currentFileIdx >= count($this->csvFiles)-1) return true;
		
		$newfile = $this->csvFiles[++$this->currentFileIdx];
		if (is_readable($newfile) && filesize($newfile) > 0) {
			FSUtils::removeBOM($newfile); // crmv@138011
			$this->fhandle = fopen($newfile, 'r');
			if (!$this->fhandle) {
				$this->log->error("Unable to open $newfile, skipped");
				return $this->openNextCsv();
			} else {
				$this->log->info("Opened CSV file $newfile");
			}
		} else {
			$this->log->error("The file $newfile is not readable or empty, skipped");
			return $this->openNextCsv();
		}
		
		return true;
	}
	
	protected function resetRowPointer() {
		$this->qresult = null;
		if ($this->fhandle) fclose($this->fhandle);
		$this->csv_rows = 0;
		$this->currentFileIdx = -1;
	}
	
	protected function getNextRow() {
		$type = $this->importInfo['srcinfo']['dimport_sourcetype'];
		
		$row = false;
		if ($type == 'database') {
			if (!$this->qresult) {
				$r = $this->generateQuery();
				if (!$r) return $r;
				
				// execute the query
				try {
					$this->qresult = $this->extdb->query($this->query);
				} catch (Exception $e) {
					return $this->log->error('Error while executing the query: '.$e->getMessage());
				}
				if (!$this->qresult) $this->log->error('There was an error executing the query');
				$this->log->info('Query returned '.$this->extdb->num_rows($this->qresult).' rows');
			}
			$row = $this->extdb->FetchByAssoc($this->qresult, -1, false);
			if (!empty($row) && $this->config['trim_db_values']) {
				$row = array_map('trim', $row);
			}
		} elseif ($type == 'csv') {
			if (!$this->fhandle) {
				// open next csv file
				$this->csv_rows = 0;
				$r = $this->openNextCsv();
				if (!$r) return $r;
			}
			// now if the file has been opened
			if ($this->fhandle) {
				$row = $this->getNextCsvRow($this->fhandle);
				// close the handle at file end
				if (empty($row)) {
					$this->log->info('Read '.$this->csv_rows.' rows from CSV file');
					fclose($this->fhandle);
					$this->fhandle = null;
					// re-iterate over the next file
					return $this->getNextRow();
				} else {
					$this->csv_rows++;
				}
			}
		}
		
		// lgo memory usage, before the increment, so log also at the beginning
		$logEvery = intval($this->config['log_memory_rows']);
		if ($logEvery > 0 && $this->row_retrieved % $logEvery == 0) {
			$this->logMemoryStats();
		}
		
		$abortEvery = intval($this->config['abort_interval']);
		if ($abortEvery > 0 && (time() - $this->last_abort_check) > $abortEvery) {
			$this->last_abort_check = time();
			if ($this->checkImportAbort()) {
				if ($this->pass == 1) {
					$this->entity_created = 0;
					$this->entity_updated = 0;
					$this->entity_deleted = 0;
					$this->row_retrieved = 0;
				}
				$this->entity_2ndpass = 0;
				$this->log->warn('Import has been aborted upon user request');
				return null;
			}
		}
		
		// increment row count
		if ($row) $this->row_retrieved++;

		return $row;
	}
	
	protected function checkImportAbort() {
		return $this->diutils->isAborted($this->importId);
	}
	
	protected function getNextCsvRow($fh) {
		global $current_user;
		
		$row = null;
		if (!$fh) return $row;
		
		if (!$this->csvreader) {
			$cfg = array(
				'fhandle' => $fh,
				'delimiter' => $this->importInfo['srcinfo']['dimport_csvdelimiter'],
				'file_encoding' => $this->importInfo['srcinfo']['dimport_csvencoding'],
				'has_header' => !!$this->importInfo['srcinfo']['dimport_csvhasheader'],
				'trim_values' => $this->config['trim_csv_values'],
			);
			$this->csvreader = new DataImporterErpCSVReader($current_user, $cfg);
		}
		$this->csvreader->setFHandle($fh);
		$row = $this->csvreader->readRow();
		
		return $row;
	}
	
	protected function logMemoryStats() {
		$mb = $this->formatBytes(memory_get_usage());
		$mpb = $this->formatBytes(memory_get_peak_usage());
		$str = "Memory usage: $mb (peak $mpb)";
		$this->log->debug($str);
	}
	
	protected function logStatistics() {
		
		$totrecords = count($this->entity_created) + count($this->entity_updated) + count($this->entity_deleted);
		$time = $this->time_total;
		if ($totrecords > 0 && $time > 0) {
			$speed = strval(round($totrecords/$time)).' record/s';
		} else {
			$speed = 'N/A';
		}
	
		$stats = "Statistics:\n\n";
		$stats .= "Total rows read: ".$this->row_retrieved."\n";
		$stats .= "Rows discarded: ".$this->row_discarded."\n";
		$stats .= "Records created: ".count($this->entity_created)."\n";
		$stats .= "Records updated: ".count($this->entity_updated)."\n";
		$stats .= "Records deleted: ".count($this->entity_deleted)."\n";
		if ($this->pass > 1) {
			$stats .= "Records affected by second pass: ".count($this->entity_2ndpass)."\n";
		}
		$stats .= "Time to read source: ".$this->log->formatTac($this->time_readsrc)."\n";
		$stats .= "Time to insert records: ".$this->log->formatTac($this->time_saverecords)."\n";
		$stats .= "Total time: ".$this->log->formatTac($this->time_total)."\n";
		$stats .= "Average speed: $speed\n";
		$this->log->info($stats);
	}
	
	private function formatBytes($size, $precision = 2) {
		$base = log($size, 1024);
		$suffixes = array('', 'K', 'M', 'G', 'T');   

		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}
	
	// called before the beginning of the import, if it returns false, the import is not executed
	public function preprocess() {
		// this method can be overloaded
		$this->log->debug('Executing preprocess method');
		return true;
	}
	
	// called at the end of the import
	public function postprocess() {
		// this method can be overloaded
		$this->log->debug('Executing postprocess method');

		$this->fixCurrencyRelTable(); //crmv@113210
		$this->updateEntityNameCache(); //crmv@144125
		
		// crmv@203591
		if ($this->prodAttrCreated || $this->module == 'ModLightProdAttr') {
			$confProdFocus = CRMEntity::getInstance('ConfProducts');
			$confProdFocus->updateFieldIds();
		} elseif ($this->module == 'Products' && count($this->prodAttrValues) > 0) {
			$this->insertProdAttrValues();
		}
		// crmv@203591e
		
		//crmv@136410
		if ($this->module != 'ProductRows') {
			if ($this->launch_workflows) $this->launch_workflows();
			if ($this->launch_processes) $this->launch_processes();
		}
		//crmv@136410e
		
		return true;
	}

	//crmv@113210
	public function fixCurrencyRelTable() {
		global $adb, $table_prefix;
		
		// TODO: do the real price conversion

		if (isProductModule($this->module)) {
			$modTable = $this->object->table_name;
			$modIndex = $this->object->table_index;
			$sql = 
				"SELECT 
					{$table_prefix}_crmentity.crmid, {$modTable}.unit_price, {$modTable}.currency_id
				FROM
				  {$modTable}
				  INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$modTable}.{$modIndex}
				  LEFT JOIN {$table_prefix}_productcurrencyrel ON {$table_prefix}_productcurrencyrel.productid = {$modTable}.{$modIndex}
				WHERE {$table_prefix}_crmentity.deleted = 0 AND {$table_prefix}_productcurrencyrel.productid IS NULL";
			$res = $adb->query($sql);
			while($row = $adb->fetchByAssoc($res, -1, false)){
				$sql_update = "INSERT INTO {$table_prefix}_productcurrencyrel (productid,currencyid,converted_price,actual_price) VALUES (?,?,?,?)";
				$params_update = array($row['crmid'],$row['currency_id'],$row['unit_price'],$row['unit_price']);
				$adb->pquery($sql_update,$params_update);
			}
			
			// crmv@185823
			// and now fix updated products
			if (is_array($this->entity_updated) && count($this->entity_updated) > 0) {
				$chunkSize = 100;
				$chunks = array_chunk($this->entity_updated, $chunkSize);
				foreach ($chunks as $chunk) {
					if ($adb->isMysql()) {
						$adb->pquery(
							"UPDATE {$table_prefix}_productcurrencyrel pcr
							INNER JOIN $modTable p ON p.{$modIndex} = pcr.productid
							SET pcr.currencyid = p.currency_id, converted_price = p.unit_price, actual_price = p.unit_price
							WHERE pcr.productid IN (".generateQuestionMarks($chunk).")",
							$chunk
						);
					} else {
						$res = $adb->pquery(
							"SELECT {$modIndex} as crmid, unit_price, currency_id
							FROM {$modTable}
							WHERE {$modIndex} IN (".generateQuestionMarks($chunk).")",
							$chunk
						);
						while ($row = $adb->fetchByAssoc($res, -1, false)) {
							$sql = "UPDATE {$table_prefix}_productcurrencyrel SET currencyid = ?, converted_price = ?, actual_price = ? WHERE productid = ?";
							$params = array($row['currency_id'],$row['unit_price'],$row['unit_price'], $row['crmid']);
							$adb->pquery($sql,$params);
						}
					}
				}
			}
			// crmv@185823
		}

	}
	//crmv@113210e
	
	//crmv@144125
	public function updateEntityNameCache() {
		$ENU = EntityNameUtils::getInstance();
		
		// crmv@177787
		if (is_array($this->entity_created) && count($this->entity_created) > 0) {
			$ENU->rebuildForRecords($this->module, $this->entity_created);
		}
		if (is_array($this->entity_updated) && count($this->entity_updated) > 0) {
			$ENU->rebuildForRecords($this->module, $this->entity_updated);
		}
		// crmv@177787e
		// deletion not supported yet
		//$ENU->removeCachedNames($this->module, $this->entity_deleted);
	}
	//crmv@144125e
	
	//crmv@136410
	protected function launch_workflows(){
		require_once('include/events/SqlResultIterator.inc');
		require_once('modules/com_workflow/VTWorkflowManager.inc');//crmv@207901
		require_once('modules/com_workflow/VTTaskManager.inc');//crmv@207901
		require_once('modules/com_workflow/VTTaskQueue.inc');//crmv@207901
		require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
		require_once('include/Webservices/Utils.php');
		require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
		require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
		require_once("include/Webservices/DataTransform.php");
		require_once("include/Webservices/WebServiceError.php");
		require_once('include/Webservices/ModuleTypes.php');
		require_once('include/Webservices/Retrieve.php');
		require_once('include/Webservices/Update.php');
		require_once('include/Webservices/WebserviceField.php');
		require_once('include/Webservices/EntityMeta.php');
		require_once('include/Webservices/VtenextWebserviceObject.php');//crmv@207871
		require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
		require_once('modules/com_workflow/VTEventHandler.inc');//crmv@207901
		
		$wfh = new VTWorkflowEventHandler();
		
		// now save the workflows with a special optimized method
		// note: workflow with the clause ("has changed into") won't work.
		if (is_array($this->entity_created) && count($this->entity_created) > 0) {
			$wfh->massWorkflows('create', $this->module, $this->entity_created);
		}
		if (is_array($this->entity_updated) && count($this->entity_updated) > 0) {
			$wfh->massWorkflows('update', $this->module, $this->entity_updated);
		}
		
	}
	
	protected function launch_processes() {
		global $adb; //crmv@185058
		$entities = array_merge($this->entity_created,$this->entity_updated);
		if (!empty($entities)) {
			foreach($entities as $id) {
				$focus = CRMEntity::getInstance($this->module);
				$focus->retrieve_entity_info_no_html($id,$this->module);
				(in_array($id,$this->entity_created)) ?  $focus->mode = '' : $focus->mode = 'edit';
				
				require_once("include/events/include.inc");
				require_once("modules/Settings/ProcessMaker/ProcessMakerHandler.php");
				$em = new VTEventsManager($adb);
				// Initialize Event trigger cache
				$em->initTriggerCache();
				$entityData  = VTEntityData::fromCRMEntity($focus);
				if (in_array($id,$this->entity_created)) $entityData->setNew(true);
				//crmv@185058
				require_once('data/VTEntityDelta.php');
				$entityDelta = new VTEntityDelta();
				$entityDelta->handleEvent('vte.entity.aftersave',$entityData);//crmv@207852
				//crmv@185058e
				$processMakerHandler = new ProcessMakerHandler();
				if (!$this->enable_process_modify_action && in_array($id,$this->entity_updated)) $processMakerHandler->real_save = false;
				$processMakerHandler->handleEvent('vte.entity.aftersave.processes', $entityData); // crmv@177677 crmv@207852
			}
		}
	}
	//crmv@136410e
	
	// apply transformations right after the row has been read from the source
	public function preprocessRow($row) {
		// this method can be overloaded
		return $row;
	}
	
	public function validateRow(&$row) {
		$valid = true;
		$mapping = $this->importInfo['mapping'];
		$invalidCase = $this->config['on_invalid_cell'];
		foreach ($row as $column => &$value) {
		 	$map = $mapping['fields'][$column];
			if (!empty($map) && !empty($map['field'])) {
				$vc = $this->validateCell($column, $value, $map, $row);
				if (!$vc) {
					if ($invalidCase == 'skip') {
						// skip all
						$valid = false;
					} elseif ($invalidCase == 'blank') {
						// empty the value
						$value = '';
					} elseif ($invalidCase == 'ignore') {
						// ignore the error
					}
				}
			}
		}
		return $valid;
	}
	
	public function validateCell($colname, $value, $map, $row) {
		$valid = true;
		if (!empty($map['srcformat'])) {
			// do the validation
			$format = $this->allFormats[$map['srcformat']];
			$formatval = $map['srcformatval'];
			$formatval2 = $map['srcformatlist']; // crmv@117880
			if (empty($format)) {
				$this->log->warn("The format {$map['srcformat']} is unknown");
				return $valid;
			}
			// use filter_var
			if ($value !== '' && !empty($format['phpfilter'])) { // crmv@113215
				$r = filter_var($value, $format['phpfilter']['filter'], $format['phpfilter']['options']);
				if (!$r) {
					$this->log->debug("The value $value doesn't match the format for {$map['srcformat']}");
					return false;
				}
			}
			// use regexp
			if ($value !== '' && !empty($format['regex'])) { // crmv@113215
				// regex validation
				if (!preg_match($format['regex'], $value)) {
					$this->log->debug("The value $value doesn't match the format for {$map['srcformat']}");
					return false;
				}
			}
			// other cases
			switch ($map['srcformat']) {
				case 'BOOL_NULL_REGEX':
					// change the value
					$row[$colname] = (empty($value) ? 0 : 1);
					break;
				case 'DATE_TIME_REGEX':
					if ($value != '') {
						$date = DateTime::createFromFormat($formatval, $value);
						if ($date) {
							$row[$colname] = $date->format('Y-m-d H:i:s');
						} else {
							$this->log->debug("The date '$value' doesn't match the specified format format '$formatval'");
							return false;
						}
					}
					break;
				// crmv@117880
				case 'NUMBER_REGEX':
					if ($value !== '' && $formatval2 != '') {
						// check number format
						$newvalue = $this->checkNumbeFormat($value, $formatval2);
						if ($newvalue === false) {
							$this->log->debug("The number '$value' doesn't match the specified format format '$formatval2'");
							return false;
						} else {
							// change the format!
							$row[$colname] = $newvalue;
						}
					}
					break;
				// crmv@117880e
			}
		}
		return $valid;
	}

	// crmv@117880
	// check and convert the number using the specified format!
	public function checkNumbeFormat($number, $format) {

		list($ts,$ds) = explode(':', $format);
		$ts = str_replace(array('EMPTY', 'PERIOD', 'COMMA', 'SPACE', 'QUOTE'), array('', '.', ',', ' ',  "'"), $ts);
		$ds = str_replace(array('EMPTY', 'PERIOD', 'COMMA', 'SPACE', 'QUOTE'), array('', '.', ',', ' ',  "'"), $ds);
		
		// add a simple check, otherwise it might go through
		$cn = $number;
		if ($ts != '') $cn = str_replace($ts, '', $number);
		if ($ds !== '.' && strpos($cn, '.') !== false) return false;

		// instantiate a single class, used only for converting numbers
		if (!$this->iutils_check) {
			$this->iutils_check = InventoryUtils::getInstance();
		}
		$this->iutils_check->decimalSeparator = $ds;
		$this->iutils_check->thousandsSeparator = $ts;
		$this->iutils_check->invalidNumber = NaN;
			
		$number = $this->iutils_check->parseUserNumber($number);
		if ($number === NaN) return false;
		
		return $number;
	}
	// crmv@117880e
	
	public function applyRowTransformations($row) {
		$mapping = $this->importInfo['mapping'];
		foreach ($row as $column => &$value) {
		 	$map = $mapping['fields'][$column];
			if (!empty($map) && !empty($map['field'])) {
			
				// apply defaults
				$value = $this->applyDefaultToCell($column, $value, $map, $row);
				
				// crmv@203591
				// apply formats
				$value = $this->applyFormatToCell($column, $value, $map, $row);
				// crmv@203591e
			
				// apply formulas
				$value = $this->applyFormulaToCell($column, $value, $map, $row);
			
				// reverse lookup for uitype10 and reference fields
				$value = $this->applyReferenciesToCell($column, $value, $map, $row);
			}
		}
		return $row;
	}	
	
	public function applyFormulas($row) {
		$mapping = $this->importInfo['mapping'];
		foreach ($row as $column => &$value) {
		 	$map = $mapping['fields'][$column];
			if (!empty($map) && !empty($map['field'])) {
				$value = $this->applyFormulaToCell($column, $value, $map, $row);
			}
		}
		return $row;
	}
	
	public function applyFormulaToCell($colname, $value, $map, $row) {
		if (!empty($map['formula'])) {
			// do the validation
			$formula = $this->allFormulas[$map['formula']];
			if (empty($formula)) {
				$this->log->warn("The formula {$map['formula']} is unknown");
				return $value;
			}
			$formulaVal = $map['formulaval'];
			switch ($map['formula']) {
				case 'PREPEND':
					$value = $formulaVal.$value;
					break;
				case 'APPEND':
					$value .= $formulaVal;
					break;
				case 'ADD':
					$value += $formulaVal;
					break;
				case 'SUBTRACT':
					$value -= $formulaVal;
					break;
				case 'YEAR':
					// date is already in a valid format
					$value = substr($value, 0, 4);
					break;
				case 'YEARMONTH':
					// idem
					$value = substr($value, 0, 7);
					break;
			}
		}
		return $value;
	}
	
	public function applyDefaults($row) {
		$mapping = $this->importInfo['mapping'];
		foreach ($row as $column => &$value) {
		 	$map = $mapping['fields'][$column];
			if (!empty($map) && !empty($map['field'])) {
				$value = $this->applyDefaultToCell($column, $value, $map, $row);
			}
		}
		return $row;
	}
	
	public function applyDefaultToCell($colname, $value, $map, $row) {
		$default = $map['default'];
		if ($default !== '' && $value == '') {
			$value = $default;
		}
		
		return $value;
	}
	
	// crmv@203591
	public function applyFormatToCell($colname, $value, $map, $row) {
		$format = $map['srcformat'];
		$charMap = array(
			'SPACE' => ' ',
			'COMMA' => ',',
			'COLON' => ':',
			'SEMICOLON' => ';',
		);
		
		// for attributes, values is an array with names for keys and an array for values
		// if only names are requested, the values array is null
		if ($format === 'ATTRIBUTES_SEPARATOR' && $map['srcformatlist'] && $value != '') {
			$value = explode($charMap[$map['srcformatlist']], $value);
			$value = array_combine($value, array_fill(0, count($value), null));
		} elseif ($format === 'ATTRIBUTES_TABLE' && $map['srcformatlist'] && $value != '') {
			if ($map['srcformatlist'] == 'JSON') {
				$value = Zend_Json::decode($value);
			}
		}
		 
		return $value;
	}
	// crmv@203591e
	
	public function applyReferencies($row) {
		$mapping = $this->importInfo['mapping'];
		foreach ($row as $column => &$value) {
		 	$map = $mapping['fields'][$column];
			if (!empty($map) && !empty($map['field'])) {
				$value = $this->applyReferenciesToCell($column, $value, $map, $row);
			}
		}
		return $row;
	}
	
	public function applyReferenciesToCell($colname, $value, $map, $row) {
		global $adb, $table_prefix;
			// TODO: cache ids ?? 
		$ref = $map['reference'];
		if (!empty($ref) && $value != '') {
			if (!$this->cache['ref_sql'][$colname]) {
				// generates the query to do the lookup by external code
				list($refmod, $refField) = explode(':', $ref);
				
				// skip the id, it will be imported with the second pass
				if ($refmod == $this->module && $this->pass == 1) return 0;
				
				$focus = CRMEntity::getInstance($refmod);
				$finfo = $this->getFieldColumn($refmod, $refField);
				$ftable = $finfo[$refField]['tablename'];
				$fcol = $finfo[$refField]['columnname'];

				if (!in_array($ftable, $focus->tab_name) || !array_key_exists($ftable, $focus->tab_name_index)) {
					$this->log->warn("The link field $refmod::$refField is in a table not declared in module class ($ftable), value left untouched");
					return $value;
				}
				$tableidx = $focus->tab_name_index[$ftable];
				$mainJoin = "";
				$fieldJoin = "INNER JOIN {$ftable} ON {$ftable}.{$tableidx} = {$table_prefix}_crmentity.crmid";
				if ($ftable != $focus->table_name) {
					$mainJoin = "INNER JOIN {$focus->table_name} ON {$focus->table_name}.{$focus->table_index} = {$table_prefix}_crmentity.crmid";
				}
				
				$query = "SELECT {$table_prefix}_crmentity.crmid 
					FROM {$table_prefix}_crmentity
					$mainJoin
					$fieldJoin
					WHERE {$table_prefix}_crmentity.deleted = 0 AND {$ftable}.{$fcol} = ?";
				$this->cache['ref_sql'][$colname] = $query;
			}
			$sql = $this->cache['ref_sql'][$colname];
			
			if ($sql) {
				$res = $adb->pquery($sql, array($value));
				if ($res && $adb->num_rows($res) > 0) {
					$value = $adb->query_result_no_html($res, 0, 'crmid');
				}
			}
		}
		
		return $value;
	}
	
	// apply transformations after the formulas have been calculated and right before being saved in the vte
	public function postprocessRow($row) {
		// this method can be overloaded
		return $row;
	}
	
	public function notifyUser() {
		// not done year
	}
	
	public function checkExistance($row, &$recordid = null) {
		// if no external key defined, consider always the entity not existing
		// also for the product rows, the lines are always deleted and re-added
		if ($this->external_code == '' || empty($row) || $this->module == 'ProductRows') return false; // crmv@117237
		
		// TODO: what is this? array??
		// NOT USED NOW
		/*if (is_array($this->external_code)){
			$func = 'get_existing_entity_runtime_unique';
		} else{
			$func = 'get_existing_entity_runtime';
		}
		$recordid = $this->$func($row);
		*/
		
		$recordid = $this->get_existing_entity_runtime($row);
		return (!empty($recordid));
	}
	
	protected function writeToFile($fh, $str, $filename = '') {
		$r = fwrite($fh, $str);
		if ($r === false) {
			$this->log->warn("There was an error while writing to a file");
		}

		// this method is useful for debugging, to see what is being written to the files
		//echo basename($filename).':  '.$str;
	}
	
	
	// ---------------------------- STUFF from old classes file
	
	private function make_create_files() {
		global $table_prefix;
		$time = time();
		
		if ($this->module == 'ProductRows') {		
			$table = $table_prefix.'_inventoryproductrel';
			$this->sql_file_name_create[$table] = $this->sqldir."/".$this->module."_sql_create_".$table."_".$time.".csv";
			@unlink($this->sql_file_name_create[$table]);
			$this->sql_file_create[$table] = fopen($this->sql_file_name_create[$table] , 'w+');
		}
		foreach ($this->object->tab_name_index as $t=>$k) {
			//file creazione
			$this->sql_file_name_create[$t] = $this->sqldir."/".$this->module."_sql_create_".$t."_".$time.".csv";
			@unlink($this->sql_file_name_create[$t]);
			$this->sql_file_create[$t] = fopen($this->sql_file_name_create[$t] , 'w+');
			//file aggiornamento
			$this->sql_file_name_update[$t] = $this->sqldir."/".$this->module."_sql_update_".$t."_".$time.".sql";
			@unlink($this->sql_file_name_update[$t]);
			$this->sql_file_update[$t] = fopen($this->sql_file_name_update[$t] , 'w+');
		}
		@unlink($this->file_create);
		$this->file_create = $this->sqldir."/".$this->module."_sql_create_global_".$time.".sql";
	}
	
	private function close_files() {
		if (is_array($this->sql_file_create)) {
			foreach ($this->sql_file_create as $fl){
				fclose($fl);
			}
		}
		if (is_array($this->sql_file_update)) {
			foreach ($this->sql_file_update as $fl){
				fclose($fl);
			}
		}
	}
	
	private function delete_files() {
		if (is_array($this->sql_file_name_create)) {
			foreach ($this->sql_file_name_create as $fl){
				@unlink($fl);
			}
		}
		if (is_array($this->sql_file_name_update)) {
			foreach ($this->sql_file_name_update as $fl){
				@unlink($fl);
			}
		}
		@unlink($this->file_create);
		$this->log->debug('Temporary files removed');
	}
	
	protected function create($data) {
		global $adb,$table_prefix;
		
		// product block
		if ($this->module == 'ProductRows') return $this->createInventory($data);

		// crmentity
		$table = $table_prefix.'_crmentity';
		if (!in_array($table,$this->object->tab_name)) {
			$table = $this->object->tab_name[0];
		}
		$id = $adb->getUniqueID($table);
		
		$prodattr = $subprodattr = array(); // crmv@203591
		$create = $this->getcached_create_arr();
		if (!empty($this->mapping_entity)){
			foreach ($this->mapping_entity as $f){
				$f = strtolower($f);
				if (trim($data[$f]) != '')
					$create[$table][$this->mapping[$f]] = $data[$f];
			}
		}
		
		// other tables
		foreach ($this->fields as $table => $arr){
			foreach ($arr as $field){
				// crmv@203591
				if ($this->fields_functions[$this->fields_name[$table][$field]] != '' && method_exists($this,$this->fields_functions[$this->fields_name[$table][$field]])) {
					$readyData = $this->{$this->fields_functions[$this->fields_name[$table][$field]]}($this->fields_name[$table][$field],$data,$table,$field,'create');
				} else {
					$readyData = $data[$this->mapping_inverse[$this->fields_name[$table][$field]]];
				}
				if ($field == 'mlProdAttr') {
					$prodattr[$id] = $readyData;
					continue;
				} elseif (substr($field, 0, 9) === 'prodattr_') {
					$subprodattr[$field] = $readyData;
					continue;
				} elseif (substr($field, -8) === 'prodattr') {
					$subprodattr[$field] = $readyData;
					continue;
				}
				$create[$table][$field] = $readyData;
				// crmv@203591e
			}
			$create[$table][$this->object->tab_name_index[$table]] = $id;
		}
		foreach ($this->object->tab_name_index as $t=>$k){
			if (!$create[$t][$k]) $create[$t][$k] = $id;
		}
		
		if (is_array($this->fields_runtime)) {
			foreach ($this->fields_runtime as $table=>$arr){
				foreach ($arr as $column=>$value){
					$create[$table][$column] = $value;
				}
			}
		}

		if ($this->sequence_field){
			$create[$this->sequence_field[0]][$this->sequence_field[1]] = $this->getModuleSeqNumber($data); //crmv@95507
		}

		foreach ($this->object->tab_name_index as $t=>$k){
			$this->insert_into_create_file($t,$create);
		}
		$this->entity_created[] = $id;

		// users case, not used now
		if ($this->module == 'Users') {
			$t = $table_prefix.'_users';
			$this->writeToFile($this->sql_file_update[$t],"insert into ".$table_prefix."_user2role values ($id,'".$this->cache[$this->module]['roleid']."');\n", $this->sql_file_name_update[$t]);
		}
		
		$this->createProdAttributes($prodattr, $table); // crmv@203591
		$this->setProdAttributes($id, $subprodattr, $create,$table); // crmv@203591
		
		return true;
	}

	//crmv@95507
	//we do not really need $data param but is usefull when overrided
	protected function getModuleSeqNumber($data){
		return $this->object->setModuleSeqNumber("increment",$this->module);
	}
	//crmv@95507e
	
	protected function createInventory($data) {
		global $adb,$table_prefix;
		
		// crmentity
		$table = $table_prefix.'_inventoryproductrel';
		$idx = 'lineitem_id';
		$id = $adb->getUniqueID($table);
		
		$deleteProducts = false;
		$inventoryId = $data[$this->mapping_inverse['inventoryid']];
		
		if (empty($inventoryId)) {
			$this->log->warn('No '.$this->invmodule.' record found for this product line, skipped');
			return true;
		}
		
		if (is_null($this->currentInventoryId)) {
			// first row
			$this->currentInventoryId = $inventoryId;
			$deleteProducts = true;
		} elseif ($this->currentInventoryId != $inventoryId) {
			// this is a new record, so output the totals
			$this->updateInventoryTotals();
			$this->currentInventoryId = $inventoryId;
			$this->currentTotals = array();
			$deleteProducts = true;
		}
		
		// the delete query
		if ($inventoryId > 0 && $deleteProducts) {
			$sql = "DELETE FROM {$table} WHERE id = '$inventoryId'\r\n";
			$this->writeToFile($this->sql_file_create[$table], $sql, $this->sql_file_name_create[$table]);
		}
		
		$create = $this->getcached_create_arr();
		
		// other tables
		foreach ($this->fields as $ftable => $arr){
			foreach ($arr as $field){
				if ($this->fields_functions[$this->fields_name[$ftable][$field]] != '' && method_exists($this,$this->fields_functions[$this->fields_name[$ftable][$field]])) {
					$create[$ftable][$field] = $this->{$this->fields_functions[$this->fields_name[$ftable][$field]]}($this->fields_name[$ftable][$field],$data,$ftable,$field,'create');
				}		
				else{
					$create[$ftable][$field] = $data[$this->mapping_inverse[$this->fields_name[$ftable][$field]]];
				}
			}
			// no id, it's already done in the fields_runtime
		}
		
		// calculate all the prices
		$prodPrices = $this->iutils->calcProductTotals($create[$table]);
		$prodFieldsToAdd = array(
			'price_discount' => 'total_notaxes', 
			'price_taxes' => 'linetotal'
		);
		
		// add the calculated fields
		foreach ($prodFieldsToAdd as $k => $fld) {
			if (array_key_exists($k, $prodPrices) && !isset($create[$table][$fld])) {
				$this->fields_runtime[$table][$fld] = $prodPrices[$k];
			}
		}	
		
		// add the lineitemid
		$this->fields_runtime[$table][$idx] = $id;
		
		if (is_array($this->fields_runtime)) {
			foreach ($this->fields_runtime as $ftable=>$arr){
				foreach ($arr as $column=>$value){
					$create[$ftable][$column] = $value;
				}
			}
		}
		
		foreach ($create as $t=>$k){
			$this->insert_into_create_file($t,$create);
		}
		$this->entity_created[] = $id;
		
		// save the totals
		$this->currentTotals[] = $prodPrices;

		return true;
	}
	
	protected function updateInventoryTotals() {
		global $adb, $table_prefix;
		
		if (empty($this->currentInventoryId) || count($this->currentTotals) == 0) {
			// nothing to calculate
			return true;
		}
		
		$table = $this->object->table_name;
		$index = $this->object->table_index;
		
		$subtotal = 0;
		foreach($this->currentTotals as $value) {
			$subtotal += $value['price_taxes'];
		}
		$grandTotal = $subtotal;
		
		// get other prices
		$res = $adb->pquery("select discount_percent, discount_amount, adjustment, s_h_amount from {$table} where {$index} = ?", array($this->currentInventoryId));
		$row = $adb->FetchByAssoc($res, -1, false);
		
		if (empty($row)) {
			$this->log->warn('It was not possible to calculate the totals, since the main '.$this->invmodule.'record was not found');
			return true;
		}

		$totalinfo = array(
			'nettotal' => floatval($subtotal),
			's_h_amount' => floatval($row['s_h_amount']),
			'discount_percent' => $row['discount_percent'],
			'discount_amount' => $row['discount_amount'],
			'adjustment' => floatval($row['adjustment']),
			'taxes' => array(),
			'shtaxes' => array(),
		);

		// calculate totals
		$totalPrices = $this->iutils->calcInventoryTotals($totalinfo);
		if ($totalPrices) {
			$grandTotal = $totalPrices['price_adjustment'];
		}
		
		$sql = "UPDATE {$table} set subtotal = ?, total = ? where {$index} = ?;\r\n";
		$params = array($subtotal,$grandTotal,$this->currentInventoryId);
		$sql = $adb->convert2Sql($sql, $adb->flatten_array($params));
		
		$this->writeToFile($this->sql_file_update[$table], $sql, $this->sql_file_name_update[$table]);
		return true;
	}
	
	protected function update($data,$id) {
		global $adb, $table_prefix;
		
		if ($this->module == 'ProductRows') {
			$this->log->warning('The update is not supported for products rows, something went wrong. Update skipped');
			return true;
		}
		
		//crmv@185058
		if ($this->launch_processes) {
			require_once('data/VTEntityDelta.php');
			$entityDelta = new VTEntityDelta();
			$entityDelta->setOldEntity($this->module,$id);
		}
		//crmv@185058e

		$prodattr = $subprodattr = array(); // crmv@203591
		$update = $this->getcached_update_arr();
		foreach ($this->fields as $table => $arr){
			foreach ($arr as $field){
				// should i skip the field in update?
				if (is_array($this->fields_jump_update) &&  in_array($this->fields_name[$table][$field],$this->fields_jump_update))	continue;
				// crmv@203591
				// which funciton to use for the value
				if ($this->fields_functions[$this->fields_name[$table][$field]] != '' && method_exists($this,$this->fields_functions[$this->fields_name[$table][$field]])) {
					$readyData = $this->{$this->fields_functions[$this->fields_name[$table][$field]]}($this->fields_name[$table][$field],$data,$table,$field,'update');
				} else {
					$readyData = $data[$this->mapping_inverse[$this->fields_name[$table][$field]]];		
				}
				
				if ($field == 'mlProdAttr') {
					$prodattr[$id] = $readyData;
					continue;
				} elseif (substr($field, 0, 9) === 'prodattr_') {
					$subprodattr[$field] = $readyData;
					continue;
				} elseif (substr($field, -8) === 'prodattr') {
					$subprodattr[$field] = $readyData;
					continue;
				}
				
				$update[$table][$field] = $readyData;
				// crmv@203591e
			}
		}
		if (is_array($this->fields_runtime)) {
			foreach ($this->fields_runtime as $table=>$arr){
				foreach ($arr as $column=>$value){
					$update[$table][$column] = $value;
				}
			}
		}
		
		foreach ($update as $table=>$arr){
			$sql = "UPDATE $table SET ";
			$first = true;
			$params = Array();
			foreach ($arr as $field=>$value){
				if (!$first)
					$sql .=",";
				$sql .= " $field = ?";
				$first = false;
				$params[] = $value;
			}
			$sql .= " WHERE ".$this->getkey('full',$table)." = ?";
			$params[] = $id;
			$sql = $adb->convert2Sql($sql,$adb->flatten_array($params));
			if ($id){
				$this->writeToFile($this->sql_file_update[$table],$sql.";\n", $this->sql_file_name_update[$table]);
			}	
		}
		$this->entity_updated[] = $id;
		
		$this->createProdAttributes($prodattr, $table); // crmv@203591
		$this->setProdAttributes($id, $subprodattr, $update,$table); // crmv@203591
		
		return true;
	}
	
	// crmv@203591
	protected function createProdAttributes($prodattr, $table) {
		global $adb, $table_prefix, $current_user;
		
		if (is_array($prodattr) && count($prodattr) > 0) {
			$confProdFocus = CRMEntity::getInstance('ConfProducts');
			$queries = [];
			foreach ($prodattr as $confprodid => $attributes) {
				// get all attributes
				$attributes = array_keys($attributes);
				$attrs = $confProdFocus->getAttributes($confprodid);
				// extract just names for comparisons
				$attrNames = array_column($attrs, 'fieldlabel');
				$toAdd = array_diff($attributes, $attrNames);
				$toDel = array_diff($attrNames, $attributes);
				if (count($toDel) > 0) {
					$sqlDel = "DELETE FROM {$table_prefix}_modlightprodattr WHERE parent_id = ? AND attr_name IN (".generateQuestionMarks($toDel).");\n";
					$sqlDel = $adb->convert2Sql($sqlDel,$adb->flatten_array(array(intval($confprodid), $toDel)));
					$queries[] = $sqlDel;
				}
				if (count($toAdd) > 0) {
					$seq = 1;
					foreach ($toAdd as $addAttr) {
						$id = $adb->getUniqueID($table_prefix.'_crmentity');
						$params = [$id, $current_user->id, $current_user->id, 'ModLightProdAttr', $this->time_start, $this->time_start, 1, 0];
						$sqlIns = "INSERT INTO {$table_prefix}_crmentity (crmid, smcreatorid, smownerid, setype, createdtime, modifiedtime, presence, deleted) VALUES (".generateQuestionMarks($params).");\n";
						$sqlIns = $adb->convert2Sql($sqlIns,$adb->flatten_array($params));
						$queries[] = $sqlIns;
						
						$params = [$id, $addAttr, '', $confprodid, $seq++];
						$sqlIns = "INSERT INTO {$table_prefix}_modlightprodattr (modlightprodattrid, attr_name, attr_values, parent_id, seq) VALUES (".generateQuestionMarks($params).");\n";
						$sqlIns = $adb->convert2Sql($sqlIns,$adb->flatten_array($params));
						$queries[] = $sqlIns;
					}
				}
			}
			if (count($queries) > 0) $this->prodAttrCreated = true;
			
			// now set values, if present
			foreach ($prodattr as $confprodid => $attributes) {
				foreach ($attributes as $attrName => $values) {
					if (is_array($values)) {
						// ok, values are passed
						$params = [implode("\r\n", $values), $confprodid, $attrName];
						$sql = "UPDATE {$table_prefix}_modlightprodattr SET attr_values = ? WHERE parent_id = ? AND attr_name = ?;\n";
						$sql = $adb->convert2Sql($sql,$adb->flatten_array($params));
						$queries[] = $sql;
					}
				}
				
			}
			
			foreach ($queries as $q) {
				$this->writeToFile($this->sql_file_update[$table],$q, $this->sql_file_name_update[$table]);
			}
		}

	}
	
	protected function setProdAttributes($id, $subprodattr, $update, $table) {
		global $adb, $table_prefix;
		
		if (is_array($subprodattr) && count($subprodattr) > 0) {
			$confprodid = $update[$table]['confproductid'];
		
			if ($confprodid > 0) {
				// take only the attributes matching the configurable product
				$toUpdate = [];
				foreach ($subprodattr as $attrName => $value) {
					if (substr($attrName, -8) === 'prodattr') {
						// I only have the label, find the matching field
						$attrName = $this->findMatchingAttribute($confprodid, str_replace('prodattr', '', $attrName));
						if (!$attrName) continue;
					}
					list($xx, $confid, $mlid) = explode('_', $attrName);
					if ($confprodid == $confid) {
						$toUpdate[$attrName] = $value;
						// save all the values for later
						$this->prodAttrValues[$confprodid][$mlid][$value] = 1;
					}
				}
				$params = [Zend_Json::encode($toUpdate), intval($id)];
				$sql = "UPDATE $table SET confprodinfo = ? WHERE productid = ?;\n";
				$sql = $adb->convert2Sql($sql,$adb->flatten_array($params));
				$this->writeToFile($this->sql_file_update[$table],$sql, $this->sql_file_name_update[$table]);
			} else {
				// delete the conf
				$sql = "UPDATE $table SET confprodinfo = NULL WHERE productid = ".intval($id).";\n";
				$this->writeToFile($this->sql_file_update[$table],$sql, $this->sql_file_name_update[$table]);
			}
		}
		
	}
	
	/**
	 * Find a matching attribute name using the label
	 */
	protected function findMatchingAttribute($confprodid, $label) {
		static $prodAttrNamesCache = array();
		
		$key = $confprodid.'_'.$label;
		if (!array_key_exists($key, $prodAttrNamesCache)) {
			$field = null;
			
			$confProdFocus = CRMEntity::getInstance('ConfProducts');
			$attrs = $confProdFocus->getAttributes($confprodid);
			foreach ($attrs as $attrinfo) {
				if ($attrinfo['fieldlabel'] == $label) {
					$field = $attrinfo['fieldname'];
					break;
				}
			}
			
			$prodAttrNamesCache[$key] = $field;
		}
		
		return $prodAttrNamesCache[$key];
	}
	
	protected function insertProdAttrValues() {
		global $adb, $table_prefix;
		
		foreach ($this->prodAttrValues as $confprodid => $attributes) {
			foreach ($attributes as $mlid => $values) {
				$values = array_keys($values);
				// get existing values
				$res = $adb->pquery("SELECT attr_values FROM {$table_prefix}_modlightprodattr WHERE modlightprodattrid = ?", array($mlid));
				if ($res && $adb->num_rows($res) > 0) {
					$existingValues = $adb->query_result_no_html($res, 0, 'attr_values');
					$existingValues = explode("\n", str_replace("\r", "", $existingValues));
					$allValues = array_unique(array_filter(array_merge($existingValues, $values)));
					$adb->pquery(
						"UPDATE {$table_prefix}_modlightprodattr SET attr_values = ? WHERE modlightprodattrid = ?",
						[implode("\r\n", $allValues), $mlid]
					);
				}
			}
		}
	}
	// crmv@203591e
	
	protected function getkey($mode = '',$table = false){
		global $table_prefix;
		if (!$table){
			$table = $table_prefix.'_crmentity';
			if(!in_array($table,$this->object->tab_name)){
				$table = $this->object->tab_name[0];
			}
		}
		if ($mode == 'full')
			return $table.".".$this->object->tab_name_index[$table];
		else
			return $this->object->tab_name_index[$table];
	}
	
	protected function executeSystemCommand($cmd) {
		if ($this->simulate) {
			$this->log->debug('SIMULATION: Execute command: '.$cmd);
		} else {
			// crmv@155585
			$lastRow = system($cmd,$result);
			if ($result != '0'){
				$this->log->error('Command error: '.$lastRow);
				return false;
			}
			// crmv@155585e
		}
		return true;
	}
	
	protected function executeUpdateQuery($q) {
		global $adb;
		if ($this->simulate) {
			$this->log->debug('SIMULATION: Execute query: '.$q);
		} else {
			$r = $adb->query($q);
			if (!$r) return false;
		}
		return true;
	}
	

	protected function execute(){
		global $dbconfig, $adb, $table_prefix;
		$this->log->debug('Reading files and executing query now...');
		
		if ($this->config['fast_mysql_insert'] && $adb->isMySQL()) {
			$port = str_replace(":","",$dbconfig['db_port']);
			$mysqlOpts = "--local-infile -h {$dbconfig['db_server']} -u {$dbconfig['db_username']} --password='{$dbconfig['db_password']}' -P {$port} {$dbconfig['db_name']}"; // crmv@169696
			
			$create_file = fopen($this->file_create,'w+');
			$pre_create = "/*!40101 SET NAMES utf8 */;\n/*!40101 SET SQL_MODE=''*/;\n/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n";
			$this->writeToFile($create_file,$pre_create, $this->file_create);
			foreach ($this->sql_file_name_create as $t=>$file){
				//faccio le create
				$fields = $this->get_column_create($t);
				if (filesize($file)>0){
					// crmv@109628
					$csvCharset = $this->extdb_charset ?: 'UTF8';
					$sql_load = 'LOAD DATA LOCAL INFILE \''.$file.'\' INTO TABLE '.$t.' CHARACTER SET '.$csvCharset.' FIELDS ESCAPED BY \'\' TERMINATED BY \',\' OPTIONALLY ENCLOSED BY \'"\' LINES TERMINATED BY \'\n\' ('.implode(",",$fields).');'."\n";
					$this->writeToFile($create_file,$sql_load, $this->file_create);
					// crmv@109628e
				}
			}
			$post_create = "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;";
			$this->writeToFile($create_file,$post_create, $this->file_create);			
			fclose($create_file);
			
			// now execute the commands
			if (filesize($this->file_create)>0){
				$filename = $this->file_create;
				$string_create = "mysql $mysqlOpts 2>&1 < {$filename}"; // crmv@155585
				$r = $this->executeSystemCommand($string_create);
				if (!$r) return $this->log->error("Error loading create file {$this->file_create}");
			}
			
			// now the updates
			foreach ($this->sql_file_name_update as $t=>$file){	
				//faccio le insert
				if (filesize($file)>0){
					$string_update = "mysql $mysqlOpts 2>&1 < {$file}"; // crmv@155585
					$r = $this->executeSystemCommand($string_update);
					if (!$r) return $this->log->error("Error loading updating file {$file}");
				}								
			}
		} else {
			// TODO: le query sembrano errate
			//faccio le create
			foreach ($this->sql_file_name_create as $t=>$file){
				$create_file = fopen($file,'rb');
				while (($buffer = fgets($create_file, 4096)) !== false) {
					if (trim($buffer) != ''){
						$this->executeUpdateQuery($buffer);
					}
				}
				fclose($create_file);
			}
			//faccio le insert
			foreach ($this->sql_file_name_update as $t=>$file){	
				if (filesize($file)>0) {
					$update_file = fopen($file,'rb');
					while (($buffer = fgets($update_file, 4096)) !== false) {
						if (trim($buffer) != ''){
							$this->executeUpdateQuery($buffer);
						}
					}
					fclose($update_file);
				}								
			}
		}
		return true;
	}
	
	protected function get_existing_entity_runtime($row){
		global $adb, $table_prefix;

		$LVU = ListViewUtils::getInstance();

		// prepare the query
		if (!isset($this->cache[$this->module]['query_unique'])){
			$sql = "select tablename,columnname from ".$table_prefix."_field where tabid = ? and fieldname = ?"; //crmv@75695
			$params[] = getTabid($this->module);
			$params[] = $this->mapping[$this->external_code];
			$res = $adb->pquery($sql,$params);
			if ($res){
				$table = $adb->query_result_no_html($res,0,'tablename');
				$columnname = $adb->query_result_no_html($res,0,'columnname'); //crmv@75695
				if (empty($table)) {
					$this->log->warn("The external code is not configured properly");
					return false;
				}
				$external_code = $table.".".$columnname; //crmv@75695
			}
			$qry = $LVU->getListQuery($this->module,"and $external_code = ?");
			if ($this->module == 'Users'){
				$qry = replaceSelectQuery($qry,'id');
			} else {
				$qry = replaceSelectQuery($qry,$table_prefix.'_crmentity.crmid');
			}
			$this->cache[$this->module]['query_unique'] = $qry;
		}
		
		// execute it
		$extcode = $row[$this->external_code];
		$qry = $this->cache[$this->module]['query_unique'];
		$res = $adb->pquery($qry,Array($extcode));

		$id = false;
		if ($res && $adb->num_rows($res)>0) {
			if ($this->module == 'Users'){
				$id = $adb->query_result_no_html($res,0,'id');
			} else {
				$id = $adb->query_result_no_html($res,0,'crmid');
			}
		}
		// log disabled, since can log every row, but kept here for future use
		//$this->log->debug("The external code '$extcode' ".($id ? "has been found. crmid = $id" : "has not been found"));
		return $id;
	}
	
	private function get_column_create($table_name) {
		$create = $this->getcached_create_arr(false);

		foreach ($this->fields as $table => $arr){
			foreach ($arr as $field){
				if (substr($field, 0, 9) === 'prodattr_' || substr($field, -8) === 'prodattr') continue; // crmv@203591
				$create[$table][$field] = '';
			}
			$idx = $this->object->tab_name_index[$table];
			if ($idx) $create[$table][$idx] = '';
		}	
		foreach ($this->object->tab_name_index as $t=>$k){
			if (!$create[$t][$k]) $create[$t][$k] = '';
		}
		if (is_array($this->fields_runtime)) {
			foreach ($this->fields_runtime as $table=>$arr){
				foreach ($arr as $column=>$value){
					$create[$table][$column] = '';
				}
			}
		}
		if ($this->sequence_field){
			$create[$this->sequence_field[0]][$this->sequence_field[1]] = '';
		}
		return array_keys($create[$table_name]);
	}
	
	private function getcached_create_arr($data = true) {
		if ($this->create_arr) return $this->create_arr;

		if (is_array($this->fields_auto_create)) {
			foreach ($this->fields_auto_create as $table => $arr){
				foreach ($arr as $field=>$def_value){
					if (!$data) $def_value = '';
					$this->create_arr[$table][$field] = $def_value;
				}			
			}
		}
		return $this->create_arr;
	}
	
	private function getcached_update_arr() {
		if ($this->update_arr) return $this->update_arr;
		if (is_array($this->fields_auto_update)){
			foreach ($this->fields_auto_update as $table => $arr){
				foreach ($arr as $field=>$def_value){
					$this->update_arr[$table][$field] = $def_value;
				}			
			}
		}
		return $this->update_arr;
	}	
	
	private function insert_into_create_file($table,$create){
		global $adb;
		if ($this->config['fast_mysql_insert'] && $adb->isMySQL()){
			$this->fputcsv2($this->sql_file_create[$table],$create[$table], ',', '"', true, $this->sql_file_name_create[$table]);
		}
		else{
			array_walk($create[$table],array($this, 'sanitize_array_sql'));
			$columns = array_keys($create[$table]);
			$adb->format_columns($columns);
			$sql = "INSERT INTO $table (".implode(",",$columns).") VALUES (".implode(",",$create[$table]).")\r\n";
			$this->writeToFile($this->sql_file_create[$table], $sql, $this->sql_file_name_create[$table]);
		}
	}
	
	// special method to save a csv in a way msql like it
	function fputcsv2 ($fh, array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = true, $filename = null) {
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');

		$output = array();
		foreach ($fields as $field) {
			if ($field === null && $mysql_null) {
				$output[] = 'NULL';
				continue;
			}

			$output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? (
				$enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure
			) : $field;
		}
		$this->writeToFile($fh, join($delimiter, $output) . "\n", $filename);
	}

	function sanitize_array_sql(&$item,&$key){
		global $adb;
		if (is_string($item)) {
			if($item == '') {
				$item = $adb->database->Quote($item);
			}
			else {
				$item = "'".$adb->sql_escape_string($item). "'";
			}
		}
		if ($item === null) {
			$item = "NULL";
		}
	}
	
	// get the field containing the sequence for the module and cache this information
	protected function getSequenceField(){
		global $adb,$table_prefix;

		if ($this->sequence_field) return $this->sequence_field;
		
		$sql = "select tablename,columnname,uitype from {$table_prefix}_field where tabid = ? and uitype = 4";
		$res = $adb->limitpQuery($sql, 0, 1, Array(getTabid($this->module)));
		if ($res && $adb->num_rows($res) > 0){
			$this->sequence_field = Array($adb->query_result_no_html($res,0,'tablename'),$adb->query_result_no_html($res,0,'columnname'));	
		}
		
		return $this->sequence_field;
	}
	
	protected function delete($data,$id){
		// NOT IMPLEMENTED!!
	}
	
}

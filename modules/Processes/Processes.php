<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');

class Processes extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'processesid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array();

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array();

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array ();
	var $list_fields_name = array (
		'Process Name' => 'process_name',
		'Status' => 'process_status',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'process_name';

	// For Popup listview and UI type support
	var $search_fields = Array();
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Process Name'=> 'process_name'
	);

	// For Popup window record selection
	var $popup_fields = Array('process_name');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'process_name';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'process_name';

	// Required Information for enabling Import feature
	var $required_fields = Array('process_name'=>1);

	var $default_order_by = 'process_name';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'process_name');
	//crmv@10759
	var $search_base_field = 'process_name';
	//crmv@10759 e

	function __construct() {
		global $log, $table_prefix; // crmv@64542
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_processes';
		//crmv@185647
		// removed $this->customFieldTable
		// removed $this->entity_table
		$this->tab_name = array($table_prefix.'_processes');
		$this->tab_name_index = array(
			$table_prefix.'_processes' => 'processesid',
		);
		$this->list_fields = array(
			'Process Name' => array($table_prefix.'_processes','process_name'),
			'Status' => array($table_prefix.'_processes','process_status'),
			'Assigned To' => array($table_prefix.'_processes','smownerid'),
		);
		//crmv@185647e
		$this->search_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Process Name'=> Array($table_prefix.'_processes','process_name')
		);
		$this->column_fields = getColumnFields(get_class()); // crmv@64542
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	/*
	// moved in CRMEntity
	function getSortOrder() { }
	function getOrderBy() { }
	*/

	// crmv@64542
	function save_module($module) {
		global $adb, $table_prefix;
		
		if ($this->parallel) {
			$set = array('processesid=?');
			$set_values = array($this->id);
			if (!empty($this->casperid) && $this->id != $this->casperid) {
				$set[] = 'casperid=?';
				$set_values[] = $this->casperid;
			}
			if ($this->engine->helper['active'] == 'on') {	// set last dynaform available
				$set[] = 'current_dynaform=?';
				$set_values[] = $this->engine->elementid;
			}
			$query = "update {$table_prefix}_process_gateway_conn set ".implode(',',$set)." where running_process = ? and elementid = ? and flow = ?";
			$adb->pquery($query,array($set_values,$this->engine->running_process,$this->gateway,$this->flow));
		}
		
		//crmv@96450
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$saved_form = $processDynaFormObj->save($this, $_REQUEST);
		//crmv@96450e
		//crmv@105312 empty request values because if I have more consecutive forms I write the same values in all
		if (!empty($saved_form)) {
			foreach($saved_form as $fieldname => $value) {
				unset($_REQUEST[$fieldname]);
			}
		}
		//crmv@105312e
		
		//crmv@97575
		if (empty($this->column_fields['father'])) {
			$PMUtils = ProcessMakerUtils::getInstance();
			$father = $PMUtils->getProcessFatherRun($this->column_fields['running_process']);
			if (!empty($father)) {
				$adb->pquery("update {$table_prefix}_processes set father = ? where processesid = ?", array($father,$this->id));
			}
		}
		//crmv@97575e
		
		//crmv@115268
		if ($this->mode == '') {
			$result = $adb->limitpQuery("SELECT userid FROM {$table_prefix}_running_processes_logs WHERE running_process = ? ORDER BY id", 0, 1, array($this->column_fields['running_process'])); //crmv@169519
			if ($result && $adb->num_rows($result) > 0) {
				$adb->pquery("update {$table_prefix}_crmentity set smcreatorid = ? where crmid = ?", array($adb->query_result($result,0,'userid'), $this->id));
			}
		}
		//crmv@115268e
		
		//crmv@126096
		if ($adb->isMysql()) {
			$adb->pquery("insert ignore into {$table_prefix}_assigned_running_processes(assigned,running_process) values(?,?)",array($this->column_fields['assigned_user_id'],$this->column_fields['running_process']));
		} else {
			$result = $adb->pquery("select * from {$table_prefix}_assigned_running_processes where assigned = ? and running_process = ?", array($this->column_fields['assigned_user_id'],$this->column_fields['running_process']));
			if ($result && $adb->num_rows($result) == 0) {
				$adb->pquery("insert into {$table_prefix}_assigned_running_processes(assigned,running_process) values(?,?)",array($this->column_fields['assigned_user_id'],$this->column_fields['running_process']));
			}
		}
		//crmv@126096e
	}
	// crmv@64542e

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb,$table_prefix;
		if($event_type == 'module.postinstall') {
			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));

			// crmv@105933
			// remove unnecessary tools
			$processesModule = Vtecrm_Module::getInstance($modulename);
			if ($processesModule) {
				$processesModule->disableTools('Import', 'Merge');
			}
			// crmv@105933e

			//crmv@29617
			$result = $adb->pquery('SELECT isentitytype FROM '.$table_prefix.'_tab WHERE name = ?',array($modulename));
			if ($result && $adb->num_rows($result) > 0 && $adb->query_result($result,0,'isentitytype') == '1') {

				$ModCommentsModuleInstance = Vtecrm_Module::getInstance('ModComments');
				if ($ModCommentsModuleInstance) {
					$ModCommentsFocus = CRMEntity::getInstance('ModComments');
					$ModCommentsFocus->addWidgetTo($modulename);
				}

				// crmv@164120 - removed code
				// crmv@164122 - removed code

				$MyNotesModuleInstance = Vtecrm_Module::getInstance('MyNotes');
				if ($MyNotesModuleInstance) {
					$MyNotesCommonFocus = CRMEntity::getInstance('MyNotes');
					$MyNotesCommonFocus->addWidgetTo($modulename);
				}
			}
			//crmv@29617e
			
			SDK::addView('Processes', 'modules/SDK/src/modules/Processes/View.php', 'constrain', 'continue');
			SDK::setAdvancedQuery('Processes', "advQueryProcesses", 'modules/SDK/src/modules/Processes/Utils.php');
			SDK::setAdvancedPermissionFunction('Processes', "advPermProcesses",  'modules/SDK/src/modules/Processes/Utils.php');
			
			$em = new VTEventsManager($adb);
			$em->registerHandler('vte.entity.aftersave.processes', 'modules/Settings/ProcessMaker/ProcessMakerHandler.php', 'ProcessMakerHandler'); // crmv@177677 crmv@207852
			$em->registerHandler('vte.entity.relate', 'modules/Settings/ProcessMaker/ProcessMakerHandler.php', 'ProcessMakerHandler'); // crmv@200009 crmv@207852
				
			require_once('include/Webservices/Utils.php');
			$res = $adb->pquery("SELECT operationid FROM {$table_prefix}_ws_operation WHERE name = ?", array('dynaform_describe'));
			if ($res && $adb->num_rows($res) == 0) {
				$operationId = vtws_addWebserviceOperation('dynaform_describe', 'include/Webservices/DynaForm.php', 'dynaform_describe', 'GET', 0, 'dynaformdescribe'); //crmv@170283
				vtws_addWebserviceOperationParam($operationId,'processmakerid','string',1);
				vtws_addWebserviceOperationParam($operationId,'metaid','string',2);
				vtws_addWebserviceOperationParam($operationId,'options','encoded',3);
			}
			
			require_once('include/utils/CronUtils.php');
			$CU = CronUtils::getInstance();
			$cronname = 'ProcessesTimer';
			$cj = CronJob::getByName($cronname);
			if (empty($cj)) {
				$cj = new CronJob();
				$cj->name = $cronname;
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Processes/Timer.service.php';
				$cj->timeout = 300;
				$cj->repeat = 60;	// run every minute
				$CU->insertCronJob($cj);
			}
			
			//crmv@98484
			require_once('include/utils/AlertNotifications.php');
			$focusAlertNot = AlertNotifications::getInstance();
			$focusAlertNot->save('LBL_PM_ACTION_FIELD_FORMAT_TYPE',array('it_it'=>'I valori vanno inseriti secondo la sintassi corretta.','en_us'=>'The values must be inserted using the correct syntax.'));
			$focusAlertNot->save('LBL_DYNAFORM_FORMAT_TYPE_DEFAULT_VALUE',array('it_it'=>'Il valore di default va inserito secondo la sintassi corretta.','en_us'=>'The default value must be inserted in the correct syntax.'));
			//crmv@98484e
			
			//crmv@93990
			$sdkInstance = Vtecrm_Module::getInstance('SDK');
			$sdkInstance->addLink('HEADERSCRIPT', 'DynaFormScript', 'modules/Processes/DynaFormScript.js');
			$sdkInstance->addLink('HEADERSCRIPT', 'ProcessesScript', 'modules/Processes/Processes.js');
			//crmv@93990e
			
			$this->enableAll();	//crmv@100731
			
			//crmv@183872
			$focusUsers = CRMEntity::getInstance('Users');
			$focusUsers->createCustomFiltersForAll();
			//crmv@183872e
			
			// crmv@189362
			SDK::setProcessMakerTaskCondition('vte_get_projecttask_usage_percent', 'modules/SDK/src/ProcessMaker/Utils.php', 'Project Task usage %');
			
			$PMUtils = ProcessMakerUtils::getInstance();
			$result = $adb->pquery("select id from {$table_prefix}_processmaker where name = ?", array('70% hours exceeded'));
			if ($adb->num_rows($result) == 0) {
				$PMUtils->importFile('modules/SDK/src/ProcessMaker/vtebpmn/70_hours_exceeded.vtebpmn',true);
			}
			$result = $adb->pquery("select id from {$table_prefix}_processmaker where name = ?", array('90% hours exceeded'));
			if ($adb->num_rows($result) == 0) {
				$PMUtils->importFile('modules/SDK/src/ProcessMaker/vtebpmn/90_hours_exceeded.vtebpmn',true);
			}
			// crmv@189362e
			
			$docModuleInstance = Vtecrm_Module::getInstance('Documents');
			$docModuleInstance->setRelatedList($processesModule,$modulename,array('select','add'),'get_documents_dependents_list');
			
			// crmv@193096
			SDK::setReportFolder('CUSTOM_REPORTS_DIR', '');
			SDK::setReport('Active Processes', '', 'CUSTOM_REPORTS_DIR', 'modules/Processes/ReportActiveProcesses.php', 'ActiveProcessesReportRun');
			// crmv@193096e
			
			SDK::setProcessMakerFieldAction("vte_json_column_fields", "modules/SDK/src/ProcessMaker/Utils.php", "Get column fields in json format (ID,[fieldname1,fieldname2,...])"); // crmv@195748
			
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	/*
	function save_related_module($module, $crmid, $with_module, $with_crmid) {
		parent::save_related_module($module, $crmid, $with_module, $with_crmid);
		//...
	}
	*/

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
	
	//crmv@92272
	function enable($module) {
		global $adb, $table_prefix;
		require_once('vtlib/Vtecrm/Field.php');
		$processesInstance = Vtecrm_Module::getInstance('Processes');
		$relInstance = Vtecrm_Module::getInstance($module);
		
		$fieldInstance = Vtecrm_Field::getInstance('related_to',$processesInstance);
		$fieldInstance->setRelatedModules(array($module));
		
		$this->enableAdvancedPermissionsWidget($module);	//crmv@100731
		
		$result = $adb->pquery("SELECT * FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = ?", array($relInstance->id, $processesInstance->id));
		if ($result && $adb->num_rows($result) == 0) $relInstance->setRelatedList($processesInstance, 'Processes', array(), 'get_dependents_list');
		
		$cache = Cache::getInstance('relatedToProcesses');
		$cache->clear();
	}
	function enableAll() {
		global $adb, $table_prefix;
		$processesInstance = Vtecrm_Module::getInstance('Processes');
		$fieldInstance = Vtecrm_Field::getInstance('related_to',$processesInstance);
		$result = $adb->pquery("SELECT relmodule FROM {$table_prefix}_fieldmodulerel WHERE fieldid=?", Array($fieldInstance->id));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$this->enable($row['relmodule']);
			}
		}
	}
	//crmv@92272e
	
	//crmv@96584 crmv@101506 crmv@150751 crmv@177598 crmv@188364
	//crmv@176621 removed crmv@104975 getExtraDetailTabs
	function getExtraDetailBlock() {
		$blocks = $this->getProcessBlock('graph');
		$blocks .= $this->getProcessBlock('history');
		return $blocks;
	}
	function getProcessBlock($mode, $selectionProcesses=false) {
		global $mod_strings, $app_strings, $theme, $adb, $table_prefix;
		$smarty = new VteSmarty();
		$smarty->assign('MOD',$mod_strings);
		$smarty->assign('APP',$app_strings);
		$smarty->assign('THEME', $theme); // crmv@154044
		$smarty->assign('MODE', $mode);
		if (!empty($selectionProcesses)) {
			$smarty->assign('SELECTION_PROCESSES',$selectionProcesses);
		} else {
			$version = ' ('.strtolower(substr(getTranslatedString('VTLIB_LBL_PACKAGE_VERSION','Settings'),0,1)).'. '.$this->getProcessVersion($this->id).')';
			$selectionProcesses[] = array(getEntityName('Processes',$this->id,true).$version, $this->id, '');
			$smarty->assign('SELECTION_PROCESSES',$selectionProcesses);
		}
		//crmv@103450
		$user_array = get_user_array(true, "Active", $assigned_user_id);
		$PMUtils = ProcessMakerUtils::getInstance();
		$actors = $PMUtils->getProcessActors($this->column_fields['running_process']);
		foreach($user_array as $id => $name) {
			if (!empty($id) && !in_array($id,$actors)) unset($user_array[$id]);
		}
		$smarty->assign('USERS_LIST',get_select_options_array($user_array, $assigned_user_id));
		//crmv@103450e
		$smarty->assign('ENABLE_ROLLBACK',$PMUtils->isEnableRollback());	//crmv@112539
		$smarty->assign('RUNNING_PROCESS_ACTIVE',$PMUtils->isActiveRunningProcess($this->column_fields['running_process']));	//crmv@112539
		return $smarty->fetch('modules/Processes/ProcessGraph.tpl');
	}
	function getProcessGraphInfo($mode='graph') {
		global $adb, $table_prefix, $mod_strings, $app_strings, $current_user;
		require_once('modules/Settings/ProcessMaker/ProcessMakerEngine.php');
		require_once('modules/Settings/ProcessMaker/ProcessMakerHandler.php');
		
		$result = $adb->pquery("select current, xml_version from {$table_prefix}_running_processes where id = ?", array($this->column_fields['running_process']));
		if ($result && $adb->num_rows($result) > 0) {
			$current = $adb->query_result($result,0,'current');
			$xml_version = $adb->query_result($result,0,'xml_version');
		}
		$current = explode('|##|',$current);
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$data = $PMUtils->retrieve($this->column_fields['processmaker'], $xml_version);
		$structure = Zend_Json::decode($data['structure']);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$helper = Zend_Json::decode($data['helper']);
		
		// TODO i dettagli(condition,process_helper,...) si basano sulla configurazione attuale e non su un log
		$info = array();
		if ($mode == 'history') $logs = array();
		
		// logs
		$count_logs = array();
		$count_occ = array();
		$result = $adb->pquery("select prev_elementid, count(*) as \"count\" from {$table_prefix}_running_processes_logs where running_process = ? group by prev_elementid", array($this->column_fields['running_process']));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$count_logs[$row['prev_elementid']] = $row['count'];
			}
		}
		$result = $adb->pquery("select elementid, count(*) as \"count\" from {$table_prefix}_running_processes_logs where running_process = ? group by elementid", array($this->column_fields['running_process']));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$count_logs[$row['elementid']] = intval($count_logs[$row['elementid']]) + $row['count'];
			}
		}
		$result = $adb->pquery("select * from {$table_prefix}_running_processes_logs where running_process = ? order by id", array($this->column_fields['running_process']));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$prev_log = '';
				if ($count_logs[$row['prev_elementid']] > 30) {
					if (!isset($count_occ[$row['prev_elementid']])) $count_occ[$row['prev_elementid']] = 0; else $count_occ[$row['prev_elementid']]++;
					
					if ($count_occ[$row['prev_elementid']] < 10) { // first 10 items
						$prev_log = $this->getProcessGraphLog('end', $row, $structure);
					} elseif (!isset($info[$row['prev_elementid']]['logs'][10])) {
						$other_label = sprintf(getTranslatedString('LBL_OTHER_X_LOGS','Processes'),($count_logs[$row['prev_elementid']] - 20));
						$prev_log = array('type'=>'etc','total'=>$count_logs[$row['prev_elementid']],'other_label'=>$other_label);
					} elseif (($count_logs[$row['prev_elementid']] - $count_occ[$row['prev_elementid']]) < 10) { // last 10 items
						$prev_log = $this->getProcessGraphLog('end', $row, $structure);
					}
				} else {
					$prev_log = $this->getProcessGraphLog('end', $row, $structure);
				}
				if (!empty($prev_log)) {
					if ($mode == 'history') {
						$prev_log['img'] = $this->getProcessGraphLogImg($row, $structure);
						$prev_log['text'] = $this->getProcessGraphLogText($prev_log, $structure);
						$prev_log['interval'] = $this->getProcessGraphLogInterval(end($logs), $prev_log);
						$prev_log['duration'] = $this->getProcessGraphLogDuration($prev_log);
						$logs[] = $prev_log;
					} else {
						$info[$row['prev_elementid']]['logs'][] = $prev_log;
					}
				}
				if ($mode == 'history') continue;
				
				$el_log = '';
				if ($count_logs[$row['elementid']] > 30) {
					if (!isset($count_occ[$row['elementid']])) $count_occ[$row['elementid']] = 0; else $count_occ[$row['elementid']]++;
					
					if ($count_occ[$row['elementid']] < 10) { // first 10 items
						$el_log = $this->getProcessGraphLog('start', $row, $structure);
					} elseif (!isset($info[$row['elementid']]['logs'][10])) {
						$other_label = sprintf(getTranslatedString('LBL_OTHER_X_LOGS','Processes'),($count_logs[$row['elementid']] - 20));
						$el_log = array('type'=>'etc','total'=>$count_logs[$row['elementid']],'other_label'=>$other_label);
					} elseif (($count_logs[$row['elementid']] - $count_occ[$row['elementid']]) < 10) { // last 10 items
						$el_log = $this->getProcessGraphLog('start', $row, $structure);
					}
				} else {
					$el_log = $this->getProcessGraphLog('start', $row, $structure);
				}
				if (!empty($el_log)) $info[$row['elementid']]['logs'][] = $el_log;
			}
		}
		
		// details
		foreach($vte_metadata as $elementid => $metadata) {
			$engineType = $PMUtils->getEngineType($structure['shapes'][$elementid]);
			if ($engineType == 'Action') {
				//crmv@112539
				$logElement = $PMUtils->getLogElement($this->column_fields['running_process'],$elementid);
				if (!empty($logElement)) {
					foreach($logElement as $i) {
						$action_arr = array('title'=>$i['action_title']);
						if (in_array($i['action_type'],array('Create','Update','Delete'))) {
							if (isPermitted($i['module'],'DetailView',$i['crmid']) == 'yes') {
								$action_arr['module'] = $i['module'];
								$action_arr['crmid'] = $i['crmid'];
								$action_arr['related_to_name'] = getEntityName($i['module'],$i['crmid'],true);
								$action_arr['related_to_module'] = getSingleModuleName($i['module']);
								$action_arr['related_to_url'] = 'index.php?module='.$i['module'].'&action=DetailView&record='.$i['crmid'];
								$action_arr['delete_perm'] = false;
								if ($i['action_type'] == 'Create') {
									//if (isPermitted($i['module'],'Delete',$i['crmid']) == 'yes') $action_arr['delete_perm'] = true;
									if (is_admin($current_user)) $action_arr['delete_perm'] = true;
								}
							}
						}
						$info[$elementid]['actions'][] = $action_arr;
					}
				//crmv@112539e
				// crmv@193096
				} elseif (!empty($metadata['actions'])) {
					foreach($metadata['actions'] as $action) {
						$info[$elementid]['actions'][] = array('title'=>$action['action_title']);
					}
				}
				// crmv@193096e
			} elseif ($engineType == 'Condition') {
				$condition_arr = array();
				$map = array(
					'ON_FIRST_SAVE'=>'LBL_ONLY_ON_FIRST_SAVE',
					'ON_EVERY_SAVE'=>'LBL_EVERYTIME_RECORD_SAVED',
					'ON_MODIFY'=>'LBL_ON_MODIFY',
					'ONCE'=>'LBL_UNTIL_FIRST_TIME_CONDITION_TRUE',
					'EVERY_TIME'=>'LBL_EVERY_TIME_TIME_CONDITION_TRUE',
					'ON_SUBPROCESS'=>'LBL_ON_SUBPROCESS',
					'MANUAL_MODE'=>'LBL_ON_MANUAL_MODE',
				);
				$condition_arr['execution_condition'] = sprintf($mod_strings['LBL_EXECUTION_CONDITION'], getTranslatedString($map[$metadata['execution_condition']],'Settings'));
				if (strpos($metadata['moduleName'],':') === false) {
					$module = $metadata['moduleName'];
					if (!empty($module)) $module = sprintf($mod_strings['LBL_CONDITION_ON_MODULE'], getTranslatedString($module,$module)).' ';
					$condition_arr['module'] = $module;
				} else {
					list($metaid,$module) = explode(':',$metadata['moduleName']);
					if ($module == 'DynaForm') {
						if (!empty($module)) $module = $mod_strings['LBL_CONDITION_ON'].' '.getTranslatedString('DynaForm').'. ';
						$condition_arr['module'] = $module;
					} else {
						$related_to = ProcessMakerEngine::getCrmid($metaid,$this->column_fields['running_process']);
						if (!empty($related_to)) {
							$condition_arr['related_to_name'] = getEntityName($module,$related_to,true);
							$condition_arr['related_to_module'] = getSingleModuleName($module);
							$condition_arr['related_to_url'] = 'index.php?module='.$module.'&action=DetailView&record='.$related_to;
						}
					}
				}
				$condition_arr['condition'] = $PMUtils->translateConditions($this->column_fields['processmaker'],$elementid,$this->column_fields['running_process'],$metadata);
				$info[$elementid]['condition'] = $condition_arr;
			} elseif ($engineType == 'SubProcess') {
				$result = $adb->pquery("SELECT {$table_prefix}_processes.processesid, {$table_prefix}_processes.process_name
					FROM {$table_prefix}_processes
					INNER JOIN {$table_prefix}_processmaker_rel rel ON rel.related = {$table_prefix}_processes.processmaker AND rel.related_role = ? AND rel.elementid = ?
					WHERE father = ?", array('son',$elementid,$this->id));
				if ($result && $adb->num_rows($result) > 0) {
					$link = 'index.php?module=Processes&action=DetailView&record='.$adb->query_result($result,0,'processesid');
					$name = $adb->query_result($result,0,'process_name');
				} else {
					$subprocess_data = $PMUtils->retrieve($this->column_fields['processmaker']);
					$name = "<b>{$subprocess_data['name']}</b>";
				}
				$info[$elementid]['subprocess']['str'] = $mod_strings['LBL_EXECUTION_SUBPROCESS'];
				$info[$elementid]['subprocess']['link'] = $link;
				$info[$elementid]['subprocess']['name'] = $name;
			} elseif ($engineType == 'Gateway') {
				$gateway = array();
				$show_required2go_check = false;
				$gatewayConditions = $PMUtils->getGatewayConditions($this->column_fields['processmaker'],$elementid,$metadata,$show_required2go_check,$this->column_fields['running_process']);
				if (!empty($gatewayConditions)) {
					foreach($gatewayConditions as $gatewayCondition) {
						if (!empty($gatewayCondition['conditions'])) {
							foreach($gatewayCondition['conditions'] as $condition) {
								if (!empty($condition['elementid'])) {
									$elementTitle = $PMUtils->getElementTitle($structure['shapes'][$condition['elementid']]);
									if ($condition['cond'] == 'cond_else') {
										$gateway[] = array('label'=>$condition['label'],'to'=>$elementTitle);
									} else {
										$gateway[] = array('label'=>gettranslatedString('LBL_FILTER_IF','Messages').' '.$condition['label'].' '.getTranslatedString('LBL_PM_GO_TO_NEXT_STEP','Settings'),'to'=>$elementTitle);
									}									
								}
							}
						}
					}
				}
				$info[$elementid]['gateway'] = $gateway;
			} elseif (in_array($engineType,array('TimerIntermediate','TimerBoundaryInterr','TimerBoundaryNonInterr'))) {
				$delay = array();
				if ($metadata['days']) $delay[] = $metadata['days'].' '.getTranslatedString('LBL_DAYS');
				if ($metadata['hours']) $delay[] = $metadata['hours'].' '.getTranslatedString('LBL_HOURS');
				if ($metadata['min']) $delay[] = $metadata['min'].' '.getTranslatedString('LBL_MINUTES');
				$info[$elementid]['delay']['str'] = getTranslatedString('LBL_PM_WAIT','Settings').' '.implode(' ',$delay);
			}
		}
		//crmv@OPER10174 crmv@169858
		foreach($helper as $elementid => $h) {
			if ($h['active'] == 'on') {
				$related_to = '';
				$related_to_name = '';
				$related_to_module = '';
				$related_to_url = '';
				$assigned_user_id = '';
				$username = '';
				if (!empty($h['related_to'])) {
					if (strpos($h['related_to'],':') !== false) list($metaid,$related_to_module) = explode(':',$h['related_to']);
					elseif (strpos($h['related_to'],'-') !== false) {
						list($metaid,$f) = explode('-',$h['related_to']);
						$metaid = str_replace('$','',$metaid);
					}
					$related_to = ProcessMakerEngine::getCrmid($metaid,$this->column_fields['running_process']);
					if (empty($related_to_module)) $related_to_module = getSalesEntityType($related_to);
				}
				$past_action = false;
				if (!empty($info[$elementid]['logs'])) {
					$logs = array_reverse($info[$elementid]['logs']);
					foreach($logs as $log) {
						if ($log['type'] == 'end') {
							$past_action = true;
							$assigned_user_id = $log['userid'];
							$username = $log['username'];
						}
						break;
					}
				}
				if (empty($assigned_user_id)) {
					if (is_numeric($h['assigned_user_id'])) {
						$assigned_user_id = $h['assigned_user_id'];
						$username = getUserName($assigned_user_id,true);
					} elseif (stripos($h['assigned_user_id'],'$ACTOR-') !== false) {
						$assigned_user_id = $PMUtils->getActor($this->column_fields['running_process'],$elementid);
						if (!empty($assigned_user_id)) {
							$username = getUserName($assigned_user_id,true);
						} else {
							$structure = $PMUtils->getStructure($this->column_fields['processmaker']);
							list($actor,$actor_elementid) = explode('$ACTOR-',$h['assigned_user_id']);
							$username = getTranslatedString('LBL_PM_PARTECIPANT_OF','Settings').' '.$PMUtils->getElementTitle($structure['shapes'][$actor_elementid]);
						}
					} elseif (stripos($h['assigned_user_id'],'$') === 0) { // ex. $8-assigned_user_id
						$assigned_user_id = '';
						$username = $h['assigned_user_id'];
					} else {
						list($meta_processid,$metaid,$module,$user_fieldname) = explode(':',$h['assigned_user_id']);
						(empty($meta_processid)) ? $running_process = $this->column_fields['running_process'] : $running_process = $PMUtils->getRelatedRunningProcess($this->column_fields['running_process'],$this->column_fields['processmaker'],$meta_processid);
						$crmid = ProcessMakerEngine::getCrmid($metaid,$running_process);
						if ($crmid !== false) {
							$entityFocus = CRMEntity::getInstance($module);
							$entityFocus->retrieve_entity_info($crmid,$module);
							$assigned_user_id = $entityFocus->column_fields[$user_fieldname];
							$username = getUserName($assigned_user_id,true);
						}
					}
				}
				(!$past_action) ? $action_label = $mod_strings['LBL_FUTURE_ACTION'] : $action_label = $mod_strings['LBL_PAST_ACTION'];
				if (!empty($related_to)) {
					$related_to_name = getEntityName($related_to_module,$related_to,true);
					$related_to_url = 'index.php?module='.$related_to_module.'&action=DetailView&record='.$related_to;
				}
				$info[$elementid]['process_helper'] = array(
					'related_to_name'=>$related_to_name,
					'related_to_module'=>getSingleModuleName($related_to_module),
					'related_to_url'=>$related_to_url,
					'description'=>$h['description'],
					'username'=>$username,
					'userimg'=>getUserAvatar($assigned_user_id),
					'action_label'=>$action_label
				);
			}
		}
		//crmv@OPER10174e crmv@169858e

		$executers = $PMUtils->getElementsExecutedByActors($this->column_fields['processmaker'], $this->column_fields['running_process']);	//crmv@103450
		
		//crmv@103450	crmv@112539
		$return = array(
			'processesid'=>$this->id,
			'processmaker'=>$this->column_fields['processmaker'],
			'running_process'=>$this->column_fields['running_process'],
			'current_elementid'=>$current,
			'xml_version'=>$xml_version,
			'info'=>$info,
			'executers'=>$executers
		);
		if ($mode == 'history') $return['logs'] = $logs;
		return $return;
		//crmv@103450e	crmv@112539e
	}
	function getProcessGraphLog($type, $row, $structure) {
		global $current_user;
		$PMUtils = ProcessMakerUtils::getInstance();
		$row['prev_elementid_title'] = $PMUtils->getElementTitle($structure['shapes'][$row['prev_elementid']]);
		$row['elementid_title'] = $PMUtils->getElementTitle($structure['shapes'][$row['elementid']]);
		$row['prev_elementid_text'] = $structure['shapes'][$row['prev_elementid']]['text'];
		$row['elementid_text'] = $structure['shapes'][$row['elementid']]['text'];
		$row['username'] = getUserName($row['userid'],true);
		$row['userfullname'] = getUserFullName($row['userid']);
		if (is_admin($current_user)) {
			$row['userlink'] = "index.php?module=Users&action=DetailView&record=".$row['userid'];
		}
		$row['userimg'] = getUserAvatar($row['userid']);
		$row['friendly_logtime'] = getFriendlyDate($row['logtime']);
		$row['time'] = $row['logtime'];
		$row['logtime'] = getDisplayDate($row['logtime']);
		$row['fulldate'] = $this->getFullDate($row['logtime']);
		$row['type'] = $type;
		return $row;
	}
	function getHTMLHistory() {
		$info = $this->getProcessGraphInfo('history');

		$smarty = new VteSmarty();
		$smarty->assign('PROCESSESID',$info['processesid']);
		$smarty->assign('PROCESSMAKER',$info['processmaker']);
		$smarty->assign('RUNNING_PROCESS',$info['running_process']);
		$smarty->assign('INFO',$info['info']);
		$smarty->assign('LOGS',$info['logs']);
		$smarty->display('modules/Processes/HistoryTab.tpl');
	}
	function getProcessGraphLogImg($row, $structure) {
		if ($structure['shapes'][$row['prev_elementid']]['type'] == 'StartEvent') {
			$vteicon = 'play_arrow';
		} elseif ($structure['shapes'][$row['elementid']]['type'] == 'EndEvent') {
			$vteicon = 'stop_arrow';
		} else {
			$vteicon = 'call_split';
		}
		return $vteicon;
	}
	function getProcessGraphLogText($row, $structure) {
		if ($structure['shapes'][$row['prev_elementid']]['type'] == 'StartEvent') {
			$text = getTranslatedString('LBL_LOG_TEXT_START','Processes');
		} elseif ($structure['shapes'][$row['elementid']]['type'] == 'EndEvent') {
			$text = getTranslatedString('LBL_LOG_TEXT_END','Processes');
		} else {
			$text = sprintf(getTranslatedString('LBL_LOG_HISTORY_STEP','Processes'),(!empty($row['prev_elementid_text']))?$row['prev_elementid_text']:$row['prev_elementid_title'],(!empty($row['elementid_text']))?$row['elementid_text']:$row['elementid_title']);
		}
		return $text;
	}
	function getProcessGraphLogInterval($prev, $current) {
		if (!empty($prev)) {
			return getFriendlyDate($prev['time'],strtotime($current['time']),'after',getTranslatedString('LBL_LOG_AUTOMATIC_EXECUTION','Processes'));
		}
	}
	function getProcessGraphLogDuration($current) {
		global $adb, $table_prefix;
		$result = $adb->limitPquery("select logtime from {$table_prefix}_running_processes_logs where running_process = ? and id > ? order by id",0,1,array($current['running_process'],$current['id']));
		if ($result && $adb->num_rows($result) > 0) {
			$logtime = $adb->query_result($result,0,'logtime');
			return getFriendlyDate($current['time'],strtotime($logtime),'none','0 '.getTranslatedString('lbl_seconds','ModComments'));
		} else {
			return getFriendlyDate($current['time'],null,'none','0 '.getTranslatedString('lbl_seconds','ModComments'));
		}
	}
	//crmv@96584e crmv@101506e crmv@150751e crmv@177598e crmv@188364e
	
	//crmv@97575
	function get_children($id, $cur_tab_id, $rel_tab_id, $actions=false) {

		global $currentModule, $app_strings, $current_user, $adb, $table_prefix;//crmv@203484 removed global singlepane

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		$parenttab = getParentTab();

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$button = '';

		// To make the edit or del link actions to return back to same view.
		if($singlepane_view == true) $returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";//crmv@203484 changed to normal bool true, not string 'true'
		else $returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$return_value = null;
		//crmv@47905
		$dependentFieldSql = $this->db->pquery("SELECT f.tabid,f.fieldname,f.columnname
			FROM {$table_prefix}_field f
			INNER JOIN {$table_prefix}_fieldmodulerel fr ON fr.fieldid = f.fieldid
			WHERE f.uitype = '10' AND fr.relmodule = ? AND fr.module = ?", array($currentModule, $related_module));
		$numOfFields = $this->db->num_rows($dependentFieldSql);
		//crmv@47905 e

		if($numOfFields > 0) {	
			$dependentColumn = $this->db->query_result($dependentFieldSql, 0, 'columnname');
			$dependentField = $this->db->query_result($dependentFieldSql, 0, 'fieldname');

			$button .= '<input type="hidden" name="'.$dependentColumn.'" id="'.$dependentColumn.'" value="'.$id.'">';
			$button .= '<input type="hidden" name="'.$dependentColumn.'_type" id="'.$dependentColumn.'_type" value="'.$currentModule.'">';
			if($actions) {
				$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions, $dependentField); // crmv@43864
			}

			if ($adb->isOracle()) {
				$query = "SELECT *"; //crmv@36534
			} else {
				$query = "SELECT ".$table_prefix."_crmentity.*, $other->table_name.*";
				$query .= ", CASE WHEN (".$table_prefix."_users.user_name is not null) THEN ".$table_prefix."_users.user_name ELSE ".$table_prefix."_groups.groupname END AS user_name";
			}

			$more_relation = '';
			if(!empty($other->related_tables)) {
				foreach($other->related_tables as $tname=>$relmap) {
					if ($tname == $this->table_name) continue; // crmv@43864
					$query .= ", $tname.*";

					// Setup the default JOIN conditions if not specified
					if(empty($relmap[1])) $relmap[1] = $other->table_name;
					if(empty($relmap[2])) $relmap[2] = $relmap[0];
					$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
				}
			}

			$query .= " FROM $other->table_name";
			$query .= " INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $other->table_name.$other->table_index";
			//crmv@24527
			if (!empty($other->customFieldTable)) {
				$query .= " INNER JOIN ".$other->customFieldTable[0]." ON $other->table_name.$other->table_index = ".$other->customFieldTable[0].".".$other->customFieldTable[1];
			}
			//crmv@24527e
			$query .= " INNER  JOIN $this->table_name processes_father ON $this->table_name.father = processes_father.processesid";

			$query .= $more_relation;
			$query .= " LEFT  JOIN ".$table_prefix."_users        ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
			if (!empty($other->groupTable) ){
				$query .= "	LEFT JOIN ".$other->groupTable[0]."
					ON ".$other->groupTable[0].".".$other->groupTable[1]." = $other->table_name.$other->table_index ";
				$query .= "	LEFT JOIN ".$table_prefix."_groups
					ON ".$other->groupTable[0].".groupname = ".$table_prefix."_groups.groupname ";
			}
			else {
				$query .= " LEFT  JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";
			}
			// crmv@64325
			$setypeCond = '';
			if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
				$setypeCond = "AND {$table_prefix}_crmentity.setype = '$related_module'";
			}
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 $setypeCond AND $this->table_name.father = $id";
			// crmv@64325e

			$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);
		}
		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}
	//crmv@97575e
	
	//crmv@100731
	function enableAllAdvancedPermissionsWidget() {
		global $adb, $table_prefix;
		$fldmod_result = $adb->pquery('SELECT relmodule FROM '.$table_prefix.'_fieldmodulerel WHERE fieldid=
			(SELECT fieldid FROM '.$table_prefix.'_field, '.$table_prefix.'_tab WHERE '.$table_prefix.'_field.tabid='.$table_prefix.'_tab.tabid AND fieldname=? AND name=? and '.$table_prefix.'_field.presence in (0,2))',
			Array('related_to', 'Processes'));
		for($index = 0; $index < $adb->num_rows($fldmod_result); ++$index) {
			$module = $adb->query_result($fldmod_result, $index, 'relmodule');
			$this->enableAdvancedPermissionsWidget($module);
		}
	}
	function enableAdvancedPermissionsWidget($module) {
		global $adb, $table_prefix;
		$moduleInstance = Vtecrm_Module::getInstance($module);
		if($moduleInstance) {
			$sequence = 0;
			$result = $adb->pquery("select max(sequence) as sequence from {$table_prefix}_links where tabid = ? and linktype = ?", array($moduleInstance->id,'DETAILVIEWWIDGET'));
			if ($result && $adb->num_rows($result) > 0) {
				$sequence = $adb->query_result($result,0,'sequence')+1;
			}
			$moduleInstance->addLink('DETAILVIEWWIDGET', 'DetailViewProcessesAdvPerm', "block://Processes:modules/Processes/Processes.php", '', $sequence, 'checkProcessesWidgetPermission:modules/Processes/widgets/CheckWidgetPermission.php'); //crmv@169362
		}
	}
	function getWidget($name) {
		if ($name == 'DetailViewProcessesAdvPerm' && isPermitted('Processes', 'DetailView') == 'yes') {
			require_once dirname(__FILE__) . '/widgets/DetailViewProcessesAdvPerm.php';
			return (new Processes_DetailViewProcessesAdvPerm());
		}
		return false;
	}
	//crmv@100731e
	
	//crmv@115579
	public function checkRetrieve($record, $module, $dieOnError = true) {
		if ($_REQUEST['action'] == 'Save' && !empty($_REQUEST['return_action'])) {
			$retrieve = parent::checkRetrieve($record, $module, false);
			if ($retrieve == 'LBL_RECORD_DELETE') {
				global $adb, $table_prefix;
				$result = $adb->pquery("SELECT processesid
					FROM {$table_prefix}_processes
					INNER JOIN {$table_prefix}_crmentity ON crmid = processesid
					WHERE deleted = 0 AND running_process = ?
					ORDER BY processesid", array(getSingleFieldValue($table_prefix.'_processes', 'running_process', 'processesid', $record)));
				if ($result && $adb->num_rows($result) > 0) {
					$new_record = $adb->query_result($result,0,'processesid');
					$url = "index.php?module=Processes&action=".$_REQUEST['return_action']."&parenttab=".$_REQUEST['parenttab'];
					if ($_REQUEST['return_action'] == 'DetailView') $url .= "&record=$new_record";
					die("<script>window.location.href='$url';</script>");
				}
			}
		}
		return parent::checkRetrieve($record, $module, $dieOnError);;
	}
	//crmv@115579e
	
	//crmv@150751
	function getProcessVersion($record) {
		global $adb, $table_prefix;
		$version = '';
		$result = $adb->pquery("select {$table_prefix}_processmaker_versions.version from {$table_prefix}_processes
		inner join {$table_prefix}_running_processes on {$table_prefix}_processes.running_process = {$table_prefix}_running_processes.id
		inner join {$table_prefix}_processmaker_versions on {$table_prefix}_processmaker_versions.processmakerid = {$table_prefix}_running_processes.processmakerid and {$table_prefix}_processmaker_versions.xml_version = {$table_prefix}_running_processes.xml_version
		where processesid=?", Array($record));
		if ($result && $adb->num_rows($result) > 0) {
			$version = $adb->query_result($result,0,'version');
		} else {
			$result = $adb->pquery("select version from {$table_prefix}_processmaker
			inner join {$table_prefix}_processes on {$table_prefix}_processes.processmaker = {$table_prefix}_processmaker.id
			where processesid = ?", array($record));
			$version = $adb->query_result($result,0,'version');
		}
		return $version;
	}
	//crmv@150751e
}
?>
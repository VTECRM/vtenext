<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Targets extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'targetsid';
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
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Target Name'=> Array('targets', 'targetname'),
		'Target Type'=> Array('targets', 'target_type'),
		'Target State'=> Array('targets', 'target_state'),
		'End Time'=> Array('targets', 'target_endtime'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Target Name'=> 'targetname',
		'Target Type'=> 'target_type',
		'Target State'=> 'target_state',
		'End Time'=> 'target_endtime',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'targetname';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Target Name'=> Array('targets', 'targetname'),
		'Target Type'=> Array('targets', 'target_type'),
		'Target State'=> Array('targets', 'target_state'),
		'End Time'=> Array('targets', 'target_endtime'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Target Name'=> 'targetname',
		'Target Type'=> 'target_type',
		'Target State'=> 'target_state',
		'End Time'=> 'target_endtime',
		'Assigned To' => 'assigned_user_id'
	);

	// For Popup window record selection
	var $popup_fields = Array('targetname');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'targetname';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'targetname';

	// Required Information for enabling Import feature
	var $required_fields = Array('targetname'=>1);

	var $default_order_by = 'targetname';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'targetname'); // crmv@177975
	//crmv@10759
	var $search_base_field = 'targetname';
	//crmv@10759 e
	function __construct() {
		global $log;
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_targets';
		$this->customFieldTable = Array($table_prefix.'_targetscf', 'targetsid');
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_targets', $table_prefix.'_targetscf');
		$this->tab_name_index = Array(
				$table_prefix.'_crmentity' => 'crmid',
				$table_prefix.'_targets'   => 'targetsid',
			    $table_prefix.'_targetscf' => 'targetsid');
		$this->column_fields = getColumnFields('Targets');
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}


	function save_module($module) {
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	// crmv@200009
	/**
	 * Get count of IDs
	 * @param $targetid
	 * @param $cvModule
	 * @param $cvid
	 * @return integer
	 */
	public function getCountList($targetid, $cvModule, $cvid)
	{
		return count($this->getCVListIds($targetid, $cvid, $cvModule));
	}

	/**
	 * Get count of IDs
	 * @param $targetid
	 * @param $cvModule
	 * @param $reportid
	 * @return integer
	 */
	public function getCountReport($targetid, $cvModule, $reportid)
	{
		return count($this->getReportListIds($targetid, $reportid, $cvModule));
	}
	// crmv@200009e

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {

			global $adb;
			global $table_prefix;
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));

			$targetsModule = Vtecrm_Module::getInstance($modulename);
			$accountsModule = Vtecrm_Module::getInstance('Accounts');
			$accountsModule->setRelatedList($targetsModule, 'Targets', Array(' '));
			$contactsModule = Vtecrm_Module::getInstance('Contacts');
			$contactsModule->setRelatedList($targetsModule, 'Targets', Array(' '));
			$leadsModule = Vtecrm_Module::getInstance('Leads');
			$leadsModule->setRelatedList($targetsModule, 'Targets', Array(' '));
			$campaignsModule = Vtecrm_Module::getInstance('Campaigns');
			$campaignsModule->setRelatedList($targetsModule, 'Targets', Array('ADD','SELECT'));

			$campaignsModule->unsetRelatedList(Vtecrm_Module::getInstance('Accounts'), 'Accounts', 'get_accounts');
			$campaignsModule->unsetRelatedList(Vtecrm_Module::getInstance('Contacts'), 'Contacts', 'get_contacts');
			$campaignsModule->unsetRelatedList(Vtecrm_Module::getInstance('Leads'), 'Leads', 'get_leads');

			$i=1;
			$adb->query("UPDATE ".$table_prefix."_relatedlists SET sequence = $i WHERE tabid = 26 AND label = 'Targets'");
			$res = $adb->query("SELECT * FROM ".$table_prefix."_relatedlists WHERE tabid = 26 AND label <> 'Targets' ORDER BY sequence");
			while($row=$adb->fetchByAssoc($res)) {
				$i++;
				$adb->pquery("UPDATE ".$table_prefix."_relatedlists SET sequence = $i WHERE relation_id = ?",array($row['relation_id']));
			}

			$this->setModuleSeqNumber('configure', 'Targets', 'TRG-', 1);
			
			//crmv@88671
			$result = $adb->pquery("SELECT relation_id, name FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = ?", array($targetsModule->id, $targetsModule->id));
			if ($result && $adb->num_rows($result) > 0) {
				$relation_id = $adb->query_result($result,0,'relation_id');
				$method = $adb->query_result($result,0,'name');
				SDK::setTurboliftCount($relation_id, $method);
			}
			//crmv@88671e

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
	
	// crmv@150024
	/**
	 * Loads a filter into the target
	 */
	public function loadCVList($targetid, $cvModule, $cvid, $setDynamicCv = true) {
		
		$ids = $this->getCVListIds($targetid, $cvid, $cvModule);

		// crmv@200009
		global $current_user;

		$VTEP = VTEProperties::getInstance();
		$limit = $VTEP->getProperty('loadrelations.limit');

		$howMuch = count($ids);
		if ($howMuch > 0){
			if($howMuch < $limit)
				$this->save_related_module('Targets', $targetid, $cvModule, $ids);
			else{
				$URtils = LoadRelationsUtils::getInstance();
				$URtils->enqueue($current_user->id, 'Targets', $targetid, $cvModule, $ids);
			}
		}
		// crmv@200009e
		
		// if the target is dynamic, set the relation
		if ($setDynamicCv) {
			$dynamic = getSingleFieldValue($this->table_name, 'target_type', $this->table_index, $targetid);
			if ($dynamic == 'TargetTypeDynamic') {
				$this->setDynamicCV($targetid, $cvid, $cvModule);
			}
		}
	}
	
	public function loadReportList($targetid, $reportModule, $reportid, $setDynamicCv = true) {
		
		$ids = $this->getReportListIds($targetid, $reportid, $reportModule);
		
		// crmv@200009
		global $current_user;

		$VTEP = VTEProperties::getInstance();
		$limit = $VTEP->getProperty('loadrelations.limit');

		$howMuch = count($ids);
		if ($howMuch > 0){
			if($howMuch < $limit)
				$this->save_related_module('Targets', $targetid, $reportModule, $ids);
			else{
				$URtils = LoadRelationsUtils::getInstance();
				$URtils->enqueue($current_user->id, 'Targets', $targetid, $reportModule, $ids);
			}
		}
		// crmv@200009e
		
		// if the target is dynamic, set the relation
		if ($setDynamicCv) {
			$dynamic = getSingleFieldValue($this->table_name, 'target_type', $this->table_index, $targetid);
			if ($dynamic == 'TargetTypeDynamic') {
				$this->setDynamicReport($targetid, $reportid, $reportModule);
			}
		}
	}
	
	public function getCVListIds($targetid, $cvid, $cvModule) {
		global $adb;
		
		$ids = array();
		
		$sql = $this->getCVListIdsQuery($targetid, $cvid, $cvModule);
		$res = $adb->query($sql);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$ids[] = intval($row['crmid']);
			}
		}
		
		return $ids;
	}
	
	public function getCVListIdsQuery($targetid, $cvid, $cvModule) {
		global $table_prefix, $adb, $current_user;
		
		$queryGenerator = QueryGenerator::getInstance($cvModule, $current_user);
		$queryGenerator->initForCustomViewById($cvid);
		$sql = $queryGenerator->getQuery();
		$sql = replaceSelectQuery($sql, $table_prefix.'_crmentity.crmid');
		
		return $sql;
	}
	
	public function getReportListIdsQuery($targetid, $reportid, $reportModule) {
		global $table_prefix, $adb, $current_user;
		
		$folderid = getSingleFieldValue($table_prefix.'_report', 'folderid', 'reportid', $reportid);
		$sdkrep = SDK::getReport($reportid, $folderid);
		if (!is_null($sdkrep)) {
			require_once($sdkrep['reportrun']);
			$oReportRun = new $sdkrep['runclass']($reportid);
		} else {
			require_once('modules/Reports/ReportRun.php');
			$oReportRun = ReportRun::getInstance($reportid);
		}
		$prefix = 0; // crmv@158088
		
		// crmv@108210
		$oReportRun->setCVInfo(array('module'=>$reportModule, 'prefix' => $prefix)); // crmv@158088
		$oReportRun->setReportTab("CV");
		$oReportRun->GenerateReport();
		// crmv@108210e
		
		//ho la tab temp ora devo fare un query per prendermi gli id
		$customview = CRMEntity::getInstance('CustomView');
		$join = $customview->getReportFilterJoin("{$table_prefix}_crmentity.crmid", $reportid, $current_user->id, $prefix);

		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND {$table_prefix}_crmentity.setype = '$reportModule'";
		}
		
		$sql = 
			"SELECT {$table_prefix}_crmentity.crmid
			FROM {$table_prefix}_crmentity
			$join
			WHERE {$table_prefix}_crmentity.deleted = 0 $setypeCond";
			
		return $sql;
	}
	
	public function getReportListIds($targetid, $reportid, $reportModule) {
		global $adb;
		
		$ids = array();
		
		$sql = $this->getReportListIdsQuery($targetid, $reportid, $reportModule);
		$res = $adb->query($sql);
		if ($res){
			while($row = $adb->fetchByAssoc($res,-1,false)){
				$ids[] = intval($row['crmid']);
			}
		}
		
		return $ids;
	}
	
	public function getDynamicCVList($targetid) {
		global $table_prefix, $adb;
		
		$list = array();
		$res = $adb->pquery(
			"SELECT tc.objectid, tc.formodule, c.viewname as objectname
			FROM {$table_prefix}_targets_cvrel tc
			INNER JOIN {$table_prefix}_customview c ON c.cvid = tc.objectid
			WHERE tc.targetid = ? AND tc.cvtype = ?",
			array($targetid, 'CustomView')
		);

		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res)) {
				if ($row['objectname'] == 'All') $row['objectname'] = getTranslatedString('LBL_ALL', 'APP_STRINGS');
				$list[] = $row;
			}
		}
		
		return $list;
	}
	
	public function getDynamicReportList($targetid) {
		global $table_prefix, $adb;
		
		$list = array();
		$res = $adb->pquery(
			"SELECT tc.objectid, tc.formodule, r.reportname as objectname
			FROM {$table_prefix}_targets_cvrel tc
			INNER JOIN {$table_prefix}_report r ON r.reportid = tc.objectid
			WHERE tc.targetid = ? AND tc.cvtype = ?",
			array($targetid, 'Report')
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res)) {
				$list[] = $row;
			}
		}
		
		return $list;
	}
	
	public function setDynamicCV($targetid, $objectid, $forModule, $type = 'CustomView') {
		global $table_prefix, $adb;
		
		$res = $adb->pquery("SELECT targetid FROM {$table_prefix}_targets_cvrel WHERE targetid = ? AND objectid = ? AND cvtype = ? AND formodule = ?", array($targetid, $objectid, $type, $forModule));
		if ($res && $adb->num_rows($res) == 0) {
			$params = array($targetid, $objectid, $type, $forModule);
			$adb->pquery("INSERT INTO {$table_prefix}_targets_cvrel (targetid, objectid, cvtype, formodule) VALUES (?,?,?,?)", $params);
		}
	}
	
	public function unsetDynamicCV($targetid, $objectid, $forModule, $type = 'CustomView') {
		global $table_prefix, $adb;
		
		$adb->pquery("DELETE FROM {$table_prefix}_targets_cvrel WHERE targetid = ? AND objectid = ? AND cvtype = ? AND formodule = ?", array($targetid, $objectid, $type, $forModule));
	}
	
	/**
	 * Called when a filter is deleted
	 */
	public function deleteDynamicCV($objectid, $type = 'CustomView') {
		global $table_prefix, $adb;
		
		$adb->pquery("DELETE FROM {$table_prefix}_targets_cvrel WHERE objectid = ? AND cvtype = ?", array($objectid, $type));
	}
	
	public function setDynamicReport($targetid, $reportid, $forModule) {
		return $this->setDynamicCV($targetid, $reportid, $forModule, 'Report');
	}
	
	public function unsetDynamicReport($targetid, $reportid, $forModule) {
		return $this->unsetDynamicCV($targetid, $reportid, $forModule, 'Report');
	}
	
	/**
	 * Called when a report is deleted
	 */
	public function deleteDynamicReport($reportid) {
		return $this->deleteDynamicCV($reportid, 'Report');
	}
	
	function getExtraDetailTabs() {
		
		$return = array(
			array('label'=>getTranslatedString('LBL_DYNAMIC_FILTERS','Targets'),'href'=>'','onclick'=>"showDynamicFilters(this)"),
		);
		
		$others = parent::getExtraDetailTabs() ?: array();

		return array_merge($return, $others);
	}
	
	function getExtraDetailBlock() {
		global $mod_strings, $app_strings, $current_user, $theme;
		
		$smarty = new VteSmarty();
		
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('MODULE', 'Targets');
		$smarty->assign('THEME', $theme);
		$smarty->assign('ID', $this->id);
		
		if ($this->id > 0) {
			$cvlist = $this->getDynamicCVList($this->id);
			$replist = $this->getDynamicReportList($this->id);

			$smarty->assign('CVLIST', $cvlist);
			$smarty->assign('REPLIST', $replist);
		}
		
		$html = $smarty->fetch('modules/Targets/DynamicFilters.tpl');
		
		return $html;
	}
	// crmv@150024e

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) {
		if (!is_array($with_crmid)) $with_crmid = array($with_crmid); // crmv@202577

		parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check);
		
		//crmv@52391
		if (isModuleInstalled('Fiere') && vtlib_isModuleActive('Fiere') && in_array($with_module,array('Leads','Contacts','Accounts')) && !empty($with_crmid)) {
			$fiereFocus = CRMEntity::getInstance('Fiere');
			foreach($with_crmid as $id) {
				$fiereFocus->create_fiera_to_entity($crmid,$id);
			}
		}
		if (isModuleInstalled('Telemarketing') && vtlib_isModuleActive('Telemarketing') && in_array($with_module,array('Leads','Contacts','Accounts')) && !empty($with_crmid)) {
			$tlmktFocus = CRMEntity::getInstance('Telemarketing');
			foreach($with_crmid as $id) {
				$tlmktFocus->create_tlmkt_to_entity($crmid,$id);
			}
		}
		//crmv@52391e		
	}

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	/* crmv@45027 */
	function delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse = true) { // crmv@146653
		parent::delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse); // crmv@146653
		if (in_array($with_module,array('Leads','Accounts','Contacts'))) {
			global $adb, $table_prefix;
			$withInstance = CRMEntity::getInstance($with_module);
			if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);
			foreach($with_crmid as $relcrmid) {
				$query = "DELETE FROM {$withInstance->relation_table} WHERE {$withInstance->relation_table_id} = ? AND {$withInstance->relation_table_otherid} = ?";
				$params = array($crmid, $relcrmid);
				if (!empty($withInstance->relation_table_module)) {
					$query .= " AND {$withInstance->relation_table_module} = ?";
					$params[] = $module;
				}
				if (!empty($withInstance->relation_table_othermodule)) {
					$query .= " AND {$withInstance->relation_table_othermodule} = ?";
					$params[] = $with_module;
				}
				$res = $adb->pquery($query, $params);
			}
		}
	}

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

	function get_related_list_target($id, $cur_tab_id, $rel_tab_id, $actions=false) {

		global $currentModule, $app_strings;//crmv@203484 removed global singlepane
		global $adb, $table_prefix,$theme; //crmv@36539 crmv@150024
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
		$singular_modname = vtlib_toSingular($related_module);

		$button = '';
		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('EMAIL', $actions)) {
				// Send mail button for selected elements
				$button .= "<input title='".getTranslatedString('LBL_SEND_MAIL_BUTTON')."' class='crmbutton small edit' value='".getTranslatedString('LBL_SEND_MAIL_BUTTON')."' type='button' name='button' onclick='rel_eMail(\"$currentModule\",this,\"$related_module\")'>&nbsp;&nbsp;";
			}
			if(in_array('LOAD', $actions)) {
				/* To get CustomView -START */
				require_once('modules/CustomView/CustomView.php');
				$ahtml = "<select id='".$related_module."_cv_list' class='small hide_turbolift'><option value='None'>-- ".getTranslatedString('Select One')." --</option>";	//crmv@64719
				$oCustomView = CRMEntity::getInstance('CustomView', $related_module); // crmv@115329
				$viewid = $oCustomView->getViewId($related_module);
				$customviewcombo_html = $oCustomView->getCustomViewCombo($viewid, false);
				$ahtml .= $customviewcombo_html;
				$ahtml .= "</select>";
				$ahtml .= '&nbsp;&nbsp;';
				/* To get CustomView -END */
				$button .= $ahtml."<input title='".getTranslatedString('LBL_LOAD_LIST',$currentModule)."' class='crmbutton small edit' value='".getTranslatedString('LBL_LOAD_LIST',$currentModule)."' type='button' name='button' onclick='loadCvListTargets(\"$related_module\",\"$id\")'>";
				$button .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			}
			// crmv@43864
			
			// crmv@150024
			// hide the select buttons if the sync is complete
			$res = $adb->pquery("SELECT target_type, target_sync_type FROM {$this->table_name} WHERE {$this->table_index} = ?", array($id));
			$targetType = $adb->query_result_no_html($res, 0, 'target_type');
			$syncType = $adb->query_result_no_html($res, 0, 'target_sync_type');
			
			if ($targetType == 'TargetTypeDynamic' && $syncType == 'TargetSyncComplete') {
				$button .= '<div style="display:inline-block;min-width:250px">&nbsp;</div>';
			} else {
				$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions);
			}
			// crmv@150024e
			
			//crmv@36539
			$add_button = '
			<div class="hide_turbolift">
				<input id="report'.$related_module.'" name="report'.$related_module.'" type="hidden" value="">
				<div class="dvtCellInfo" style="float:left;width:50%">
					<input id="report'.$related_module.'_display" name="report'.$related_module.'_display" type="text" value="'.getTranslatedString('LBL_SEARCH_STRING').'" class="detailedViewTextBox detailedViewReference" />
				</div>
				<script type="text/javascript">
					initAutocomplete(\'report'.$related_module.'\',\'report'.$related_module.'_display\',encodeURIComponent(\'module=Reports&action=ReportsAjax&file=AutocompleteRL&field=report'.$related_module.'&cvmodule='.$related_module.'\'));
				</script>
				<i class="vteicon md-link valign-bottom" title="'.getTranslatedString('LBL_SELECT').'" onclick=\'jQuery( this ).blur(); jQuery("#report'.$related_module.'_display").vteautocomplete("search","ALL");\'>view_list</i> <!-- crmv@200318 -->
				<i class="vteicon md-link valign-bottom" title="'.getTranslatedString('LBL_CLEAR').'" onClick="jQuery(\'#report'.$related_module.'\').val(\'\');jQuery(\'#report'.$related_module.'_display\').val(\'\'); enableReferenceField(jQuery(\'#report'.$related_module.'_display\')[0]); return false;">highlight_off</i>
				<i class="vteicon md-link valign-bottom" title="'.getTranslatedString('LBL_CREATE').'" onClick="popupReport_rl(\'new\',\''.$related_module.'\',jQuery(\'#report'.$related_module.'_display\').val(),\'report'.$related_module.'\');">add</i>
				<i class="vteicon md-link valign-bottom" title="'.getTranslatedString('LBL_EDIT').'" onClick="popupReport_rl(\'edit\',\''.$related_module.'\',jQuery(\'#report'.$related_module.'_display\').val(),\'report'.$related_module.'\');">create</i>
				<input type="button" onclick="loadReportListTargets(jQuery(\'#report'.$related_module.'\').val(),\''.$id.'\',\''.$related_module.'\')" name="button" value="'.getTranslatedString('LBL_LOAD_REPORT','Targets').'" class="crmbutton small edit" title="Carica Report">
			</div>';
			// crmv@43864e
			$button = "<table><tr><td nowrap>".$button."</td></tr><tr><td nowrap>".$add_button."</td></tr></table>";
			//crmv@36539 e
		}

		// To make the edit or del link actions to return back to same view.
		if($singlepane_view == true) $returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";//crmv@203484 changed to normal bool true, not string 'true'
		else $returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$query = "SELECT ".$table_prefix."_crmentity.crmid";
		//crmv@fix query
		foreach ($other->list_fields as $label=>$arr){
			foreach ($arr as $table=>$field){
				if ($table != 'crmentity' && !is_numeric($table) && $field){
					if (strpos($table,$table_prefix.'_') !== false)
						$query.=",$table.$field";
					else
						$query.=",".$table_prefix."_$table.$field";
				}
			}
		}
		//crmv@fix query end
		$query .= ", CASE WHEN (".$table_prefix."_users.user_name is not null) THEN ".$table_prefix."_users.user_name ELSE ".$table_prefix."_groups.groupname END AS user_name";

		$more_relation = '';
		if(!empty($other->related_tables)) {
			foreach($other->related_tables as $tname=>$relmap) {
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
		// crmv@161254 crmv@161515
		if (is_array($other->tab_name)) {
			foreach($other->tab_name as $tab_name){
				if (stripos($query,"join ".$tab_name) === false && stripos($query,"from ".$tab_name) === false && stripos($more_relation,"join ".$tab_name) === false) { // crmv@167298 crmv@171367
					$query .= " INNER JOIN ".$tab_name." ON ".$tab_name.".".$other->tab_name_index[$tab_name]." = $other->table_name.$other->table_index";
				}
			}
		}
		// crmv@161254e crmv@161515e
		if ($related_module == 'Products'){
			$query .= " INNER JOIN ".$table_prefix."_seproductsrel ON (".$table_prefix."_seproductsrel.crmid = ".$table_prefix."_crmentity.crmid OR ".$table_prefix."_seproductsrel.productid = ".$table_prefix."_crmentity.crmid)";
		}
		elseif ($related_module == 'Documents'){
			$query .= " INNER JOIN ".$table_prefix."_senotesrel ON (".$table_prefix."_senotesrel.notesid = ".$table_prefix."_crmentity.crmid OR ".$table_prefix."_senotesrel.crmid = ".$table_prefix."_crmentity.crmid)";
		}
		else {
			//$query .= " INNER JOIN ".$table_prefix."_crmentityrel ON (".$table_prefix."_crmentityrel.relcrmid = ".$table_prefix."_crmentity.crmid OR ".$table_prefix."_crmentityrel.crmid = ".$table_prefix."_crmentity.crmid)";
			$query .= " INNER JOIN ".$table_prefix."_crmentityrel ON ".$table_prefix."_crmentityrel.relcrmid = ".$table_prefix."_crmentity.crmid";
		}
		$query .= " LEFT  JOIN $this->table_name   ON $this->table_name.$this->table_index = ".$table_prefix."_crmentityrel.crmid";
		$query .= $more_relation;
		$query .= " LEFT  JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
		$query .= " LEFT  JOIN ".$table_prefix."_groups       ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";
		if ($related_module == 'Products'){
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 AND (".$table_prefix."_seproductsrel.crmid = $id OR ".$table_prefix."_seproductsrel.productid = $id)";
		}
		elseif ($related_module == 'Documents'){
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 AND (".$table_prefix."_senotesrel.crmid = $id OR ".$table_prefix."_senotesrel.notesid = $id)";
		}
		else {
			//$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 AND (".$table_prefix."_crmentityrel.crmid = $id OR ".$table_prefix."_crmentityrel.relcrmid = $id)";
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_crmentityrel.crmid = $id";
		}
		$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}

	function getFathers($include_myself=false) {
		$fathers = array();
		if ($include_myself) {
			$fathers[] = $this->id;
		}
		$this->get_father($fathers,$this->id);
		return $fathers;
	}

	function get_father($fathers,$id) {
		global $adb;
		global $table_prefix;
		$result = $adb->pquery("select crmid from ".$table_prefix."_crmentityrel_ord WHERE module = 'Targets' AND relmodule = 'Targets' AND relcrmid = ?", array($id)); // crmv@125816 crmv@208173
		if ($result && $adb->num_rows($result)>0) {
			$father = $adb->query_result($result,0,'crmid');
			$fathers[] = $father;
			$this->get_father($fathers,$father);
		}
	}

	function getChildren() {
		global $adb;
		global $table_prefix;
		$children = array();
		$result = $adb->query("select relcrmid from ".$table_prefix."_crmentityrel_ord WHERE module = 'Targets' AND relmodule = 'Targets' AND crmid = $this->id"); // crmv@125816
		if ($result && $adb->num_rows($result)>0) {
			while($row=$adb->fetchByAssoc($result)) {
				$children[] = $row['relcrmid'];
			}
			return $children;
		}
	}
}
?>
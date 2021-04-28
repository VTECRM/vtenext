<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class MyNotes extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'mynotesid';
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
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Subject'=> 'subject',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'subject';

	// For Popup listview and UI type support
	var $search_fields = Array();
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Subject'=> 'subject'
	);

	// For Popup window record selection
	var $popup_fields = Array('subject');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'subject';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'subject';

	// Required Information for enabling Import feature
	var $required_fields = Array('subject'=>1);

	var $default_order_by = 'modifiedtime';
	var $default_sort_order='DESC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'subject');
	//crmv@10759
	var $search_base_field = 'subject';
	//crmv@10759 e
	
	var $skip_modules = array('Calendar','Events','Emails','Fax','Sms','ModComments','Charts','MyFiles','MyNotes','Messages'); // crmv@164120 crmv@164122
	var $only_widget_modules = array('Calendar','Events');

	function __construct() {
		global $log, $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_mynotes';
		$this->customFieldTable = Array($table_prefix.'_mynotescf', 'mynotesid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_mynotes', $table_prefix.'_mynotescf');
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity' => 'crmid',
			$table_prefix.'_mynotes'   => 'mynotesid',
			$table_prefix.'_mynotescf' => 'mynotesid'
		);
		$this->list_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Subject'=> Array($table_prefix.'_mynotes', 'subject'),
			'Assigned To' => Array($table_prefix.'_crmentity','smownerid')
		);
		$this->search_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Subject'=> Array($table_prefix.'_mynotes', 'subject')
		);
		$this->column_fields = getColumnFields(get_class()); //crmv@146187

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

	function getQueryExtraWhere() {
		global $current_user, $currentModule; // crmv@193097
		//crmv@120523 crmv@146221
		$sql = '';
		$noteid = intval($_REQUEST['record']);
		if ($_REQUEST['action'] == 'SimpleView' || $_REQUEST['file'] == 'SimpleListViewAjax') {	// in list view always just mine
			if ($noteid > 0) {
				// here is not a problem to have the OR, since the visibility restriction are done before
				$sql = " and ({$this->entity_table}.smownerid = $current_user->id OR {$this->entity_table}.crmid = $noteid)";
			} else {
				$sql = " and {$this->entity_table}.smownerid = $current_user->id";
			}
		} else {
			$defSharingPermissionData = getDefaultSharingAction();
			$tabid = getTabid('MyNotes');
			$this_mod_share = $defSharingPermissionData[$tabid];
			if($this_mod_share == 3){
				$sql = " and {$this->entity_table}.smownerid = $current_user->id";
			}
			
			if ($noteid > 0 && $currentModule != 'Reports') { // crmv@193097e
				$sql .= " AND {$this->entity_table}.crmid = $noteid";
			}
		}
		//crmv@120523e crmv@146221e
		return $sql;
	}
	
	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb,$table_prefix;
		if($event_type == 'module.postinstall') {
		
			$adb->pquery("UPDATE {$table_prefix}_tab SET customized=0 WHERE name=?", array($modulename));

			// crmv@164120 - removed code
			
			$em = new VTEventsManager($adb);
			$em->registerHandler('vte.entity.beforesave', "modules/{$modulename}/{$modulename}Handler.php", "{$modulename}Handler");//crmv@207852
			
			SDK::setLanguageEntries('APP_STRINGS', 'MyNotes', array('it_it'=>'Note','en_us'=>'Notes'));
			SDK::setMenuButton('fixed','MyNotes',"openPopup('index.php?module=MyNotes&action=SimpleView');",'description','', '', 'checkPermissionSDKButton:modules/MyNotes/widgets/Utils.php'); // crmv@174672
			
			$this->addWidgetToAll();
			
			// reload tabdata and other files to prevent errors in migrateNotebook2MyNotes
			$tmp_skip_recalculate = VteSession::get('skip_recalculate');
			VteSession::set('skip_recalculate', 0);
			Vtecrm_Access::syncSharingAccess();
			Vtecrm_Menu::syncfile();
			Vtecrm_Module::syncfile();
			VteSession::set('skip_recalculate', $tmp_skip_recalculate);
			
			$this->migrateNotebook2MyNotes();

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
	
	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) {
		return parent::save_related_module($module, $crmid, $with_module, $with_crmid, true);
	}
		
	static function getWidget($name) {
		if ($name == 'DetailViewMyNotesWidget' &&
				isPermitted('MyNotes', 'DetailView') == 'yes') {
			require_once dirname(__FILE__) . '/widgets/DetailViewMyNotesWidget.php';
			return (new MyNotes_DetailViewMyNotesWidget());
		}
		return false;
	}

	// crmv@106527
	function moduleHasNotes($module) {
		global $adb, $table_prefix;
		$tabid = getTabid($module);
		$res = $adb->pquery("SELECT linkid FROM {$table_prefix}_links WHERE tabid = ? AND linktype = ? AND linkurl = ?", array($tabid, 'DETAILVIEWWIDGET', 'block://MyNotes:modules/MyNotes/MyNotes.php'));
		return ($res && $adb->num_rows($res) > 0);
	}
	// crmv@106527e
	
	function addWidgetToAll() {
		global $adb,$table_prefix;
		$result = $adb->pquery('SELECT name FROM '.$table_prefix.'_tab WHERE isentitytype = 1 AND name NOT IN ('.generateQuestionMarks($this->skip_modules).')',$this->skip_modules);
		if ($result && $adb->num_rows($result) > 0) {
			$modcomm_module = array();
			while($row=$adb->fetchByAssoc($result)) {
				$modcomm_module[] = $row['name'];
			}
			$this->addWidgetTo($modcomm_module);
		}
		if (!empty($this->only_widget_modules)) {
			$this->addWidgetTo($this->only_widget_modules,true);
		}
	}
	
	function addWidgetTo($moduleNames, $onlyWidget=false, $widgetType='DETAILVIEWWIDGET', $widgetName='DetailViewMyNotesWidget') {
		if (empty($moduleNames)) return;
		
		global $adb, $table_prefix;
		include_once 'vtlib/Vtecrm/Module.php';
		
		$currentModuleInstance = Vtecrm_Module::getInstance('MyNotes');
		
		if (is_string($moduleNames)) $moduleNames = array($moduleNames);
		foreach($moduleNames as $moduleName) {
			$module = Vtecrm_Module::getInstance($moduleName);
			if($module) {
				$module->addLink($widgetType, $widgetName, "block://MyNotes:modules/MyNotes/MyNotes.php");
				if ($onlyWidget) continue;
				$check = $adb->pquery("SELECT * FROM ".$table_prefix."_relatedlists WHERE tabid=? AND related_tabid=? AND name=? AND label=?", 
					Array($currentModuleInstance->id, $module->id, 'get_related_list', $moduleName));
				if ($check && $adb->num_rows($check) > 0) {
					// do nothing
				} else {					
					$currentModuleInstance->setRelatedList($module, $moduleName, Array('SELECT','ADD'), 'get_related_list');
				}
			}
		}
	}
	
	/* 
	 * crmv@56114 if private mode I only my notes
	 * crmv@68000
	*/
	function getRelNotes($crmid,$limit='') {
		global $adb, $table_prefix, $current_user;
		$return = array();
		
		$parentModule = getSalesEntityType($crmid);
		if ($parentModule == 'Documents' || $parentModule == 'Products') {
			$relatedInstance = CRMEntity::getInstance($parentModule);
		} else {
			$relatedInstance = null;
		}
		if (!empty($relatedInstance)) {
			$relationTab = $relatedInstance->relation_table;
			$relationId = $relatedInstance->relation_table_id;
			$relationIdOther = $relatedInstance->relation_table_otherid;
			$relationModule = $relatedInstance->relation_table_othermodule;
		} else {
			$relationTab = "{$table_prefix}_crmentityrel";
			$relationId = 'relcrmid';
			$relationIdOther = 'crmid';
			$relationModule = 'relmodule';
		}
		
		$query = "SELECT {$table_prefix}_mynotes.mynotesid
					FROM {$table_prefix}_mynotes
					INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_mynotes.mynotesid = {$table_prefix}_crmentity.crmid
					INNER JOIN $relationTab ON {$table_prefix}_mynotes.mynotesid = {$relationTab}.{$relationIdOther}
					INNER JOIN {$table_prefix}_crmentity relEntity ON {$relationTab}.{$relationId} = relEntity.crmid
					WHERE {$table_prefix}_crmentity.deleted = 0 AND {$relationTab}.{$relationId} = ?";
		$params = array($crmid);
		
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		if (empty($defaultOrgSharingPermission)) $defaultOrgSharingPermission = getAllDefaultSharingAction();
		if($defaultOrgSharingPermission[getTabid('MyNotes')] == 3) {
			$query .= " AND {$table_prefix}_crmentity.smownerid = ?";
			$params[] = $current_user->id;
		}
		
		$query .= " ORDER BY {$table_prefix}_crmentity.modifiedtime desc";
		
		if (!empty($limit)) {
			$result = $adb->limitPquery($query,0,$limit,$params);
		} else {
			$result = $adb->pquery($query,$params);
		}
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$return[] = $row['mynotesid'];
			}
		}
		return $return;
	}
	
	function getDetailViewNavigation($crmid) {
		$rel_notes = $this->getRelNotes($crmid);
		if (!empty($rel_notes)) {
			$current = array_search($this->id,$rel_notes);
			$str_current = ($current+1);
			$total = count($rel_notes);
			if ($total > 1) {
				$string = $str_current.' '.getTranslatedString('LBL_LIST_OF').' '.$total;
				$prev = $rel_notes[$current-1];
				$succ = $rel_notes[$current+1];
			}
		}
		return array($string,$prev,$succ);
	}
	
	function migrateNotebook2MyNotes() {
		global $adb, $table_prefix;
		if(Vtecrm_Utils::CheckTable($table_prefix.'_notebook_contents')) {
			$query = "SELECT {$table_prefix}_notebook_contents.userid, stufftitle, contents
						FROM {$table_prefix}_homestuff
						INNER JOIN {$table_prefix}_notebook_contents ON {$table_prefix}_homestuff.stuffid = {$table_prefix}_notebook_contents.notebookid
						WHERE stufftype = ?";
			$result = $adb->pquery($query,array('Notebook'));
			$num_notebooks = $adb->num_rows($result);
			$num_mynotes = 0;
			if ($result && $num_notebooks > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$focus = CRMEntity::getInstance('MyNotes');
					$focus->column_fields['assigned_user_id'] = $row['userid'];
					$focus->column_fields['subject'] = $row['stufftitle'];
					$focus->column_fields['description'] = $row['contents'];
					$focus->save('MyNotes');
					if (!empty($focus->id)) {
						$num_mynotes++;
					}
				}
			}
			if ($num_mynotes == $num_notebooks) {
				$adb->pquery("delete from {$table_prefix}_homestuff where stufftype = ?",array('Notebook'));
				$sqlarray = $adb->datadict->DropTableSQL($table_prefix.'_notebook_contents');
				$adb->datadict->ExecuteSQLArray($sqlarray);
			}
		}
	}
	
	/* crmv@53684 crmv@56114 */
	function getAdvancedPermissionFunction($is_admin,$module,$actionname,$record_id) {
		if (!empty($record_id)) {
			global $current_user;
			
			require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
			if (empty($defaultOrgSharingPermission)) $defaultOrgSharingPermission = getAllDefaultSharingAction();
			if ($defaultOrgSharingPermission[getTabid('MyNotes')] != 3 && in_array($actionname, array('DetailView', 'SimpleView'))) { // crmv@171029
				return '';
			}
			
			$recordOwnerArr=getRecordOwnerId($record_id);
			foreach($recordOwnerArr as $type=>$id)
			{
				$recOwnType=$type;
				$recOwnId=$id;
			}
			if($current_user->id != $recOwnId) {
				return 'no';
			}
		}
	}
}
?>
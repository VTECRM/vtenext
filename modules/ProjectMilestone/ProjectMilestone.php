<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class ProjectMilestone extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'projectmilestoneid';
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
		'Project Milestone Name'=> Array('projectmilestone', 'projectmilestonename'),
		'Milestone Date' => Array ('projectmilestone', 'projectmilestonedate'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Project Milestone Name'=> 'projectmilestonename',
		'Milestone Date' => 'projectmilestonedate',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'projectmilestonename';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Project Milestone Name'=> Array('projectmilestone', 'projectmilestonename'),
		'Milestone Date' => Array ('projectmilestone', 'projectmilestonedate'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Project Milestone Namee'=> 'projectmilestonename',
		'Milestone Date' => 'projectmilestonedate',
		'Assigned To' => 'assigned_user_id'
	);

	// For Popup window record selection
	var $popup_fields = Array('projectmilestonename');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'projectmilestonename';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'projectmilestonename';

	// Required Information for enabling Import feature
	var $required_fields = Array('projectmilestonename'=>1);

	var $default_order_by = 'projectmilestonedate';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'projectmilestonename', 'projectid'); // crmv@177975
	
	//crmv@10759
	var $search_base_field = 'projectmilestonename';
	//crmv@10759 e	
	
	function __construct() {
		global $log;
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_projectmilestone';
		$this->customFieldTable = Array($table_prefix.'_projectmilestonecf', 'projectmilestoneid');
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_projectmilestone', $table_prefix.'_projectmilestonecf');
		$this->tab_name_index = Array(
		$table_prefix.'_crmentity' => 'crmid',
		$table_prefix.'_projectmilestone'   => 'projectmilestoneid',
	    $table_prefix.'_projectmilestonecf' => 'projectmilestoneid');
		
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

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {
			global $adb,$table_prefix;
			$projectmilestoneTabid = getTabid($modulename);
			
			//crmv@18829
			include_once('vtlib/Vtecrm/Module.php');//crmv@207871
			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			$docModuleInstance = Vtecrm_Module::getInstance('Documents');
			$docModuleInstance->setRelatedList($moduleInstance,'Project Milestones',array('select','add'),'get_documents_dependents_list');
			//crmv@18829e
			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));

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
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }
	
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
}
?>
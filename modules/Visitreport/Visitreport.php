<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Visitreport extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'visitreportid';
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
		'Visitreport Name'=> Array('visitreport', 'visitreportname'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Visitreport Name'=> 'visitreportname',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'visitreportname';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Visitreport Name'=> Array('visitreport', 'visitreportname')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Visitreport Name'=> 'visitreportname'
	);

	// For Popup window record selection
	var $popup_fields = Array('visitreportname');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'visitreportname';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'visitreportname';

	// Required Information for enabling Import feature
	var $required_fields = Array('visitreportname'=>1);

	var $default_order_by = 'visitreportname';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'visitreportname'); //  crmv@177975
	//crmv@10759
	var $search_base_field = 'visitreportname';
	//crmv@10759 e
	function __construct() {
		global $log;
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_visitreport';
		$this->customFieldTable = Array($table_prefix.'_visitreportcf', 'visitreportid');
 		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_visitreport', $table_prefix.'_visitreportcf');
		$this->tab_name_index = Array(
		$table_prefix.'_crmentity' => 'crmid',
		$table_prefix.'_visitreport'   => 'visitreportid',
	    $table_prefix.'_visitreportcf' => 'visitreportid');
		$this->column_fields = getColumnFields(get_class()); //crmv@146187
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	function save_module($module){
		//module specific save
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	//crmv@43864

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $table_prefix;
		if($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			include_once('vtlib/Vtecrm/Module.php');//crmv@207871
			include_once('vtlib/Vtecrm/Menu.php');//crmv@207871

			$moduleInstance=Vtecrm_Module::getInstance($modulename);
			
			$accountmodule=Vtecrm_Module::getInstance('Accounts');
			$accountmodule->setRelatedList($moduleInstance, $modulename, Array('ADD'),'get_dependents_list');
			
			$contactsModule=Vtecrm_Module::getInstance('Contacts');
			$contactsModule->setRelatedList($moduleInstance, $modulename, Array('ADD'),'get_dependents_list');
			
			$leadsModule=Vtecrm_Module::getInstance('Leads');
			$leadsModule->setRelatedList($moduleInstance, $modulename, Array('ADD'),'get_dependents_list');
			
			//crmv@18830
			$productmodule=Vtecrm_Module::getInstance('Products');
			$productmodule->setRelatedList($moduleInstance, $modulename, Array('ADD','SELECT'),'get_related_list');
			//crmv@18830e
			/*
			$accountmodule->addLink(
							'DETAILVIEWBASIC',
							'LBL_ADD_VISITREPORT',
							'index.php?module=Visitreport&action=EditView&return_module=$MODULE$&return_action=$ACTION$&return_id=$RECORD$&accountid=$RECORD$&parenttab=Sales',
							'vteicon:note_add'
							);
			*/
			global $adb;
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
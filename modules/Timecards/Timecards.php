<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Timecards extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'timecardsid';
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
		'sortOrder'=> Array('timecards', 'sortorder'),
		'TCDate'=> Array('timecards', 'workdate'),
		'TCUnits'=> Array('timecards', 'tcunits'),
		'TCTime'=> Array('timecards', 'worktime'),
		'TCType'=> Array('timecards', 'timecardtype'),
		'Product Name'=> Array('products', 'productname'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'sortOrder'=> 'sortorder',
		'TCDate'=> 'workdate',
		'TCUnits'=> 'tcunits',
		'TCTime'=> 'worktime',
		'TCType'=> 'timecardtype',
		'Product Name'=> 'productname',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'sortorder';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'sortOrder'=> Array('timecards', 'sortorder'),
		'TCDate'=> Array('timecards', 'workdate'),
		'TCUnits'=> Array('timecards', 'tcunits'),
		'TCTime'=> Array('timecards', 'worktime'),
		'TCType'=> Array('timecards', 'timecardtype'),
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'sortOrder'=> 'sortorder',
		'TCDate'=> 'workdate',
		'TCUnits'=> 'tcunits',
		'TCTime'=> 'worktime',
		'TCType'=> 'timecardtype',
	);

	// For Popup window record selection
	var $popup_fields = Array('sortorder');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'title';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'sortorder';

	// Required Information for enabling Import feature
	var $required_fields = Array();

	var $default_order_by = 'sortorder';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'workdate'); // crmv@177975
	//crmv@10759
	var $search_base_field = 'sortorder';
	//crmv@10759 e		
	function __construct() {
		global $log;
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_timecards';
		$this->customFieldTable = Array($table_prefix.'_timecardscf', 'timecardsid');
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_timecards', $table_prefix.'_timecardscf');
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity' => 'crmid',
			$table_prefix.'_timecards'   => 'timecardsid',
	    	$table_prefix.'_timecardscf' => 'timecardsid');
		$this->column_fields = getColumnFields(get_class()); //crmv@146187
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	function save_module($module) {
        global $adb, $table_prefix;

        // update sortorder on create
        if ($this->mode != 'edit') { // crmv@178802
			if(!empty($this->column_fields['ticket_id'])){ //crmv@60507
				$srtordsql = 'SELECT max(sortorder) as max FROM '.$table_prefix.'_timecards WHERE ticket_id='.$this->column_fields['ticket_id'];
				$rstsrtord = $adb->query($srtordsql);
				$sorder = $adb->query_result($rstsrtord,0,'max');
			} //crmv@60507
            $sorder =(is_null($sorder) ? 0 : $sorder)+1;
            $rstsrtord = $adb->query("update ".$table_prefix."_timecards set sortorder=$sorder where timecardsid=".$this->id);
        }

        // if ticketstatus != 'Maintain', change troubleticket status and add to update info
        $reportsTo = intval($this->column_fields['newresp']); //crmv@144936 crmv@145669
        if ($this->column_fields['ticketstatus'] != 'Maintain' || $reportsTo > 0) {
			// Get all the orignal values
			$fHD = CRMEntity::getInstance('HelpDesk');
			$fHD->id = $this->column_fields['ticket_id'];
			$fHD->retrieve_entity_info_no_html($this->column_fields['ticket_id'],"HelpDesk");
			$fHD->name = $fHD->column_fields['ticket_title'];
			$fHD->mode = 'edit';
			
			// Change those that we have been told to change     
			if ($this->column_fields['ticketstatus']!='Maintain') $fHD->column_fields['ticketstatus'] = $this->column_fields['ticketstatus'];

			if ($reportsTo > 0) {
				$fHD->column_fields['assigned_user_id'] = $reportsTo;
				$fHD->column_fields['assigntype'] = 'U';
			}
			
			// Empty Comments
			$_REQUEST['comments'] = '';
			$fHD->column_fields['comments'] = '';
			
			// Save changes
			$fHD->save("HelpDesk");
		} 
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
	function vtlib_handler($moduleName, $event_type) {
		global $table_prefix;
		require_once('include/utils/utils.php');
		if($event_type == 'module.postinstall') {
			require_once('vtlib/Vtecrm/Module.php');
			$moduleInstance = Vtecrm_Module::getInstance($moduleName);			
			global $adb;
			include('modules/Timecards/addTimecards.php');
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			//crmv@14132
			if ($this->version == '5.1.1') {
				global $adb;
				$moduleInstance = Vtecrm_Module::getInstance($moduleName);
				$adb->pquery("UPDATE ".$table_prefix."_relatedlists SET actions='ADD' WHERE tabid = 13 AND related_tabid = ?",Array($moduleInstance->id));
				require_once("modules/Update/Update.php");
				Update::change_field($table_prefix.'_timecards','worktime','C','5');
			}
			//crmv@14132e
		}
	}
	
	//crmv@14132	//crmv@16373
	function unlinkRelationship($id, $return_module, $return_id) {
		global $adb;
		// crmv@154211
		if ($return_module === 'HelpDesk') {
			$this->trash($module, $id);
		} else {
			parent::unlinkRelationship($id, $return_module, $return_id);
		}
		// crmv@154211e
	}
	function trash($module, $id) {
		CRMEntity::trash($module, $id);
    	$this->decrement_helpdesk_time();
	}
	function decrement_helpdesk_time() {
		global $adb;
		global $table_prefix;
		//tolgo il tempo dell'intervento all'ammontare del ticket
		$change_sec = $this->get_seconds($this->column_fields['worktime']);
		$change_hours = $change_sec/60/60;
		$change_days = $change_hours/24;
		if ($this->column_fields['ticket_id'] != '') {
			$tktFocus = CRMEntity::getInstance('HelpDesk');
			$tktFocus->id = $this->column_fields['ticket_id'];
			$tktFocus->retrieve_entity_info_no_html($this->column_fields['ticket_id'],'HelpDesk');
			$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET hours = ? WHERE ticketid = ?', array($tktFocus->column_fields['hours']-$change_hours,$tktFocus->id));
			$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET days = ? WHERE ticketid = ?', array($tktFocus->column_fields['days']-$change_days,$tktFocus->id));
		}
	}
	function get_seconds($val) {
		if ($val != '') {
			$tmp = explode(':',$val);
			return $tmp[0]*3600+$tmp[1]*60;
		}
		else
			return 0;
	}
	//crmv@14132e	//crmv@16373e

	/** 
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function save_related_module($module, $crmid, $with_module, $with_crmid) { }
	
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
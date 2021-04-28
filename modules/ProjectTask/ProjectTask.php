<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class ProjectTask extends CRMEntity {
    var $db, $log; // Used in class functions of CRMEntity

    var $table_name;
    var $table_index= 'projecttaskid';
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
        'Project Task Name'=> Array('projecttask', 'projecttaskname'),
        'Start Date'=> Array('projecttask', 'startdate'),
        'End Date'=> Array('projecttask', 'enddate'),
        'Progress'=>Array('projecttask','projecttaskprogress'),
        'Assigned To' => Array('crmentity','smownerid')
        
    );
    var $list_fields_name = Array(
        /* Format: Field Label => fieldname */
        'Project Task Name'=> 'projecttaskname',
        'Start Date'=>'startdate',
        'End Date'=> 'enddate',
        'Progress'=>'projecttaskprogress',
        'Assigned To' => 'assigned_user_id'
    );

    // Make the field link to detail view from list view (Fieldname)
    var $list_link_field = 'projecttaskname';

    // For Popup listview and UI type support
    var $search_fields = Array(
        /* Format: Field Label => Array(tablename, columnname) */
        // tablename should not have prefix 'vte_'
        'Project Task Name'=> Array('projecttask', 'projecttaskname'),
        'Start Date'=> Array('projecttask', 'startdate'),
        'Assigned To' => Array('crmentity','smownerid')
    );
    var $search_fields_name = Array(
        /* Format: Field Label => fieldname */
        'Project Task Name'=> 'projecttaskname',
        'Start Date'=>'startdate',
        'Assigned To' => 'assigned_user_id'
    );

    // For Popup window record selection
    var $popup_fields = Array('projecttaskname');

    // Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
    var $sortby_fields = Array();

    // For Alphabetical search
    var $def_basicsearch_col = 'projecttaskname';

    // Column value to use on detail view record text display
    var $def_detailview_recname = 'projecttaskname';

    // Required Information for enabling Import feature
    var $required_fields = Array('projecttaskname'=>1);

    var $default_order_by = 'projecttaskname';
    var $default_sort_order='ASC';
    // Used when enabling/disabling the mandatory fields for the module.
    // Refers to vte_field.fieldname values.
    var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'projecttaskname', 'projectid'); // crmv@177975
	
	//crmv@10759
	var $search_base_field = 'projecttaskname';
	//crmv@10759 e
    
    function __construct() {
		global $table_prefix;
        global $log;
		parent::__construct(); // crmv@37004
        $this->table_name = $table_prefix.'_projecttask';
		$this->customFieldTable = Array($table_prefix.'_projecttaskcf', 'projecttaskid');
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_projecttask', $table_prefix.'_projecttaskcf');
		$this->tab_name_index = Array(
        $table_prefix.'_crmentity' => 'crmid',
        $table_prefix.'_projecttask'   => 'projecttaskid',
        $table_prefix.'_projecttaskcf' => 'projecttaskid');
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
			global $adb;
			global $table_prefix;
			$projecttaskTabid = getTabid($modulename);
			
			//crmv@18829
			include_once('vtlib/Vtecrm/Module.php');//crmv@207871
			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			$docModuleInstance = Vtecrm_Module::getInstance('Documents');
			$docModuleInstance->setRelatedList($moduleInstance,'Project Tasks',array('select','add'),'get_documents_dependents_list');
			//crmv@18829e
			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));

			$modcommentsModuleInstance = Vtecrm_Module::getInstance('ModComments');
			if($modcommentsModuleInstance) {
				include_once 'modules/ModComments/ModComments.php';
				if(class_exists('ModComments')) ModComments::addWidgetTo(array('ProjectTask'));
			}
			
			//crmv@29506
			$HelpDeskModuleInstance = Vtecrm_Module::getInstance('HelpDesk');
			$moduleInstance->setRelatedList($HelpDeskModuleInstance,'HelpDesk',array('add'),'get_dependents_list');	//crmv@150309
			//crmv@29506e
			
			$SalesOrderModuleInstance = Vtecrm_Module::getInstance('SalesOrder');
			$SalesOrderModuleInstance->setRelatedList($moduleInstance,'Project Tasks',array('add'),'get_dependents_list');	//crmv@150309
			
			//crmv@104562
			$em = new VTEventsManager($adb);
			$em->registerHandler('vte.entity.beforesave', 'modules/ProjectTask/ProjectTaskHandler.php', 'ProjectTaskHandler');//crmv@207852
			
			SDK::addView('ProjectTask', 'modules/SDK/src/modules/ProjectTask/View.php', 'constrain', 'continue');
			//crmv@104562e
			
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
    function delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse = true) { // crmv@146653
    	//crmv@29506
    	if ($with_module == 'HelpDesk') {
    		if (!is_array($with_crmid)) $with_crmid = Array($with_crmid);
    		foreach($with_crmid as $relcrmid) {
    			$child = CRMEntity::getInstance($with_module);
    			$child->retrieve_entity_info($relcrmid, $with_module);
    			$child->mode='edit';
    			$child->column_fields['projecttaskid']='';
    			$child->id = $relcrmid;
    			$child->save($with_module);
    		}
    	} else {
    		parent::delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse); // crmv@146653
    	}
    	//crmv@29506e
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
}
?>
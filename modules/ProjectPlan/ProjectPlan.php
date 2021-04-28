<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class ProjectPlan extends CRMEntity {
    var $db, $log; // Used in class functions of CRMEntity

    var $table_name;
    var $table_index= 'projectid';
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
    	'Project No'=> Array('project', 'project_no'),
		'Project Name'=> Array('project', 'projectname'),
		'Start Date'=> Array('project', 'startdate'),
		'Status'=>Array('project','projectstatus'),
		'Type'=>Array('project','projecttype'),
		'Assigned To' => Array('crmentity','smownerid')
    );
    var $list_fields_name = Array(
    /* Format: Field Label => fieldname */
    	'Project No'=> 'project_no',
		'Project Name'=> 'projectname',
		'Start Date'=> 'startdate',
		'Status'=>'projectstatus',
		'Type'=>'projecttype',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'projectname';

	// For Popup listview and UI type support
	var $search_fields = Array(
	/* Format: Field Label => Array(tablename, columnname) */
	// tablename should not have prefix 'vte_'
	'Project Name'=> Array('project', 'projectname'),
	'Start Date'=> Array('project', 'startdate'),
	'Status'=>Array('project','projectstatus'),
	'Type'=>Array('project','projecttype'),
	);
	var $search_fields_name = Array(
	/* Format: Field Label => fieldname */
	'Project Name'=> 'projectname',
	'Start Date'=> 'startdate',
	'Status'=>'projectstatus',
	'Type'=>'projecttype',
	);

	// For Popup window record selection
	var $popup_fields = Array('projectname');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'projectname';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'projectname';

	// Required Information for enabling Import feature
	var $required_fields = Array('projectname'=>1);

	var $default_order_by = 'projectname';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'projectname'); // crmv@177975
	
	//crmv@10759
	var $search_base_field = 'projectname';
	//crmv@10759 e	

	function __construct() {
	    global $log;
	    global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_project';
	    $this->customFieldTable = Array($table_prefix.'_projectcf', 'projectid');
	    $this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_project', $table_prefix.'_projectcf');
	    $this->tab_name_index = Array(
		$table_prefix.'_crmentity' => 'crmid',
		$table_prefix.'_project'   => 'projectid',
	    $table_prefix.'_projectcf' => 'projectid');
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
			include_once('vtlib/Vtecrm/Module.php');//crmv@207871
			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			$projectTabid = $moduleInstance->id;

			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));

			// Add Project module to the related list of Accounts module
			$accountsModuleInstance = Vtecrm_Module::getInstance('Accounts');
			$accountsModuleInstance->setRelatedList($moduleInstance, 'Project Plans', Array('ADD','SELECT'), 'get_dependents_list');
						
			// Add Project module to the related list of Contacts module
			$contactsModuleInstance = Vtecrm_Module::getInstance('Contacts');
			$contactsModuleInstance->setRelatedList($moduleInstance, 'Project Plans', Array('ADD','SELECT'), 'get_dependents_list');
			
			// Add Project module to the related list of Vendors module
			$vendorsModuleInstance = Vtecrm_Module::getInstance('Vendors');
			$vendorsModuleInstance->setRelatedList($moduleInstance, 'ProjectPlan', array('ADD'), 'get_dependents_list');

			$modcommentsModuleInstance = Vtecrm_Module::getInstance('ModComments');
			if($modcommentsModuleInstance) {
				include_once 'modules/ModComments/ModComments.php';
				if(class_exists('ModComments')) ModComments::addWidgetTo(array('ProjectPlan'));
			}
			
			//crmv@manuele		
			$adb->query("INSERT INTO ".$table_prefix."_parenttab VALUES (9,'ProjectPlan',9,0,0)");
			
			require_once('vtlib/Vtecrm/Menu.php');//crmv@207871
			$menu = Vtecrm_Menu::getInstance('ProjectPlan');
			$menu->addModule(Vtecrm_Module::getInstance('ProjectMilestone'));
			$menu->addModule(Vtecrm_Module::getInstance('ProjectTask'));
			$menu->addModule($moduleInstance);
			
			$menu = Vtecrm_Menu::getInstance('Support');
			$menu->removeModule(Vtecrm_Module::getInstance('ProjectMilestone'));
			$menu->removeModule(Vtecrm_Module::getInstance('ProjectTask'));
			$menu->removeModule($moduleInstance);
			
			create_tab_data_file();
			//crmv@manuele-e
			
			//crmv@18829
			$docModuleInstance = Vtecrm_Module::getInstance('Documents');
			$docModuleInstance->setRelatedList($moduleInstance,'Project Plans',array('select','add'),'get_documents_dependents_list');
			//crmv@18829e
			
			//crmv@29506
			$HelpDeskModuleInstance = Vtecrm_Module::getInstance('HelpDesk');
			$moduleInstance->setRelatedList($HelpDeskModuleInstance,'HelpDesk',array('add'),'get_dependents_list');	//crmv@150309
			//crmv@29506e
			
			// crmv@201101
			$joModuleInstance = Vtecrm_Module::getInstance('JobOrder');
			$result = $adb->pquery("select * from {$table_prefix}_relatedlists where tabid = ? and related_tabid = ?", array($joModuleInstance->id,$moduleInstance->id));
			if ($adb->num_rows($result) == 0) {
				$joModuleInstance->setRelatedList($moduleInstance,'Project Plans',array('add','select'),'get_dependents_list');
			}
			// crmv@201101e
		
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
     * Here we override the parent's method,
     * This is done because the related lists for this module use a custom query
     * that queries the child module's table (column of the uitype10 field)
     *
     * @see data/CRMEntity#save_related_module($module, $crmid, $with_module, $with_crmid)
     */
    function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) { // crmv@146653
         if (!in_array($with_module, array('ProjectMilestone', 'ProjectTask'))) {
             parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check); // crmv@146653
             return;
         }
        /** 
         * $_REQUEST['action']=='Save' when choosing ADD from Related list.
         * Do nothing on the project's entity when creating a related new child using ADD in relatedlist
         * by doing nothing we do not insert any line in the crmentity's table when
         * we are relating a module to this module
         */
        if ($_REQUEST['action'] != 'updateRelations') {
            return;
        }
        //update the child elements' column value for uitype10
        $destinationModule = vtlib_purify($_REQUEST['destination_module']);
        if (!is_array($with_crmid)) $with_crmid = Array($with_crmid);
        foreach($with_crmid as $relcrmid) {
            $child = CRMEntity::getInstance($destinationModule);
            $child->retrieve_entity_info_no_html($relcrmid, $destinationModule); // crmv@188369
            $child->mode='edit';
            $child->column_fields['projectid']=$crmid;
			//crmv@17662
			$child->id = $relcrmid;
            $child->save($destinationModule);
            //crmv@17662e
            //crmv@29617
			$obj = ModNotifications::getInstance(); // crmv@164122
			$obj->saveRelatedModuleNotification($crmid, $module, $relcrmid, $with_module);
			//crmv@29617e
        }
    }
    
    /**
     * Here we override the parent's method
     * This is done because the related lists for this module use a custom query
     * that queries the child module's table (column of the uitype10 field)
     * 
     * @see data/CRMEntity#delete_related_module($module, $crmid, $with_module, $with_crmid)
     */
    function delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse = true) { // crmv@146653
    	if (!in_array($with_module, array('ProjectMilestone', 'ProjectTask', 'HelpDesk'))) {	//crmv@29506
    		parent::delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse); // crmv@146653
    		return;
    	}
        $destinationModule = vtlib_purify($_REQUEST['destination_module']);
        if (!is_array($with_crmid)) $with_crmid = Array($with_crmid);
        foreach($with_crmid as $relcrmid) {
            $child = CRMEntity::getInstance($destinationModule);
            $child->retrieve_entity_info($relcrmid, $destinationModule);
            $child->mode='edit';
            //crmv@29506
            if ($with_module == 'HelpDesk')
            	$child->column_fields['projectplanid']='';
            else
            //crmv@29506
            	$child->column_fields['projectid']='';
			//crmv@17662
			$child->id = $relcrmid;
            $child->save($destinationModule);
            //crmv@17662e
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

	//crmv@104562
	function getExtraDetailTabs() {
		global $app_strings;
		
		$return = array(
			array('label'=>getTranslatedString('LBL_PROGRESS_CHART'),'href'=>'','onclick'=>"changeDetailTab('{$this->modulename}', '{$this->id}', 'Gantt', this)")
		);
		$others = parent::getExtraDetailTabs() ?: array();

		return array_merge($return, $others);
	}
	function getExtraDetailBlock($selectionProcesses=false) {
		global $mod_strings, $app_strings;
		require_once('include/utils/EntityColorUtils.php');
		$smarty = new VteSmarty();
		$ECU = EntityColorUtils::getInstance();
		$smarty->assign('CURRENT_PATH','modules/ProjectPlan/thirdparty/jQueryGantt/');
		$smarty->assign('STATUS_COLORS', $ECU->getModuleColors('ProjectTask'));
		$extra = parent::getExtraDetailBlock();
		return $extra.$smarty->fetch('modules/ProjectPlan/GanttTab.tpl');
	}
	function getGanttHolidays($record) {
		global $adb, $table_prefix;
		$holidays = '';
		$result = $adb->pquery("SELECT MIN(startdate) AS \"startdate\", MAX(enddate) AS \"enddate\"
			FROM {$table_prefix}_projecttask pt 
			INNER JOIN {$table_prefix}_crmentity crment ON pt.projecttaskid=crment.crmid 
			WHERE projectid=? AND crment.deleted=0 AND pt.startdate IS NOT NULL AND pt.enddate IS NOT NULL",
			array($record));
		if ($result && $adb->num_rows($result) > 0) {
			$startdate = $adb->query_result($result,0,'startdate');
			$enddate = $adb->query_result($result,0,'enddate');
			$crmv_utils = CRMVUtils::getInstance();
			$holidays = $crmv_utils->getHolidays($startdate,$enddate,'jQueryGantt');
			if (!empty($holidays)) $holidays = '#'.implode('#',$holidays).'#';
		}
		return $holidays;
	}
	function getGanttContent($record,$return_mode='array') {
		global $adb, $table_prefix;
		require_once('include/utils/EntityColorUtils.php');
		$ECU = EntityColorUtils::getInstance();
			
		$tasks = array();
		$resources = array();
		$roles = array();
		// tasks
		//crmv@145469
		$result = $adb->pquery("SELECT pt.*, crment.smownerid, role.roleid, role.rolename
			FROM {$table_prefix}_projecttask pt
			INNER JOIN {$table_prefix}_crmentity crment ON pt.projecttaskid=crment.crmid
			LEFT JOIN {$table_prefix}_user2role u2r ON u2r.userid = smownerid
			LEFT JOIN {$table_prefix}_role role ON role.roleid = u2r.roleid
			WHERE projectid=? AND crment.deleted=0 AND pt.startdate IS NOT NULL AND pt.enddate IS NOT NULL",
			array($record)) or die("Please install the ProjectMilestone and ProjectTasks modules first.");
			//crmv@145469e
		//ORDER BY pt.startdate ASC, pt.enddate DESC
		$resources_ids = array();
		$roles_ids = array();
		while($row=$adb->fetchByAssoc($result)){
			$startdate = $row['startdate'];
			if ($row['projecttaskprogress'] == "--none--") {
				$progress = 0;
			} else {
				$progress = str_replace("%","",$row['projecttaskprogress']);
			}
			$clvColor = $ECU->getEntityColor('ProjectTask',$row['projecttaskid']);
			(!empty($clvColor)) ? $status = 'COLOR_'.str_replace('#','',$clvColor) : $status = 'DEFAULT';
			
			$assigs = array();
			$tasks[] = array(
				'id'=>$row['projecttaskid'],
				'name'=>$row['projecttaskname'],
				'code'=>$row['projecttask_no'],
				'level'=>0,
				'status'=>$status,
				'start'=>strtotime($startdate)*1000,
				'duration'=>$row['working_days'],
				'end'=>strtotime($startdate)*1000 + ($row['working_days']*24*60*60),
				'startIsMilestone'=>false,
				'endIsMilestone'=>false,
				'collapsed'=>false,
				'depends'=>'',
				'hasChild'=>false,
        		'progress'=>$progress,
				'assigs'=>array(
					array(
						'resourceId'=>$row['smownerid'],
			            'id'=>$row['smownerid'],
			            'roleId'=>$row['roleid'],
			            'effort'=>0,	//TODO
					),
				),
			);
			if (!in_array($row['smownerid'],$resources_ids)) {
				$resources_ids[] = $row['smownerid'];
				//crmv@145469
				$name = getUserFullName($row['smownerid']);
				if (empty($name)) {
					$groupInfo = getGroupName($row['smownerid']);
					$resources[] = array(
							'id'=>$row['smownerid'],
							'name'=>$groupInfo[0],
							'img'=>getGroupAvatar(),
					);
				} else {
					$resources[] = array(
							'id'=>$row['smownerid'],
							'name'=>$name,
							'img'=>getUserAvatar($row['smownerid']),
					);
				}
				//crmv@145469e
			}
			if (!in_array($row['roleid'],$roles_ids)) {
				$roles_ids[] = $row['roleid'];
				$roles[] = array(
					'id'=>$row['roleid'],
		            'name'=>$row['rolename'],
				);
			}
		}
		// milestones
		$result = $adb->pquery("SELECT pm.*, smownerid FROM {$table_prefix}_projectmilestone pm 
			INNER JOIN {$table_prefix}_crmentity crment on pm.projectmilestoneid=crment.crmid 
			WHERE projectid=? and crment.deleted=0",
			array($record)) or die("Please install the ProjectMilestone and ProjectTasks modules first.");
		while($row=$adb->fetchByAssoc($result)){
			$tasks[] = array(
				'id'=>$row['projectmilestoneid'],
				'name'=>$row['projectmilestonename'],
				'code'=>$row['projectmilestone_no'],
				'level'=>0,
				'status'=>$status,
				'start'=>strtotime($row['projectmilestonedate'])*1000,
				'duration'=>1,
				'end'=>strtotime($row['projectmilestonedate'].' 23:59:59')*1000,
				'isMilestone'=>true,
			);
		}
		usort($tasks, function($a, $b) {
			return ($a['start'] > $b['start']);
		});
		$return = array(
			'tasks'=>$tasks,
			'resources'=>$resources,
			'roles'=>$roles,
			'selectedRow'=>0,
			'canWrite'=>false,
			'canWriteOnParent'=>false,
		);
		if ($return_mode == 'json') {
			/* ex.
			 {"tasks":[
		     {"id":100,"name":"Gantt editor","code":"","level":0,"status":"s0","start":1396994400000,"duration":21,"end":1399672799999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"hasChild":false}
		     ,{"id":2,"name":"coding","code":"","level":0,"status":"s2","start":1396994400000,"duration":10,"end":1398203999999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"description":"","progress":0,"hasChild":false}
		     ,{"id":32,"name":"gantt part","code":"","level":0,"status":"s3","start":1396994400000,"duration":2,"end":1397167199999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"depends":"","hasChild":false}
		     ,{"id":45,"name":"editor part","code":"","level":0,"status":"s1","start":1397167200000,"duration":4,"end":1397685599999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"depends":"","hasChild":false}
		     ,{"id":56,"name":"testing","code":"","level":0,"status":"s1","start":1398981600000,"duration":6,"end":1399672799999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"depends":"","description":"","progress":0,"hasChild":false}
		     ,{"id":678,"name":"test on safari","code":"","level":0,"status":"","start":1398981600000,"duration":2,"end":1399327199999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"depends":"","hasChild":false}
		     ,{"id":700,"name":"test on ie","code":"","level":0,"status":"STATUS_UNDEFINED","start":1399327200000,"duration":3,"end":1399586399999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"depends":"","hasChild":false}
		     ,{"id":8365,"name":"test on chrome","code":"","level":0,"status":"s2","start":1399327200000,"duration":2,"end":1399499999999,"startIsMilestone":false,"endIsMilestone":false,"collapsed":false,"assigs":[],"depends":"","hasChild":false}
		     ],"selectedRow":0,"canWrite":false,"canWriteOnParent":true}
			 */
			return Zend_Json::encode($return);
		} else {
			return $return;
		}
	}
	//crmv@104562e
}
?>
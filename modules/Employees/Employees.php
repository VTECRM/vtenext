<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


class Employees extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'employeesid';
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
		'LBL_LAST_NAME'=>'lastname',
		'LBL_FIRST_NAME'=>'firstname',
		'LBL_EMAIL'=>'email',
  		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'lastname';

	// For Popup listview and UI type support
	var $search_fields = Array();
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'LBL_LAST_NAME'=> 'lastname',
		'LBL_FIRST_NAME'=> 'firstname',
	);

	// For Popup window record selection
	var $popup_fields = Array('lastname');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'lastname';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'lastname';

	// Required Information for enabling Import feature
	var $required_fields = Array('lastname'=>1);

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'lastname';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'lastname');
	//crmv@10759
	var $search_base_field = 'lastname';
	//crmv@10759 e
	
	//crmv@161021
	var $synchronizeUser = true;
	var $synchronizeUserMapping = array(
		'username'=>'user_name',
		'lastname'=>'last_name',
		'firstname'=>'first_name',
		'role'=>'roleid',
		'title'=>'title',
		'department'=>'department',
		'active'=>'status',
		'email'=>'email1',
		'other_email'=>'email2',
		'mobile'=>'phone_mobile',
		'office_phone'=>'phone_work',
		'fax'=>'phone_fax',
		'home_phone'=>'phone_home',
		'other_phone'=>'phone_other',
		'street'=>'address_street',
		'country'=>'address_country',
		'city'=>'address_city',
		'postalcode'=>'address_postalcode',
		'state'=>'address_state',
	);
	var $synchronizeUserMappingDefaults = array(
		'create' => array(
			'employee_type' => 'Internal employee'
		),
		'update' => array(),
	);
	var $synchronizeFieldsReadOnly = true;
	//crmv@161021e

	function __construct() {
		global $log, $table_prefix; // crmv@64542
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_employees';
		$this->customFieldTable = Array($table_prefix.'_employeescf', 'employeesid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->tab_name = array($table_prefix.'_crmentity',$table_prefix.'_employees',$table_prefix.'_employeescf');
		$this->tab_name_index = array(
			$table_prefix.'_crmentity' => 'crmid',
			$table_prefix.'_employees' => 'employeesid',
			$table_prefix.'_employeescf' => 'employeesid',
		);
		$this->list_fields = array(
			'LBL_LAST_NAME' => array($table_prefix.'_employees','lastname'),
			'LBL_FIRST_NAME' => array($table_prefix.'_employees','firstname'),
			'LBL_EMAIL' => array($table_prefix.'_employees','email'),
			'Assigned To' => array($table_prefix.'_crmentity','smownerid'),
		);
		$this->search_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'LBL_LAST_NAME' => array($table_prefix.'_employees','lastname'),
			'LBL_FIRST_NAME' => array($table_prefix.'_employees','firstname'),
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

	function save_module($module) {}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Create query to export the records.
	 */
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $current_user,$table_prefix;
		$thismodule = $_REQUEST['module'];

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery($thismodule, "detail_view");

		$fields_list = getFieldsListFromQuery($sql);

		$query = 
			"SELECT $fields_list, {$table_prefix}_users.user_name AS user_name
			FROM {$table_prefix}_crmentity 
			INNER JOIN $this->table_name ON {$table_prefix}_crmentity.crmid=$this->table_name.$this->table_index";
		
		// crmv@96636
		foreach ($this->tab_name as $tab) {
			if ($tab == "{$table_prefix}_crmentity" || $tab == $this->table_name) continue;
			if ($this->customFieldTable && $tab == $this->customFieldTable[0]) continue;
			$index = $this->tab_name_index[$tab];
			if ($index) {
				$query .= " INNER JOIN {$tab} ON {$tab}.{$index} = {$this->table_name}.{$this->table_index}";
			}
		}
		// crmv@96636e

		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}

		$query .= " LEFT JOIN {$table_prefix}_groups ON {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid";
		$query .= " LEFT JOIN {$table_prefix}_users ON {$table_prefix}_crmentity.smownerid = {$table_prefix}_users.id and {$table_prefix}_users.status='Active'";

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM {$table_prefix}_field" .
				" INNER JOIN {$table_prefix}_fieldmodulerel ON {$table_prefix}_fieldmodulerel.fieldid = {$table_prefix}_field.fieldid" .
				" WHERE uitype='10' AND {$table_prefix}_fieldmodulerel.module=?", array($thismodule));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);

			$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $this->table_name.$columnname";
		}

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e

		//crmv@58099
		$query .= $this->getNonAdminAccessControlQuery($thismodule,$current_user);
		$where_auto = " {$table_prefix}_crmentity.deleted = 0 ";

		if($where != '') $query .= " WHERE ($where) AND $where_auto";
		else $query .= " WHERE $where_auto";
		
		$query = $this->listQueryNonAdminChange($query, $thismodule);
		//crmv@58099e
		
		return $query;
	}

	/**
	 * Initialize this instance for importing.
	 */
	function initImport($module) {
		$this->db = PearDatabase::getInstance();
		$this->initImportableFields($module);
	}

	/**
	 * Create list query to be shown at the last step of the import.
	 * Called From: modules/Import/UserLastImport.php
	 */
	function create_import_query($module) {
		global $current_user,$table_prefix;
		$query = "SELECT {$table_prefix}_crmentity.crmid, case when ({$table_prefix}_users.user_name is not null) then {$table_prefix}_users.user_name else {$table_prefix}_groups.groupname end as user_name, $this->table_name.* FROM $this->table_name
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = $this->table_name.$this->table_index
			LEFT JOIN {$table_prefix}_users_last_import ON {$table_prefix}_users_last_import.bean_id={$table_prefix}_crmentity.crmid
			LEFT JOIN {$table_prefix}_users ON {$table_prefix}_users.id = {$table_prefix}_crmentity.smownerid
			LEFT JOIN {$table_prefix}_groups ON {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid
			WHERE {$table_prefix}_users_last_import.assigned_user_id='$current_user->id'
			AND {$table_prefix}_users_last_import.bean_type='$module'
			AND {$table_prefix}_users_last_import.deleted=0";
		return $query;
	}

	/**
	 * Transform the value while exporting
	 */
	function transform_export_value($key, $value) {
		return parent::transform_export_value($key, $value);
	}

	/**
	 * Function which will set the assigned user id for import record.
	 */
	function set_import_assigned_user()
	{
		global $current_user, $adb,$table_prefix;
		$record_user = $this->column_fields["assigned_user_id"];

		if($record_user != $current_user->id){
			$sqlresult = $adb->pquery("select id from {$table_prefix}_users where id = ? union select groupid as id from {$table_prefix}_groups where groupid = ?", array($record_user, $record_user));
			if($this->db->num_rows($sqlresult)!= 1) {
				$this->column_fields["assigned_user_id"] = $current_user->id;
			} else {
				$row = $adb->fetchByAssoc($sqlresult, -1, false);
				if (isset($row['id']) && $row['id'] != -1) {
					$this->column_fields["assigned_user_id"] = $row['id'];
				} else {
					$this->column_fields["assigned_user_id"] = $current_user->id;
				}
			}
		}
	}

	/**
	 * Function which will give the basic query to find duplicates
	 */
	function getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr,$select_cols='') {
	global $table_prefix;
		$select_clause = "SELECT ". $this->table_name .".".$this->table_index ." AS recordid, {$table_prefix}_users_last_import.deleted,".$table_cols;

		// Select Custom Field Table Columns if present
		if(isset($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$from_clause = " FROM $this->table_name";

		$from_clause .= "	INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(isset($this->customFieldTable)) {
			$from_clause .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}
		$from_clause .= " LEFT JOIN {$table_prefix}_users ON {$table_prefix}_users.id = {$table_prefix}_crmentity.smownerid
						LEFT JOIN {$table_prefix}_groups ON {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid";

		$where_clause = "	WHERE {$table_prefix}_crmentity.deleted = 0";
		$where_clause .= $this->getListViewSecurityParameter($module);

		if (isset($select_cols) && trim($select_cols) != '') {
			$sub_query = "SELECT $select_cols FROM  $this->table_name AS t " .
				" INNER JOIN {$table_prefix}_crmentity AS crm ON crm.crmid = t.".$this->table_index;
			// Consider custom table join as well.
			if(isset($this->customFieldTable)) {
				$sub_query .= " INNER JOIN ".$this->customFieldTable[0]." tcf ON tcf.".$this->customFieldTable[1]." = t.$this->table_index";
			}
			$sub_query .= " WHERE crm.deleted=0 GROUP BY $select_cols HAVING COUNT(*)>1";
		} else {
			$sub_query = "SELECT $table_cols $from_clause $where_clause GROUP BY $table_cols HAVING COUNT(*)>1";
		}

		$query = $select_clause . $from_clause .
					" LEFT JOIN {$table_prefix}_users_last_import ON {$table_prefix}_users_last_import.bean_id=" . $this->table_name .".".$this->table_index .
					" INNER JOIN (" . $sub_query . ") temp ON ".get_on_clause($field_values,$ui_type_arr,$module) .
					$where_clause .
					" ORDER BY $table_cols,". $this->table_name .".".$this->table_index ." ASC";

		return $query;
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb,$table_prefix;
		if($event_type == 'module.postinstall') {
			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			if ($moduleInstance->is_mod_light) {	//crmv@106857
				$moduleInstance->hide(array('hide_module_manager'=>1,'hide_profile'=>1,'hide_report'=>1));
			} else {
				//crmv@29617
				$result = $adb->pquery('SELECT isentitytype FROM '.$table_prefix.'_tab WHERE name = ?',array($modulename));
				if ($result && $adb->num_rows($result) > 0 && $adb->query_result($result,0,'isentitytype') == '1') {
	
					$ModCommentsModuleInstance = Vtecrm_Module::getInstance('ModComments');
					if ($ModCommentsModuleInstance) {
						$ModCommentsFocus = CRMEntity::getInstance('ModComments');
						$ModCommentsFocus->addWidgetTo($modulename);
					}
	
					// crmv@164120 - removed line
					// crmv@164122 - removed line
					
					$ModNotificationsModuleInstance = Vtecrm_Module::getInstance('ModNotifications');
					if ($ModNotificationsModuleInstance) {
						$ModNotificationsCommonFocus = CRMEntity::getInstance('ModNotifications');
						$ModNotificationsCommonFocus->addWidgetTo($modulename);
					}
	
					$MyNotesModuleInstance = Vtecrm_Module::getInstance('MyNotes');
					if ($MyNotesModuleInstance) {
						$MyNotesCommonFocus = CRMEntity::getInstance('MyNotes');
						$MyNotesCommonFocus->addWidgetTo($modulename);
					}
				}
				//crmv@29617e
				
				//crmv@92272
				$ProcessesFocus = CRMEntity::getInstance('Processes');
				$ProcessesFocus->enable($modulename);
				//crmv@92272e
				
				//crmv@105882 - initialize home for all users
				require_once('include/utils/ModuleHomeView.php');
				$MHW = ModuleHomeView::install($modulename);
				//crmv@105882e
				
				//crmv@161021
				SDK::addView('Employees', 'modules/Employees/View.php', 'constrain', 'continue');
				$this->syncUserEmployees();
				$adb->pquery("update {$table_prefix}_employee_type set presence = ? where employee_type = ?", array(0,'Internal employee')); // readonly value
				$adb->pquery("update {$table_prefix}_entityname set fieldname = ? where modulename = ?", array('lastname,firstname',$modulename));
				//crmv@161021e
				
				//crmv@173509
				$docModuleInstance = Vtecrm_Module::getInstance('Documents');
				$docModuleInstance->setRelatedList($moduleInstance,$modulename,array('select','add'),'get_documents_dependents_list');
				//crmv@173509e
			}
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
	
	//crmv@161021
	function syncUserEmployees() {
		global $adb, $table_prefix, $current_user;
		if (basename($_SERVER['PHP_SELF']) == 'install.php') { // only during installation
			$crmid = $adb->getUniqueID($table_prefix."_crmentity");
			$date_var = date('Y-m-d H:i:s');
			$result = $adb->query("select * from {$table_prefix}_users inner join {$table_prefix}_user2role on {$table_prefix}_users.id = {$table_prefix}_user2role.userid");
			$row = $adb->fetchByAssoc($result);
			$adb->pquery("insert into {$table_prefix}_crmentity (crmid,smownerid,setype,createdtime,modifiedtime) values (?,?,?,?,?)", array($crmid, $row['id'], 'Employees', $adb->formatDate($date_var, true), $adb->formatDate($date_var, true)));
			$adb->pquery("insert into {$table_prefix}_employees (employeesid,lastname,firstname,employee_type,username,role,active,email) values (?,?,?,?,?,?,?,?)", array($crmid, $row['last_name'], $row['first_name'], 'Internal employee', $row['user_name'], $row['roleid'], 1, $row['email1']));
			$adb->pquery("insert into {$table_prefix}_employeescf (employeesid) values (?)", array($crmid));
		} else {
			if (empty($current_user)) {
				require_once('modules/Users/Users.php');
				$current_user = Users::getActiveAdminUser();
			}
			$result = $adb->query("SELECT id FROM {$table_prefix}_users");
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$this->syncUserEmployee($row['id']);
				}
			}
		}
	}
	function syncUserEmployee($userid, $mode='') {
		global $adb, $table_prefix;
		
		$focusUsers = CRMEntity::getInstance('Users');
		$focusUsers->retrieve_entity_info($userid,'Users');
		
		// check exists
		$employeeid = $this->getEmployee($focusUsers); // crmv@201835
		
		if (in_array($mode,array('','create','edit'))) {
			$focus = CRMEntity::getInstance('Employees');
			if ($employeeid) {
				// update
				$focus->retrieve_entity_info_no_html($employeeid,'Employees');
				$focus->mode = $focus_mode = 'edit';
			} else {
				// create
				$focus->mode = '';
				$focus_mode = 'create';
			}
			foreach($this->synchronizeUserMapping as $c_fieldname => $u_fieldname) {
				if ($u_fieldname == 'status') {
					$focus->column_fields[$c_fieldname] = ($focusUsers->column_fields[$u_fieldname] == 'Active') ? '1' : '0';
				} else {
					$focus->column_fields[$c_fieldname] = $focusUsers->column_fields[$u_fieldname];
				}
			}
			if (!empty($this->synchronizeUserMappingDefaults[$focus_mode])) {
				foreach($this->synchronizeUserMappingDefaults[$focus_mode] as $default_fieldname => $default_value) {
					$focus->column_fields[$default_fieldname] = $default_value;
				}
			}
			$focus->column_fields['assigned_user_id'] = $focusUsers->id;
			$focus->save('Employees');
		} elseif ($mode == 'delete') {
			if ($employeeid) {
				$focus = CRMEntity::getInstance('Employees');
				$focus->trash('Employees',$employeeid);
			}
		}
	}
	//crmv@161021e
	
	// crmv@201835
	function getEmployee($user=null) {
		global $adb, $table_prefix, $current_user;
		if (empty($user)) $user = $current_user;
		$user_name = $user->column_fields['user_name'];
		
		$result = $adb->pquery("select employeesid
			from {$table_prefix}_employees
			inner join {$table_prefix}_crmentity on {$table_prefix}_employees.employeesid = {$table_prefix}_crmentity.crmid
			where deleted = 0 and username = ?", array($user_name));
		if ($result && $adb->num_rows($result) > 0) {
			return $adb->query_result($result,0,'employeesid');
		}
		return false;
	}
	// crmv@201835e
}
?>
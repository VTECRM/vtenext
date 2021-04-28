<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * this file will be used to store the functions to be used in the picklist module
 */

/**
 * Function to get picklist fields for the given module
 * @ param $fld_module
 * It gets the picklist details array for the given module in the given format
 * $fieldlist = Array(Array('fieldlabel'=>$fieldlabel,'generatedtype'=>$generatedtype,'columnname'=>$columnname,'fieldname'=>$fieldname,'value'=>picklistvalues))
 */
function getUserFldArray($fld_module,$roleid)
{
	global $adb, $log,$table_prefix;
	$user_fld = Array();
	$tabid = getTabid($fld_module);

	$query="select ".$table_prefix."_field.fieldlabel,".$table_prefix."_field.columnname,".$table_prefix."_field.fieldname, ".$table_prefix."_field.uitype" .
			" FROM ".$table_prefix."_field inner join ".$table_prefix."_picklist on ".$table_prefix."_field.fieldname = ".$table_prefix."_picklist.name" .
			" where (displaytype=1 and ".$table_prefix."_field.tabid=? and ".$table_prefix."_field.uitype in ('15','55','33','16','111','300') " . // crmv@30528
			" or (".$table_prefix."_field.tabid=? and fieldname='salutationtype' and fieldname !='vendortype')) " .
			" and ".$table_prefix."_field.presence in (0,2) ORDER BY ".$table_prefix."_picklist.picklistid ASC";
	$result = $adb->pquery($query, array($tabid, $tabid));
	$noofrows = $adb->num_rows($result);

    if($noofrows > 0){
		$fieldlist = array();
    	for($i=0; $i<$noofrows; $i++){
			$user_fld = array();
			$fld_name = $adb->query_result($result,$i,"fieldname");
			if (!in_array($fieldname,Array('recurringtype','visibility'))){
				$user_fld['fieldlabel'] = $adb->query_result($result,$i,"fieldlabel");
				$user_fld['generatedtype'] = $adb->query_result($result,$i,"generatedtype");
				$user_fld['columnname'] = $adb->query_result($result,$i,"columnname");
				$user_fld['fieldname'] = $adb->query_result($result,$i,"fieldname");
				$user_fld['uitype'] = $adb->query_result($result,$i,"uitype");
				$user_fld['value'] = getAssignedPicklistValues($user_fld['fieldname'], $roleid, $adb);
				$fieldlist[] = $user_fld;
			}
		}
	}
	return $fieldlist;
}

/**
 * Function to get modules which has picklist values
 * It gets the picklist modules and return in an array in the following format
 * $modules = Array($tabname=>$label,...)
 */
function getPickListModules(){
	global $adb,$table_prefix;
	// vtlib customization: Ignore disabled modules.
	$query = 'select distinct '.$table_prefix.'_field.fieldname,'.$table_prefix.'_field.tabid,'.$table_prefix.'_tab.tablabel, '.$table_prefix.'_tab.name as tabname, uitype
		from '.$table_prefix.'_field
		inner join '.$table_prefix.'_tab on '.$table_prefix.'_tab.tabid='.$table_prefix.'_field.tabid
		where uitype IN (15,33,300) and '.$table_prefix.'_field.tabid != 29 and '.$table_prefix.'_tab.presence != 1 and '.$table_prefix.'_field.presence in (0,2)
		order by '.$table_prefix.'_field.tabid ASC'; //crmv@98693
	// END
	//crmv@113771 skip also light modules
	$modules_not_supported = array();
	$result = $adb->pquery("SELECT {$table_prefix}_tab.name
		FROM {$table_prefix}_tab_info
		INNER JOIN {$table_prefix}_tab ON {$table_prefix}_tab_info.tabid = {$table_prefix}_tab.tabid
		WHERE prefname = ? AND prefvalue = ?", array('is_mod_light',1));
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$modules_not_supported[] = $row['name'];
		}
	}
	//crmv@113771e
	$result = $adb->pquery($query, array());
	while($row = $adb->fetch_array($result)){
		if (in_array($row['tabname'],$modules_not_supported)) continue;	//crmv@113771
		$modules[$row['tabname']] = getTranslatedString($row['tabname'], $row['tabname']);
	}
	asort($modules);
	return $modules;
}

/**
 * this function returns all the roles present in the CRM so that they can be displayed in the picklist module
 * @return array $role - the roles present in the CRM in the array format
 */
function getrole2picklist(){
	global $adb,$table_prefix;
	$query = "select rolename,roleid from ".$table_prefix."_role where roleid not in('H1') order by roleid";
	$result = $adb->pquery($query, array());
	while($row = $adb->fetch_array($result)){
		$role[$row['roleid']] = $row['rolename'];
	}
	return $role;

}

/**
 * this function returns the picklists available for a module
 * @param array $picklist_details - the details about the picklists in the module
 * @return array $module_pick - the picklists present in the module in an array format
 */
function get_available_module_picklist($picklist_details){
	$avail_pick_values = $picklist_details;
	foreach($avail_pick_values as $key => $val){
		$module_pick[$avail_pick_values[$key]['fieldname']] = $avail_pick_values[$key]['fieldlabel'];	//crmv@28070
	}
	return $module_pick;
}

/**
 * this function returns all the picklist values that are available for a given
 * @param string $fieldName - the name of the field
 * @return array $arr - the array containing the picklist values
 */
function getAllPickListValues($fieldName,$module = ''){
	global $adb,$table_prefix;
	$sql = 'SELECT * FROM '.$table_prefix.'_'.$adb->sql_escape_string($fieldName);
	$result = $adb->query($sql);
	$count = $adb->num_rows($result);

	$arr = array();
	for($i=0;$i<$count;$i++){
		$val = $adb->query_result($result, $i, $fieldName);
		$arr[$val] = getTranslatedstring($val,$module);
	}
	return $arr;
}


/**
 * this function accepts the fieldname and the language string array and returns all the editable picklist values for that fieldname
 * @param string $fieldName - the name of the picklist
 * @param array $lang - the language string array
 * @param object $adb - the peardatabase object
 * @return array $pick - the editable picklist values
 */
function getEditablePicklistValues($fieldName, $lang, $adb){
	global $table_prefix;
	$values = array();
	$fieldName = $adb->sql_escape_string($fieldName);
	$sql="select $fieldName from ".$table_prefix."_$fieldName where presence=1 and $fieldName <> '--None--'";
	$res = $adb->query($sql);
	$RowCount = $adb->num_rows($res);
	if($RowCount > 0){
		for($i=0;$i<$RowCount;$i++){
			$pick_val = $adb->query_result($res,$i,$fieldName);
			if($lang[$pick_val] != ''){
				$values[$pick_val]=$lang[$pick_val];
			}else{
				$values[$pick_val]=$pick_val;
			}
		}
	}
	return $values;
}

/**
 * this function accepts the fieldname and the language string array and returns all the non-editable picklist values for that fieldname
 * @param string $fieldName - the name of the picklist
 * @param array $lang - the language string array
 * @param object $adb - the peardatabase object
 * @return array $pick - the no-editable picklist values
 */
function getNonEditablePicklistValues($fieldName, $lang, $adb){
	global $table_prefix;
	$values = array();
	$fieldName = $adb->sql_escape_string($fieldName);
	$sql = "select $fieldName from ".$table_prefix."_$fieldName where presence=0";
	$result = $adb->query($sql);
	$count = $adb->num_rows($result);
	for($i=0;$i<$count;$i++){
		$non_val = $adb->query_result($result,$i,$fieldName);
		if($lang[$non_val] != ''){
			$values[$non_val]=$lang[$non_val];
		}else{
			$values[$non_val]=$non_val;
		}
	}
	if(count($values)==0){
		$values = "";
	}
	return $values;
}

/**
 * this function returns all the assigned picklist values for the given tablename for the given roleid
 * @param string $tableName - the picklist tablename
 * @param integer $roleid - the roleid of the role for which you want data
 * @param object $adb - the peardatabase object
 * @return array $val - the assigned picklist values in array format
 */
function getAssignedPicklistValues($tableName, $roleid, $adb, $module = '', $currentValue = '', $useTransitions = false, $translate = true){	//crmv@26328	//crmv@27889
	//crmv@15934
	global $current_user, $adb, $table_prefix;
	if (vtlib_isModuleActive('Transitions') && $module == 'Timecards' && $tableName == 'ticketstatus'
		&& $_REQUEST['return_id'] != '' && $_REQUEST['return_module'] == 'HelpDesk'){
		//crmv@31357
		$transitions_obj = CRMEntity::getInstance('Transitions');
		$transitions_obj->Initialize('HelpDesk',$current_user->roleid);	
		//crmv@31357e
		if ($transitions_obj->status_field == $tableName){
			$ticket_obj = CRMEntity::getInstance('HelpDesk');
			$ticket_obj->retrieve_entity_info($_REQUEST['return_id'],"HelpDesk");
			//crmv@19396
			$permitted_states = $transitions_obj->get_permitted_states($ticket_obj->column_fields[$tableName]);
			$arr = array();
			foreach($permitted_states as $state) {
				//crmv@27889
				if ($translate) {
					$arr[$state] = getTranslatedString($state,"HelpDesk");
				} else {
					$arr[$state] = $state;
				}
				//crmv@27889e
			}
			//crmv@19396e
			return $arr;
		}
	//crmv@15934 end
	//crmv@26328
	} elseif ($useTransitions && !empty($module) && !empty($currentValue) && vtlib_isModuleActive('Transitions') && vtlib_isModuleActive($module)) {
		$transitions_obj = CRMEntity::getInstance('Transitions');
		$transitions_obj->Initialize($module,$current_user->roleid);

		if ($transitions_obj->status_field == $tableName) {
			$permitted_states = $transitions_obj->get_permitted_states($currentValue);
			$arr = array();
			foreach($permitted_states as $state) {
				//crmv@27889
				if ($translate) {
					$arr[$state] = getTranslatedString($state,$module);
				} else {
					$arr[$state] = $state;
				}
				//crmv@27889e
			}
			return $arr;
		}
	}
	//crmv@26328e
	$arr = array();
	//se la picklist supporta il nuovo metodo
	$columns = $adb->database->MetaColumnNames($table_prefix."_$tableName");
	if (!$columns)
		return $arr;
	if (in_array('picklist_valueid',$columns) && $tableName != 'product_lines'){
		$order_by = "sortid,$tableName";
		$pick_query="select $tableName from ".$table_prefix."_$tableName inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$tableName.picklist_valueid and roleid = ? ";
		$params = array($roleid);
	}
	//altrimenti uso il vecchio
	else {
		if (in_array('sortorderid',$columns))
			$order_by = "sortorderid,$tableName";
		else
			$order_by = $tableName;
		$pick_query="select $tableName from ".$table_prefix."_$tableName";
		$params = array();
	}
	$pick_query.=" order by $order_by asc";
	$pickListResult = $adb->pquery($pick_query, $params);
	$count = $adb->num_rows($pickListResult);
	if($count) {
		while($resultrow = $adb->fetch_array_no_html($pickListResult)) {
			$val = $resultrow[$tableName];
			//crmv@27889
			if ($translate) {
				$arr[$val] = getTranslatedString($val,$module);
			} else {
				$arr[$val] = $val;
			}
			//crmv@27889e
		}
	}
	// END
	return $arr;
}
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

 /**
  *		Plugin for search_engine for field assigned_user_id
  *		
  */
if ( ! SEARCHENGINE_LOADED ) return false;
global $is_admin,$current_user,$default_charset,$adb;
//include_once('include/utils/utils.php');
$srch_val = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$search_engine['options']['input']) : $search_engine['options']['input']; // crmv@167702
$srch_val = $adb->sql_escape_string($srch_val); // crmv@200034
$module = $_REQUEST['modulename']; // crmv@200034
$values_arr = getAssignedPicklistValuesWithWhere($_GET['fieldname'], $current_user->roleid, $adb, $module_name, $search_engine['options']['input']);
$search_engine['results'] = $values_arr;

//estende la funzione getAssignedPicklistValues in modules/PickListUtils.php
function getAssignedPicklistValuesWithWhere($tableName, $roleid, $adb,$module = '',$where=''){	//crmv@20094
	//crmv@15934
	global $table_prefix;
	if (vtlib_isModuleActive('Transitions') && $module == 'Timecards' && $tableName == 'ticketstatus'
		&& $_REQUEST['return_id'] != '' && $_REQUEST['return_module'] == 'HelpDesk'){
		global $current_user;
		$transitions_obj = CRMEntity::getInstance('Transitions');
		$transitions_obj->Initialize('HelpDesk',$current_user->roleid);

		if ($transitions_obj->status_field == $tableName){
			$ticket_obj = CRMEntity::getInstance('HelpDesk');
			$ticket_obj->retrieve_entity_info($_REQUEST['return_id'],"HelpDesk");
			//crmv@19396
			$permitted_states = $transitions_obj->get_permitted_states($ticket_obj->column_fields[$tableName]);
			$arr = array();
			foreach($permitted_states as $state) {
				$arr[$state] = getTranslatedString($state,"HelpDesk");
			}
			//crmv@19396e
			return $arr;
		}
	}	
	//crmv@15934 end
	$arr = array();
	//se la picklist supporta il nuovo metodo
	$columns = $adb->database->MetaColumnNames($table_prefix."_$tableName");
	if (!$columns)
		return $arr;
	if (in_array('picklist_valueid',$columns) && $tableName != 'product_lines'){
		$order_by = "sortid,$tableName";
		$pick_query="select $tableName from ".$table_prefix."_$tableName inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$tableName.picklist_valueid and roleid = ? ";
		$params = array($roleid);
		//crmv@20094
		if ($where != '') {
			$pick_query .= " where $tableName like '%$where%'";
		}
		//crmv@20094e
	}
	//altrimenti uso il vecchio
	else {
		if (in_array('sortorderid',$columns))
			$order_by = "sortorderid,$tableName";
		else
			$order_by = $tableName;
		$pick_query="select $tableName from ".$table_prefix."_$tableName";	
		if ($tableName == 'product_lines')
			$pick_query .= ' where presence = 1';
		//vtc e	
		$params = array();
		//crmv@20094
		if ($where != '')
			$pick_query .= " and $tableName like '%$where%'";
		//crmv@20094e
	}
	$pick_query.=" order by $order_by asc";
	$pickListResult = $adb->pquery($pick_query, $params);
	$count = $adb->num_rows($pickListResult);
	if($count) {
		while($resultrow = $adb->fetch_array_no_html($pickListResult)) {
			$val = $resultrow[$tableName];
			$arr[$val] = getTranslatedString($val,$module);
		}
	}
	// END
	return $arr;
}
?>
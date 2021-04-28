<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@171832 */
$return_array = Array(
	'success'=>false,
	'etag'=>'',
);
$values = array();
if (isset($_REQUEST['fields'])){
	$values_arr = Zend_Json::decode($_REQUEST['fields']);
}
if (!empty($values_arr)){
	global $current_user;
	$module = $_REQUEST['module_req'];
	$record = $_REQUEST['record_req'];
	$userid = $current_user->id;
	global $currentModule;
	$currentModule_backup = $currentModule;
	$currentModule = $module;
	$module_obj = CRMEntity::getInstance($module);
	$currentModule = $currentModule_backup;
	$values = Array();
	
	// crmv@186949
	if ($values_arr['assigntype'] && $values_arr['assigntype']['value'] == 'T') {
		$values_arr['assigned_user_id'] = $values_arr['assigned_group_id'];
	}
	// crmv@186949e
	
	foreach ($values_arr as $fieldname=>$fieldarr){
		if (!isset($module_obj->column_fields[$fieldname])){
			continue;
		}
		if (strtolower($fieldarr['type']) == 'textarea'){
			$values[$fieldname]= str_replace("\n","\r\n",$fieldarr['value']);
		}
		else{
			$values[$fieldname] = $fieldarr['value'];
		}
	}
	if (method_exists('EditViewChangelog','store_editview') ){
		EditViewChangelog::store_editview($record,$userid,$values);
	}
	if (method_exists('EditViewChangelog','store_editview') ){
		$etag = EditViewChangelog::get_currentid();
	}
	if ($etag != ''){
		$return_array['success'] = true;
		$return_array['etag'] = $etag;
	}	
}
echo Zend_Json::encode($return_array);
exit;
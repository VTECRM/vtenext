<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user, $currentModule;
global $table_prefix;
$focus = CRMEntity::getInstance($currentModule);
setObjectValuesFromRequest($focus);

$mode = $_REQUEST['mode'];
$record=$_REQUEST['record'];
if($mode) $focus->mode = $mode;
if($record)$focus->id  = $record;

if($_REQUEST['assigntype'] == 'U') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

$focus->save($currentModule);
$return_id = $focus->id;

//crmv@17763
if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true' && $_REQUEST['isDuplicateFrom'] != ''){

	include_once('modules/ProjectMilestone/ProjectMilestone.php');
	include_once('modules/ProjectTask/ProjectTask.php');
	
	$fpm = CRMEntity::getInstance('ProjectMilestone');
	$fpt = CRMEntity::getInstance('ProjectTask');
	
	$query_pm = "SELECT projectmilestoneid 
					FROM ".$table_prefix."_projectmilestone 
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_projectmilestone.projectmilestoneid = ".$table_prefix."_crmentity.crmid 
					WHERE deleted = 0 AND projectid = ?";
	$result = $adb->pquery($query_pm,array($_REQUEST['isDuplicateFrom']));
	
	if (vtlib_isModuleActive('ProjectMilestone') !== false) {
		foreach($result as $key => $value){
			$fpm->retrieve_entity_info($value,'ProjectMilestone');
			$fpm->mode = '';
			$fpm->id = '';
			$fpm->column_fields['projectid'] = $return_id;
			$fpm->save('ProjectMilestone');
			
		}
	}
	
	$query_pt = "SELECT projecttaskid FROM ".$table_prefix."_projecttask 
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_projecttask.projecttaskid = ".$table_prefix."_crmentity.crmid 
					WHERE deleted = 0 AND projectid = ?";
	$result = $adb->pquery($query_pt,array($_REQUEST['isDuplicateFrom']));
	
	if (vtlib_isModuleActive('ProjectTask') !== false) {
		foreach($result as $key => $value){
			$fpt->retrieve_entity_info($value,'ProjectTask');
			$fpt->mode = '';
			$fpt->id = '';
			$fpt->column_fields['projectid'] = $return_id;
			$fpt->save('ProjectTask');
			
		}
	}
}
//crmv@17763e

$search = vtlib_purify($_REQUEST['search_url']);

$parenttab = getParentTab();
if($_REQUEST['return_module'] != '') {
	$return_module = vtlib_purify($_REQUEST['return_module']);
} else {
	$return_module = $currentModule;
}

if($_REQUEST['return_action'] != '') {
	$return_action = vtlib_purify($_REQUEST['return_action']);
} else {
	$return_action = "DetailView";
}

if($_REQUEST['return_id'] != '') {
	$return_id = vtlib_purify($_REQUEST['return_id']);
}

//crmv@54375
if($_REQUEST['return2detail'] == 'yes') {
	$return_module = $currentModule;
	$return_action = 'DetailView';
	$return_id = $focus->id;
}
//crmv@54375e

$url = "index.php?action=$return_action&module=$return_module&record=$return_id&parenttab=$parenttab&start=".vtlib_purify($_REQUEST['pagenumber']).$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");
?>
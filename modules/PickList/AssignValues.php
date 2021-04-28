<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'include/utils/utils.php';
require_once 'modules/PickList/PickListUtils.php';

global $adb, $current_user, $app_strings, $current_language, $theme;

$smarty = new VteSmarty();
$smarty->assign("IMAGE_PATH",$image_path);

$fieldName = vtlib_purify($_REQUEST["fieldname"]);
$fieldLabel = vtlib_purify($_REQUEST['fieldlabel']);
$moduleName = vtlib_purify($_REQUEST["moduleName"]);
$roleid = vtlib_purify($_REQUEST['roleid']);
if(!empty($roleid)){
	$roleName = getRoleName($roleid);
}

if($moduleName == 'Events'){
	$temp_module_strings = return_module_language($current_language, 'Calendar');
}else{
	$temp_module_strings = return_module_language($current_language, $moduleName);
}

if(!empty($fieldName)){
	$values = getAllPickListValues($fieldName,$moduleName);
}

$assignedValues = getAssignedPicklistValues($fieldName, $roleid, $adb,$moduleName);

$smarty->assign("THEME",$theme);
$smarty->assign("FIELDNAME",$fieldName);
$smarty->assign("FIELDLABEL", getTranslatedString($fieldLabel));
$smarty->assign("MODULE",$moduleName);
$smarty->assign("PICKVAL",$values);
$smarty->assign("ASSIGNED_VALUES",$assignedValues);
$smarty->assign("ROLEID",$roleid);
$smarty->assign("ROLENAME", $roleName);
$smarty->assign("MOD", return_module_language($current_language,'PickList'));
$smarty->assign("APP",$app_strings);

$smarty->display("modules/PickList/AssignPicklistValues.tpl");
?>
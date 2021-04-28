<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $app_strings, $mod_strings, $current_language, $currentModule, $theme,$current_user,$adb,$log;

require_once('modules/Webforms/Webforms.php');
require_once 'config.inc.php';

Webforms::checkAdminAccess($current_user);

$isCreate = !isset($_REQUEST['id']);

$webform = false;
if ($isCreate) {
	$webform = new Webforms_Model();
} else {
	$webform = Webforms_Model::retrieveWithId(vtlib_purify($_REQUEST['id']));
}

$smarty = new VteSmarty();

$category = getParentTab();
$targetModules = array('Leads');

$usersList = get_user_array(false);

$smarty->assign('WEBFORM',$webform);
$smarty->assign('USERS',$usersList);
$smarty->assign('WEBFORMMODULES', $targetModules);
$smarty->assign('THEME', $theme);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('CATEGORY', $category);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('CALENDAR_LANG','en');
$smarty->assign('LANGUAGE',$current_language);
$smarty->assign('DATE_FORMAT', $current_user->date_format);
if ($webform->hasId()) {
	//crmv@162158
	require_once('modules/com_workflow/VTTaskManager.inc');//crmv@207901
	require_once('modules/com_workflow/tasks/VTEmailTask.inc');//crmv@207901
	$task = new VTEmailTask();
	$metaVariables = $task->getMetaVariables(false,true);
	$smarty->assign('META_VARIABLES', $metaVariables);
	//crmv@162158e
	$smarty->assign('WEBFORMFIELDS', Webforms::getFieldInfos($webform->getTargetModule()));
	$smarty->assign('ACTIONPATH',$site_URL.'/modules/Webforms/capture.php');
	$smarty->assign('WEBFORMID',$webform->getId());
	$disp_view = 'edit_view';
	$smarty->assign("MOD_SEQ_ID",$_REQUEST['id']);
}else{
	$disp_view = 'create_view';
}
$name = $webform->getName();
if (isset($name)) $smarty->assign("NAME", $name);
else $smarty->assign("NAME", "");
$smarty->assign("SINGLE_MOD",'Webform');
$smarty->assign("OP_MODE",$disp_view);
$smarty->display(vtlib_getModuleTemplate($currentModule,'EditView.tpl'));
?>
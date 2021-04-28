<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $app_strings, $mod_strings, $current_language, $currentModule, $theme,$current_user,$adb,$log;

require_once('modules/Webforms/Webforms.php');
require_once('modules/Webforms/model/WebformsModel.php');

Webforms::checkAdminAccess($current_user);
$webformFields=Webforms::getFieldInfos($_REQUEST["targetmodule"]);

$smarty = new VteSmarty();

$category = getParentTab();

$smarty->assign('WEBFORM',new Webforms_Model());
$smarty->assign('WEBFORMFIELDS',$webformFields);
$smarty->assign("THEME", $theme);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('CATEGORY', $category);
$smarty->assign('CHECK', $tool_buttons);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('CALENDAR_LANG','en');
$smarty->assign('LANGUAGE',$current_language);
$smarty->assign('DATE_FORMAT', $current_user->date_format);
//crmv@162158
require_once('modules/com_workflow/VTTaskManager.inc');//crmv@207901
require_once('modules/com_workflow/tasks/VTEmailTask.inc');//crmv@207901
$task = new VTEmailTask();
$metaVariables = $task->getMetaVariables(false,true);
$smarty->assign('META_VARIABLES', $metaVariables);
//crmv@162158e
$smarty->display(vtlib_getModuleTemplate($currentModule,'FieldsView.tpl'));
?>
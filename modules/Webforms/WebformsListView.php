<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $app_strings, $mod_strings, $current_language, $currentModule, $theme,$current_user,$adb,$log;

require('user_privileges/user_privileges_'.$current_user->id.'.php');
require_once('modules/Webforms/Webforms.php');
require_once('modules/Webforms/model/WebformsModel.php');

Webforms::checkAdminAccess($current_user);

$webforms = Webforms_Model::listAll();

$smarty = new VteSmarty();

$category = getParentTab();
$smarty->assign('WEBFORMS',$webforms);
$smarty->assign('ENABLED',$enabled);
$smarty->assign('ACTION','list');
$smarty->assign("THEME", $theme);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('CATEGORY', $category);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('LANGUAGE',$current_language);
$smarty->assign("MODSETTINGS", return_module_language($current_language,'Settings')); //crmv@30683
$smarty->display(vtlib_getModuleTemplate($currentModule,'ListView.tpl'));
?>
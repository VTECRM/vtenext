<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@18592 crmv@54707 */

global $app_strings,$current_language,$theme;

$smarty = new VteSmarty();

$tabs = getParentTabs();
$smarty->assign("TABS", $tabs);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME",$theme);
$smarty->assign("APP", $app_strings);
$smarty->assign("MODE", $_REQUEST['mode']);

$menu_module_list = getMenuModuleList();
$smarty->assign('VisibleModuleList', $menu_module_list[0]);
$smarty->assign('OtherModuleList', $menu_module_list[1]);

require_once('modules/Area/Area.php');
$areaManager = AreaManager::getInstance();
$enable_areas = $areaManager->getToolValue('enable_areas');
if ($enable_areas == 1) {
	$enable_areas = 'checked';
} else {
	$enable_areas = '';
}
$smarty->assign("ENABLE_AREAS", $enable_areas);

if ($_REQUEST['mode'] != 'edit')
	$smarty->display("Settings/menuSettings.tpl");
else
	$smarty->display("Settings/menuSettingsEdit.tpl");
?>
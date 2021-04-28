<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43942 */

global $theme;

require_once('modules/Area/Area.php');

$areaid = intval($_REQUEST['mod']);

$areaManager = AreaManager::getInstance();
$areaList = array();
$allList = $areaManager->getSelectableModuleList($areaid,$areaList);

$smarty = new VteSmarty();
$smarty->assign('THEME', $theme);
$smarty->assign('AREAID', '');
$smarty->assign('MODE', 'create');
$smarty->assign('PERMISSION_DELETE', false);
$smarty->assign('CURRENTMODULES', $areaList);
$smarty->assign('OTHERMODULES', $allList);
$smarty->assign('HIGHTLIGHT_FIXED_MODULES', $areaManager->hightlight_fixed_modules);
$smarty->assign('HIDE_FIXED_MODULES', $areaManager->hide_fixed_modules);
$smarty->display('modules/Popup/SettingsArea.tpl');
?>
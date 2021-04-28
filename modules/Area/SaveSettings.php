<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43942 */

global $current_user;

$areaid = intval(vtlib_purify($_REQUEST['area']));

$areaManager = AreaManager::getInstance();

$modules = $_REQUEST['modules'];
$other_modules = $_REQUEST['other_modules'];

// if user don't have customisations duplicate default settings
if (!$areaManager->getSearchByUser()) {
	$areaManager->forceDefaultSettings($current_user->id);
}

if ($_REQUEST['mode'] == 'create') {
	$areaid = $areaManager->createArea($_REQUEST['areaname'],$modules);
} elseif ($_REQUEST['mode'] == 'edit') {
	$areaManager->editArea($areaid,$modules);
} elseif ($_REQUEST['mode'] == 'delete') {
	$areaManager->deleteArea($areaid);
}

header("location: index.php?module=Popup&action=PopupAjax&file=SettingsAreas&show_module=$areaid");
?>
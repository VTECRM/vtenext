<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@54707 */

require_once('modules/Area/Area.php');
$areaManager = AreaManager::getInstance();
$block_area_layout = $areaManager->getToolValue('block_area_layout');
if ($block_area_layout == 1) {
	$block_area_layout = 'checked';
} else {
	$block_area_layout = '';
}

$smarty = new VteSmarty();
$smarty->assign('BLOCK_AREA_LAYOUT', $block_area_layout);
$smarty->display('modules/Popup/AreaTools.tpl');
?>
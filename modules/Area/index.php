<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43942 */

$areaid = vtlib_purify($_REQUEST['area']);

$area = Area::getInstance();
$area->constructById($areaid);
$area->setSessionVars();
$areaid = $area->getId();

if (empty($areaid)) {
	die('This page do not exists.');
}

global $theme, $app_strings, $currentModule;

$smarty = new VteSmarty();
$smarty->assign('APP',$app_strings);
$smarty->assign('THEME',$theme);
$smarty->assign('MODULE',$currentModule);
$smarty->assign('REQUEST_ACTION',$_REQUEST['action']);
$smarty->assign('AREAID',$areaid);
$smarty->assign('AREANAME',$area->getName());
$smarty->assign('AREALABEL',$area->getLabel());
$smarty->assign('MODULES',$area->getModules());
if ($_REQUEST['query'] == 'true' || $_REQUEST['search'] == 'true') { // fix parameter
	$smarty->assign('QUERY_SCRIPT',$_REQUEST['search_text']);
	if (empty($_REQUEST['ajax'])) {
		$smarty->assign('AJAXCALL',true);
	} else {
		$list = $area->search($_REQUEST['search_text']);
	}
} else {
	$list = $area->getLastModified();
}
$smarty->assign('AREAMODULELIST',$list);
$smarty->display('modules/Area/Area.tpl');
?>
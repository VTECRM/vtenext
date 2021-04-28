<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@151308 */

global $allow_exports, $mod_strings, $app_strings, $theme, $current_user;

$module = vtlib_purify($_REQUEST['module']);

//Security Check
if(isPermitted($module,"Export") != "yes") {
	$allow_exports="none";
}

if ($allow_exports=='none' || ( $allow_exports=='admin' && ! is_admin($current_user))) {
	$smarty = new VteSmarty();
	$smarty->assign('APP',$app_strings);
	$smarty->assign('MOD',$mod_strings);
	$smarty->assign("THEME", $theme);
	$smarty->display(vtlib_getModuleTemplate('VteCore','OperationNotPermitted.tpl'));
	exit;
}

$search_type = vtlib_purify($_REQUEST['search_type']);
$export_data = vtlib_purify($_REQUEST['export_data']);
$ids = explode(";", vtlib_purify($_REQUEST['idstring'])); // crmv@37463

$ExpUtils = ExportUtils::getInstance($module);
$ExpUtils->doExport($search_type, $export_data, $ids);

exit;
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@29463
require_once('data/Tracker.php');
require_once('include/utils/utils.php');
require_once('include/utils/UserInfoUtil.php');
require_once 'include/Webservices/DescribeObject.php';

global $currentModule, $app_strings, $log, $current_user, $theme;

if (isset($_REQUEST['record'])) {
	$id = vtlib_purify($_REQUEST['record']);
	$log->debug(" the id is " . $id);
}
$category = getParentTab();

require_once 'modules/Leads/ConvertLeadUI.php';
$uiinfo = new ConvertLeadUI($id, $current_user);

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings); // crmv@98824
$smarty->assign('UIINFO', $uiinfo);
$smarty->assign('MODULE', 'Leads');
$smarty->assign('CATEGORY', $category);
$smarty->assign('THEME', $theme);
$smarty->assign('DATE_FORMAT', $current_user->date_format);
$smarty->assign('CAL_DATE_FORMAT', parse_calendardate($app_strings['NTC_DATE_FORMAT']));
$smarty->display(vtlib_getModuleTemplate($currentModule, 'ConvertLead.tpl'));
//crmv@29463e
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@104566 */
require_once('modules/ChangeLog/ChangeLog.php'); // crmv@164120

global $currentModule, $current_user;
$module = vtlib_purify($_REQUEST['pmodule']);
$record = vtlib_purify($_REQUEST['record']);
$smarty = new VteSmarty();

if ($module === 'Events') $module = 'Calendar'; // crmv@160233

$focus = ChangeLog::getInstance(); // crmv@164120
$query_result = $focus->get_history_query($module, $record);
$history = $focus->get_history_log($module, $record, $query_result);

$smarty->assign('HISTORY',$history);
$smarty->display('modules/ChangeLog/HistoryTab.tpl');
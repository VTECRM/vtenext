<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@91082 */

global $current_user;

require_once('modules/Users/LoginHistory.php');
require_once('modules/Users/Users.php');

// crmv@184240
if (!is_admin($current_user)) {
	echo getTranslatedString('LBL_UNAUTHORIZED_ACCESS', 'Users');
	die();
}
// crmv@184240e

global $theme, $app_strings, $mod_strings, $app_list_strings;
global $current_language, $current_user, $currentModule;
global $adb, $table_prefix;

global $list_max_entries_per_page;
global $urlPrefix;

$log = LoggerManager::getLogger('login_list');

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$focus = LoginHistory::getInstance();
$LVU = ListViewUtils::getInstance();

$smarty = new VteSmarty();

$category = getParenttab();

$userid = intval($_REQUEST['record']);
$username = getUserName($userid);
$qry_result = $adb->pquery("SELECT COUNT(*) as cnt FROM {$focus->table_name} WHERE user_name= ?", array($username));
$no_of_rows = intval($adb->query_result_no_html($qry_result, 0, 'cnt'));

//Retreiving the start value from request
if(isset($_REQUEST['start']) && $_REQUEST['start'] != '') {
	$start = vtlib_purify($_REQUEST['start']);
} else {
	$start=1;
}

//Retreive the Navigation array
$navigation_array = $LVU->getNavigationValues($start, $no_of_rows, '10');

$start_rec = $navigation_array['start'];
$end_rec = $navigation_array['end_val'];
$record_string= $app_strings['LBL_SHOWING']." " .$start_rec." - ".$end_rec." " .$app_strings['LBL_LIST_OF'] ." ".$no_of_rows; // crmv@172864

$navigationOutput = $LVU->getTableHeaderNavigation($navigation_array, $url_string,"Users","ShowHistory",'');

$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MOD", return_module_language($current_language, "Settings"));
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("LIST_HEADER",$focus->getHistoryListViewHeader());
$smarty->assign("LIST_ENTRIES",$focus->getHistoryListViewEntries($username, $navigation_array, $sorder, $sortby));
$smarty->assign("RECORD_COUNTS", $record_string);
$smarty->assign("NAVIGATION", $navigationOutput);
$smarty->assign("CATEGORY",$category);

$smarty->display("ShowHistoryContents.tpl");
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@56023 */

require_once('modules/Settings/LoginProtectionViewer.php');

global $app_strings,$mod_strings;
global $current_language, $current_user, $adb, $table_prefix;
global $list_max_entries_per_page;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$current_module_strings = return_module_language($current_language, 'Settings');
$category = getParenttab();
$user_list = getUserslist(false);

$focus = LoginProtectionViewer::getInstance();
$smarty = new VteSmarty();

$LVU = ListViewUtils::getInstance();

$protection_enabled = $focus->getLoginProtectionStatus();

$userid = vtlib_purify($_REQUEST['userid']);
if($userid == 'all') $userid = '';

//for performance
if(!empty($_REQUEST['ajax'])){
	$qry = "select * from ".$table_prefix."_check_logins";
	$params = array();
	if(!empty($userid)){
		$qry .= " where userid = ? ";
		array_push($params,$userid);
	}
	$qry = mkCountQuery($qry);
	$qry_result = $adb->pquery($qry, $params);
	$no_of_rows = $adb->query_result($qry_result,0,"count");
	
	//Retreiving the start value from request
	$tmp_start = vtlib_purify($_REQUEST['start']);
	if(isset($tmp_start) && $tmp_start != ''){
		$start = $tmp_start;
	}
	else{
		$start = 1;
	}
	
	//Retreive the Navigation array
	$navigation_array = $LVU->getNavigationValues($start, $no_of_rows, $list_max_entries_per_page);
	
	$start_rec = $navigation_array['start'];
	$end_rec = $navigation_array['end_val'];
	$record_string= $app_strings['LBL_SHOWING']." " .$start_rec." - ".$end_rec." " .$app_strings['LBL_LIST_OF'] ." ".$no_of_rows; // crmv@167234
	
	$navigationOutput = $focus->getLoginProtectionNavigation($navigation_array, '',"Settings","LoginProtection",'');
	
	$smarty->assign("LIST_HEADER",$focus->getLoginProtectionHeader());
	$smarty->assign("LIST_ENTRIES",$focus->getLoginProtectionEntries($userid, $navigation_array, $sorder, $sortby));
	$smarty->assign("RECORD_COUNTS", $record_string);
	$smarty->assign("NAVIGATION", $navigationOutput);
}

$smarty->assign("MOD", $current_module_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("THEME_PATH",$theme_path);
$smarty->assign("THEME", $theme);
$smarty->assign("CATEGORY",$category);
$smarty->assign("USERLIST", $user_list);
$smarty->assign("ENABLED", $protection_enabled);

if(!empty($_REQUEST['ajax']))
	$smarty->display("Settings/LoginProtectionPanelContents.tpl");
else	
	$smarty->display("Settings/LoginProtectionPanel.tpl");
?>
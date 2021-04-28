<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@164355 crmv@202301 */

require_once('modules/Settings/AuditTrail.php');
require_once('modules/Settings/AuditTrail/Extractor.php');

global $app_strings, $mod_strings;
global $current_language, $current_user;
$current_module_strings = return_module_language($current_language, 'Settings');

$log = LoggerManager::getLogger('audit_trial');

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$focus = new AuditTrail();
$smarty = new VteSmarty();

$category = getParenttab();

$userid = intval($_REQUEST['userid']);
$interval = $_REQUEST['interval'];

// get valid dates for the time interval
if ($interval == 'custom') {
	$dateStart = getValidDBInsertDateValue($_REQUEST['date_start']);
	$dateEnd = getValidDBInsertDateValue($_REQUEST['date_end']);
} else {
	$CU = CRMVUtils::getInstance();
	$int = $CU->getTimeIntervals(['labels' => false, 'display_dates' => false, 'dates' => ['past', 'until_today', 'around_today']]);
	
	if (array_key_exists($interval, $int)) {
		$dateStart = $int[$interval]['from'];
		$dateEnd = $int[$interval]['to'];
	} else {
		$dateStart = $dateEnd = '';
	}
}


//Retreiving the start value from request
if(isset($_REQUEST['start']) && $_REQUEST['start'] != '') {
	$start = $_REQUEST['start'];
} else {
	$start=1;
}

$config = array(
	'userid' => $userid,
	'from' => $dateStart,
	'to' => $dateEnd,
);

$ATE = new AuditTrailExtractor($config);

$history = $ATE->extract();

$no_of_rows = count($history);

//$no_of_rows = $focus->countEntries($userid, $dateStart, $dateEnd);

//Retreive the Navigation array
$LVU = ListViewUtils::getInstance();
$navigation_array = $LVU->getNavigationValues($start, $no_of_rows, '20');

$start_rec = $navigation_array['start'];
$end_rec = $navigation_array['end_val'];
$record_string= $app_strings['LBL_SHOWING']." " .$start_rec." - ".$end_rec." " .$app_strings['LBL_LIST_OF'] ." ".$no_of_rows;

$navigationOutput = $LVU->getTableHeaderNavigation($navigation_array, $url_string,"Settings","ShowAuditTrail",'');

$smarty->assign("MOD", $current_module_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("THEME_PATH",$theme_path);
$smarty->assign("LIST_HEADER",$ATE->getHeaderLabels());
$smarty->assign("LIST_ENTRIES",$ATE->getListViewData($history, $start_rec, 20)); // crmv@164355
$smarty->assign("RECORD_COUNTS", $record_string);
$smarty->assign("NAVIGATION", $navigationOutput);
$smarty->assign("USERID", $userid);
$smarty->assign("CATEGORY",$category);

// crmv@202301
if($_REQUEST['ajax'] !='')
	$smarty->display("Settings/AuditTrail/ShowContents.tpl");
else	
	$smarty->display("Settings/AuditTrail/Show.tpl");
// crmv@202301e
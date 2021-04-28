<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887

$category = getParentTab();

global $theme, $app_strings, $mod_strings, $currentModule, $current_user;

$theme_path = "themes/" . $theme . "/";
$image_path = $theme_path . "images/";

$smarty = new VteSmarty();
$smarty->assign("MODULE", $currentModule);
$smarty->assign("CATEGORY", $category);
$smarty->assign("APP", $app_strings);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("CHECK", Button_Check($currentModule));
$smarty->assign("CURRENT_USER_ID", $current_user->id);

$activityView = null;

// crmv@178136
if ($_REQUEST['activity_view'] == 'Today') {
	$activityView = 'day';
} elseif ($_REQUEST['activity_view'] == 'This Month') {
	$activityView = 'month';
} elseif ($_REQUEST['activity_view'] == 'This Week') {
	$activityView = 'week';
} else {
	$activityView = $cal_header['view'];
}
// crmv@178136e

$smarty->assign("VIEW", $activityView);

// crmv@189225
if (IN_ICAL) {
	$record = intval($_REQUEST['from_crmid']);
	$smarty->assign("USE_ICAL", true);
	$smarty->assign("ACTIVITY_ID", $_REQUEST['activityid']);
	$smarty->assign("ICALID", $_REQUEST['icalid']);
	$smarty->assign("MESSAGE_ID", $record);
	$smarty->assign("DISABLE_CAL_CONTESTUAL_BUTTON", true);
}
// crmv@189225e

if ($_REQUEST['related_add']) {
	$smarty->assign('DISABLE_CAL_CONTESTUAL_BUTTON', true);
}

$smarty->display("Buttons_List.tpl");
$smarty->display("modules/Calendar/Header.tpl");
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@98866

require_once "include/utils/CommonUtils.php";
require_once "include/CustomFieldUtil.php";
require_once "modules/Calendar/Activity.php";
require_once "modules/Calendar/Calendar.php";
require_once "modules/Calendar/CalendarCommon.php";
require_once "modules/Emails/mail.php";
require_once "modules/PickList/PickListUtils.php";

global $adb, $theme, $mod_strings, $app_strings, $current_user;

$smarty = new VteSmarty();
$smarty->assign("MODULE", 'Calendar');
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);

$theme_path = "themes/{$theme}/";
$image_path = "{$theme_path}images/";
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("THEME", $theme);

$mysel = vtlib_purify($_REQUEST['view']);
$calendar_arr = Array();
$calendar_arr['IMAGE_PATH'] = $image_path;
if (empty($mysel)) {
	if ($current_user->activity_view == "This Year") {
		$mysel = 'year';
	} else if ($current_user->activity_view == "This Month") {
		$mysel = 'month';
	} else if ($current_user->activity_view == "This Week") {
		$mysel = 'week';
	} else {
		$mysel = 'day';
	}
}

$date_data = array();
if (isset($_REQUEST['day'])) {
	$date_data['day'] = $_REQUEST['day'];
}
if (isset($_REQUEST['month'])) {
	$date_data['month'] = $_REQUEST['month'];
}
if (isset($_REQUEST['week'])) {
	$date_data['week'] = $_REQUEST['week'];
}
if (isset($_REQUEST['year'])) {
	if ($_REQUEST['year'] > 2037 || $_REQUEST['year'] < 1970) {
		print("<font color='red'>" . $app_strings['LBL_CAL_LIMIT_MSG'] . "</font>");
		exit();
	}
	$date_data['year'] = $_REQUEST['year'];
}

if (empty($date_data)) {
	$data_value = date('Y-m-d H:i:s');
	preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $data_value, $value);
	$date_data = Array('day' => $value[3], 'month' => $value[2], 'year' => $value[1], 'hour' => $value[4], 'min' => $value[5]);
}

$calendar_arr['calendar'] = new Calendar($mysel, $date_data);
$calendar_arr['view'] = $mysel;
if ($current_user->hour_format == '') {
	$calendar_arr['calendar']->hour_format = 'am/pm';
} else {
	$calendar_arr['calendar']->hour_format = $current_user->hour_format;
}
$smarty->assign("CALENDAR_OBJ", $calendar_arr);

$visibilityPermissions = array();
$visibilityPermissions['eventstatus'] = getFieldVisibilityPermission('Events', $current_user->id, 'eventstatus') == '0';
$visibilityPermissions['taskstatus'] = getFieldVisibilityPermission('Calendar', $current_user->id, 'taskstatus') == '0';
$smarty->assign("VISIBILITY_PERMISSIONS", $visibilityPermissions);

$smarty->assign("EDITVIEW_PERMITTED", isPermitted("Calendar", "EditView") == "yes");
$smarty->assign("DELETE_PERMITTED", isPermitted("Calendar", "Delete") == "yes");

$eventlist_arr = getActivityTypeValues('event', 'array');
$smarty->assign("EVENT_LIST_ARR", $eventlist_arr);

$usersList = getUserslist();
$groupList = getGroupslist();
$smarty->assign("USERS_LIST", $usersList);
$smarty->assign("GROUP_LIST", $groupList);

$smarty->display("modules/Calendar/AddEventUI.tpl");

?>
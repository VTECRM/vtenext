<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $theme,$current_user,$app_strings;
$theme_path = "themes/".$theme."/";
$image_path = $theme_path."images/";
require_once("modules/Calendar/calendarLayout.php");
require_once("modules/Calendar/Calendar.php");
$mysel= $_REQUEST['view'];
$activity_view = $_REQUEST['activity_view'];// crmv@vte10usersFix
$subtab = $_REQUEST['subtab'];
$viewBox = $_REQUEST['viewOption'];
 //crmvillage: START
 //crmv@7633
$view_filter = $_REQUEST['view_filter'];
if (isset($_REQUEST['filter_assigned_user_id']) && $_REQUEST['filter_assigned_user_id'] != "")
	$view_filter = $_REQUEST['filter_assigned_user_id'];
if(empty($view_filter))
{
	$view_filter = 'mine';
}
//crmv@7633e

if(empty($viewBox))
{
	$viewBox = 'hourview';	//crmv@16867
}
if(empty($subtab))
{
	$subtab = 'event';
}
$calendar_arr = Array();
$calendar_arr['IMAGE_PATH'] = $image_path;
/* fix (for Ticket ID:2259 GA Calendar Default View not working) given by dartagnanlaf START --integrated by Minnie */
if(empty($mysel)){
	if($current_user->activity_view == "This Year"){
		$mysel = 'year';
	}else if($current_user->activity_view == "This Month"){
		$mysel = 'month';
	}else if($current_user->activity_view == "This Week"){
		$mysel = 'week';
	}else{
		$mysel = 'day';
	}
}

// crmv@68357 crmv@81126 crmv@189225 - support for ical preview, so far only from messages module
if (IN_ICAL) {
	if ($_REQUEST['activityid'] > 0 && $_REQUEST['is_update'] != '1' &&  $_REQUEST['is_update'] != 'true') { // crmv@189405 crmv@202383
		// it's an existing event
		$date = getSingleFieldValue($table_prefix.'_activity', 'date_start', 'activityid', $_REQUEST['activityid']);
		if ($date) {
			$_REQUEST['year'] = substr($date, 0, 4);
			$_REQUEST['month'] = substr($date, 5, 2);
			$_REQUEST['day'] = substr($date, 8, 2);
		}
	} else {
		// it's only a preview
		$messageFocus = CRMEntity::getInstance('Messages');
		$messageFocus->id = intval($_REQUEST['from_crmid']);
		$icalRow = '';
		$date = $messageFocus->getIcalStartDate(intval($_REQUEST['icalid']), $icalRow);
		if ($date) {
			$_REQUEST['year'] = substr($date, 0, 4);
			$_REQUEST['month'] = substr($date, 5, 2);
			$_REQUEST['day'] = substr($date, 8, 2);
			$calendar_arr['icals'] = array($icalRow);
		}
	}
}
// crmv@68357e crmv@81126e crmv@189225e

/* fix given by dartagnanlaf END --integrated by Minnie */
$date_data = array();
if ( isset($_REQUEST['day']))
{

        $date_data['day'] = $_REQUEST['day'];
}

if ( isset($_REQUEST['month']))
{
        $date_data['month'] = $_REQUEST['month'];
}

if ( isset($_REQUEST['week']))
{
        $date_data['week'] = $_REQUEST['week'];
}

if ( isset($_REQUEST['year']))
{
        if ($_REQUEST['year'] > 2037 || $_REQUEST['year'] < 1970)
        {
		print("<font color='red'>".$app_strings['LBL_CAL_LIMIT_MSG']."</font>");
                exit;
        }
        $date_data['year'] = $_REQUEST['year'];
}

if(empty($date_data))
{
	//crmv@17997
	$date_data = Array(
		'day'=>date('d'),
		'month'=>date('m'),
		'year'=>date('Y'),
		'hour'=>0,
		'min'=>0,
	);
	//crmv@17997 end
}
$calendar_arr['calendar'] = new Calendar($mysel,$date_data);
if($current_user->hour_format != '') 
	$calendar_arr['calendar']->hour_format=$current_user->hour_format;
if ($viewBox == 'hourview' && ($mysel == 'day' || $mysel == 'week' || $mysel == 'month' || $mysel == 'year'))
{
	$calendar_arr['calendar']->add_Activities($current_user,"",$view_filter); //crmv@7633
}
$calendar_arr['view'] = $mysel;
$calendar_arr['in_ical'] = IN_ICAL; // crmv@189225
calendar_layout($calendar_arr,$viewBox,$subtab,$view_filter,$activity_view); //crmv@7633 // crmv@vte10usersFix
?>
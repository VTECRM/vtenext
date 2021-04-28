<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/utils/utils.php');
require_once('modules/Calendar/Appointment.php');
require_once('modules/Calendar/Date.php');
require_once('modules/Calendar/wdCalendar/php/functions.php');
global $adb,$current_user,$table_prefix;
$start = $_REQUEST['startdate'];
list($year,$month,$day) = explode("-",$start);
if (!checkdate($month, $day,$year)){
	$year = date('Y');
	$month = date('n');
	$day = date('j');
}
if ($month == date('n')){
	$hour = date("H");
	$minute = date("i");
	$second = date("s");
}
else{
	$hour = $minute = $second = 0;
}
$firstday = mktime($hour,$minute,$second,$month,1,$year);
$lastday = mktime(23,59,59,$month,date('t',$firstday),$year);
$start_date = array('ts'=>$firstday);
$start_date = new vt_DateTime($start_date,true);
$end_date = array('ts'=>$lastday);
$end_date = new vt_DateTime($end_date,true);
$appointmentInstance = Appointment::getInstance();
$sql = $appointmentInstance->readAppointment($current_user,$start_date,$end_date,'','mine',true);

// crmv@103023
$sql_head = "SELECT ".$adb->database->SQLDate('d','date_start')." as day,".$adb->database->SQLDate('m','date_start')." as month FROM ( ";
$sql = $sql_head.$sql;
$sql .= " ) activity";
$sql .=" group by ".$adb->database->SQLDate('d','activity.date_start').",".$adb->database->SQLDate('m','activity.date_start');
// crmv@103023e

$res = $adb->query($sql);
$ret_arr = Array();
$ret_arr['success'] = false;
if ($res){
	$ret_arr['success'] = true;
	$ret_arr['result'] = Array();
	while ($row = $adb->fetchByAssoc($res,-1,false)){
		$ret_arr['result'][(int)$row['day']] = 1;
	}
}
echo Zend_Json::encode($ret_arr);
exit;
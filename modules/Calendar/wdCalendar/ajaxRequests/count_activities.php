<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("modules/Calendar/wdCalendar/php/functions.php");
require_once("modules/Calendar/calendarLayout.php");
require_once("modules/Calendar/Calendar.php");
global $current_user;
$cal_log =& LoggerManager::getLogger('calendar');

preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',$_REQUEST['startdate'],$value);
$date_data = Array('day'=>$value[3],'month'=>$value[2],'year'=>$value[1],'hour'=>$value[4],'min'=>$value[5]);

$calendar_arr = Array();
$calendar_arr['calendar'] = new Calendar($_REQUEST['calendar_type'],$date_data);
$calendar_arr['view'] = $_REQUEST['calendar_type'];
	
echo str_replace('&nbsp;',' ',getEventInfo($calendar_arr,'listcnt',$_REQUEST['view_filter']));
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once('include/utils/utils.php');
global $adb,$current_user;
//al salvataggio di un evento in modalità veloce l'array "values" è completamente diverso da quello del salvataggio normale, per cui devo aggiustare l'array
if (isset($values['CalendarTitle']) && isset($values['EventType'])){ //sono in creazione veloce
	include_once('modules/Calendar/wdCalendar/php/functions.php');
	$values['activity_mode'] = 'Events';
	$values['assigntype'] = 'U';
	$values['mode'] = 'create';
	$values['subject'] = $values['CalendarTitle'];
	$values['activitytype'] = $values['EventType'];
	$values['assigned_user_id'] = $current_user->id;
	$values['is_all_day_event'] = $values['IsAllDayEvent'];
	$values['description'] = $values['Description'];
	$values['location'] = $values['Location'];
	$st = js2PhpTime($st);
	$start_date = array();
	$start_date = explode(' ',date("Y-m-d H:i",js2PhpTime($values['CalendarStartTime'])));
	$values['date_start'] = $start_date[0];
	$start_time = explode(':',$start_date[1]);
	$values['time_start'] = $start_time[0].':'.$start_time[1];
	$values['starthr'] = $start_time[0];
	$values['startmin'] = $start_time[1];
	$et = js2PhpTime($et);
	$end_date = array();
	$end_date = explode(' ',date("Y-m-d H:i",js2PhpTime($values['CalendarEndTime'])));
	$values['due_date'] = $end_date[0];
	$end_time = explode(':',$end_date[1]);
	$values['time_end'] = $end_time[0].':'.$end_time[1];
	$values['endhr'] = $end_time[0];
	$values['endmin'] = $end_time[1];
	$values['eventstatus'] = 'Planned';
	$values['taskpriority'] = 'Low';
	$values['ajaxCalendar'] = 'quickAdd';
	$current_user_date_format_backup = $current_user->date_format;
	$current_user->date_format = 'yyyy-mm-dd';
}
elseif (isset($values['calendarId'])){ //sono in modifica veloce (spostamento evento)
	include_once('modules/Calendar/wdCalendar/php/functions.php');
	$obj = CRMEntity::getInstance('Calendar');
	$obj->retrieve_entity_info_no_html($values['calendarId'],'Calendar');
	$start_date = array();
	$end_date = array();
	$start_date = explode(' ',date("Y-m-d H:i",js2PhpTime($values['CalendarStartTime'])));
	$end_date = explode(' ',date("Y-m-d H:i",js2PhpTime($values['CalendarEndTime'])));
	$values = $obj->column_fields;
	$values['activity_mode'] = 'Events';
	$values['assigntype'] = 'U';
	$values['mode'] = 'create';	
	$values['date_start'] = $start_date[0];
	$start_time = explode(':',$start_date[1]);
	$values['time_start'] = $start_time[0].':'.$start_time[1];
	$values['starthr'] = $start_time[0];
	$values['startmin'] = $start_time[1];
	$et = js2PhpTime($et);
	$values['due_date'] = $end_date[0];
	$end_time = explode(':',$end_date[1]);
	$values['time_end'] = $end_time[0].':'.$end_time[1];
}
//...ora si può utilizzare l'array values con tutti i valori a posto...

?>
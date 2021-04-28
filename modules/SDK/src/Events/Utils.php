<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Calendar/Appointment.php');
require_once('modules/Calendar/Date.php');
require_once('modules/Calendar/wdCalendar/php/functions.php');

function getEventList($userid,$mode='',$year,$month,$day,&$count='', $onlycount = false) {
	global $adb, $history_max_viewed, $current_user,$table_prefix;
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
	$firstday = mktime($hour,$minute,$second,$month,$day,$year);
	$lastday = mktime(23,59,59,$month,date('t',$firstday),$year);
	$start_date = array('ts'=>$firstday);
	$start_date = new vt_DateTime($start_date,true);
	$end_date = array('ts'=>$lastday);
	$end_date = new vt_DateTime($end_date,true);
	$appointmentInstance = Appointment::getInstance();
	$sql = $appointmentInstance->readAppointment($current_user,$start_date,$end_date,'','mine',true);
	
	// crmv@103023
	$sql_head = "SELECT DISTINCT {$table_prefix}_activity.activityid,{$table_prefix}_activity.date_start,{$table_prefix}_activity.time_start,{$table_prefix}_activity.due_date,{$table_prefix}_activity.time_end,{$table_prefix}_activity.subject,{$table_prefix}_activity.description FROM( ";
	$sql = $sql_head.$sql;
	if ($onlycount) {
		$sql = replaceSelectQuery($sql,'count(*) as cnt');
	}
	$sql .= ") {$table_prefix}_activity ";
	$sql.=" order by {$table_prefix}_activity.date_start,{$table_prefix}_activity.time_start";
	// crmv@103023e
	
	if ($mode == 'all') {
		$result = $adb->query($sql);
	} else {
		$result = $adb->limitQuery($sql,0,$history_max_viewed);
	}

	if ($onlycount) {
		$count = $adb->query_result_no_html($result, 0, 'cnt');
		return array();
	}
	// crmv@36871e
	$count = 0;
	$now = strtotime(date('Y-m-d H:i'));
	$now_onlyday = strtotime(date('Y-m-d',$firstday));
	$list = array();
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$expired = 'no';
			$is_now = 'no';
			$is_today = 'no';
			if (strtotime($row['due_date']." ".$row['time_end']) <=$now) {
				$count++;
				$expired = 'yes';
			}
			if (strtotime($row['date_start']) <= $now_onlyday && strtotime($row['due_date']) >=$now_onlyday) {
				$is_today = 'yes';
			}
			if (strtotime($row['date_start']." ".$row['time_start']) <= $now && strtotime($row['due_date']." ".$row['time_end']) >=$now) {
				$is_now = 'yes';
			}
			$list[] = array(
				'activityid'=>$row['activityid'],
				'subject'=>$row['subject'],
				'date'=>$row['date_start'],
				'date_start'=>$row['date_start']." ".$row['time_start'],
				'date_end'=>$row['due_date']." ".$row['time_end'],
				'description'=>textlength_check($row['description']),
				'expired'=>$expired,
				'is_now'=>$is_now,
				'is_today'=>$is_today,
				'now_onlyday'=>$now_onlyday,
			);
		}
	}
	return $list;
}

// crmv@36871
function getHtmlEventList($userid,$mode='',$year,$month,$day,&$count='') {
	global $theme;

	$list = getEventList($userid,$mode,$year,$month,$day,$count);
	$listbydate = array();
	foreach ($list as $info) {
		$unseen = false;
		$expired_str = getTranslatedString('Will begin','Calendar');
		if ($info['expired'] == 'yes') {
			$unseen = true;
			$expired_str = getTranslatedString('Begun','Calendar');
			if ($info['is_now'] == 'yes') {
				$expired_str = getTranslatedString('Will begin','Calendar');
			}
		}
		$timestampAgo = $info['date'];
		if ($info['is_today'] == 'yes') {
			if (strtotime(date('Y-m-d')) == $info['now_onlyday']){
				$group = getTranslatedString('LBL_TODAY');
			}
			else{
				if (isModuleInstalled('ModNotifications')) {
					require_once('modules/ModNotifications/models/Comments.php');
					$model = new ModNotifications_CommentsModel(array('createdtime'=>date('Y-m-d',$info['now_onlyday'])));
					$group = $model->timestamp();
				}
				else{
					$group = date('Y-m-d',$info['now_onlyday']);
				}
			}
		} else {
			$group = getTranslatedString('LBL_NEXT_DAYS','Calendar');
		}
		// crmv@164654
		$timestampAgo = CRMVUtils::timestampAgo($info['date_start']);
		$timestamp = CRMVUtils::timestamp($info['date_start']);
		if (strpos($timestampAgo,'-') !== false) {
			$timestampAgo = $timestamp;
		}
		// crmv@164654e
		$eventItem = array(
			'activityid'=>$info['activityid'],
			'subject'=>$info['subject'],
			'duration'=>$info['exp_duration'],
			'description'=>$info['description'],
			'timestamp'=>$timestamp,
			'timestamp_ago'=>$timestampAgo,
			'group'=>$group,
			'expired_str'=>$expired_str,
			'unseen'=> $unseen,
		);
		$listbydate[$group][] = $eventItem;

	}
	$min_events_in_period = 2;

	$smarty = new VteSmarty();
	$smarty->assign('THEME', $theme);
	$smarty->assign('EVENTLIST_EVENTSINPERIOD', $min_events_in_period);
	$smarty->assign('EVENTLIST_DATE', $listbydate);

	return $smarty->fetch('modules/SDK/src/Events/EventList.tpl');
}
// crmv@36871e

//crmv@28295e	//crmv@30009e
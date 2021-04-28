<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@28295	//crmv@30009
function getTodosList($userid,$mode='',&$count='', $onlycount = false) { // crmv@36871
	global $adb, $history_max_viewed, $current_user,$table_prefix; // crmv@25610

	$arr = getCalendarType('todo','history');
	$pickListValue_comma = "(";
	$noofpickrows = is_array($arr['status_field_value']) ? count($arr['status_field_value']) : 0; // crmv@172864
	if ($noofpickrows!=0){
		for($k=0; $k < $noofpickrows; $k++)
		{
			$pickListValue = $arr['status_field_value'][$k];
			$pickListValue_comma.="'".$pickListValue."'";
			if($k < ($noofpickrows-1))
			$pickListValue_comma.=',';
		}
		$pickListValue_comma.= ")";
	}
	else  $pickListValue_comma = "('')";
	$calendar_condition = $pickListValue_comma;

	// crmv@64325
	$setypeCond = '';
	if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
		$setypeCond = "AND {$table_prefix}_crmentity.setype = 'Calendar'";
	}
	// crmv@36871
	$sql = 'SELECT activityid, due_date, subject, exp_duration, '.$table_prefix.'_activity.description FROM '.$table_prefix.'_activity
			INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_activity.activityid
			WHERE deleted = 0 '.$setypeCond.' and '.$table_prefix.'_activity.activitytype = \'Task\' and '.$table_prefix.'_activity.status not in '.$calendar_condition.' AND '.$table_prefix.'_crmentity.smownerid = ?
			ORDER BY '.$table_prefix.'_activity.due_date';
	
	if ($onlycount) {
		// optimezed query for count only
		if ($adb->isMysql()) {
			$countcond = $table_prefix.'_activity.due_date <= NOW()';
		} elseif ($adb->isMssql()) {
			$countcond = $table_prefix.'_activity.due_date <= CURRENT_TIMESTAMP';
		} elseif ($adb->isOracle()) {
			$countcond = $table_prefix.'_activity.due_date <= CURRENT_DATE';
		}
		$sql = 'SELECT count(*) as cnt FROM '.$table_prefix.'_activity
		INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_activity.activityid
		WHERE deleted = 0 '.$setypeCond.' and '.$table_prefix.'_activity.activitytype = \'Task\' and '.$table_prefix.'_activity.status not in '.$calendar_condition.' AND '.$table_prefix.'_crmentity.smownerid = ? and '.$countcond;
	}
	// crmv@64325e

	// crmv@185894
	if ($mode == 'all') {
		if ($onlycount)
			$result = $adb->pquerySlave('BadgeCount',$sql,array($userid));
		else
			$result = $adb->pquery($sql,array($userid));
	} else {
		if ($onlycount)
			$result = $adb->limitpQuerySlave('BadgeCount',$sql,0,$history_max_viewed,array($userid));
		else
			$result = $adb->limitpQuery($sql,0,$history_max_viewed,array($userid));
	}
	// crmv@185894e

	if ($onlycount) {
		$count = $adb->query_result_no_html($result, 0, 'cnt');
		return array();
	}
	// crmv@36871e

	$count = 0;
	$now = strtotime(date('Y-m-d'));
	$list = array();
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$expired = 'no';
			$is_now = 'no';
			if (strtotime($row['due_date']) <= $now) {
				$count++;
				$expired = 'yes';
				if (strtotime($row['due_date']) == $now) {
					$is_now = 'yes';
				}
			}
			$list[] = array('activityid'=>$row['activityid'],'subject'=>$row['subject'],'date'=>$row['due_date'],'description'=>textlength_check($row['description']),'expired'=>$expired,'is_now'=>$is_now, 'exp_duration'=>$row['exp_duration']);
		}
	}
	return $list;
}

// crmv@36871
function getHtmlTodosList($userid,$mode='',&$count='') {
	global $theme;

	$list = getTodosList($userid,$mode,$count);
	$listbydate = array();
	foreach ($list as $info) {
		$unseen = false;
		$expired_str = getTranslatedString('Will expire','Calendar');
		if ($info['expired'] == 'yes') {
			$unseen = true;
			$expired_str = getTranslatedString('Expired','Calendar');
			if ($info['is_now'] == 'yes') {
				$expired_str = getTranslatedString('Will expire','Calendar');
			}
		}
		$timestampAgo = $info['date'];
		if ($info['is_now'] == 'yes') {
			$timestampAgo = getTranslatedString('LBL_TODAY');
		} else {
			// crmv@164654
			$timestampAgo = CRMVUtils::timestampAgo($info['date']);
			$timestamp = CRMVUtils::timestamp($info['date']);
			if (strpos($timestampAgo,'-') !== false) {
				$timestampAgo = $timestamp;
			}
			// crmv@164654e
		}
		if (empty($info['exp_duration'])) $info['exp_duration'] = 'DurationMore';

		$todoItem = array(
			'activityid'=>$info['activityid'],
			'subject'=>$info['subject'],
			'duration'=>$info['exp_duration'],
			'description'=>$info['description'],
			'timestamp'=>$timestamp,
			'timestamp_ago'=>$timestampAgo,
			'expired_str'=>$expired_str,
			'unseen'=> $unseen,
		);

		$listbydate[$timestampAgo][] = $todoItem;
		$listbyduration[getTranslatedString($info['exp_duration'], 'Calendar')][] = $todoItem;

	}
	if (is_array($listbyduration)) ksort($listbyduration);

	$min_todos_in_period = 2;

	$smarty = new VteSmarty();
	$smarty->assign('THEME', $theme);
	$smarty->assign('TODOLIST_TODOSINPERIOD', $min_todos_in_period);
	$smarty->assign('TODOLIST_DATE', $listbydate);
	$smarty->assign('TODOLIST_DURATION', $listbyduration);

	return $smarty->fetch('modules/SDK/src/Todos/TodoList.tpl');
}
// crmv@36871e

//crmv@28295e	//crmv@30009e
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $app_strings,$table_prefix;
global $currentModule,$image_path,$theme,$adb, $current_user;

require_once('modules/Calendar/Activity.php');

$cur_time = time();
// crmv@128133
VteSession::setMulti(array(
	'last_reminder_check_time' => $cur_time,
	'next_reminder_interval' => 60,
));
// crmv@128133e
if(VteSession::get('next_reminder_time') == 'None') {
	return;
} elseif(VteSession::hasKey('next_reminder_interval') && ((VteSession::get('next_reminder_time') -
		VteSession::get('next_reminder_interval')) > $cur_time)) {
	echo "<script type='text/javascript' id='activityreminder_callback_interval_'>".
		(VteSession::get('next_reminder_interval') * 1000)."</script>";
	return;
}

$log = LoggerManager::getLogger('Activity_Reminder');
$smarty = new VteSmarty();
if(isPermitted('Calendar','index') == 'yes'){
	$active = $adb->pquery("select reminder_interval from ".$table_prefix."_users where id=?",array($current_user->id));
	$active_res = $adb->query_result($active,0,'reminder_interval');
	if($active_res == 'None') {
		VteSession::set('next_reminder_time', 'None');
	}
	if($active_res!='None'){
		$interval=$adb->query_result($active,0,"reminder_interval");
		$intervalInMinutes = ConvertToMinutes($interval);
		// check for reminders every minute
		$time = time();
		VteSession::set('next_reminder_time', $time + ($intervalInMinutes * 60));
		$date = date('Y-m-d', strtotime("+$intervalInMinutes minutes", $time));
		$time = date('H:i',   strtotime("+$intervalInMinutes minutes", $time));
		// crmv@64325
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND {$table_prefix}_crmentity.setype = 'Calendar'";
		}
		//crmv@19691 crmv@103120
		$callback_query =
		"SELECT {$table_prefix}_act_reminder_popup.*
		FROM {$table_prefix}_act_reminder_popup
		INNER JOIN {$table_prefix}_crmentity on {$table_prefix}_act_reminder_popup.recordid = {$table_prefix}_crmentity.crmid $setypeCond
		WHERE {$table_prefix}_crmentity.smownerid = {$current_user->id} AND {$table_prefix}_crmentity.deleted = 0
		AND {$table_prefix}_act_reminder_popup.status = 0
		AND (
			{$table_prefix}_act_reminder_popup.date_start < '$date'
			OR ({$table_prefix}_act_reminder_popup.date_start = '$date' AND {$table_prefix}_act_reminder_popup.time_start <= '$time')
		)";
		//
		//crmv@19691e crmv@64325e crmv@103120e
		$result = $adb->query($callback_query);

		$cbrows = $adb->num_rows($result);
		if($cbrows > 0) {
			for($index = 0; $index < $cbrows; ++$index) {
				$reminderid = $adb->query_result($result, $index, "reminderid");
				$cbrecord = $adb->query_result($result, $index, "recordid");
				$cbmodule = $adb->query_result($result, $index, "semodule");

				$focus = CRMEntity::getInstance($cbmodule);
				if (!isRecordExists($cbrecord)) {
					$del_qry = "delete from ".$table_prefix."_act_reminder_popup where reminderid = ?";
					$adb->pquery($del_qry,Array($reminderid));
					continue;
				}
								
				if (in_array($cbmodule,array('Calendar','Events'))) {
					// crmv@207365
					$r = $focus->retrieve_entity_info($cbrecord,$cbmodule,false);
					if(in_array($r,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND'))){
						$del_qry = "delete from ".$table_prefix."_act_reminder_popup where reminderid = ?";
						$adb->pquery($del_qry,Array($reminderid));
						continue;
					}
					// crmv@207365e
					$cbsubject = $focus->column_fields['subject'];
					$cbactivitytype   = $focus->column_fields['activitytype'];
					$cbdate   = $focus->column_fields["date_start"];
					$cbtime   = $focus->column_fields["time_start"];
					// crmv@98866
					$duedate = $focus->column_fields["due_date"];
					$duetime = $focus->column_fields["time_end"];
					$location = $focus->column_fields["location"];
					// crmv@98866 end
				} else {
					// For non-calendar records.
					$cbsubject      = array_values(getEntityName($cbmodule, $cbrecord));
					$cbsubject      = $cbsubject[0];
					$cbactivitytype = getTranslatedString($cbmodule, $cbmodule);
					$cbdate         = $adb->query_result($result, $index, 'date_start');
					$cbtime         = $adb->query_result($result, $index, 'time_start');
				}

				if($cbactivitytype=='Task')
					$cbstatus   = $focus->column_fields["taskstatus"];
				else
					$cbstatus   = $focus->column_fields["eventstatus"];
				// Appending recordid we can get unique callback dom id for that record.
				$popupid = "ActivityReminder_$cbrecord";
				if($cbdate <= date('Y-m-d')){
					if(substr($cbdate,0,10) == date('Y-m-d') && $cbtime > date('H:i')) $cbcolor = '';
					else $cbcolor= '#FF1515';
				}
				$smarty->assign("THEME", $theme);
				$smarty->assign("popupid", $popupid);
				$smarty->assign("APP", $app_strings);
				$smarty->assign("cbreminderid", $reminderid);
				$smarty->assign("cbdate", getDisplayDate($cbdate));
				$smarty->assign("cbtime", $cbtime);
				$smarty->assign("cbsubject", $cbsubject);
				$smarty->assign("cbmodule", $cbmodule);
				$smarty->assign("cbrecord", $cbrecord);
				$smarty->assign("cbstatus", $cbstatus);
				$smarty->assign("cbcolor", $cbcolor);
				$smarty->assign("cblinkdtl", $cblinkdtl);
				$smarty->assign("activitytype", $cbactivitytype);
				
				// crmv@98866
				
				// crmv@103354
				$allDate = $cbdate . ' ' . $cbtime;
				$adjustedDate = adjustTimezone($allDate, 0, null, true);
				$cbdate1 = substr($adjustedDate, 0, 10);
				if (strlen($adjustedDate) > 10) {
					$cbtime1 = substr($adjustedDate, strpos($adjustedDate, ' ') + 1, 5);
				}
				$allDate1 = $cbdate1 . ' ' . $cbtime1;
				$allDateObj = new DateTime($allDate1);
				//crmv@138716
				if($allDate1 > date('Y-m-d H:i:s')){
					$difference = (strtotime($allDate1) - time()) / 60;
					if(intval($difference) > 1) {
						if(intval($difference) > 60) $period = intval($difference/60)." ".getTranslatedString('LBL_HOURS');
						else $period = intval($difference)." ".getTranslatedString('lbl_minutes', 'ModComments');
					}elseif(intval($difference) == 0){
						$diff_in_seconds = ($difference * 60);
						if($diff_in_seconds > 1){
							$period = $diff_in_seconds." ".getTranslatedString('lbl_seconds', 'ModComments');
						}else{
							$period = $diff_in_seconds." ".getTranslatedString('lbl_second', 'ModComments');
						}
					}else{
						$period = intval($difference)." ".getTranslatedString('lbl_minute', 'ModComments');
					}
					$smarty->assign('OVERDUE', getTranslatedString('STARTING_IN'));
				}else{
					$period = getFriendlyDate($allDateObj->format('Y-m-d H:i:s'));
					$smarty->assign('OVERDUE', getTranslatedString('LBL_OVERDUE'));
				}
				//crmv@138716e
				$smarty->assign("PERIOD", $period);
				// crmv@103354e
				
				$when_string = getTranslatedString('LBL_WHEN') . ':';
				$when_string .= ' ' . $allDateObj->format('d') . '-' . getTranslatedString($allDateObj->format('M')) . '-' . $allDateObj->format('y');
				$when_string .= ' ' . getTranslatedString('LBL_FROM_HOUR') . ' ' . $cbtime;
				if (!empty($duetime)) {
					$when_string .= ' ' . getTranslatedString('LBL_TO_HOUR') . ' ' . $duetime;
				}
				
				$location_string = '';
				if (!empty($location)) {
					$location_string .= getTranslatedString('LBL_APP_LOCATION') . ':';
					$location_string .= ' ' . $location;
				}
				
				$smarty->assign("WHEN_STRING", $when_string);
				$smarty->assign("LOCATION_STRING", $location_string);
				// crmv@98866 end
				
				$smarty->display("ActivityReminderCallback.tpl");

				$mark_reminder_as_read = "UPDATE ".$table_prefix."_act_reminder_popup set status = 1 where reminderid = ?";
				$adb->pquery($mark_reminder_as_read, array($reminderid));
			}
			
			// crmv@104512
			echo "<script type='text/javascript'>
					if (!window.oldWindowDocumentTitle) {
						window.oldWindowDocumentTitle = window.top.document.title.replace(' - ' + browser_title, '');
						updateBrowserTitle('".$app_strings['LBL_APPOINTMENT_REMINDER']."');
					}
				</script>";
			// crmv@104512e
		} else {
			//crmv@19691 crmv@64325
			$callback_query =
			"SELECT ".$table_prefix."_act_reminder_popup.* FROM ".$table_prefix."_act_reminder_popup inner join ".$table_prefix."_crmentity on ".$table_prefix."_act_reminder_popup.recordid = ".$table_prefix."_crmentity.crmid where " .
			" ".$table_prefix."_act_reminder_popup.status = 0 and " .
			" ".$table_prefix."_crmentity.smownerid = ".$current_user->id." and ".$table_prefix."_crmentity.deleted = 0 $setypeCond ".
			"AND ".$table_prefix."_act_reminder_popup.reminderid > 0 ORDER BY date_start DESC , ".
			"".$table_prefix."_act_reminder_popup.time_start DESC";
			//crmv@19691e crmv@64325e
			$result = $adb->limitQuery($callback_query,0,1);
			$it = new SqlResultIterator($adb, $result);
			$nextReminderTime = null;
			foreach ($it as $row) {
				$nextReminderTime = strtotime($row->date_start.' '.$row->time_start);
			}
			VteSession::set('next_reminder_time', $nextReminderTime - ($intervalInMinutes * 60));
		}
		echo "<script type='text/javascript' id='activityreminder_callback_interval_'>".
				(VteSession::get('next_reminder_interval') * 1000)."</script>";
	}
}

?>
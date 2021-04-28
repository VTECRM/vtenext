<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33448 crmv@55708 crmv@62394 crmv@186476 */

// TODO: move all the other functions here
class CalendarTracking {
	
	// properties moved to vteprop
	
	public static function isEnabledForModule($module) {
		$VTEP = VTEProperties::getInstance();
		$enabled = $VTEP->getProperty('calendar_tracking.enabled');
		$detailview_modules = $VTEP->getProperty('calendar_tracking.detailview_modules');
		$turbolift_modules = $VTEP->getProperty('calendar_tracking.turbolift_modules');
		return ($enabled && (in_array($module, $detailview_modules) || in_array($module, $turbolift_modules)));
	}
	
	public static function isEnabledForDetailview($module) {
		$VTEP = VTEProperties::getInstance();
		$enabled = $VTEP->getProperty('calendar_tracking.enabled');
		$detailview_modules = $VTEP->getProperty('calendar_tracking.detailview_modules');
		return ($enabled && in_array($module, $detailview_modules));
	}
	
	public static function isEnabledForTurbolift($module) {
		$VTEP = VTEProperties::getInstance();
		$enabled = $VTEP->getProperty('calendar_tracking.enabled');
		$turbolift_modules = $VTEP->getProperty('calendar_tracking.turbolift_modules');
		return ($enabled && in_array($module, $turbolift_modules));
	}
	
	public static function getTrackerData($module, $recordid) {
		$return = array();
		
		$enable_buttons = true;
		$active_tracked = getActiveTracked();
		if ($active_tracked !== false && $recordid != $active_tracked) {
			$enable_buttons = false;
		}
		$return['current_tracked'] = $active_tracked;
		$return['enable_buttons'] = $enable_buttons;
		
		$buttons = array();
		if ($enable_buttons) {
			
			if ($recordid > 0 && $recordid == $active_tracked) {
				$buttons['start'] = false;
				$buttons['pause'] = true;
				$buttons['stop'] = true;
			} else {
				$buttons['start'] = true;
				$buttons['pause'] = false;
				$buttons['stop'] = false;
			}
			
			$return['buttons'] = $buttons;
			
			$labels = array(
				'start' => getTranslatedString('LBL_TRACK_START_FOR', 'APP_STRING').getTranslatedString('SINGLE_'.$module),
				'pause' => getTranslatedString('LBL_TRACK_PAUSE_FOR', 'APP_STRING').getTranslatedString('SINGLE_'.$module),
				'stop' => getTranslatedString('LBL_TRACK_STOP_FOR', 'APP_STRING').getTranslatedString('SINGLE_'.$module),
			);
			$return['buttons_labels'] = $labels;
			
		} else {
			$active_tracked_module = getSalesEntityType($active_tracked);
			$active_tracked_name = array_values(getEntityName($active_tracked_module, $active_tracked));
			$active_tracked_entity_type = getSingleModuleName($active_tracked_module, $active_tracked);
			
			$return['current_tracked_name'] = $active_tracked_name[0];
			$return['current_tracked_module'] = $active_tracked_module;
			$return['current_tracked_entity_type'] = $active_tracked_entity_type;
		}
		
		//crmv@65492 - 18
		$module_heldesk_active = Vtlib_isModuleActive('HelpDesk');
		$tickets_available_permission = false;
		if($module_heldesk_active){
			//check also for user profile permissions
			if(isPermitted('HelpDesk','EditView','') == 'yes'){
				$tickets_available_permission = true;
			}
		}
		$return['tickets_available'] = $tickets_available_permission;
		//crmv@65492e - 18
		
		$return['already_tracking_by_other'] = getOtherUsersTracking($recordid);
		
		return $return;
	}
	
	// create the tracking event and inject the id in the request, so it's linked to the outgoing email
	//crmv@69922
	public static function trackSendEmail($startTs, $stopTs = null, $otherFields=array()) {
		
		$now = $stopTs ?: time();
		$timediff = $now - $startTs;
		$subject = trim(vtlib_purify($_REQUEST['subject']));
		
		if ($timediff <= 0 || $timediff > 3600*24) return false;
		if (empty($subject)) $subject = "Tracking Email";
		
		$fields = array(
			'subject' => $subject,
			'description' => 'Email tracking',
			'date_start' => date('Y-m-d', $startTs),
			'time_start' => date('H:i', $startTs),
			'due_date' => date('Y-m-d', $now),
			'time_end' => date('H:i', $now),
		);
		$fields = array_merge($fields, $otherFields);
		$calid = self::createCalTrackForCreate($fields);
		
		if ($calid > 0) {
			// now inject the id
			if (substr($_REQUEST['relation'], -1, 1) != '|') $_REQUEST['relation'] .= '|';
			$_REQUEST['relation'] .= $calid;
		}
		
		return $calid;
	}
	
	public static function createCalTrackForCreate($fields) {
		global $current_user;
		
		$focus = CRMEntity::getInstance('Events');
		
		$focus->mode = '';
		
		// set the fields
		$focus->column_fields = array_merge($focus->column_fields, $fields);
		
		// sanitize
		$focus->column_fields['date_start'] = substr($focus->column_fields['date_start'],0,10);
		$focus->column_fields['time_start'] = substr($focus->column_fields['time_start'],0,5);
		$focus->column_fields['due_date'] = substr($focus->column_fields['due_date'],0,10);
		$focus->column_fields['time_end'] = substr($focus->column_fields['time_end'],0,5);
		
		// forced values
		$focus->column_fields['activitytype'] = 'Tracked';
		$focus->column_fields['eventstatus'] = 'Held';
		$focus->column_fields['taskpriority'] = 'Low';
		$focus->column_fields['visibility'] = 'Standard';
		$focus->column_fields['assigned_user_id'] = $current_user->id;
		
		//save
		$focus->save('Events');
		
		return $focus->id;
	}
	//crmv@69922e
	
	// crmv@OPER4876 crmv@189362
	public static function getUser($id) {
		static $users = array();
		if (!isset($users[$id])) {
			$user = CRMEntity::getInstance('Users');
			$user->retrieveCurrentUserInfoFromFile($id);
			$users[$id] = $user;
		}
		return $users[$id];
	}
	public static function getDailyCost($projecttask, $projectplan) {
		global $adb, $table_prefix;
		$result = $adb->pquery("
			SELECT {$table_prefix}_account.daily_cost
			FROM {$table_prefix}_project
			INNER JOIN {$table_prefix}_account ON {$table_prefix}_account.accountid = {$table_prefix}_project.linktoaccountscontacts
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_account.accountid
			WHERE deleted = 0 AND projectid = ?", array($projectplan));
		if ($result && $adb->num_rows($result) > 0) {
			return intval($adb->query_result($result,0,'daily_cost'));
		}
		return 0;
	}
	public static function roundHours($value) {
		$minutes = $value * 60;
		$first_digit = substr($minutes, -1);
		if ($first_digit < 5) {			// from 0 to 4 -> 4
			$minutes = $minutes - $first_digit;
		} elseif ($first_digit > 5) {	// from 6 to 9 -> 10
			$minutes = $minutes - $first_digit + 10;
		}
		$hours = round($minutes/60,2);
		return $hours;
	}
	/*
	 * Calculate the hours tracked for tickets and project tasks:
	 * - in tickets sum the duration of related activities
	 * - in project tasks sum the duration of related activities and the duration of activities of related tickets
	 */
	public static function recalculateHours($record, $module) {
		global $adb, $table_prefix;
		$excluded_activitytype = array('Free for appointment', 'Planned');
		$hours = 0;
		$query = "SELECT SUM(duration_hours + (duration_minutes/60)) AS duration
			FROM {$table_prefix}_activity a
			INNER JOIN {$table_prefix}_crmentity c ON c.crmid = a.activityid
			INNER JOIN {$table_prefix}_seactivityrel sea ON sea.activityid = a.activityid
			WHERE c.deleted = 0 AND sea.crmid = ? AND a.eventstatus = ? AND a.activitytype NOT IN (".generateQuestionMarks($excluded_activitytype).")";
		$result = $adb->pquery($query, array($record, 'Held', $excluded_activitytype));
		if ($result && $adb->num_rows($result) > 0) {
			$hours += $adb->query_result($result,0,'duration');
		}
		if ($module == 'ProjectTask') {
			$projecttaskids = array();
			$result = $adb->pquery("SELECT t.ticketid FROM {$table_prefix}_troubletickets t
				INNER JOIN {$table_prefix}_crmentity c ON c.crmid = t.ticketid
				WHERE t.projecttaskid = ?", array($record));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$projecttaskids[] = $row['ticketid'];
				}
			}
			if (!empty($projecttaskids)) {
				$query = "SELECT SUM(duration_hours + (duration_minutes/60)) AS duration
					FROM {$table_prefix}_activity a
					INNER JOIN {$table_prefix}_crmentity c ON c.crmid = a.activityid
					INNER JOIN {$table_prefix}_seactivityrel sea ON sea.activityid = a.activityid
					WHERE c.deleted = 0 AND sea.crmid IN (".generateQuestionMarks($projecttaskids).") AND a.eventstatus = ? AND a.activitytype NOT IN (".generateQuestionMarks($excluded_activitytype).")";
				$result = $adb->pquery($query, array($projecttaskids, 'Held', $excluded_activitytype));
				if ($result && $adb->num_rows($result) > 0) {
					$hours += $adb->query_result($result,0,'duration');
				}
			}
		}
		return $hours;
	}
	public static function unTrackActivity($record) {
		global $adb, $table_prefix;
		$excluded_activitytype = array('Free for appointment', 'Planned', 'Task');
		$result = $adb->pquery("select eventstatus from {$table_prefix}_activity where activityid = ? AND activitytype NOT IN (".generateQuestionMarks($excluded_activitytype).")", array($record, $excluded_activitytype));
		$eventStatus = $adb->query_result($result,0,'eventstatus');
		if ($eventStatus == 'Held') {
			$focus = CRMEntity::getInstance('Activity');
			$focus->retrieve_entity_info_no_html($record, 'Events');
			$data = $focus->column_fields;
			$parent_module = getSalesEntityType($data['parent_id']);
			if (in_array($parent_module,array('ProjectTask','HelpDesk'))) {
				($parent_module == 'ProjectTask') ? $projecttaskid = $data['parent_id'] : $projecttaskid = getSingleFieldValue($table_prefix.'_troubletickets', 'projecttaskid', 'ticketid', $data['parent_id']);
				$duration = $data['duration_hours'] + ($data['duration_minutes'] / 60);
				if ($parent_module == 'HelpDesk') {
					$focus = CRMEntity::getInstance('HelpDesk');
					$focus->retrieve_entity_info_no_html($data['parent_id'], 'HelpDesk');
					$focus->id = $data['parent_id'];
					$focus->mode = 'edit';
					$focus->column_fields['hours'] = $focus->column_fields['hours'] - self::roundHours($duration);
					$focus->column_fields['comments'] = '';
					$focus->save('HelpDesk');
				}
				if (!empty($projecttaskid)) {
					$focus = CRMEntity::getInstance('ProjectTask');
					$focus->retrieve_entity_info_no_html($projecttaskid, 'ProjectTask');
					$focus->id = $projecttaskid;
					$focus->mode = 'edit';
					$focus->column_fields['used_hours'] = $focus->column_fields['used_hours'] - self::roundHours($duration);
					$focus->save('ProjectTask');
				}
			}
		}
	}
	public static function getFieldReadonly($module, $fieldname, $col_fields) {
		// if create or servicetype empty all fields hidden
		if (in_array($fieldname,array('salesprice','expected_hours','package_hours','invoiced_hours','used_hours','residual_hours','used_budget','residual_budget','hours_to_be_invoiced'))) {
			$readonly = 100;
		}
		if ($col_fields['servicetype'] == 'Project' && in_array($fieldname,array('salesprice','expected_hours','used_hours','used_budget','residual_budget'))) {
			$readonly = 1;
		} elseif ($col_fields['servicetype'] == 'Package' && in_array($fieldname,array('salesprice','package_hours','used_hours','residual_hours'))) {
			$readonly = 1;
		} elseif ($col_fields['servicetype'] == 'Consumptive' && in_array($fieldname,array('invoiced_hours','used_hours','used_budget','hours_to_be_invoiced'))) {
			$readonly = 1;
		}
		return $readonly;
	}
	// crmv@OPER4876e crmv@189362e
}

// crmv@143804
// function used to check if the button should be shown
function isCalendarTrackingEnabled($buttonInfo) {
	$VTEP = VTEProperties::getInstance();
	return $VTEP->getProperty('calendar_tracking.enabled');
}
// crmv@143804e

function getActiveTracked() {
	global $adb, $table_prefix, $current_user;
	//crmv@178163
	$result = $adb->pquery("select record from {$table_prefix}_cal_tracker where userid = ?",array($current_user->id));
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result,-1,false)){
			$record = $row['record'];
			$module = getSalesEntityType($record);
			if (!empty($module)) {
				$focus = CRMEntity::getInstance($module);
				$error = $focus->checkRetrieve($record, $module, false);
				if (empty($error)) return $record;
			}
		}
	}
	//crmv@178163e
	return false;
}
function deleteTracking($record) {
	global $adb, $table_prefix, $current_user;
	$result = $adb->pquery("delete from {$table_prefix}_cal_tracker_log where userid = ? and record = ?",array($current_user->id,$record));
}
function getTrackedStatus($record) {
	global $adb, $table_prefix, $current_user;
	$q = "SELECT status FROM {$table_prefix}_cal_tracker_log WHERE userid = ? AND record = ? ORDER BY id DESC";
	$result = $adb->limitpQuery($q,0,1,array($current_user->id,$record));
	$status = '';
	if ($result && $adb->num_rows($result)) {
		$status = $adb->query_result($result,0,'status');
	}
	return $status;
}
function activateTrack($record) {
	global $adb, $table_prefix, $current_user;
	$id = $adb->getUniqueID($table_prefix."_cal_tracker_log");
	$adb->pquery("insert into {$table_prefix}_cal_tracker (userid,record,id) values (?,?,?)",array($current_user->id,$record,$id));
	$adb->pquery("insert into {$table_prefix}_cal_tracker_log (id,userid,record,status,date) values (?,?,?,?,?)",array($id,$current_user->id,$record,'Started',date('Y-m-d H:i:s')));
}
function pauseTrack($module,$record,$description,$other_args=array()) { //crmv@69922
	global $adb, $table_prefix, $current_user;
	$id = $adb->getUniqueID($table_prefix."_cal_tracker_log");
	$adb->pquery("delete from {$table_prefix}_cal_tracker where userid = ? and record = ?",array($current_user->id,$record));
	$date = date('Y-m-d H:i:s');
	$adb->pquery("insert into {$table_prefix}_cal_tracker_log (id,userid,record,status,date) values (?,?,?,?,?)",array($id,$current_user->id,$record,'Paused',$date));
	$activityid = createCalTrack($id,$module,$record,$date,$description,$other_args); //crmv@69922
	return $activityid;
}
function stopTrack($module,$record,$description,$other_args=array()) { //crmv@69922
	global $adb, $table_prefix, $current_user;
	$id = $adb->getUniqueID($table_prefix."_cal_tracker_log");
	$adb->pquery("delete from {$table_prefix}_cal_tracker where userid = ? and record = ?",array($current_user->id,$record));
	$date = date('Y-m-d H:i:s');
	$adb->pquery("insert into {$table_prefix}_cal_tracker_log (id,userid,record,status,date) values (?,?,?,?,?)",array($id,$current_user->id,$record,'Stopped',$date));
	$activityid = createCalTrack($id,$module,$record,$date,$description,$other_args); //crmv@69922
	return $activityid;
}
function createCalTrack($id,$module,$record,$due_date,$description,$other_args=array()) { //crmv@69922
	global $adb, $table_prefix, $current_user;
	
	$q = "SELECT * FROM {$table_prefix}_cal_tracker_log WHERE userid = ? AND record = ? AND id < ? AND status = ? ORDER BY id DESC";
	$result = $adb->limitpQuery($q,0,1,array($current_user->id,$record,$id,'Started'));
	if ($result && $adb->num_rows($result)) {
		$date_start = $adb->query_result($result,0,'date');
	} else {
		return false;
	}
	
	$parent_module = getSalesEntityType($record);
	$subject = array_values(getEntityName($parent_module, $record));
	
	$focus = CRMEntity::getInstance('Events');
	$focus->mode = '';
	$focus->column_fields['subject'] = $subject[0];
	$focus->column_fields['activitytype'] = 'Tracked';
	$focus->column_fields['date_start'] = substr($date_start,0,10);
	$focus->column_fields['time_start'] = substr($date_start,11,5);
	$focus->column_fields['due_date'] = substr($due_date,0,10);
	$focus->column_fields['time_end'] = substr($due_date,11,5);
	$focus->column_fields['eventstatus'] = 'Held';
	$focus->column_fields['priority'] = 'Basso';
	$focus->column_fields['visibility'] = 'Standard';
	if ($parent_module == 'Contacts') {
		$focusContacts = CRMEntity::getInstance($parent_module);
		$focusContacts->retrieve_entity_info($record, $parent_module);
		$focus->column_fields['parent_id'] = $focusContacts->column_fields['account_id'];
		$focus->column_fields['contact_id'] = $record;
	} elseif ($parent_module == 'Messages') {
		// done later
	} else {
		$focus->column_fields['parent_id'] = $record;
	}
	$focus->column_fields['assigned_user_id'] = $current_user->id;
	$focus->column_fields['description'] = $description;
	
	$focus->column_fields = array_merge($focus->column_fields, $other_args); //crmv@69922
	
	$focus->save('Events');
	
	if ($parent_module == 'Messages' && $focus->id > 0) {
		$messFocus = CRMEntity::getInstance('Messages');
		$messFocus->save_related_module('Messages', $record, 'Calendar', $focus->id);
	}
	
	return $focus->id;
}
function getOtherUsersTracking($record) {
	global $adb, $table_prefix, $current_user;
	$result = $adb->pquery("select userid from {$table_prefix}_cal_tracker where record = ?",array($record)); //crmv@178163
	if ($result && $adb->num_rows($result) > 0) {
		$users = array();
		while($row=$adb->fetchByAssoc($result)) {
			if ($current_user->id != $row['userid']) {
				$users[] = $row['userid'];
			}
		}
		return $users;
	}
	return false;	
}
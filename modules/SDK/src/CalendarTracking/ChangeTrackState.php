<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33448 crmv@55708 crmv@62394 */
require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');

$record = intval($_REQUEST['record']);
$type = vtlib_purify($_REQUEST['type']);
$mode = vtlib_purify($_REQUEST['mode']);
$create_ticket = vtlib_purify($_REQUEST['create_ticket']);
$description = vtlib_purify($_REQUEST['description']);

//crmv@69922
$other_args = array();
if (isset($_REQUEST['relcrmid'])) {
	$relcrmid = intval($_REQUEST['relcrmid']);
	if ($relcrmid > 0) {
		$relsetype = getSalesEntityType($relcrmid);
		if ($relsetype == 'Contacts') {
			$other_args = array('contact_id' => $relcrmid);
		} else {
			$other_args = array('parent_id' => $relcrmid);
		}
	}
}
//crmv@69922e

global $currentModule, $current_user;
$currentModule = getSalesEntityType($record);

if ($mode == 'save_state') {
	// crmv@OPER4876 crmv@189362
	$VTEP = VTEProperties::getInstance();
	$status_fields = $VTEP->getProperty('calendar_tracking.status_fields');
	$status_values = $VTEP->getProperty('calendar_tracking.status_values');
	if (isset($status_fields[$currentModule]) && !empty($status_values[$currentModule][$type])) {
		$focus = CRMEntity::getInstance($currentModule);
		$focus->retrieve_entity_info_no_html($record,$currentModule);
		if ($focus->column_fields[$status_fields[$currentModule]] != $status_values[$currentModule][$type]) {
			$focus->id = $record;
			$focus->mode = 'edit';
			$focus->column_fields[$status_fields[$currentModule]] = $status_values[$currentModule][$type];
			$focus->column_fields['comments'] = '';
			$focus->save($currentModule);
		}
	}
	// crmv@OPER4876e crmv@189362e
} else {
	switch ($type) {
		case 'start': 
			if (getActiveTracked() === false) {
				activateTrack($record);
			}
			break;
		case 'pause': 
			$activityid = pauseTrack($currentModule,$record,$description,$other_args); //crmv@69922
			break;
		case 'stop': 
			$activityid = stopTrack($currentModule,$record,$description,$other_args); //crmv@69922
			break;
	}

	if (!empty($activityid) && $create_ticket == 'yes' && Vtlib_isModuleActive('HelpDesk')) {
		
		$result = $adb->pquery("select duration_hours, duration_minutes from {$table_prefix}_activity where activityid = ?",array($activityid));
		if ($result && $adb->num_rows($result) > 0) {
			$duration_hours = $adb->query_result($result,0,'duration_hours');
			$duration_minutes = $adb->query_result($result,0,'duration_minutes');
			$duration = $duration_hours + ($duration_minutes / 60);
		}
		
		if (empty($description)) $description = getTranslatedString('LBL_TRACK_NAME', 'APP_STRINGS');
		
		$focus = CRMEntity::getInstance('HelpDesk');
		$focus->column_fields['ticket_title'] = $description;
		$focus->column_fields['ticketstatus'] = 'Open';
		$focus->column_fields['hours'] = CalendarTracking::roundHours($duration); // crmv@OPER4876 crmv@189362
		$focus->column_fields['assigned_user_id'] = $current_user->id;
		$focus->save('HelpDesk');
		
		if ($currentModule == 'Messages') {
			$messFocus = CRMEntity::getInstance('Messages');
			$messFocus->save_related_module('Messages', $record, 'HelpDesk', $focus->id);
		} else {
			$relationManager = RelationManager::getInstance();
			$relationManager->relate('HelpDesk', $focus->id, $currentModule, $record);
			//$relationManager->relate('HelpDesk', $focus->id, 'Calendar', $activityid);
		}
		
	}
}
echo 'SUCCESS';
if ($_REQUEST['dont_die'] != 1) exit;
?>
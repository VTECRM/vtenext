<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33448 crmv@55708 crmv@62394 crmv@199978 */
global $adb, $table_prefix, $current_user, $theme;
global $app_strings;
require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');

if ($_REQUEST['mode'] == 'save_state') {
	$record = intval($_REQUEST['record']);
	switch ($_REQUEST['type']) {
		case 'start': 
			if (getActiveTracked() === false) {
				activateTrack($record);
			}
			break;
		case 'pause': 
			pauseTrack($record);
			break;
		case 'stop': 
			stopTrack($record);
			break;
	}
}

$list = array();
$result = $adb->pquery(
	"SELECT ct.id, ct.record
	FROM {$table_prefix}_cal_tracker ct
	WHERE ct.userid = ?",array($current_user->id)
);
if ($result && $adb->num_rows($result) > 0) {
	$id = $adb->query_result_no_html($result,0,'id');
	$record = $adb->query_result_no_html($result,0,'record');

	$entity_type = getSalesEntityType($record);
	
	if (Vtlib_isModuleActive($entity_type)) {
	
		$focus = CRMEntity::getInstance($entity_type);
		$check = $focus->checkRetrieve($record, $entity_type, false);
		if (empty($check)) {
		
			$entitd_name = array_values(getEntityName($entity_type,$record));
			/* Module Sequence Numbering
			$mod_seq_field = getModuleSequenceField($entity_type);
			if ($mod_seq_field != null) {
				$mod_seq_id = getSingleFieldValue($mod_seq_field['table'], $mod_seq_field['column'], $focus->tab_name_index[$mod_seq_field['table']], $record);
			} else {
				$mod_seq_id = $record;
			}
			*/
			$list[$record] = array(
				// 'number'=>$mod_seq_id,
				'name'=>$entitd_name[0],
				'module'=>$entity_type,
				'entity_type'=>getSingleModuleName($entity_type,$record),
				'enable'=>true
			);
		}
	}
}

// crmv@63349 - removed temporary table
$result = $adb->pquery(
	"SELECT ctl.record
	FROM {$table_prefix}_cal_tracker_log ctl
	INNER JOIN (
		SELECT MAX(id) AS id 
		FROM {$table_prefix}_cal_tracker_log 
		WHERE userid = ? 
		GROUP BY record
	) subctl ON subctl.id = ctl.id
	WHERE ctl.status = ?",
	array($current_user->id, 'Paused')
);
// crmv@63349e
if ($result && $adb->num_rows($result) > 0) {
	while($row = $adb->fetchByAssoc($result, -1, false)) {
		$record = $row['record'];
		$entity_type = getSalesEntityType($record);
		
		if (!Vtlib_isModuleActive($entity_type)) continue;
		
		$focus = CRMEntity::getInstance($entity_type);
		$check = $focus->checkRetrieve($record, $entity_type, false);
		if (!empty($check)) continue;
		
		$entitd_name = array_values(getEntityName($entity_type,$record));
		/* Module Sequence Numbering
		$mod_seq_field = getModuleSequenceField($entity_type);
		if ($mod_seq_field != null) {
			$mod_seq_id = getSingleFieldValue($mod_seq_field['table'], $mod_seq_field['column'], $focus->tab_name_index[$mod_seq_field['table']], $record);
		} else {
			$mod_seq_id = $record;
		}
		*/
		$list[$record] = array(
			// 'number'=>$mod_seq_id,
			'name'=>$entitd_name[0],
			'module'=>$entity_type,
			'entity_type'=>getSingleModuleName($entity_type,$record),
			'enable'=>false
		);
	}
}

$active_tracked = getActiveTracked();


// ----- display section ------

$small_page_title = getTranslatedString('LBL_TRACK_MANAGER_TITLE', 'APP_STRINGS');
include('themes/SmallHeader.php');

$smarty = new VteSmarty();
$smarty->assign('THEME',$theme);
$smarty->assign('APP',$app_strings);

$smarty->assign('ID',$record);
$smarty->assign('RECORD',$record);
$smarty->assign('ACTIVE_TRACKED',$active_tracked);
$smarty->assign('TRACKLIST',$list);
$smarty->assign('TICKETS_AVAILABLE', Vtlib_isModuleActive('HelpDesk'));

$smarty->display('modules/SDK/src/CalendarTracking/TrackingList.tpl');

include('themes/SmallFooter.php');
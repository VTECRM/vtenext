<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@101503 */

require_once('modules/CustomView/CustomView.php');
//crmv@203484 removed including file
global $table_prefix,$php_max_execution_time;
global $adb,$current_user,$currentModule; //crmv@203484 removed global singlepane
global $current_user, $currentModule;
set_time_limit($php_max_execution_time);

$targetname = vtlib_purify($_REQUEST['targetname']);
$title = vtlib_purify($_REQUEST['title']);
$campaignid = vtlib_purify($_REQUEST['campaignid']);

$focus = CRMEntity::getInstance('Targets');
$focus->id = '';
$focus->mode = '';
$focus->column_fields['targetname'] = $targetname;
$focus->column_fields['assigned_user_id'] = $current_user->id;
$focus->column_fields['target_endtime'] = date("Y-m-d");
$focus->save('Targets');

$targetId = $focus->id;
$focusCamp = CRMEntity::getInstance('Campaigns');
$focusCamp->retrieve_entity_info($campaignid,'Campaigns');

if($title == 'Message Queue'){
	$temp_xls_list = $focusCamp->get_statistics_message_queue($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_queue.crmid'; //crmv@127439
}elseif($title == 'Sent Messages'){
	$temp_xls_list = $focusCamp->get_statistics_sent_messages($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_queue.crmid'; //crmv@127439
}elseif($title == 'Viewed Messages'){
	$temp_xls_list = $focusCamp->get_statistics_viewed_messages($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_queue.crmid'; //crmv@127439
}elseif($title == 'Tracked Link'){
	$temp_xls_list = $focusCamp->get_statistics_tracked_link($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_tl.crmid'; //crmv@127439
}elseif($title == 'Unsubscriptions'){
	$temp_xls_list = $focusCamp->get_statistics_unsubscriptions($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_tl.crmid'; //crmv@127439
}elseif($title == 'Bounced Messages'){
	$temp_xls_list = $focusCamp->get_statistics_bounced_messages($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_queue.crmid'; //crmv@127439
}elseif($title == 'Suppression list'){
	$temp_xls_list = $focusCamp->get_statistics_suppression_list($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_unsub.crmid'; //crmv@127439
}elseif($title == 'Failed Messages'){
	$temp_xls_list = $focusCamp->get_statistics_failed_messages($campaignid, 26, 0, false, true, false, false);
	$table_id = 'tbl_s_newsletter_queue.crmid'; //crmv@127439
}
$mod_listquery = strtolower($title)."_listquery";
$queryCont = VteSession::get($mod_listquery);

$list_query = replaceSelectQuery($queryCont,$table_id); //crmv@122616 crmv@127439
$res = $adb->query($list_query);

if ($res && $adb->num_rows($res)>0) {
	$ids = array();
	$idsMod = array();
	$focus = CRMEntity::getInstance('Targets');
	while($row=$adb->fetchByAssoc($res)) {
		$ids[] = $row['crmid'];
		$idsMod[getSalesEntityType($row['crmid'])][] = $row['crmid'];
	}
	if(!empty($idsMod['Accounts'])){
		$focus->save_related_module('Targets', $targetId, 'Accounts', $idsMod['Accounts']);
	}
	if(!empty($idsMod['Contacts'])){
		$focus->save_related_module('Targets', $targetId, 'Contacts', $idsMod['Contacts']);
	}
	if(!empty($idsMod['Leads'])){
		$focus->save_related_module('Targets', $targetId, 'Leads', $idsMod['Leads']);
	}
}

header("Location: index.php?module=Targets&action=DetailView&ajax=true&record=".vtlib_purify($targetId));
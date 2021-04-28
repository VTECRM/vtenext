<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@187622 */
// TODO add isPermitted check
$ajaxaction = $_REQUEST["ajxaction"];
if ($ajaxaction == 'GETOPTIONS') {
	require_once('modules/SDK/src/73/73Utils.php');
	global $adb, $table_prefix, $app_strings;
	$smarty = new VteSmarty();
	$uitypeTimeUtils = UitypeTimeUtils::getInstance();
	
	$messagesid = intval($_REQUEST['messagesid']);
	if (!empty($messagesid)) {
		$result = $adb->pquery("select date from {$table_prefix}_emails_send_queue where messagesid = ?", array($messagesid));
		if ($result && $adb->num_rows($result) > 0) {
			list($date,$hour) = explode(' ',$adb->query_result($result,0,'date'));
		}
		$smarty->assign('MESSAGESID', $messagesid);
	} else {
		$date = date('Y-m-d');
		$hour = date('H:i',strtotime('+1 hour'));
	}
	
	$dateHtml = getOutputHtml(5, 'schedule_date', 'Date', 100, array('schedule_date'=>$date), 1, 'Emails');
	$hour = $uitypeTimeUtils->time2Seconds($hour);
	$hourHtml = getOutputHtml(73, 'schedule_hour', 'Hour', 100, array('schedule_hour'=>$hour), 1, 'Emails');
	
	$focus = CRMEntity::getInstance('Emails');
	$options = $focus->getScheduleSendingOptions();
	$smarty->assign('OPTIONS', $options);
	$smarty->assign('APP', $app_strings);
	$smarty->assign('DATE_HTML', $dateHtml);
	$smarty->assign('HOUR_HTML', $hourHtml);
	$smarty->display("modules/Emails/ScheduleSendingOptions.tpl");
} elseif($ajaxaction == "SENDNOW") {
	$messagesid = intval($_REQUEST['record']);
	if ($messagesid > 0) {
		$emailsFocus = CRMEntity::getInstance('Emails');
		$emailsFocus->sendNow($messagesid);
		
		$messagesFocus = CRMEntity::getInstance('Messages');
		$messagesFocus->retrieve_entity_info_no_html($messagesid,'Messages');
		$messagesFocus->reloadCacheFolderCount($messagesFocus->column_fields['assigned_user_id'],$messagesFocus->column_fields['account'],'vteScheduled');
		
		echo 'SUCCESS::'.getTranslatedString('MESSAGE_MAIL_SENT_SUCCESSFULLY','Emails');
	}
} elseif ($ajaxaction == 'RESCHEDULE') {
	$messagesid = intval($_REQUEST['messagesid']);
	if ($messagesid > 0) {
		$focus = CRMEntity::getInstance('Emails');
		$focus->schedule($messagesid, $_REQUEST['date']);
	}
} elseif ($ajaxaction == 'ENABLE') {
	$messagesid = intval($_REQUEST['messagesid']);
	if ($messagesid > 0) {
		$focus = CRMEntity::getInstance('Emails');
		$focus->setScheduleStatus($messagesid,0);
	}
} elseif ($ajaxaction == 'DISABLE') {
	$messagesid = intval($_REQUEST['messagesid']);
	if ($messagesid > 0) {
		$focus = CRMEntity::getInstance('Emails');
		$focus->setScheduleStatus($messagesid,3);
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Emails/mail.php');
global $adb,$current_user,$currentModule,$mod_strings;
$record = $_REQUEST['record'];

$focus = CRMEntity::getInstance($currentModule);
$focus->id = $record;
$focus->retrieve_entity_info($record,$currentModule);

if (in_array($focus->column_fields['templateemailid'],array('',0))) {
	die($mod_strings['LBL_TEMPLATE_EMPTY']);
}

try { set_time_limit(900); } catch(Exception $e) { }	// 15 minutes

if ($_REQUEST['mode'] == 'test') {	//send test email
	$to_address = getUserEmailId('id',$current_user->id);
	// crmv@151466
	$target_list = $focus->getTargetList();
	$crmid = (!empty($target_list) ? $target_list[0] : ''); // take the first recipient as an example
	$mail_status = $focus->sendNewsletter($crmid,'test',$to_address);
	// crmv@151466
	if ($mail_status == 1) {
		die($mod_strings['LBL_TEST_MAIL_SENT']);
	} else {
		die(getTranslatedString('LBL_NOTIFICATION_ERROR','Calendar'));
	}
} else {	//schedule email
	// crmv@126696
	// populate queue
	$r = $focus->enqueueTargets(); 
	if ($r === false) {
		die($mod_strings['LBL_TARGET_LIST_EMPTY']);
	}
	// crmv@126696e

	$focus->mode = 'edit';
	$focus->column_fields['scheduled'] = 1;
	$focus->save($currentModule);

	die($mod_strings['LBL_MAIL_SCHEDULED']);
}
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@55137 */
$success = false;
$append_status = false;	//crmv@86304
// crmv@187622
if (!isset($nlparam)) $nlparam = ''; // crmv@114260
$to = $focus->getToList('single',$to_mail,$myids);
// crmv@187622e
$subject_send = $subject;
$description_send = $description;
//Email Tracking disabilitato per il send_mode single
$pos = strpos($description_send, '$logo$');
if ($pos !== false) {
	$description_send = str_replace('$logo$','<img src="cid:logo" />', $description_send);
	$logo = 1;
}
if ($message_mode == 'forward') {	// || $message_mode == 'draft' (crmv@48501*1)
	$attach_messageid = $messageid;
} else {
	$attach_messageid = '';
}

$attach_mode = 'all';
if (!empty($_REQUEST['attachments_mode'])) $attach_mode = $_REQUEST['attachments_mode'];
$send_mail_status = send_mail('Emails',$to,$from_name,$from_address,$subject_send,$description_send,$cc,$bcc,$attach_mode,$attach_messageid,$logo,$nlparam,$mail_tmp,$messageid,$message_mode); // crmv@114260

$focus->saveUnknownContacts($to, $cc, $bcc); // crmv@191584

if($send_mail_status == 1) {
	if (!empty($_REQUEST['sending_queue_currentid'])) $adb->pquery("update {$table_prefix}_emails_send_queue set s_send = ? where id = ?",array(1,$_REQUEST['sending_queue_currentid']));	//crmv@48501
	//crmv@2043m
	if(isset($_REQUEST['reply_mail_converter']) && $_REQUEST['reply_mail_converter'] != '') {
		global $current_user;
		$HelpDeskFocus = CRMEntity::getInstance('HelpDesk');
		$HelpDeskFocus->retrieve_entity_info_no_html($_REQUEST['reply_mail_converter_record'], 'HelpDesk');
		$HelpDeskFocus->id = $_REQUEST['reply_mail_converter_record'];
		$HelpDeskFocus->mode = 'edit';
		if ($HelpDeskFocus->waitForResponseStatus != '') {
			$HelpDeskFocus->column_fields['ticketstatus'] = $HelpDeskFocus->waitForResponseStatus;
		}
		$HelpDeskFocus->column_fields['comments'] = strip_tags($description);
		$HelpDeskFocus->sendPortalEmails = false; //crmv@OPER11080
		$HelpDeskFocus->save('HelpDesk');
	}
	//crmv@2043me
	//crmv@86304
	$append_status = append_mail($mail_tmp,$account,$parentid,$to,$from_name,$from_address,$subject,$description,$cc,$bcc,$send_mode);
	if (!$append_status) {
		global $currentModule;
		$currentModule = 'Messages';
		$focusMessages = CRMentity::getInstance($currentModule);
		$focusMessages->internalAppendMessage($mail_tmp,$account,$parentid,$to,$from_name,$from_address,$subject,$description,$cc,$bcc,$send_mode);
		$currentModule = 'Emails';
	}
	//crmv@86304e
	$success = true;
} else {
	$error_message = $send_mail_status;
}
if ($success) {
	if (!empty($_REQUEST['sending_queue_currentid'])) $adb->pquery("update {$table_prefix}_emails_send_queue set s_append = ? where id = ?",array(1,$_REQUEST['sending_queue_currentid']));	//crmv@48501

	cleanPuploadAttachments($_REQUEST['uploaddir']);
	
	if (!empty($_REQUEST['sending_queue_currentid'])) $adb->pquery("update {$table_prefix}_emails_send_queue set s_clean_pupload_attach = ? where id = ?",array(1,$_REQUEST['sending_queue_currentid']));	//crmv@48501

	/*
	 * TODO: eliminare cartelle tmp di allegati di bozze
	 *
	 * quando viene salvata una bozza con allegati non posso svuotare la cartella perch� se poi clicco Salva non troverebbe pi� l'allegato da inviare
	 * per� nel momento in cui chiudo la finestra di composizione e si � salvata la bozza la cartella con i file diventa inutile perch� se riapro la bozza faccio riferimento agli allegati che sono nel server
	 * quindi si potrebbe fare uno script che cancella le cartelle pi� vecchie di X giorni
	 */
	if (!empty($messageid) && !empty($message_mode)) {
		$javascript_code .= setflag_mail($messageid, $message_mode);
	}
} else {
	$skip_delete_drafts = true;
}
?>
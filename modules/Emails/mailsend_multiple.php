<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@55137 */
$success = false;
$append_status = false;	//crmv@86304
$error_message = array();
$error_addr = array();
$to = array();

if (!isset($nlparam)) $nlparam = ''; // crmv@114260

$attach_mode = 'all';
if (!empty($_REQUEST['attachments_mode'])) $attach_mode = $_REQUEST['attachments_mode'];

if ($message_mode == 'forward') {	// || $message_mode == 'draft' (crmv@48501*1)
	$attach_messageid = $messageid;
} else {
	$attach_messageid = '';
}
if(isset($to_mail) && $to_mail != '') {
	$Exploded_Mail = explode(',',$to_mail);
	$Exploded_Mail = array_map('trim', $Exploded_Mail);
	$Exploded_Mail = array_filter($Exploded_Mail);
	foreach($Exploded_Mail as $mailadd) {
		$to[] = $mailadd;
		$subject_send = $subject;
		$description_send = $description;
		
		// crmv@83971 - try to replace the tpl vars even for raw recipients
		$relation_id = vtlib_purify($_REQUEST['relation']);
		if (!empty($relation_id)) {
			$relation_module = getSalesEntityType($relation_id);
			if (!empty($relation_module)) {
				$description_send = getMergedDescription($description_send,$relation_id,$relation_module);
				$subject_send = getMergedDescription($subject_send,$relation_id,$relation_module);
			}
		}
		// crmv@83971e

		if ($mail_tmp == '') {
			$send_mail_status = send_mail('Emails',$mailadd,$from_name,$from_address,$subject_send,$description_send,$cc,$bcc,$attach_mode,$attach_messageid,$logo,$nlparam,$mail_tmp,$messageid,$message_mode); // crmv@114260
		} else {
			$mail_tmp_empty = '';
			$send_mail_status = send_mail('Emails',$mailadd,$from_name,$from_address,$subject_send,$description_send,$cc,$bcc,$attach_mode,$attach_messageid,$logo,$nlparam,$mail_tmp_empty,$messageid,$message_mode); // crmv@114260
		}
		if($send_mail_status != 1) {
			$error_message[] = $send_mail_status;
			$error_addr[] = $mailadd;
		}
	}
}
if (!empty($myids)) {
	if (!is_array($myids)) {
		$myids = explode('|',$myids);
	}
	foreach($myids as $myid) {
		$subject_send = $subject;
		$description_send = $description;
		$realid = explode("@",$myid);
		$nemail = count($realid);
		$mycrmid = $realid[0];
		
		// support to old mode
		if($realid[1] == -1) {
			$emailadd = $adb->query_result($adb->pquery("select email1 from ".$table_prefix."_users where id=?", array($mycrmid)),0,'email1');
			$pmodule = 'Users';
			$description_send = getMergedDescription($description_send,$mycrmid,$pmodule);
			$subject_send = getMergedDescription($subject_send,$mycrmid,$pmodule);
			if ($mail_tmp == '') {
				$send_mail_status = send_mail('Emails',$emailadd,$from_name,$from_address,$subject_send,$description_send,$cc,$bcc,$attach_mode,$attach_messageid,$logo,$nlparam,$mail_tmp,$messageid,$message_mode); // crmv@114260
			} else {
				$mail_tmp_empty = '';
				$send_mail_status = send_mail('Emails',$emailadd,$from_name,$from_address,$subject_send,$description_send,$cc,$bcc,$attach_mode,$attach_messageid,$logo,$nlparam,$mail_tmp_empty,$messageid,$message_mode); // crmv@114260
			}
			$to[] = $emailadd;
			if($send_mail_status != 1) {
				$error_message[] = $send_mail_status;
				$error_addr[] = $emailadd;
			}
			continue;
		}
		
		$pmodule = getSalesEntityType($mycrmid);
		if ($pmodule == '') {
            $res = $adb->pquery('SELECT * FROM '.$table_prefix.'_users WHERE id = ?', array($mycrmid));//crmv@208173
			if ($res && $adb->num_rows($res)>0) {
				$pmodule = 'Users';
			}
		}
		for ($j=1;$j<$nemail;$j++) {
			$temp = $realid[$j];
			if (strpos($temp,'-') === 0) {
				$pmodule = 'Users';
				$temp = substr($temp,1);
			}
			$myquery = 'Select fieldname from '.$table_prefix.'_field where fieldid = ? and '.$table_prefix.'_field.presence in (0,2)';
			$fresult = $adb->pquery($myquery, array($temp));
			// vtlib customization: Enabling mail send from other modules
			$myfocus = CRMEntity::getInstance($pmodule);
			$myfocus->retrieve_entity_info($mycrmid, $pmodule);
			// END
			$fldname = $adb->query_result($fresult,0,'fieldname');
			$emailadd = br2nl($myfocus->column_fields[$fldname]);
			if($emailadd != '') {
				
				//crmv@106075
				$pos = strpos($description_send, '$logo$');
				if ($pos !== false) {
					$description_send = str_replace('$logo$','<img src="cid:logo" />', $description_send);
					$logo = 1;
				}
				//crmv@106075e
				
				//crmv@58992
				//merge also for a relation, if present and for a different module of the $mycrmid
				$relation_id = vtlib_purify($_REQUEST['relation']);
				if(!empty($relation_id)){
					$relation_module = getSalesEntityType($relation_id);
				}
				if(!empty($relation_module) && $relation_module !=$pmodule){
					$description_send = getMergedDescription($description_send,$relation_id,$relation_module);
					$subject_send = getMergedDescription($subject_send,$relation_id,$relation_module);
				}
				else{
					//old way
					$description_send = getMergedDescription($description_send,$mycrmid,$pmodule);
					$subject_send = getMergedDescription($subject_send,$mycrmid,$pmodule);
				}
				//crmv@58992e
				
				//if(isPermitted($pmodule,'DetailView',$mycrmid) == 'yes' || $pmodule == 'Users') {
				if ($mail_tmp == '') {
					$send_mail_status = send_mail('Emails',$emailadd,$from_name,$from_address,$subject_send,$description_send,$cc,$bcc,$attach_mode,$attach_messageid,$logo,$nlparam,$mail_tmp,$messageid,$message_mode); // crmv@114260
				} else {
					$mail_tmp_empty = '';
					$send_mail_status = send_mail('Emails',$emailadd,$from_name,$from_address,$subject_send,$description_send,$cc,$bcc,$attach_mode,$attach_messageid,$logo,$nlparam,$mail_tmp_empty,$messageid,$message_mode); // crmv@114260
				}
				//}
				$to[] = $emailadd;
				if($send_mail_status != 1) {
					$error_message[] = $send_mail_status;
					$error_addr[] = $emailadd;
				}
			}
		}		
	}
}
if (!empty($error_message)) {
	$error_message = implode("\n",$error_message);
	$to = array_diff($to,$error_addr);
	if (!empty($to)) {
		$error_message .= "\n\n".getTranslatedString('LBL_SMTP_ERROR_MULTIPLE','Emails');
	}
} else {
	//crmv@122111
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
		$HelpDeskFocus->save('HelpDesk');
	}
	//crmv@122111e
	if (!empty($_REQUEST['sending_queue_currentid'])) $adb->pquery("update {$table_prefix}_emails_send_queue set s_send = ? where id = ?",array(1,$_REQUEST['sending_queue_currentid']));	//crmv@48501
	$success = true;
}
$focus->saveUnknownContacts($to, $cc, $bcc); // crmv@191584
if (!empty($to)) {
	//crmv@86304 crmv@109175
	$subject_append = $subject;
	$description_append = $description;
	if (is_array($myids) && count($myids) == 1) {
		$subject_append = $subject_send;
		$description_append = $description_send;
	}
	$append_status = append_mail($mail_tmp,$account,$parentid,$to,$from_name,$from_address,$subject_append,$description_append,$cc,$bcc,$send_mode);
	if (!$append_status) {
		global $currentModule;
		$currentModule = 'Messages';
		$focusMessages = CRMentity::getInstance($currentModule);
		$focusMessages->internalAppendMessage($mail_tmp,$account,$parentid,$to,$from_name,$from_address,$subject_append,$description_append,$cc,$bcc,$send_mode);
		$currentModule = 'Emails';
	}
	//crmv@86304e crmv@109175e
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
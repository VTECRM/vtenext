<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31263 crmv@55137 */

global $adb, $current_user, $table_prefix;

//set the return module and return action and set the return id based on return module and record
$returnmodule = vtlib_purify($_REQUEST['return_module']);
$returnaction = vtlib_purify($_REQUEST['return_action']);
if((($returnmodule != 'Emails') || ($returnmodule == 'Emails' && $_REQUEST['record'] == '')) && $_REQUEST['return_id'] != '') {
	$returnid = vtlib_purify($_REQUEST['return_id']);
}

$from_name = $current_user->user_name;
$from_address = $current_user->column_fields['email1'];
//crmv@2051m
if (isset($_REQUEST['from_email'])) {
	$from_address = $_REQUEST['from_email'];
	$from_name = $focus->getFromEmailName($from_address);
	$account = $focus->getFromEmailAccount($from_address);
}
//crmv@2051me
$to = array();
$cc = $_REQUEST['ccmail'];
$bcc = $_REQUEST['bccmail'];
$cc = trim($cc);
$bcc = trim($bcc);
if (substr($cc,-1) == ','){
	$cc = substr($cc, 0, -1);
}
if (substr($bcc,-1) == ','){
	$bcc = substr($bcc, 0, -1);
}
$subject = utf8_encode($_REQUEST['subject']);	//crmv@27759
$description = $_REQUEST['description'];
$parentid= $_REQUEST['parent_id'];
$myids = explode("|",$parentid);
if (!empty($myids)) {
	$myids = array_filter($myids);
}
$mail_tmp = '';
$logo = '';
$mail_status_str = '';
$attach_messageid = vtlib_purify($_REQUEST['message']);
$attachment = ''; // instead of 'all' in order to not save the attachemnts in draft

if(isset($_REQUEST['to_mail']) && $_REQUEST["to_mail"] != '') {
	$to = explode(',',$_REQUEST['to_mail']);
	$to = array_map('trim', $to);
	$to = array_filter($to);
}
foreach($myids as $myid) {
	$emailadd = '';
	$realid = explode("@",$myid);
	$nemail = count($realid);
	$mycrmid = $realid[0];
	
	// support to old mode
	if($realid[1] == -1) {
		$emailadd = $adb->query_result($adb->pquery("select email1 from ".$table_prefix."_users where id=?", array($mycrmid)),0,'email1');
		$to[] = $emailadd;
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
		$myquery = 'select fieldname from '.$table_prefix.'_field where fieldid = ? and '.$table_prefix.'_field.presence in (0,2)';
		$fresult = $adb->pquery($myquery, array($temp));
		// vtlib customization: Enabling mail send from other modules
		$myfocus = CRMEntity::getInstance($pmodule);
		$myfocus->retrieve_entity_info($mycrmid, $pmodule);
		// END
		$fldname = $adb->query_result($fresult,0,'fieldname');
		$emailadd = br2nl($myfocus->column_fields[$fldname]);
	}
	if($emailadd != '') {
		$to[] = $emailadd;
	}
}
$draft_result = save_draft_mail('Emails',$account,$to,$from_name,$from_address,$subject,$description,$cc,$bcc,$attachment,$attach_messageid,$mail,$_REQUEST['relation'],$myids,$_REQUEST['send_mode']);
if (!$draft_result) {
	if (empty($skip_exit)) {	//crmv@48501
		echo '|##|ERROR_DRAFT|##|'.getTranslatedString('Draft error','Emails');
		exit;
	} else {
		return;
	}
}
if (isset($_REQUEST['draft_id']) && $_REQUEST['draft_id'] != '') {
	delete_draft_mail($_REQUEST['draft_id'],$mail->getLastMessageID()); // crmv@180739
	
	if ($_REQUEST['save_in_draft'] == 'save') {
		$msg = getTranslatedString('Draft saved at','Emails');
	} elseif ($_REQUEST['save_in_draft'] == 'auto_save') {
		$msg = getTranslatedString('Draft saved automatically at','Emails');
	}
	//select last Message created
	/* crmv@43444
	$messagesid = '';
	$result = $adb->pquery("SELECT crmid FROM {$table_prefix}_messages_drafts
							INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagehash = {$table_prefix}_messages_drafts.messagehash
							INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_messages.messagesid
							WHERE deleted = 0 
							AND {$table_prefix}_messages_drafts.id = ? AND {$table_prefix}_messages.messageid = ?"
							,array($_REQUEST['draft_id'],$mail->message_id));
	if ($result && $adb->num_rows($result) > 0) {
		$messagesid = $adb->query_result($result,0,'crmid');
	}
	crmv@43444e */
}
if (empty($skip_exit)) {	//crmv@48501
	//crmv@43444
	//echo $messagesid.'|##|'.$mail->message_id.'|##|'.$msg.' '.date('H:i');
	echo $attach_messageid.'|##|'.$mail->getLastMessageID().'|##|'.$msg.' '.date('H:i'); // crmv@180739
	//crmv@43444e
	exit;
}
?>
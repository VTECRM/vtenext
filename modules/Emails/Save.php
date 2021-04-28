<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@15309	crmv@25356	crmv@25351	crmv@31263	crmv@48501 crmv@51130 */

require_once("modules/Emails/mail.php");

global $adb, $table_prefix, $current_user;

$focus = CRMEntity::getInstance('Emails'); // crmv@114260

//check for mail server configuration thro ajax
if (isset($_REQUEST['server_check']) && $_REQUEST['server_check'] == 'true') {
	// crmv@114260
	$accountid = intval($_REQUEST['accountid']);
	$check = $focus->checkSmtpServer($accountid);
	echo ($check ? 'SUCCESS' : 'FAILURE');
	die;
	// crmv@114260
}
//crmv@167238
if (isset($_REQUEST['draft_folder_check']) && $_REQUEST['draft_folder_check'] == 'true') {
	$accountid = intval($_REQUEST['accountid']);
	$check = $focus->checkDraftFolder($accountid);
	echo ($check ? 'SUCCESS' : 'FAILURE|##|'.getTranslatedString('Draft error','Emails'));
	die;
}
//crmv@167238e

//crmv@62821
if (isset($_REQUEST['cron_messagessend_check']) && $_REQUEST['cron_messagessend_check'] == 'true') {
	$return_str = 'SUCCESS';

	$result = $adb->pquery("SELECT COUNT(*) AS c FROM {$table_prefix}_cronjobs WHERE cronname = ? AND lastrun = ? AND active = ?",array('MessagesSend','0000-00-00 00:00:00',1));
	if ($result) {
		$count = $adb->query_result($result,0,'c');
		if ($count > 0) {
			$return_str = 'FAILURE';
		}
	}
	die($return_str);
}
//crmv@62821 e

(isset($_REQUEST['save_in_draft']) && $_REQUEST['save_in_draft'] != '') ? $method = 'draft' : $method = 'send'; // crmv@192217

if (isset($_REQUEST['add2queue']) && $_REQUEST['add2queue'] == 'true') {
	$request = $_REQUEST;
	unset($request['add2queue']);
	// crmv@192217 removed
	
	// crmv@62394 - Calendar Tracking, save end time
	if ($method == 'send' && $_REQUEST['tracking_compose_track'] == '1' && $_REQUEST['tracking_compose_start_ts'] > 0) {
		$_REQUEST['tracking_compose_stop_ts'] = $request['tracking_compose_stop_ts'] = time();
	}
	// crmv@62394e
	
	// check errors
	$error = '';
	$scheduled = ($method == 'send' && !empty($request['scheduled_date'])); // crmv@187622
	$check = $focus->checkBeforeSending($request,$error);
	if ($check === true) {
		$focus->add2SendingQueue($current_user->id, $method, $request);
	}
	
	if ($method == 'send') {
		if (!$check && empty($error)) exit; // crmv@201913 in order to force error message in the page
		// crmv@187622
		($scheduled) ? $alert = getTranslatedString('MESSAGE_MAIL_SCHEDULED_SUCCESSFULLY','Emails') : $alert = getTranslatedString('MESSAGE_MAIL_SENT_SUCCESSFULLY','Emails');
		($scheduled) ? $reload_folders = 'yes' : $reload_folders = 'no';
		echo '[#]SUCCESS[#]'.Zend_Json::encode(array('error'=>$error,'alert'=>$alert,'reload_folders'=>$reload_folders)).'[#]';	//crmv@56973 crmv@125629 crmv@159178
		// crmv@187622e
	} else {
		echo vtlib_purify($_REQUEST['message']).'|##||##|';
	}
	exit;
// crmv@192217
} elseif (isset($_REQUEST['add2queue']) && $_REQUEST['add2queue'] != 'true') {
	$error = '';
	$check = $focus->checkBeforeSending($_REQUEST,$error);
	if ($method == 'send' && !$check) {
		echo '[#]SUCCESS[#]'.Zend_Json::encode(array('error'=>$error)).'[#]';
		exit;
	}
// crmv@192217e
}

$local_log =& LoggerManager::getLogger('index');

global $mod_strings,$app_strings;

if (isset($_REQUEST['description']) && $_REQUEST['description'] !='') {
	$_REQUEST['description'] = fck_from_html($_REQUEST['description']);
}
//crmv@55515
$_REQUEST["to_mail"] = str_replace("\n",',',str_replace("\r",',',str_replace("\r\n",',',$_REQUEST["to_mail"])));
$_REQUEST["hidden_toid"] = str_replace("\n",',',str_replace("\r",',',str_replace("\r\n",',',$_REQUEST["hidden_toid"])));
$_REQUEST["parent_name"] = str_replace("\n",',',str_replace("\r",',',str_replace("\r\n",',',$_REQUEST["parent_name"])));
//crmv@55515e
$all_to_ids = $_REQUEST["hidden_toid"];
$all_to_ids .= $_REQUEST["saved_toid"];
$other_to_mails = explode(',',$_REQUEST["to_mail"]);
foreach ($other_to_mails as $other_to_mail) {
	if ($other_to_mail != '') {
		$all_to_ids .= ','.trim($other_to_mail);
	}
}
$_REQUEST["saved_toid"] = implode(',',array_filter(explode(',',$all_to_ids)));
$_REQUEST['email_flag'] = 'DRAFT';
setObjectValuesFromRequest($focus);

//crmv@69922
$other_args = array();
$parent_ids = $focus->column_fields['parent_id'];
if(!empty($parent_ids)) {
	$ids_tokens = array_filter(explode('|', $parent_ids));
	if (count($ids_tokens) > 0) {
		$first_token = explode('@', $ids_tokens[0]);
		$crmid = intval($first_token[0]);
		if ($crmid > 0) {
			$setype = getSalesEntityType($crmid);
			if ($setype == 'Contacts') {
				$other_args['contact_id'] = $crmid;
			} else {
				$other_args['parent_id'] = $crmid;
			}
		}
	}
}
//crmv@69922e

//assign the focus values
$focus->parent_id = $_REQUEST['parent_id'];
$focus->parent_type = $_REQUEST['parent_type'];
$focus->column_fields["assigned_user_id"]=$current_user->id;
$focus->column_fields["activitytype"]="Emails";
$focus->column_fields["date_start"]= date(getNewDisplayDate());//This will be converted to db date format in save

$error_message = '';
$skip_delete_drafts = false;

if (isset($_REQUEST['send_mail']) && $_REQUEST['send_mail']) {

	// crmv@62394 - tracking: create the event and inject the created id in the list of relations
	require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');
	if (CalendarTracking::isEnabledForModule('Emails')) {
		if ($_REQUEST['tracking_compose_track'] == '1' && $_REQUEST['tracking_compose_start_ts'] > 0) {
			CalendarTracking::trackSendEmail(intval($_REQUEST['tracking_compose_start_ts']), intval($_REQUEST['tracking_compose_stop_ts']), $other_args);
		}
	}
	// crmv@62394e

	include("modules/Emails/mailsend.php");
}

if (isset($_REQUEST['save_in_draft']) && $_REQUEST['save_in_draft'] != '') {
	include("modules/Emails/maildraft.php");
} elseif (!$skip_delete_drafts && isset($_REQUEST['draft_id']) && $_REQUEST['draft_id'] != '') {
	delete_draft_mail($_REQUEST['draft_id']);
}

//crmv@62821
if($_REQUEST['add2queue'] != 'true'){
	if ($_REQUEST['send_mail']) {
		$javascript_code .= 'alertSendEmail("'.getTranslatedString('MESSAGE_MAIL_SENT_SUCCESSFULLY_ENABLE_CRON','Emails').'",5);';	//crmv@125629 crmv@159178
	}
}
//crmv@62821 e

if (empty($skip_exit)) {
	echo '[#]SUCCESS[#]'.Zend_Json::encode(array('error'=>$error_message,'javascript'=>$javascript_code)).'[#]';
	exit;
}
?>
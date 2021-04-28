<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/*	crmv@15309	crmv@16175	crmv@22837	crmv@25356	crmv@25351	crmv@25852	crmv@25472	crmv@24120	crmv@2963m */

$adb->println("\n\nMail Sending Process has been started.");

require_once("include/utils/GetGroupUsers.php");
require_once("include/utils/UserInfoUtil.php");

global $adb, $table_prefix, $current_user;

$send_mode = $focus->column_fields['send_mode'];
$from_name = $current_user->user_name;
$from_address = $current_user->column_fields['email1'];
//crmv@2051m
if (isset($_REQUEST['from_email'])) {
	$from_address = $_REQUEST['from_email'];
	$from_name = $focus->getFromEmailName($from_address);
	$account = $focus->getFromEmailAccount($from_address);
	// crmv@114260
	if ($account > 0) {
		// check if I can use the account smtp
		$msgFocus = CRMEntity::getInstance('Messages');
		if ($msgFocus->hasSmtpAccount($account)) {
			$smtpinfo = $msgFocus->getSmtpConfig($account);
			if ($smtpinfo) {
				// convert to nlparam, to override smtp server
				$nlparam = array(
					'smtp_config' => array(
						'enable' => true,
						'server' => $smtpinfo['smtp_server'],
						'server_port' => $smtpinfo['smtp_port'],
						'server_username' => $smtpinfo['smtp_username'],
						'server_password' => $smtpinfo['smtp_password'],
						'smtp_auth' => $smtpinfo['smtp_auth'],
					)
				);
			}
		}
	}
	// crmv@114260e
}
//crmv@2051me
$to_mail = $_REQUEST['to_mail'];
//crmv@32091
$cc = explode(',',$_REQUEST['ccmail']);
$cc = array_map('trim', $cc);
$cc = array_filter($cc);
$cc = implode(',',$cc);
$bcc = explode(',',$_REQUEST['bccmail']);
$bcc = array_map('trim', $bcc);
$bcc = array_filter($bcc);
$bcc = implode(',',$bcc);
//crmv@32091e
$subject = $_REQUEST['subject'];
$description = $_REQUEST['description'];
$parentid = $_REQUEST['parent_id'];
$myids = explode("|",$parentid);
if (!empty($myids)) {
	$myids = array_filter($myids);
}
$message_mode = vtlib_purify($_REQUEST['message_mode']);
$messageid = vtlib_purify($_REQUEST['message']);
$logo = '';
$mail_tmp = '';
include("modules/Emails/mailsend_{$send_mode}.php");

$adb->println("Mail Sending Process has been finished.\n\n");
?>
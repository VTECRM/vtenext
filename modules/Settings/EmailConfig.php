<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@32079

global $mod_strings;
global $app_strings;
global $app_list_strings;
global $table_prefix;
global $adb;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$focusEmails = CRMEntity::getInstance('Emails');

$smarty->assign("SERVER_ACCOUNT", $_REQUEST['account_type']);
if (isset($_REQUEST['calculate_account'])) {
	$default = $focusEmails->default_account[$_REQUEST['account_type']][$_REQUEST['calculate_account']];
	if ($_REQUEST['account_type'] == 'smtp') {
		$smarty->assign("MAILSERVER",$default['server']);
		$smarty->assign("USERNAME",$default['server_username']);
		$smarty->assign("PASSWORD",$default['server_password']);
		$smarty->assign("SMTP_AUTH",$default['smtp_auth']);
		$smarty->assign("MAILSERVERPORT",$default['server_port']);
		$smarty->assign("MAILSERVERNOTE",$default['note']);
	} elseif ($_REQUEST['account_type'] == 'imap') {
		$account_imap_list_tmp = array_keys($focusEmails->default_account['imap']);
		$account_imap_list = array();
		$account_imap_list[''] = $app_strings['Select'];
		foreach ($account_imap_list_tmp as $i => $v) {
			if ($v == 'Other') {
				$account_imap_list[$v] = $mod_strings['LBL_ACCOUNT_MAIL_OTHER'];
			} else {
				$account_imap_list[$v] = $v;
			}
		}
		$smarty->assign("ACCOUNT_IMAP_LIST",$account_imap_list);
		
		$smarty->assign("account",array(
			'account_type'=>$_REQUEST['calculate_account'],
			'server'=>$default['server'],
			'port'=>$default['server_port'],
			'ssl'=>$default['ssl_tls'],
			'domain'=>$default['domain'],
		));
		
		$smarty->assign("i",$_REQUEST['seq']);
	}
	$smarty->display("Settings/EmailConfigAccount.tpl");
	exit;
}

//Display the mail send status
if($_REQUEST['mail_error'] != '') {
	require_once("modules/Emails/mail.php");
	$error_msg = strip_tags(parseEmailErrorString($_REQUEST['mail_error']));
	$error_msg = $mod_strings['LBL_MAILSENDERROR'];
	$smarty->assign("ERROR_MSG",$mod_strings['LBL_TESTMAILSTATUS'].' <b><font class="warning">'.$error_msg.'</font></b>');
}
//crmv@157490
$serverConfigUtils = ServerConfigUtils::getInstance();
$serverConfig = $serverConfigUtils->getConfiguration('email');
$mail_server = $serverConfig['server'];
$mail_server_username = $serverConfig['server_username'];
$mail_server_password = $serverConfig['server_password'];
$smtp_auth = $serverConfig['smtp_auth'];
$from_email_field = $serverConfig['from_email_field'];
$mail_server_account = $serverConfig['account'];
$mail_server_port = $serverConfig['server_port'];
//crmv@157490e
$servername = vtlib_purify($_REQUEST['server_name']);
$username = vtlib_purify($_REQUEST['server_user']);
$account_smtp = vtlib_purify($_REQUEST['account_smtp']);
$server_port = vtlib_purify($_REQUEST['port']);
if(!empty($servername)) {
	$validInput = validateServerName($servername);
	if(! $validInput) {
		$servername = '';
	}
	$smarty->assign("MAILSERVER",$servername);
} elseif(isset($mail_server)) {
	$smarty->assign("MAILSERVER",$mail_server);
}
if(!empty($username)) {
	//$validInput = validateEmailId($username);
	$validInput = $username;
	if(! $validInput) {
		$username = '';
	}
	$smarty->assign("USERNAME",$username);
} elseif(isset($mail_server_username)) {
	$smarty->assign("USERNAME",$mail_server_username);
}
if (isset($mail_server_password)) {
	$smarty->assign("PASSWORD",$mail_server_password);
}
if(isset($_REQUEST['from_email_field'])) {
	$smarty->assign("FROM_EMAIL_FIELD",vtlib_purify($_REQUEST['from_email_field']));
} elseif(isset($from_email_field)) {
	$smarty->assign("FROM_EMAIL_FIELD",$from_email_field);
}
if(isset($_REQUEST['auth_check']))
{
	if($_REQUEST['auth_check'] == 'on') {
		$smarty->assign("SMTP_AUTH",'checked');
	} else {
		$smarty->assign("SMTP_AUTH",'');
	}
} elseif (isset($smtp_auth)) {
	if($smtp_auth == 'true') {
		$smarty->assign("SMTP_AUTH",'checked');
	} else {
		$smarty->assign("SMTP_AUTH",'');
	}
}
if(!empty($account_smtp)) {
	$smarty->assign("ACCOUNT_SMTP",$account_smtp);
	if (isset($focusEmails->default_account['smtp'][$account_smtp]['note'])) {
		$smarty->assign("MAILSERVERNOTE",$focusEmails->default_account['smtp'][$account_smtp]['note']);
	}
} elseif(isset($mail_server_account)) {
	$smarty->assign("ACCOUNT_SMTP",$mail_server_account);
	if (isset($focusEmails->default_account['smtp'][$mail_server_account]['note'])) {
		$smarty->assign("MAILSERVERNOTE",$focusEmails->default_account['smtp'][$mail_server_account]['note']);
	}
}
if(!empty($server_port)) {
	if ($server_port == 0) $server_port = '';
	$smarty->assign("MAILSERVERPORT",$server_port);
} elseif(isset($mail_server_port)) {
	if ($mail_server_port == 0) $mail_server_port = '';
	$smarty->assign("MAILSERVERPORT",$mail_server_port);
}
$account_smtp_list_tmp = array_keys($focusEmails->default_account['smtp']);
$account_smtp_list = array();
$account_smtp_list[''] = $app_strings['Select'];
foreach ($account_smtp_list_tmp as $i => $v) {
	if ($v == 'Other') {
		$account_smtp_list[$v] = $mod_strings['LBL_ACCOUNT_MAIL_OTHER'];
	} else {
		$account_smtp_list[$v] = $v;
	}
}
$smarty->assign("ACCOUNT_SMTP_LIST",$account_smtp_list);

//crmv@16265
$focus = CRMEntity::getInstance('Messages');
$accounts = $focus->getConfiguredAccounts();
$smarty->assign("IMAP_ACCOUNTS",$accounts);

$account_imap_list_tmp = array_keys($focusEmails->default_account['imap']);
$account_imap_list = array();
$account_imap_list[''] = $app_strings['Select'];
foreach ($account_imap_list_tmp as $i => $v) {
	if ($v == 'Other') {
		$account_imap_list[$v] = $mod_strings['LBL_ACCOUNT_MAIL_OTHER'];
	} else {
		$account_imap_list[$v] = $v;
	}
}
$smarty->assign("ACCOUNT_IMAP_LIST",$account_imap_list);
//crmv@16265e

if(isset($_REQUEST['emailconfig_mode']) && $_REQUEST['emailconfig_mode'] != '') {
	$smarty->assign("EMAILCONFIG_MODE",vtlib_purify($_REQUEST['emailconfig_mode']));
} else {
	$smarty->assign("EMAILCONFIG_MODE",'view');
}

//crmv@94084
require_once('include/utils/VTEProperties.php');
$VTEProperties = VTEProperties::getInstance();
$smarty->assign("SMTP_EDITABLE", $VTEProperties->getProperty('smtp_editable'));
//crmv@94084e

$smarty->display("Settings/EmailConfig.tpl");
?>
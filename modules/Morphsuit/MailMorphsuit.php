<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@198545
if (!isset($root_directory)) {
	require_once('../../config.inc.php');
	chdir($root_directory);
}
require_once('include/utils/utils.php'); 
// crmv@198545e

//crmv@35153
$installation_mode = false;
if (empty($_SESSION)) {
	VteSession::start();
}
if (VteSession::get('morph_mode') == 'installation') {
	$installation_mode = true;
	// crmv@198545 - removed code
	global $currentModule, $mod_strings, $app_strings;
	$currentModule = 'Morphsuit';
	$current_language = 'en_us';
	$path = '../../';
	$mod_strings = return_module_language($current_language, $currentModule);
	$app_strings = return_application_language($current_language);
}
//crmv@35153e


global $mod_strings,$currentModule;
require_once("modules/Emails/mail.php");
$mail = new VTEMailer(); // crmv@180739

$type = $_REQUEST['type'];
if ($type == 'SendMorphsuit') {
	$subject = $mod_strings['LBL_MORPHSUIT_ACTIVATION'].' VTE';
	//crmv@35153
	if (!empty($_REQUEST['vte_user_info'])) {
		$vte_user_info = Zend_Json::decode($_REQUEST['vte_user_info']);
		$from_email = $vte_user_info['email'];
		$from_name = $vte_user_info['name'];
	}
	//crmv@35153e
	require_once('modules/Morphsuit/Morphsuit.php');
	$focusMorphsuit = new Morphsuit();
	$to_email = $focusMorphsuit->vteActivationMail;
	$contents = $_REQUEST['chiave'];
} elseif ($type = 'ErrorFreeKey') {
	$subject = $mod_strings['LBL_ERROR_VTE_FREE'];
	$from_email = $from_name = $_REQUEST['email'];
	$to_email = 'errors@crmvillage.biz';
	$contents = '<b>$_REQUEST</b><pre>'.print_r($_REQUEST,true).'</pre><b>$_SESSION</b><pre>'.print_r($_SESSION,true).'</pre><b>$_SERVER</b><pre>'.print_r($_SERVER,true).'</pre>';
}

setMailerProperties($mail,$subject,$contents,$from_email,$from_name,trim($to_email,","),'','',$currentModule,'');
$mail->SMTPAuth = true;
$mail->Port = 465; // crmv@198545
// crmv@196952
eval(Users::m_de_cryption());
eval($hash_version[24]);
// crmv@196952e
$mail_status = MailSend($mail);
if($mail_status != 1)
	echo $mail_status;
die;
?>
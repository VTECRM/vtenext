<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("PortalConfig.php");
require_once("Smarty_setup.php"); // crmv@198415
require_once("include/utils/utils.php");

SDK::loadGlobalPhp(); // crmv@168297

$smarty = new VTECRM_Smarty();

if (!empty($_REQUEST['login_language'])){
	// crmv@127527
	global $default_language, $languages;
	if (array_key_exists($_REQUEST['login_language'], $languages)) {
		$default_language = $_REQUEST['login_language'];
	}
	// crmv@127527e
}
$smarty->assign('LOGINLANGUAGE',$default_language);

loadTranslations(); // crmv@168297

if($_REQUEST['mail_send_message'] != '')
{
	$mail_send_message = explode("@@@",$_REQUEST['mail_send_message']);
	
	$smarty->assign('MAILSENDMESSAGE', $mail_send_message);

}

elseif($_REQUEST['param'] == 'forgot_password')
{
// 	$list = GetForgotPasswordUI();
// 	GetForgotPasswordUI();
	$smarty->assign('FORGOTPASSWORD', true);
	
//         echo $list;
}
elseif($_REQUEST['param'] == 'sign_up')
{
	echo 'Sign Up..........';
	exit;
}

$smarty->display('supportpage.tpl');
?>
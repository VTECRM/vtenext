<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings;
global $app_strings;
global $app_list_strings;
global $table_prefix;

//Display the mail send status
$smarty = new VteSmarty();

if($_REQUEST['sms_error'] != '')
{
    require_once("modules/Sms/sms_.php");
    $error_msg = strip_tags(parseSmsErrorString($_REQUEST['sms_error']));
	$error_msg = $mod_strings['LBL_SMSSENDERROR'].$error_msg;
	$smarty->assign("ERROR_MSG",$mod_strings['LBL_TESTSMSSTATUS'].' <b><font class="warning">'.$error_msg.'</font></b>');
}
global $adb;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
//crmv@157490
$sql="select server_type from tbl_s_smsservertype where presence = ?";
$result = $adb->pquery($sql, array(1));
$n=$adb->num_rows($result);
for ($i=0;$i<$n;$i++){
	$sms_server_type_arr[] = $adb->query_result($result,$i,'server_type');
}
$serverConfigUtils = ServerConfigUtils::getInstance();
$serverConfig = $serverConfigUtils->getConfiguration('sms', array('server','service_type','server_username','server_password','domain','account','prefix','name','smtp_auth'), 'server_type', false, $sms_server_type_arr);
$sms_server_type = $serverConfig['service_type'];
$sms_server = $serverConfig['server'];
$sms_server_username = $serverConfig['server_username'];
$sms_server_domain = $serverConfig['domain'];
$sms_server_account = $serverConfig['account'];
$sms_server_prefix = $serverConfig['prefix'];
$sms_server_name = $serverConfig['name'];
$sms_server_password = $serverConfig['server_password'];
$smtp_auth = $serverConfig['smtp_auth'];
//crmv@157490e
if(isset($_REQUEST['adv_domain']))
	$smarty->assign("ADVDOMAIN",$_REQUEST['adv_domain']);
elseif(isset($sms_server_domain))
	$smarty->assign("ADVDOMAIN",$sms_server_domain);
if(isset($_REQUEST['adv_account']))
	$smarty->assign("ADVACCOUNT",$_REQUEST['adv_account']);
elseif(isset($sms_server_account))
	$smarty->assign("ADVACCOUNT",$sms_server_account);
if(isset($_REQUEST['adv_prefix']))
	$smarty->assign("ADVPREFIX",$_REQUEST['adv_prefix']);
elseif(isset($sms_server_prefix))
	$smarty->assign("ADVPREFIX",$sms_server_prefix);
if(isset($_REQUEST['adv_name']))
	$smarty->assign("ADVNAME",$_REQUEST['adv_name']);
elseif(isset($sms_server_name))
	$smarty->assign("ADVNAME",$sms_server_name);	
if(isset($_REQUEST['server_name']))
	$smarty->assign("SMSSERVER",$_REQUEST['server_name']);
elseif(isset($sms_server))
	$smarty->assign("SMSSERVER",$sms_server);
if(isset($_REQUEST['server_user']))
	$smarty->assign("USERNAME",$_REQUEST['server_user']);
elseif(isset($sms_server_username))
	$smarty->assign("USERNAME",$sms_server_username);
if (isset($sms_server_password))
	$smarty->assign("PASSWORD",$sms_server_password);
if(isset($_REQUEST['auth_check']))
{
	if($_REQUEST['auth_check'] == 'on')
		$smarty->assign("SMTP_AUTH",'checked');
	else
		$smarty->assign("SMTP_AUTH",'');
}
elseif (isset($smtp_auth))
{
	if($smtp_auth == 'true')
		$smarty->assign("SMTP_AUTH",'checked');
	else
		$smarty->assign("SMTP_AUTH",'');
}

if(isset($_REQUEST['smsconfig_mode']) && $_REQUEST['smsconfig_mode'] != '')
	$smarty->assign("SMSCONFIG_MODE",$_REQUEST['smsconfig_mode']);
else
	$smarty->assign("SMSCONFIG_MODE",'view');
$smarty->assign("SERVER_TYPE",$sms_server_type_arr);
$smarty->assign("SMSSERVERTYPE",$sms_server_type);
$smarty->assign("THEME", $theme);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->display("Settings/SmsConfig.tpl");

?>
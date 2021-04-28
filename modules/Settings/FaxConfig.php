<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings;
global $app_strings;
global $app_list_strings;
global $table_prefix;
//die(print_r($_REQUEST));
//Display the mail send status
$smarty = new VteSmarty();
if($_REQUEST['fax_error'] != '')
{
        require_once("modules/Fax/fax_.php");
        $error_msg = strip_tags(parseFaxErrorString($_REQUEST['fax_error']));
	$error_msg = $mod_strings['LBL_FAXSENDERROR'].$error_msg;
	$smarty->assign("ERROR_MSG",$mod_strings['LBL_TESTFAXSTATUS'].' <b><font class="warning">'.$error_msg.'</font></b>');
}

global $adb;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
//crmv@157490
$sql="select server_type from tbl_s_faxservertype where presence = ?";
$result = $adb->pquery($sql, array(1));
$n=$adb->num_rows($result);
for ($i=0;$i<$n;$i++){
	$fax_server_type_arr[] = $adb->query_result($result,$i,'server_type');
}
$serverConfigUtils = ServerConfigUtils::getInstance();
$serverConfig = $serverConfigUtils->getConfiguration('fax', array('server','service_type','server_username','server_password','domain','account','prefix','name','smtp_auth'), 'server_type', false, $fax_server_type_arr);
$fax_server_type = $serverConfig['service_type'];
$fax_server = $serverConfig['server'];
$fax_server_username = $serverConfig['server_username'];
$fax_server_domain = $serverConfig['domain'];
$fax_server_account = $serverConfig['account'];
$fax_server_prefix = $serverConfig['prefix'];
$fax_server_name = $serverConfig['name'];
$fax_server_password = $serverConfig['server_password'];
$smtp_auth = $serverConfig['smtp_auth'];
//crmv@157490e
if(isset($_REQUEST['adv_domain']))
	$smarty->assign("ADVDOMAIN",$_REQUEST['adv_domain']);
elseif(isset($fax_server_domain))
	$smarty->assign("ADVDOMAIN",$fax_server_domain);
if(isset($_REQUEST['adv_account']))
	$smarty->assign("ADVACCOUNT",$_REQUEST['adv_account']);
elseif(isset($fax_server_account))
	$smarty->assign("ADVACCOUNT",$fax_server_account);
if(isset($_REQUEST['adv_prefix']))
	$smarty->assign("ADVPREFIX",$_REQUEST['adv_prefix']);
elseif(isset($fax_server_prefix))
	$smarty->assign("ADVPREFIX",$fax_server_prefix);
if(isset($_REQUEST['adv_name']))
	$smarty->assign("ADVNAME",$_REQUEST['adv_name']);
elseif(isset($fax_server_name))
	$smarty->assign("ADVNAME",$fax_server_name);	
if(isset($_REQUEST['server_name']))
	$smarty->assign("FAXSERVER",$_REQUEST['server_name']);
elseif(isset($fax_server))
	$smarty->assign("FAXSERVER",$fax_server);
if(isset($_REQUEST['server_user']))
	$smarty->assign("USERNAME",$_REQUEST['server_user']);
elseif(isset($fax_server_username))
	$smarty->assign("USERNAME",$fax_server_username);
if (isset($fax_server_password))
	$smarty->assign("PASSWORD",$fax_server_password);
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

if(isset($_REQUEST['faxconfig_mode']) && $_REQUEST['faxconfig_mode'] != '')
	$smarty->assign("FAXCONFIG_MODE",$_REQUEST['faxconfig_mode']);
else
	$smarty->assign("FAXCONFIG_MODE",'view');
	$smarty->assign("THEME", $theme);
$smarty->assign("SERVER_TYPE",$fax_server_type_arr);
$smarty->assign("FAXSERVERTYPE",$fax_server_type);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->display("Settings/FaxConfig.tpl");
?>
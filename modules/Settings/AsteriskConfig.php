<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $mod_strings;
global $app_strings;
global $app_list_strings;
$smarty = new VteSmarty();
if($_REQUEST['error'] != '')
{
//        require_once("modules/Fax/fax_.php");
        $error_msg =$_REQUEST['error'];
//	$error_msg = $mod_strings['LBL_FAXSENDERROR'].$error_msg;
	$smarty->assign("ERROR_MSG",' <b><font class="warning">'.$error_msg.'</font></b>');
}

global $adb,$table_prefix;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

//crmv@157490
$serverConfigUtils = ServerConfigUtils::getInstance();
$serverConfig = $serverConfigUtils->getConfiguration('asterisk', array('server','server_port','server_username','server_password','inc_call'));
$server = $serverConfig['server'];
$server_port = $serverConfig['server_port'];
$server_username = $serverConfig['server_username'];
$server_password = $serverConfig['server_password'];
$inc_call = $serverConfig['inc_call'];
//crmv@157490e
if(isset($_REQUEST['asterisk_server_mode']) && $_REQUEST['asterisk_server_mode'] != '')
	$smarty->assign("ASTERISK_SERVER_MODE",$_REQUEST['asterisk_server_mode']);
else
	$smarty->assign("ASTERISK_SERVER_MODE",'view');
if(isset($_REQUEST['server']))
	$smarty->assign("ASTERISKSERVER",$_REQUEST['server']);
elseif (isset($server))
	$smarty->assign("ASTERISKSERVER",$server);
if (isset($_REQUEST['port']))
        $smarty->assign("ASTERISKPORT",$_REQUEST['port']);      
elseif (isset($server_port))
        $smarty->assign("ASTERISKPORT",$server_port);
else  $smarty->assign("ASTERISKPORT",'5038'); 
if (isset($_REQUEST['server_user']))
	$smarty->assign("ASTERISKUSER",$_REQUEST['server_user']);
elseif (isset($server_username))
        $smarty->assign("ASTERISKUSER",$server_username);
else  $smarty->assign("ASTERISKUSER",'phpagi');      
if (isset($server_password))
	$smarty->assign("ASTERISKPASSWORD",$server_password);
else $smarty->assign("ASTERISKPASSWORD",'phpagi');
if (isset($inc_call))
	$smarty->assign("ASTERISKINC_CALL",$inc_call);
else $smarty->assign("ASTERISKINC_CALL",0);

if ($server_port) $smarty->assign("ACTIVE", 'yes');
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->display("Settings/AsteriskServer.tpl");
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $theme;
global $adb, $table_prefix;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

if($_REQUEST['error'] != '') {
	if($_REQUEST["error"] == 'LBL_PROXY_AUTHENTICATION_REQUIRED')
		$smarty->assign("ERROR_MSG",'<b><font color="red">'.$mod_strings[$_REQUEST["error"]].'</font></b>');
	else
		$smarty->assign("ERROR_MSG",'<b><font color="red">'.$_REQUEST["error"].'</font></b>');
}

//crmv@157490
$serverConfigUtils = ServerConfigUtils::getInstance();
$serverConfig = $serverConfigUtils->getConfiguration('proxy', array('server','server_port','server_username','server_password'));
$server = $serverConfig['server'];
$server_port = $serverConfig['server_port'];
$server_username = $serverConfig['server_username'];
$server_password = $serverConfig['server_password'];
//crmv@157490e

if(isset($_REQUEST['proxy_server_mode']) && $_REQUEST['proxy_server_mode'] != '')
	$smarty->assign("PROXY_SERVER_MODE",$_REQUEST['proxy_server_mode']);
else
	$smarty->assign("PROXY_SERVER_MODE",'view');
	
if(isset($_REQUEST['server']))
	$smarty->assign("PROXYSERVER",$_REQUEST['server']);
elseif (isset($server))
	$smarty->assign("PROXYSERVER",$server);
	
if (isset($_REQUEST['port']))
	$smarty->assign("PROXYPORT",$_REQUEST['port']);
elseif (isset($server_port))
	$smarty->assign("PROXYPORT",$server_port);
        
if (isset($_REQUEST['server_user']))
	$smarty->assign("PROXYUSER",$_REQUEST['server_user']);
elseif (isset($server_username))
	$smarty->assign("PROXYUSER",$server_username);
	
if (isset($server_password))
	$smarty->assign("PROXYPASSWORD",$server_password);

$smarty->assign("THEME", $theme);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

$smarty->display("Settings/ProxyServer.tpl");

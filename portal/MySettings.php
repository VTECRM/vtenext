<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
$cookieurl = str_replace('MySettings.php', '', $_SERVER['SCRIPT_NAME']) ?: '/';

session_set_cookie_params(0, $cookieurl, null, $isHttps, true);
session_start();

$errormsg = '';
require_once("PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '') {
	@header("Location: $Authenticate_Path/login.php");
	exit;
}
require_once("include/utils/utils.php");
SDK::loadGlobalPhp(); // crmv@168297

VteCsrf::check(); // crmv@171581

$default_language = getPortalCurrentLanguage();
loadTranslations(); // crmv@168297
global $default_charset;
header('Content-Type: text/html; charset='.$default_charset);

// crmv@201760 - move smarty include here
require_once("Smarty_setup.php");
$smarty = new VTECRM_Smarty();
// crmv@201760e

if($_REQUEST['fun'] != '' && $_REQUEST['fun'] == 'savepassword')
{
	include("include.php");
	require_once("HelpDesk/Utils.php");
	include("version.php");
	global $version;
	$errormsg = SavePassword($version);
}
$smarty->assign("ERRORMSG",$errormsg);

if($_REQUEST['last_login'] != '')
{
	$last_login = portal_purify(stripslashes($_REQUEST['last_login']));
	$_SESSION['last_login'] = $last_login;
	$smarty->assign('LASTLOGIN',$last_login);
}
elseif($_SESSION['last_login'] != '')
{
	$last_login = $_SESSION['last_login'];
}

if($_REQUEST['support_start_date'] != '')
	$_SESSION['support_start_date'] = $support_start_date = portal_purify(stripslashes(
		$_REQUEST['support_start_date']));
elseif($_SESSION['support_start_date'] != '')
	$support_start_date = $_SESSION['support_start_date'];

$smarty->assign('SUPPORTSTART',$support_start_date);

if($_REQUEST['support_end_date'] != '')
	$_SESSION['support_end_date'] = $support_end_date = portal_purify(stripslashes(
		$_REQUEST['support_end_date']));
elseif($_SESSION['support_end_date'] != '')
	$support_end_date = $_SESSION['support_end_date'];

$smarty->assign("SUPPORTEND",$support_end_date);

$smarty->display('MySettings.tpl');
?>
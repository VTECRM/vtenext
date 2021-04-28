<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("PortalConfig.php");
// crmv@81291
if (!empty($_REQUEST['login_language'])){ // crmv@149178
	// crmv@127527
	global $default_language, $languages;
	if (array_key_exists($_REQUEST['login_language'], $languages)) {
		$default_language = $_REQUEST['login_language'];
	}
	// crmv@127527e
}
// crmv@81291e

include("version.php");
require_once('include/utils/utils.php');
require_once("Smarty_setup.php");

include("templates/setting.php");


SDK::loadGlobalPhp(); // crmv@168297

loadTranslations(); // crmv@168297

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
$cookieurl = str_replace('login.php', '', $_SERVER['SCRIPT_NAME']) ?: '/';

session_set_cookie_params(0, $cookieurl, null, $isHttps, true);
@session_start();

VteCsrf::check(); // crmv@171581

if(isset($_SESSION['customer_id']) && isset($_SESSION['customer_name']))
{
	/*crmv
	header("Location: index.php?action=index&module=.'$module'");*/
	exit;
}
if($_REQUEST['close_window'] == 'true')
{
   ?>
<script language="javascript">
        	window.close();
	</script>
<?php
}
global $default_charset;
header('Content-Type: text/html; charset='.$default_charset);

$smarty = new VTECRM_Smarty();
$smarty->assign('LOGINLANGUAGE',$default_language);

// crmv@168297
$smarty->assign('GLOBAL_CSS', SDK::getGlobalCss());
$smarty->assign('GLOBAL_JS', SDK::getGlobalJs());
// crmv@168297e

$smarty->assign('BROWSERNAME',$browsername );
$smarty->assign('TITLE',$site_title);
$smarty->assign('LOGINPAGE',true); // crmv@168297
$smarty->assign('LANGUAGE', getPortalLanguages());
$smarty->assign('JSLANGUAGE',$default_language); // crmv@168297

//Display the login error message 
if($_REQUEST['login_error'] != '')
	$smarty->assign("LOGIN_ERROR", strip_tags(base64_decode($_REQUEST['login_error']))); // crmv@203188

// crmv@168297 - removed code

// crmv@167855
if ($enable_registration) {
	$smarty->display('login_plusreg.tpl');
} else {
	$smarty->display('login.tpl');
}
// crmv@167855e
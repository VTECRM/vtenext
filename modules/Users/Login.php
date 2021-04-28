<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@24269 crmv@91082 (ajax login) */

global $theme, $default_language, $current_language, $currentModule;
global $app_strings;

$login_ajax = ($_REQUEST['login_view'] == 'ajax');

//crmv@56023
$focus = CRMEntity::getInstance('Users');
if ($focus->checkBannedLogin() && !$login_ajax) {	// crmv@91082
	header('HTTP/1.0 403 Forbidden');
	include('modules/Users/403error.html');
	exit;
}
//crmv@56023

$theme_path="themes/$theme/";
$theme_path_login="themes/$theme/images/login/";

//we don't want the parent module's string file, but rather the string file specifc to this subpanel
$current_module_strings = return_module_language($current_language, 'Users');

define("IN_LOGIN", true);

include_once('vtlib/Vtecrm/Language.php');//crmv@207871

//crmv@16312
// Retrieve username and password from the session if possible.
if(VteSession::hasKey("login_user_name"))
{
	if (isset($_REQUEST['default_user_name'])) {
		$login_user_name = trim(vtlib_purify($_REQUEST['default_user_name']), '"\'');
	} else {
		$login_user_name =  trim(vtlib_purify($_REQUEST['login_user_name']), '"\'');
	}
} else {
	if (isset($_REQUEST['default_user_name'])) {
		$login_user_name = trim(vtlib_purify($_REQUEST['default_user_name']), '"\'');
	}  else {
		$login_user_name = $default_user_name;
	}
	$_session['login_user_name'] = $login_user_name;
}
$current_module_strings['VLD_ERROR'] = base64_decode('UGxlYXNlIHJlcGxhY2UgdGhlIFN1Z2FyQ1JNIGxvZ29zLg==');

// Retrieve username and password from the session if possible.
if(VteSession::hasKey("login_password")) {
	$login_password = trim(vtlib_purify($_REQUEST['login_password']), '"\'');
} else {
	$login_password = $default_password;
	$_session['login_password'] = $login_password;
}
//crmv@16312 end
if(VteSession::hasKey("login_error")) {
	$login_error = VteSession::get('login_error');
}

$smarty = new VteSmarty();

$smarty->assign('THEME', $theme);
$smarty->assign('MOD', $current_module_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign('LOGIN_AJAX', $login_ajax);
$smarty->assign('USERNAME', $login_user_name);
$smarty->assign('PASSWORD', $login_password);
$smarty->assign('SAVELOGIN', $savelogin);

if ($_REQUEST['logout_reason_code']) {
	$str = '';
	if ($_REQUEST['logout_reason_code'] == 'concurrent') {
		$str = getTranslatedString('LBL_LOGOUT_REASON_CONCURRENT', 'Users');
	} elseif ($_REQUEST['logout_reason_code'] == 'expired') {
		$str = getTranslatedString('LBL_LOGOUT_REASON_EXPIRED', 'Users');
	}
	$smarty->assign('LOGOUT_REASON', $str);
}

// define this function (SDK::setUtil) to override the logo with anything
if ($theme === 'next') {
	// crmv@187403
	if (function_exists('get_logo_override')) {
		$logoImg = get_logo_override('login');
	} else {
		$logoImg = get_logo('login');
	}
	// crmv@187403e
} else {
	if (function_exists('get_logo_override')) {
		$logoImg = get_logo_override('project');
	} else {
		global $enterprise_project; 
		if (!empty($enterprise_project)) $logoImg = '<img src="'.get_logo('project').'" border="0">';
	}
}
$smarty->assign('LOGOIMG', $logoImg);

$TU = ThemeUtils::getInstance($theme);
$backgroundColor = $TU->getLoginBackgroundColor();
$backgroundImage = $TU->getLoginBackgroundImage();

if (!empty($backgroundColor)) $smarty->assign('BACKGROUND_COLOR', $backgroundColor);
if (!empty($backgroundImage['path'])) $smarty->assign('BACKGROUND_IMAGE', $backgroundImage['path']);

$error_str = '&nbsp;';
if (VteSession::hasKey('validation')) {
	$error_str = $current_module_strings['VLD_ERROR'];
} elseif (isset($login_error) && $login_error != "") {
	$error_str = $login_error;
}
$smarty->assign('ERROR_STR', $error_str);

$smarty_template = 'Login.tpl';

$sdk_custom_file = 'LoginCustomisations';
if (isModuleInstalled('SDK')) {
	$tmp_sdk_custom_file = SDK::getFile($currentModule, $sdk_custom_file);
	if (!empty($tmp_sdk_custom_file)) {
		$sdk_custom_file = $tmp_sdk_custom_file;
	}
}
@include ("modules/$currentModule/$sdk_custom_file.php");

$smarty->display($smarty_template);
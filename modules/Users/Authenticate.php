<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Users/CreateUserPrivilegeFile.php');
// crmv@202301 - removed line

global $mod_strings, $default_charset;
global $login_error;	//crmv@28327
global $table_prefix;
$focus = CRMEntity::getInstance('Users');

$login_ajax = ($_REQUEST['login_view'] == 'ajax'); //crmv@91082

// Add in defensive code here.
$focus->column_fields["user_name"] = to_html($_REQUEST['user_name']);
$user_password = $_REQUEST['user_password'];	//crmv@38918

//crmv@29377
$cookielogin = false;
if (!empty($_COOKIE['savelogindata'])) $cookielogin = true;
$loadResult = $focus->load_user($user_password, $cookielogin);
//crmv@29377e

if($focus->is_authenticated())
{
	//Inserting entries for audit trail during login
	// crmv@202301
	require_once('modules/Settings/AuditTrail.php');
	$AuditTrail = new AuditTrail();
	$AuditTrail->processAuthenticate($focus);
	// crmv@202301e

	// crmv@91082
	// Recording the login info
	require_once('modules/Users/LoginHistory.php');
	$loghistory = LoginHistory::getInstance();
	$Signin = $loghistory->user_login($focus->column_fields["user_name"]);
	// crmv@91082e

	//Security related entries start
	createUserPrivilegesfile($focus->id);
	createUserPrivilegesfile($focus->id, 1); // crmv@39110

	//Security related entries end
	// crmv@128133
	VteSession::removeMulti(array('login_password', 'login_error', 'login_user_name'));
	VteSession::setMulti(array(
		'authenticated_user_id' => $focus->id,
		'app_unique_key' => $application_unique_key,
		'vte_root_directory' => $root_directory
	));
	// crmv@128133e

	// store the user's theme in the session
	// crmv@26809
	$focus->column_fields['default_theme'] = getSingleFieldValue($table_prefix.'_users', 'default_theme', 'id', $focus->id);
	if (!empty($focus->column_fields['default_theme'])) {
		$authenticated_user_theme = $focus->column_fields['default_theme'];
	} else {
		$authenticated_user_theme = $default_theme;
	}

	// store the user's language in the session
	$focus->column_fields['default_language'] = getSingleFieldValue($table_prefix.'_users', 'default_language', 'id', $focus->id);
	if (!empty($focus->column_fields['default_language'])) {
		$authenticated_user_language = $focus->column_fields['default_language'];
	} else {
		$authenticated_user_language = $default_language;
	}
	// crmv@26809-end

	// If this is the default user and the default user theme is set to reset, reset it to the default theme value on each login
	if($reset_theme_on_default_user && $focus->user_name == $default_user_name)
	{
		$authenticated_user_theme = $default_theme;
	}
	if(isset($reset_language_on_default_user) && $reset_language_on_default_user && $focus->user_name == $default_user_name)
	{
		$authenticated_user_language = $default_language;
	}

	// crmv@128133
	VteSession::setMulti(array(
		'authenticated_user_theme' => $authenticated_user_theme,//crmv@207841
		'authenticated_user_language' => $authenticated_user_language,
		'just_authenticated' => 'web', // crmv@181161
	));
	// crmv@128133e

	$log->debug("authenticated_user_theme is $authenticated_user_theme");
	$log->debug("authenticated_user_language is $authenticated_user_language");
	$log->debug("authenticated_user_id is ". $focus->id);
	$log->debug("app_unique_key is $application_unique_key");

	//Clear all uploaded import files for this user if it exists

	global $import_dir;

	$tmp_file_name = $import_dir. "IMPORT_".$focus->id;

	if (file_exists($tmp_file_name)) {
		unlink($tmp_file_name);
	}

	//crmv@91082 crmv@101201
	if ($login_ajax) {
		$SV = SessionValidator::getInstance();
		$userChanged = $SV->userChanged($focus);
		if (!$userChanged) {
			$SV->restoreSessionVars($focus->id);
		} else {
			$SV->clearSessionVars($focus->id);
		}
		$SV->refresh();
		$output = array('success' => true, 'user_changed' => $userChanged);
		$SV->ajaxOutput($output);
	}
	//crmv@91082e crmv@101201e

	$arr = VteSession::get('lastpage');
	if($arr[0]) // crmv@128133
		header("Location: index.php?".$arr[0]);
	else
		header("Location: index.php");
}
else
{
	$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'); // crmv@80972
	setcookie('savelogindata', false, 0, $cookieurl, "", $isHttps, true); //crmv@29377 (cookie url is set in index.php)

	// crmv@43592
	if ($loadResult == 'EXPIRED') { // crmv@187476
		// generate a token for the change password (1 hour only)
		$key = getUserAuthtokenKey('password_recovery',$focus->id,3600);
		header('Location: hub/rpwd.php?action=change_old_pwd&key='.$key); // crmv@192078
		exit;
	}
	// crmv@43592e

	// crmv@128133
	VteSession::setMulti(array(
		'login_user_name' => $focus->column_fields["user_name"],
		'login_password' => $user_password,
		'login_error' => $login_error ?: $mod_strings['ERR_INVALID_PASSWORD'], //crmv@28327
	));
	// crmv@128133e

	//crmv@91082
	if ($login_ajax) {
		$SV = SessionValidator::getInstance();
		$output = array('success' => false, 'error' => VteSession::get('login_error'));
		$SV->ajaxOutput($output);
	}
	//crmv@91082e

	// go back to the login screen.
	// create an error message for the user.
	// crmv@118789
	$arr = VteSession::get('lastpage');
	if(is_array($arr) && $arr[0])
		header("Location: index.php?".$arr[0]);
	else		
		header("Location: index.php");
	// crmv@118789e
}

// this file always redirects somewhere, so it should exit immediately
exit; // crmv@181161
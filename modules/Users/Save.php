<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@341228: rewrite and format, move checks to Users::save()

require_once('modules/Users/Users.php');
require_once('include/logging.php');
require_once('include/utils/UserInfoUtil.php');

global $adb, $table_prefix, $current_user;

$log = &LoggerManager::getLogger('index');
$is_admin = is_admin($current_user);

$focus = CRMEntity::getInstance('Users');
$focus->mode = '';

if (($_REQUEST["record"] ?? '') !== '') {
	$focus->mode = 'edit';
	$focus->id = RequestHandler::paramInt("record");
}

if (empty($_REQUEST['status'])) {
	$_REQUEST['status'] = 'Active';
}

$mode = null;
foreach (['dup_check', 'deleteImage', 'changepassword'] as $k) {
	if (($_REQUEST[$k] ?? '') !== '') {
		$mode = $k;
		break;
	}
}

switch ($mode) {
	case 'dup_check':
		if (!$is_admin) {
			echo 'Unauthorized';
			exit;
		}
		$user_name = $_REQUEST['userName'] ?? '';
		$user_query = "SELECT user_name FROM " . $table_prefix . "_users WHERE user_name =?";
		$user_result = $adb->pquery($user_query, [$user_name]);
		$group_query = "SELECT groupname FROM " . $table_prefix . "_groups WHERE groupname =?";
		$group_result = $adb->pquery($group_query, [$user_name]);

		if ($adb->num_rows($user_result) > 0) {
			echo $mod_strings['LBL_USERNAME_EXIST'];
		} elseif ($adb->num_rows($group_result) > 0) {
			echo $mod_strings['LBL_GROUPNAME_EXIST'];
		} else {
			echo 'SUCCESS';
		}
		exit;
	
	case 'deleteImage':
		if (!$focus->filterOrDenySave()) {
			echo 'Unauthorized';
			exit;
		}
		$focus->id = $_REQUEST['recordid'];
		$focus->deleteImage();
		echo "SUCCESS";
		exit;

	case 'changepassword':
		if (!$focus->filterOrDenySave()) {
			echo 'Unauthorized';
			exit;
		}
		$focus->retrieve_entity_info($_REQUEST['record'], 'Users');
		$focus->id = $_REQUEST['record'];
		if (!isset($_POST['new_password'])) {
			exit;
		}

		$new_passwd = $_POST['new_password'];
		if (!$focus->change_password('', $new_passwd, true, true)) {
			RequestHandler::outputRedirect("index.php?action=Error&module=Users&error_string=" . urlencode($focus->error_string)); // crmv@150748
			exit;
		}
		break;
	
	default:
		// normal save
		$_REQUEST["is_admin"]  = $_POST['is_admin'] ?? 'off';
		$_REQUEST["deleted"]   = $_POST['deleted'] ?? '0';
		$_REQUEST["homeorder"] = $_POST['homeorder'] ?? ''; // crmv@283757
		$_REQUEST["roleid"]    = ($_POST['roleid'] ?? null) ?: ($_POST['user_role'] ?? null) ?: '';

		$focus->column_fields['internal_mailer'] = intval(($_REQUEST['internal_mailer'] ?? '') === 'on');

		if (VteSession::hasKey('internal_mailer') && VteSession::get('internal_mailer') != $focus->column_fields['internal_mailer'])
			VteSession::set('internal_mailer', $focus->column_fields['internal_mailer']);

		setObjectValuesFromRequest($focus);

		// crmv@42024 - translate separators
		$focus->column_fields['decimal_separator'] = $focus->convertToSeparatorValue($focus->column_fields['decimal_separator']);
		$focus->column_fields['thousands_separator'] = $focus->convertToSeparatorValue($focus->column_fields['thousands_separator']);
		// crmv@42024e

		if (!$focus->filterOrDenySave()) {
			RequestHandler::outputRedirect("index.php?module=Users&action=Logout"); // crmv@150748
			exit;
		}
		$focus->save("Users"); //crmv@22622
		$return_id = $focus->id;

		//crmv@17001
		if($_REQUEST['mode'] == 'create') {
			$sql = "update ".$table_prefix."_users set hour_format=? where id=?";
			$adb->pquery($sql, array('24', $focus->id));
		}
		//crmv@17001e

		if (($focus->id ?? '') != '' && ($_POST['group_name'] ?? '') != '') {
			updateUsers2GroupMapping($_POST['group_name'], $focus->id);
		}

		// crmv@187823
		if ($_REQUEST['mode'] == 'create') {
			$focus->initCalendarSharing();
		} else {
			$shareduser_ids = array_filter(explode(";", $_REQUEST['shar_userid']));
			$shareduserocc_ids = array_filter(explode(";", $_REQUEST['sharocc_userid']));
			$shownduser_ids = array_filter(explode(";", $_REQUEST['shown_userid']));
			$focus->updateCalendarSharing($shareduser_ids, $shareduserocc_ids, $shownduser_ids);
		}
		// crmv@187823e
		
		break;
}

$return_module = "Users";
$return_action = "DetailView";

if (($_POST['return_module'] ?? '') != "") $return_module = vtlib_purify($_REQUEST['return_module']);
if (($_POST['return_action'] ?? '') != "") $return_action = vtlib_purify($_REQUEST['return_action']);
if (($_POST['return_id'] ?? '') != "")     $return_id     = vtlib_purify($_REQUEST['return_id']);

if (isset($_REQUEST['activity_mode'])) $activitymode = '&activity_mode=' . vtlib_purify($_REQUEST['activity_mode']);
if (isset($_POST['parenttab']))        $parenttab = getParentTab();

$log->debug("Saved record with id of " . $return_id);

if ($_REQUEST['mode'] == 'create') {
	global $app_strings, $mod_strings, $default_charset;
    require_once('modules/Emails/mail.php');
 	$user_emailid = $focus->column_fields['email1'];

    $subject = $mod_strings['User Login Details'];
    $email_body = $app_strings['MSG_DEAR']." ". $focus->column_fields['last_name'] .",<br><br>";
    $email_body .= $app_strings['LBL_PLEASE_CLICK'] . " <a href='" . $site_URL . "' target='_blank'>"
        . $app_strings['LBL_HERE'] . "</a> " . $mod_strings['LBL_TO_LOGIN'] . "<br><br>";
    $email_body .= $mod_strings['LBL_USER_NAME'] . " : " . $focus->column_fields['user_name'] . "<br>";
    //crmv@36525
    if (!($focus->column_fields['use_ldap'] == '1' || $focus->column_fields['use_ldap'] == 'on')) {
		$email_body .= $mod_strings['LBL_PASSWORD'] . " : " . $focus->column_fields['user_password'] . "<br>";
    }
    //crmv@36525e
 	$email_body .= $mod_strings['LBL_ROLE_NAME'] . " : " . getRoleName($_POST['user_role']) . "<br>";
    $email_body .= "<br>" . $app_strings['MSG_THANKS'] . "<br>" . $current_user->user_name;

    $mail_status = send_mail('Users', $user_emailid, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $subject, $email_body);

    if ($mail_status != 1) {
		$mail_status_str = $user_emailid . "=" . $mail_status . "&&&";
		$error_str = getMailErrorString($mail_status_str);
	}
}

//crmv@29617
if (($_REQUEST['notification_module_settings'] ?? '') === 'yes') {
	$ModNotificationsFocus = ModNotifications::getInstance(); // crmv@164122
	$ModNotificationsFocus->saveModuleSettings($focus->id, $_REQUEST);
}
//crmv@29617e

//crmv@230349
if ($return_module == 'Calendar' && $return_action == 'index')
	$location = "index.php?action=" . vtlib_purify($return_action) . "&module=" . vtlib_purify($return_module);
else
	$location = "index.php?action=" . vtlib_purify($return_action) . "&module=" . vtlib_purify($return_module) . "&record=" . vtlib_purify($return_id);
//crmv@230349e

if ($_REQUEST['modechk'] != 'prefview') {
	$location .= "&parenttab=" . vtlib_purify($parenttab);
}

if ($error_str != '') {
	$user = $focus->column_fields['user_name'];
	$location .= "&user=$user&$error_str";
}

RequestHandler::outputRedirect($location); // crmv@150748

// crmv@341228e

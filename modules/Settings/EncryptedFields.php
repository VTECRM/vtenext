<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * crmv@37679
 */

require_once('modules/SDK/src/208/208Utils.php');

global $mod_strings, $app_strings, $app_list_strings;
global $adb, $table_prefix;
global $current_language, $currentModule, $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$subaction = $_REQUEST['subaction'];
if (!is_admin($current_user)) $subaction = '';


// initialize (here tables and sdk changes are applied)
$uitype208 = new EncryptedUitype();
$uitype208->registerUitype();

// actions
if ($subaction == 'delete') {
	$fieldid = intval($_REQUEST['fieldid']);
	$pwd = $_REQUEST['password'];

	$ret = array('success' => false);

	// check pwd
	$valid = $uitype208->checkFieldPassword($fieldid, $pwd);
	if ($valid) {

		set_time_limit(300); // 5 minutes
		$convert = $uitype208->restoreField($fieldid, $pwd);
		if ($convert) {
			$uitype208->clearCachedPassword($fieldid);
			$ret['success'] = true;
		} else {
			$ret['message'] = getTranslatedString('LBL_UT208_RESTORE_FAILED', 'Settings');
		}
	} else {
		$ret['message'] = getTranslatedString('LBL_UT208_WRONGPWD', 'ALERT_ARR');
	}

	echo json_encode($ret);
	die();
} elseif ($subaction == 'addfield') {

	$fieldid = intval($_REQUEST['fieldid']);
	$pwd = $_REQUEST['password'];

	$ret = array('success' => false);

	if (strlen($pwd) < 6) {
		$ret['message'] = getTranslatedString('LBL_UT208_PWD_TOO_SHORT', 'Settings');
	} elseif (!$uitype208->selfCheck($pwd)) {
		$ret['message'] = getTranslatedString('LBL_UT208_PWD_NOT_SUITABLE', 'Settings');
	} else {

		set_time_limit(300); // 5 minutes
		$convert = $uitype208->convertField($fieldid, $pwd);
		if ($convert) {
			$ret['success'] = true;
		} else {
			$ret['message'] = getTranslatedString('LBL_UT208_GENERIC_ERROR', 'Settings');
		}
	}

	echo json_encode($ret);
	die();
} elseif ($subaction == 'editfield') {

	$fieldid = intval($_REQUEST['fieldid']);
	$pwd = $_REQUEST['password'];
	if (!empty($pwd)) {
		$pwdnew = $_REQUEST['newpassword'];
	}
	$timeout = intval($_REQUEST['timeout'])*60;
	$roles = array_filter(explode(',', $_REQUEST['roles']));
	foreach ($roles as &$role) $role = substr($role, 0, 2);
	$ips = array_filter(explode(' ', $_REQUEST['ips']));

	$ret = array('success' => true);

	// password
	if (!empty($pwd) && !empty($pwdnew)) {
		$ret['success'] = false;
		if (strlen($pwdnew) < 6) {
			$ret['message'] = getTranslatedString('LBL_UT208_PWD_TOO_SHORT', 'Settings');
		} elseif (!$uitype208->selfCheck($pwdnew)) {
			$ret['message'] = getTranslatedString('LBL_UT208_PWD_NOT_SUITABLE', 'Settings');;
		} elseif (!$uitype208->checkFieldPassword($fieldid, $pwd)) {
			$ret['message'] = getTranslatedString('LBL_UT208_WRONGPWD', 'ALERT_ARR');
		} else {
			// do change password
			$r = $uitype208->changePassword($fieldid, $pwd, $pwdnew);
			if ($r) {
				$ret['success'] = true;
			} else {
				$ret['message'] = getTranslatedString('LBL_UT208_GENERIC_ERROR', 'Settings');
			}
		}
	}

	// config
	$config = array(
		'pwd_timeout' => $timeout,
		'valid_ip' => $ips,
		'valid_roles' => $roles,
	);
	$r = $uitype208->saveConfig($fieldid, $config);
	if ($r) {
		$uitype208->clearCachedPassword($fieldid);
	}
	$ret['success'] = $ret['success'] && $r;

	echo json_encode($ret);
	die();
}



// normal list view

$smarty = new VteSmarty();

$smarty->assign("MODULE",$fld_module);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("SELFTEST",$uitype208->selfCheck('pwd123pwd') && $uitype208->selfCheck(''));


if ($subaction == 'add') {
	$availFields = $uitype208->getConvertibleFields();
	$availModules = array();
	foreach ($availFields as $module => $info) {
		$availModules[$module] = getTranslatedString($module, $module);
	}
	asort($availModules);

	$smarty->assign('AVAIL_MODULES', $availModules);
	$smarty->assign('AVAIL_FIELDS', $availFields);

	$smarty->display("Settings/EncryptedFieldsAdd.tpl");
} elseif ($subaction == 'edit') {
	$fieldid = intval($_REQUEST['fieldid']);

	$fieldinfo = $uitype208->getFieldInfo($fieldid);
	$fieldinfo['fieldlabel_trans'] = getTranslatedString($fieldinfo['fieldlabel'], $fieldinfo['module']);

	$fieldconfig = $uitype208->getConfig($fieldid);

	$allroles = $uitype208->getAllRoles();

	$smarty->assign("FIELDID", $fieldid);
	$smarty->assign("FIELDINFO", $fieldinfo);
	$smarty->assign("FIELDCONFIG", $fieldconfig);
	$smarty->assign("ALLROLES", $allroles);

	$smarty->display("Settings/EncryptedFieldsEdit.tpl");
} else {

	$listfields = $uitype208->getAllFields();
	$smarty->assign('LISTFIELDS', $listfields);

	$smarty->display("Settings/EncryptedFields.tpl");
}
?>
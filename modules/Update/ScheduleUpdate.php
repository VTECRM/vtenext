<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181161 crmv@182073 crmv@183486 */

global $current_user;
if (!is_admin($current_user)) die('Unauthorized');

global $theme, $mod_strings, $app_strings;

require_once('modules/Update/AutoUpdater.php');

$smarty = new VteSmarty();

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign("DATE_FORMAT", $current_user->date_format);

$subaction = $_REQUEST['subaction'];

$AU = new AutoUpdater();

if (!$AU->canScheduleUpdate($current_user)) {
	$smarty->assign('TEXT', getTranslatedString('LBL_ALREADY_CHOSEN', 'Update'));
	$smarty->display("AccessDenied.tpl");
	die();
}


$status = $AU->getStatus();
$reason = '';
$showDiffAlert = false;

// check status and reason
if ($status == AutoUpdater::STATUS_NOT_UPDATABLE) {
	$reason = $AU->getInfo("reason");
	
	if ($reason == AutoUpdater::REASON_NEED_PHP_70) {
		// check, in case they updated the version
		$ok = version_compare(phpversion(), '7.0', '>=');
		if ($ok) {
			// we're good, wait for cron to run
			$smarty->assign('TEXT', getTranslatedString('LBL_PHP_OK_WAIT_CRON', 'Update'));
		} else {
			// alert to update php 7
			$smarty->assign('TEXT', getTranslatedString('LBL_NEED_PHP_70', 'Update'));
		}
		$smarty->display("AccessDenied.tpl");
		die();
	} elseif ($reason == AutoUpdater::REASON_OS_NOT_SUPPORTED) {
		// if on windows, show info on how to update manually
		if ($AU->isCommunity()) {
			// crmv@183486
			global $enterprise_current_build, $root_directory;

			$dest_revision = $AU->getInfo("new_revision");
			$info = $AU->getRevisionInfo($dest_revision);
			
			$smarty->assign('BACKUP_INFO_URL', $info['url_info_backup']);
			$smarty->assign('PACKAGE_URL', $AU->getFreePackageUrl());
			$smarty->assign("UPDATE_URL", "index.php?module=Update&amp;action=index&amp;force=1&amp;start={$enterprise_current_build}&amp;end={$dest_revision}");
			$smarty->assign('VTE_FOLDER', $root_directory);
			// crmv@183486e
			
			$smarty->display("modules/Update/WindowsInfo.tpl");
		} else {
			// win not supported for business
			$smarty->assign('TEXT', getTranslatedString('LBL_OS_NOT_SUPPORTED', 'Update'));
			$smarty->display("AccessDenied.tpl");
		}
		die();
	} elseif ($reason == AutoUpdater::REASON_FILES) {
		$showDiffAlert = true;
	}
}


if ($subaction == 'do_schedule') {
	
	$data = array();
	$error = '';
	
	try {
		if ($AU->validateSchedule($_POST, $data, $error)) {
			if ($AU->canScheduleUpdate($current_user)) {
				$AU->scheduleUpdate($current_user, $data);
			} else {
				$error = getTranslatedString('LBL_ALREADY_CHOSEN', 'Update');
			}
		}
	} catch (Exception $e) {
		$error = $e->getMessage();
	}
	
	if ($error) {

		$smarty->assign('ERROR', $error);
		$smarty->assign("DATE_VALUE", $_POST['schedule_date']);
		$smarty->assign("HOUR_VALUE", $_POST['schedule_hour']);
		$smarty->assign("SCHEDULE_ALERT", $_POST['schedule_alert'] == 'on');
		$smarty->assign("SCHEDULE_USERS", $_POST['schedule_users']);
		$smarty->assign("MESSAGE_TEXT", $_POST['schedule_message']);
		
		$smarty->assign("SHOW_DIFF_ALERT", $showDiffAlert);
		$smarty->assign("DIFF_ALERT", $_POST['alert_changes'] == 'on');

		require_once('modules/Reports/Reports.php');
		$repObj = Reports::getInstance();
		// crmv@183486
		$users = $repObj->getSubordinateUsers();
		array_unshift($users, array(
			'userid' => 0,
			'username' => getTranslatedString('LBL_ALL_USERS', 'Update'),
			'label' => '-- '.getTranslatedString('LBL_ALL_USERS', 'Update').' --',
			'value' => 'users::all',
		));
		$smarty->assign("SHAREUSERS_JS", Zend_Json::encode($users));
		// crmv@183486e
		$smarty->assign("SHAREGROUPS_JS", Zend_Json::encode($repObj->getUserGroups()));
		
		$smarty->display('modules/Update/ScheduleUpdate.tpl');
	} else {
		$smarty->display('modules/Update/ScheduleOK.tpl');
	}

} else {

	if (date('H') >= 20) {
		// tomorrow at 20
		$smarty->assign("DATE_VALUE", getDisplayDate(date('Y-m-d', time()+3600*6)));
	} else {
		// today at 20
		$smarty->assign("DATE_VALUE", getNewDisplayDate());
	}
	$smarty->assign("HOUR_VALUE", '20:00');
	
	$smarty->assign("SCHEDULE_ALERT", true);
	$smarty->assign("MESSAGE_TEXT", getTranslatedString('LBL_UPDATE_DEFAULT_MESSAGE', 'Update'));
	
	$smarty->assign("SHOW_DIFF_ALERT", $showDiffAlert);

	require_once('modules/Reports/Reports.php');
	$repObj = Reports::getInstance();
	// crmv@183486
	$users = $repObj->getSubordinateUsers();
	array_unshift($users, array(
		'userid' => 0,
		'username' => getTranslatedString('LBL_ALL_USERS', 'Update'),
		'label' => '-- '.getTranslatedString('LBL_ALL_USERS', 'Update').' --',
		'value' => 'users::all',
	));
	$smarty->assign("SHAREUSERS_JS", Zend_Json::encode($users));
	// crmv@183486e
	$smarty->assign("SHAREGROUPS_JS", Zend_Json::encode($repObj->getUserGroups()));

	$smarty->display('modules/Update/ScheduleUpdate.tpl');

}
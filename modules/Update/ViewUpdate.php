<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@182073 */

global $current_user;
if (!is_admin($current_user)) die('Unauthorized');

global $theme, $mod_strings, $app_strings;

require_once('modules/Update/AutoUpdater.php');

$AU = new AutoUpdater();

$smarty = new VteSmarty();

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign("DATE_FORMAT", $current_user->date_format);

$status = $AU->getStatus(); // crmv@199352
if ($AU->shouldShowPopup($current_user)) {
	
	$version = $AU->getInfo('new_version');
	
	$title = getTranslatedString('LBL_POPUP_TITLE', 'Update');
	$title = str_replace('{version}', $version, $title);
	$smarty->assign('TITLE', $title);

	$smarty->assign('REMINDER_OPTIONS', $AU->getReminderOptions());

	$smarty->display("modules/Update/ViewUpdate.tpl");
// crmv@199352
} elseif (empty($status) || $status == $AU::STATUS_IDLE) {
	if ($status === false) {
		// cron never started
		$smarty->assign('TITLE', getTranslatedString('LBL_NO_UPDATES_CRON', 'Update'));
	} else {
		// idle status
		$lastCheck = $AU->getInfo('last_check_time');
		if (empty($lastCheck) || intval(substr($lastCheck, 0, 4)) < 1980) {
			// never
			$smarty->assign('LAST_CHECK', false);
			$smarty->assign('LAST_CHECK_TEXT', getTranslatedString('Never', 'Users'));
		} else {
			$smarty->assign('LAST_CHECK', getDisplayDate($lastCheck));
			$smarty->assign('LAST_CHECK_TEXT', getFriendlyDate($lastCheck));
			
		}
		$smarty->assign('TITLE', getTranslatedString('LBL_NO_UPDATES_AVAILABLE', 'Update'));
	}
	
	$smarty->display("modules/Update/ViewUpdateIdle.tpl");
} else {
	$processedStatuses = array(
		$AU::STATUS_POSTPONED, $AU::STATUS_REFUSED, $AU::STATUS_SCHEDULED
	);
	if (in_array($status, $processedStatuses)) {
		$smarty->assign('TEXT', getTranslatedString('LBL_ALREADY_CHOSEN', 'Update'));
	} else {
		// processing...
		$smarty->assign('TEXT', getTranslatedString('LBL_PROCESSING_UPDATE', 'Update'));
	}
	$smarty->display("AccessDenied.tpl");
}
// crmv@199352e
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@183486 */

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

if (!$AU->canCancelUpdate($current_user)) {
	$smarty->assign('TEXT', getTranslatedString('LBL_CANNOT_CANCEL', 'Update'));
	$smarty->display("AccessDenied.tpl");
	die();
}

$info = $AU->getInfo();

list($sdate, $stime) = explode(' ', $info['scheduled_time']);

$text = getTranslatedString('LBL_CANCEL_UPDATE_TEXT', 'Update');
$text = str_replace(array('{date}', '{hour}'), array(getDisplayDate($sdate), substr($stime, 0, 5)), $text);
$smarty->assign("CANCEL_TEXT", $text);

$smarty->display('modules/Update/CancelUpdate.tpl');
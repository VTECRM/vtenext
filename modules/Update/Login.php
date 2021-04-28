<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181161 crmv@182073 */

global $app_strings;
global $currentModule, $current_user,$current_language;

require_once('data/Tracker.php');
require_once('include/utils/utils.php');

if($current_user->is_admin != 'on') {
	die("<br><br><center>".$app_strings['LBL_PERMISSION']." <a href='javascript:window.history.back()'>".$app_strings['LBL_GO_BACK'].".</a></center>");
}

// if you really want to hurt yourself with a manual update, pass the following param in the url
if ($_REQUEST['force'] !== '1') {
	die("<br><br><center>Operation not permitted <a href='javascript:window.history.back()'>".$app_strings['LBL_GO_BACK'].".</a></center>");
}

$smarty = new VteSmarty();

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty->assign("IMAGE_PATH",$image_path);
$mod_strings = return_module_language($current_language, $currentModule);
$settings_strings = return_module_language($current_language,'Settings');
$smarty->assign("SMOD", $settings_strings);
$smarty->assign("MOD", return_module_language($current_language,'Update'));
$smarty->assign("APP", $app_strings);

require('vteversion.php'); // crmv@181168

$start_revision = intval($_REQUEST['start']);
if (!$start_revision) {
	$start_revision = $enterprise_current_build;
}

$dest_revision = intval($_REQUEST['end']);

if ($dest_revision > 0 && $dest_revision <= $start_revision) {
	die("<br><br><center>Invalid revision specified <a href='javascript:window.history.back()'>".$app_strings['LBL_GO_BACK'].".</a></center>");
}

if ($dest_revision != $enterprise_current_build) {
	die("<br><br><center>You haven't copied the new files yet <a href='javascript:window.history.back()'>".$app_strings['LBL_GO_BACK'].".</a></center>");
}

if ($_REQUEST['start'] > 0 && $_REQUEST['end'] > 0) {
	$smarty->assign("FREEZE_VERSION",true);
} else {
	$smarty->assign("FREEZE_VERSION",false);
}


$smarty->assign("CURRENT_VERSION",$start_revision);
$smarty->assign("DEST_VERSION",$dest_revision);

$smarty->display("modules/$currentModule/Update.tpl");
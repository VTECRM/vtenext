<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@104283 */

global $app_strings, $mod_strings;
global $theme,$default_charset, $current_language;
global $adb, $table_prefix;

$delete_prof_id = intval($_REQUEST['profileid']);
if (empty($delete_prof_id)) die('No profile id specified');

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$mod_strings_users = return_module_language($current_language, 'Users');

$smarty = new VteSmarty();

$smarty->assign("MOD", $mod_strings_users);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);

$delete_prof_name = getProfileName($delete_prof_id);
$profilenameHtml = htmlentities($delete_prof_name,ENT_QUOTES,$default_charset);

$otherProfiles = array();
$result = $adb->pquery("select * from ".$table_prefix."_profile WHERE profileid != ?", array($delete_prof_id));
if ($result && $adb->num_rows($result) > 0) {
	while ($row = $adb->FetchByAssoc($result, -1, false)) {
		$row['profilename'] = htmlentities($row["profilename"],ENT_QUOTES,$default_charset);
		$otherProfiles[] = $row;
	}
}

$smarty->assign("PROFILEID", $delete_prof_id);
$smarty->assign("PROFILENAME", $profilenameHtml);
$smarty->assign("PROFILES", $otherProfiles);

$smarty->display("modules/Users/ProfileDelete.tpl");
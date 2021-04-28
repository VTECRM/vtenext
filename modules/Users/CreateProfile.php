<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

global $table_prefix;
global $mod_strings;
global $adb;
global $current_user;
global $hash_version;
global $current_language;
global $app_strings;
// crmv@184240
if (!is_admin($current_user)) {
    echo getTranslatedString('LBL_UNAUTHORIZED_ACCESS', 'Users');
    die();
}
// crmv@184240e

$profilename = vtlib_purify($_REQUEST['profile_name']);

if (isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != '') {
    eval(Users::m_de_cryption());
    eval($hash_version[6]);
    //crmv@150592
    $profileid = vtlib_purify($_REQUEST['profileid']);
    $query = 'select profilename from ' . $table_prefix . '_profile where profilename=?';
    $params = array($profilename);
    if (!empty($profileid)) {
        $query .= ' and profileid <> ?';
        $params[] = $profileid;
    }
    $result = $adb->pquery($query, $params);
    //crmv@150592e
    if ($adb->num_rows($result) > 0) {
        echo $mod_strings['LBL_PROFILENAME_EXIST'];
        die;
    } else {
        echo 'SUCCESS';
        die;
    }
}

global $theme;
$theme_path = "themes/" . $theme . "/";
$image_path = $theme_path . "images/";
$smarty = new VteSmarty();

if (isset($_REQUEST['parent_profile']) && $_REQUEST['parent_profile'] != '')
    $smarty->assign("PARENT_PROFILE", vtlib_purify($_REQUEST['parent_profile']));
if (isset($_REQUEST['radio_button']) && $_REQUEST['radio_button'] != '')
    $smarty->assign("RADIO_BUTTON", vtlib_purify($_REQUEST['radio_button']));
if (isset($_REQUEST['profile_name']) && $_REQUEST['profile_name'] != '')
    $smarty->assign("PROFILE_NAME", vtlib_purify($_REQUEST['profile_name']));
if (isset($_REQUEST['profile_description']) && $_REQUEST['profile_description'] != '')
    $smarty->assign("PROFILE_DESCRIPTION", vtlib_purify($_REQUEST['profile_description']));
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] != '')
    $smarty->assign("MODE", vtlib_purify($_REQUEST['mode']));

$smarty->assign("MOD", return_module_language($current_language, 'Settings'));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

$sql = "select * from " . $table_prefix . "_profile";
$result = $adb->pquery($sql, array());
$profilelist = array();
$temprow = $adb->fetch_array($result);
do {
    $name = $temprow["profilename"];
    $profileid = $temprow["profileid"];
    $profilelist[] = array($name, $profileid);
} while ($temprow = $adb->fetch_array($result));
$smarty->assign("PROFILE_LISTS", $profilelist);
$smarty->display("CreateProfile.tpl");
?>
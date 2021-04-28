<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Users/LoginHistory.php');


global $app_strings;
global $mod_strings;
global $app_list_strings;
global $current_language, $current_user, $adb;
$current_module_strings = return_module_language($current_language, 'Settings');

global $list_max_entries_per_page;
global $urlPrefix;

$log = LoggerManager::getLogger('login_list');

global $currentModule;

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$focus = new LoginHistory();

$smarty = new VteSmarty();

$category = getParenttab();

$user_list = getUserslist(false);

$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MOD", $current_module_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("USERLIST", $user_list);
$smarty->assign("CATEGORY",$category);
$smarty->assign("THEME", $theme);
$smarty->display("ListLoginHistory.tpl");

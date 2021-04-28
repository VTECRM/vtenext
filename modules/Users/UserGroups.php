<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

global $current_user;
global $mod_strings;

$user_id = intval($_REQUEST['record']);

// crmv@184240
if (!is_admin($current_user) && $current_user->id != $user_id) {
    echo getTranslatedString('LBL_UNAUTHORIZED_ACCESS', 'Users');
    die();
}
// crmv@184240e

$smarty = new VteSmarty();
$oGetUserGroups = new GetUserGroups();
$oGetUserGroups->getAllUserGroups($user_id);
$user_group_info = array();
foreach ($oGetUserGroups->user_groups as $groupid) {
    $user_group_info[$groupid] = getGroupDetails($groupid);
}
$smarty->assign("IS_ADMIN", is_admin($current_user));
$smarty->assign("GROUPLIST", $user_group_info);
$smarty->assign("UMOD", $mod_strings);
$smarty->display("UserGroups.tpl");

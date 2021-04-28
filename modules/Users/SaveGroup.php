<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
global $adb, $table_prefix, $mod_strings, $current_user;

// crmv@184240
if (!is_admin($current_user)) {
    // redirect to settings, where an error will be shown
    header("Location: index.php?module=Settings&action=index&parenttab=Settings");
    die();
}
// crmv@184240e


$groupName = from_html(trim($_REQUEST['groupName']));
$description = from_html($_REQUEST['description']);
$mode = $_REQUEST['mode'];

if (isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != '') {
    if ($mode != 'edit') {
        $query = 'select groupname from ' . $table_prefix . '_groups where groupname=?';
        $params = array($groupName);
    } else {
        $groupid = $_REQUEST['groupid'];
        $query = 'select groupname from ' . $table_prefix . '_groups  where groupname=? and groupid !=?';
        $params = array($groupName, $groupid);
    }
    $result = $adb->pquery($query, $params);

    $user_query = "SELECT user_name FROM " . $table_prefix . "_users WHERE user_name =?";
    $user_result = $adb->pquery($user_query, array($groupName));

    if ($adb->num_rows($result) > 0) {
        echo $mod_strings['LBL_GROUPNAME_EXIST'];
        die;
    } elseif ($adb->num_rows($user_result) > 0) {
        echo $mod_strings['LBL_USERNAME_EXIST'];
        die;
    } else {
        echo 'SUCCESS';
        die;
    }
}


/** returns the group members in an formatted array
 * @param $member_array -- member_array:: Type varchar
 * @returns $groupMemberArray:: Type varchar
 *
 * $groupMemberArray['groups'] -- gives the array of sub groups members ;
 * $groupMemberArray['roles'] -- gives the array of roles present in the group ;
 * $groupMemberArray['rs'] -- gives the array of roles & subordinates present in the group ;
 * $groupMemberArray['users'] -- gives the array of roles & subordinates present in the group;
 *
 */
function constructGroupMemberArray($member_array)
{

    $groupMemberArray = array();
    $roleArray = array();
    $roleSubordinateArray = array();
    $groupArray = array();
    $userArray = array();

    foreach ($member_array as $member) {
        $memSubArray = explode('::', $member);
        if ($memSubArray[0] == 'groups') {
            $groupArray[] = $memSubArray[1];
        }
        if ($memSubArray[0] == 'roles') {
            $roleArray[] = $memSubArray[1];
        }
        if ($memSubArray[0] == 'rs') {
            $roleSubordinateArray[] = $memSubArray[1];
        }
        if ($memSubArray[0] == 'users') {
            $userArray[] = $memSubArray[1];
        }
    }

    $groupMemberArray['groups'] = $groupArray;
    $groupMemberArray['roles'] = $roleArray;
    $groupMemberArray['rs'] = $roleSubordinateArray;
    $groupMemberArray['users'] = $userArray;

    return $groupMemberArray;

}

if (isset($_REQUEST['returnaction']) && $_REQUEST['returnaction'] != '') {
    $returnaction = vtlib_purify($_REQUEST['returnaction']) . '&roleid=' . vtlib_purify($_REQUEST['roleid']);
} else {
    $returnaction = 'GroupDetailView';
}

//Inserting values into Role Table
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'edit') {
    $groupId = $_REQUEST['groupId'];
    $selected_col_string = $_REQUEST['selectedColumnsString'];
    $member_array = explode(';', $selected_col_string);
    $groupMemberArray = constructGroupMemberArray($member_array);
    updateGroup($groupId, $groupName, $groupMemberArray, $description);

    $loc = "Location: index.php?action=" . $returnaction . "&module=Settings&parenttab=Settings&groupId=" . vtlib_purify($groupId);
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'create') {
    $selected_col_string = $_REQUEST['selectedColumnsString'];
    $member_array = explode(';', $selected_col_string);
    $groupMemberArray = constructGroupMemberArray($member_array);
    $groupId = createGroup($groupName, $groupMemberArray, $description);
    $loc = "Location: index.php?action=" . $returnaction . "&parenttab=Settings&module=Settings&groupId=" . vtlib_purify($groupId);

}

header($loc);
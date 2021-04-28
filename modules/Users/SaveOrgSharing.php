<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@184240 */
global $adb, $table_prefix, $current_user;

if (!is_admin($current_user)) {
    // redirect to settings, where an error will be shown
    header("Location: index.php?module=Settings&action=index&parenttab=Settings");
    die();
}


$sql2 = "select * from " . $table_prefix . "_def_org_share where editstatus=0";
$result2 = $adb->pquery($sql2, array());
$num_rows = $adb->num_rows($result2);

for ($i = 0; $i < $num_rows; $i++) {
    $ruleId = $adb->query_result($result2, $i, 'ruleid');
    $tabId = $adb->query_result($result2, $i, 'tabid');
    $reqval = $tabId . '_per';
    $permission = $_REQUEST[$reqval];
    $sql7 = "update " . $table_prefix . "_def_org_share set permission=? where tabid=? and ruleid=?";
    $adb->pquery($sql7, array($permission, $tabId, $ruleId));

    if ($tabId == 6) {
        $sql8 = "update " . $table_prefix . "_def_org_share set permission=? where tabid=4";
        $adb->pquery($sql8, array($permission));

    }

    if ($tabId == 9) {
        $sql8 = "update " . $table_prefix . "_def_org_share set permission=? where tabid=16";
        $adb->pquery($sql8, array($permission));

    }
}
$loc = "Location: index.php?action=OrgSharingDetailView&module=Settings&parenttab=Settings";
header($loc);
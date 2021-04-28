<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@2963m */
global $current_user;
$focus = CRMEntity::getInstance('Messages');

$query = "SELECT count(*) AS \"count\" FROM {$focus->table_name} WHERE deleted = 0 AND smownerid = ? AND seen = ? AND mtype = ?"; //crmv@171021
$params = array($current_user->id,0,'Webmail');

$specialFolders = $focus->getAllSpecialFolders('INBOX');
if (empty($specialFolders)) {
	echo 0;
} else {
	$tmp = array();
	foreach($specialFolders as $account => $folders) {
		$tmp[] = "({$table_prefix}_messages.account = '{$account}' AND {$table_prefix}_messages.folder = '{$folders['INBOX']}')";
	}
	$query .= " AND (".implode(' OR ',$tmp).")";
	$result = $adb->pquerySlave('BadgeCount',$query,$params); // crmv@185894
	if ($result && $adb->num_rows($result) > 0) {
		$count = $adb->query_result($result,0,'count');
	}
	if ($count > 0) {
		echo $count;
	} else {
		echo 0;
	}
}
?>
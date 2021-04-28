<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* retrieves roles  */

global $adb, $table_prefix, $login, $userId;
global $current_user;

if (!$login || !$userId) {
	echo 'Login Failed';
} else {

	$roles = array();
	$res = $adb->query(
		"select *
		from {$table_prefix}_role r
		inner join {$table_prefix}_role2profile rp on rp.roleid = r.roleid"
	);

	if ($res) {
		while ($row = $adb->fetchByAssoc($res)) {
			$roles[] = $row;
		}
	}

	echo Zend_Json::encode(array('roles' => $roles));
}
?>
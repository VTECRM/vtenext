<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@35153
include_once('../../config.inc.php');
chdir($root_directory);
global $adb, $table_prefix;
$user_name = vtlib_purify($_REQUEST['user_name']);
$result = $adb->query("SELECT user_name FROM {$table_prefix}_users WHERE id = 1");
if ($result && $adb->num_rows($result) > 0) {
	if ($user_name == $adb->query_result($result,0,'user_name')) {
		echo 'yes';
		exit;
	}
}
echo 'no';
exit;
//crmv@35153e
?>
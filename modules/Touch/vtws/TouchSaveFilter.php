<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */
global $login, $userId, $current_user, $currentModule;

$module = $_REQUEST['module'];
$viewid = intval($_REQUEST['viewid']);
$recordid = intval($_REQUEST['record']);
$relRecordid = intval($_REQUEST['relrecord']);


if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {


	// non fa nulla per ora
}
?>
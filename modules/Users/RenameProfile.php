<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@150592 */

/* crmv@184240 */

global $current_user;

if (!is_admin($current_user)) {
	die('Not authorized');
}

$profileid = vtlib_purify($_REQUEST['profileid']);
if(strtolower($default_charset) == 'utf-8') {	
	$profilename = $_REQUEST['profilename'];
	$profileDesc = $_REQUEST['description'];
} else {
	$profilename = utf8RawUrlDecode($_REQUEST['profilename']);
	$profileDesc = utf8RawUrlDecode($_REQUEST['description']);
}

$userInfoUtils = UserInfoUtils::getInstance();
$userInfoUtils->renameProfile($profileid, $profilename, $profileDesc);
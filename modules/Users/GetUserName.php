<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@35153 crmv@341231
global $current_user;

if (!is_admin($current_user)) {
    header('HTTP/1.0 403 Forbidden');
	include('modules/Users/403error.html');
	exit;
}

$record = vtlib_purify($_REQUEST['record']);
echo getUserName($record);
exit;
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@161554 */

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

global $adb, $log, $current_user, $table_prefix;

$log =& LoggerManager::getLogger('Newsletter');
$log->debug("invoked Newsletter");

if (!$current_user) {
	require_once('modules/Users/Users.php');
	$current_user = Users::getActiveAdminUser();
}

$gdprws = GDPRWS::getInstance();
$gdprws->checkNoConfirmDeletion();
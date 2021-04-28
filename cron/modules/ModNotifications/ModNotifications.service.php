<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 */

require_once('include/utils/utils.php');
require_once('include/logging.php');
require_once('modules/ModNotifications/ModNotificationsCommon.php');

global $adb, $log, $current_user, $table_prefix;

$log =& LoggerManager::getLogger('ModNotifications');
$log->debug("invoked ModNotifications");

$focus = ModNotifications::getInstance(); // crmv@164122
$query = "SELECT id FROM {$table_prefix}_users WHERE notify_me_via = 'ModNotifications' AND notify_summary NOT IN ('','Never') AND deleted = 0 AND status = 'Active'"; //crmv@33465
$result = $adb->query($query);
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result)) {
		$focus->sendNotificationSummary($row['id']);
	}
}

checkAllListNotificationCount();

$log->debug("end ModNotifications procedure");
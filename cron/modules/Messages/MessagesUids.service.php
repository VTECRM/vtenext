<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

// crmv@42264

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

ini_set('memory_limit','256M');

global $log;
$log =& LoggerManager::getLogger('Messages');
$log->debug("invoked Messages");

$_REQUEST['service'] = 'Messages';
$focus = CRMEntity::getInstance('Messages');

$user_start = $_REQUEST['ustart'];
$user_end = $_REQUEST['uend'];

if ($user_start != '') {
    $params = [];
	global $adb, $table_prefix;
	$query = "select userid from {$table_prefix}_messages_account where userid >= ?";
	$params[] = $user_start;
	if ($user_end != '') {
        $query .= " and userid <= ?";
        $params[] = $user_end;
    }
	$result = $adb->pquery($query, $params);
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$focus->syncUids(true, $row['userid']);
		}
	}
} else {
	$focus->syncUids();
}

$log->debug("end Messages procedure");
?>
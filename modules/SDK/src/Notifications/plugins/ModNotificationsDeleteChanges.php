<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user;
require_once('modules/SDK/src/Notifications/Notifications.php');
$focus = CRMEntity::getInstance($_REQUEST['plugin']);
// crmv@43194
if ($_REQUEST['id'] == 'all') {
	$focus->setAllRecordsSeen();
} else {
	$focus->setRecordSeen(vtlib_purify($_REQUEST['id']));
}
// crmv@43194e
echo '|##|';
include('modules/SDK/src/Notifications/plugins/'.$_REQUEST['plugin'].'CheckChanges.php');
?>
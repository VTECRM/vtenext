<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user;
require_once('modules/SDK/src/Notifications/Notifications.php');
$focus = new Notifications($current_user->id,vtlib_purify($_REQUEST['plugin']));
if (strpos(vtlib_purify($_REQUEST['id']),',') !== false) {
	$ids = array_filter(explode(',',vtlib_purify($_REQUEST['id'])));
	foreach($ids as $id) {
		$focus->deleteNotification($id);
	}
} else {
	$focus->deleteNotification(vtlib_purify($_REQUEST['id']));
}
echo '|##|'.$focus->getUserNotificationNo();
?>
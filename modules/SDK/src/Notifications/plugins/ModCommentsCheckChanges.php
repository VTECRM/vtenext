<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user;
require_once('modules/SDK/src/Notifications/Notifications.php');
$focus = new Notifications($current_user->id,'ModComments');
echo $focus->getUserNotificationNo();
?>
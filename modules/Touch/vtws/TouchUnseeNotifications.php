<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* imposta le notifiche o i commenti come non lette */

require_once('modules/SDK/src/Notifications/Notifications.php');

global $login, $userId, $current_user;

// il modulo, commenti o notifiche
$module = $_REQUEST['module'];

if (!$login || !$userId) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$notif = 0;

	$ids = array_map('intval', explode(':', $_REQUEST['records']));
	$forced = ($_REQUEST['forced'] == '1');

	// segno come non lette
	if ($module == 'ModComments') {
		$focus = CRMEntity::getInstance($module);

		if (count($ids) > 0) {
			foreach ($ids as $id) {
				if (isPermitted($module, 'EditView', $id) == 'yes') {
					$focus->setAsUnread($id);
				}
			}
		}

		$focusNotif = new Notifications($current_user->id,$module);
		// conteggio
		$unseen =  $focusNotif->getUserNotificationNo();
		if (!empty($unseen)) {
			$notif = $unseen;
		}

	}

	// return
	echo Zend_Json::encode($notif);
}
?>
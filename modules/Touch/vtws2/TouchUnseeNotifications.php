<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* imposta le notifiche o i commenti come non lette */

require_once('modules/SDK/src/Notifications/Notifications.php');

class TouchUnseeNotifications extends TouchWSClass {

	public $validateModule = true;

	function process(&$request) {
		global $current_user, $touchInst, $touchUtils;
		
		// il modulo, commenti o notifiche
		$module = $request['module'];
		
		$notif = 0;

		$ids = array_map('intval', explode(':', $request['records']));
		$forced = ($request['forced'] == '1');

		// segno come non lette
		if ($module == 'ModComments') {
			$focus = $touchUtils->getModuleInstance($module);

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
		
		return $this->success(array('notifications'=>$notif));
	}
}

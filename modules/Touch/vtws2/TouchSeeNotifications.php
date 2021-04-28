<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33545 */
/* imposta le notifiche o i commenti come lette */

require_once('modules/SDK/src/Notifications/Notifications.php');

class TouchSeeNotifications extends TouchWSClass {

	public $validateModule = true;

	function process(&$request) {
		global $touchInst, $touchUtils, $current_user;
		
		// il modulo, commenti o notifiche
		$module = $request['module'];
		
		$notif = 0;
		
		$ids = array_map('intval', explode(':', $request['records']));
		$forced = ($request['forced'] == '1');
		
		// crmv@107199
		if ($request['all'] == '1') {
			return $this->seeAllNotifications($module);
		}
		// crmv@107199e

		// segno come lette
		if ($module == 'ModComments') {
			$focus = new Notifications($current_user->id,$module);
			if (count($ids) > 0) {
				foreach ($ids as $id) {
					$focus->deleteNotification($id, $forced);
				}
			}
			// conteggio
			$unseen =  $focus->getUserNotificationNo();
			if (!empty($unseen)) {
				$notif = $unseen;
			}

		} elseif ($module == 'ModNotifications') {
			$focus = $touchUtils->getModuleInstance($module);
			if (count($ids) > 0) {
				foreach ($ids as $id) {
					$focus->setRecordSeen($id);
				}
			}
			// conteggio
			$widgetInstance = $focus->getWidget('DetailViewBlockCommentWidget');
			$widgetInstance->setDefaultCriteria(0);
			$unseen = $widgetInstance->getUnseenComments('',array('ID'=>''));
			if (!empty($unseen) && is_array($unseen)) {
				$notif = count($unseen);
			}
		} else {
			return $this->error("Module $module is not supported");
		}

		return $this->success(array('notifications'=>$notif));
	
	}
	
	// crmv@107199
	function seeAllNotifications($module) {
		global $touchInst, $touchUtils;
		
		// other modules are not supported
		if ($module == 'ModNotifications') {
			$focus = $touchUtils->getModuleInstance($module);
			$focus->setAllRecordsSeen();
		} else {
			return $this->error("Module $module is not supported");
		}
		
		return $this->success(array('notifications'=>0));
	}
	// crmv@107199e
}
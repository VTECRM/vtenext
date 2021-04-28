<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
require_once('modules/SDK/src/Notifications/Notifications.php');

class TouchGetNotifications extends TouchWSClass {

	public $validateModule = true;

	function process(&$request) {
		global $touchInst, $touchUtils, $current_user;

		$module = $request['module'];

		$notif = array();

		$widgetController = $touchUtils->getModuleInstance('ModNotifications');
		$widgetInstance = $widgetController->getWidget('DetailViewBlockCommentWidget');

		if ($request['onlycount'] == 'true') {

			$widgetInstance->setDefaultCriteria(0);
			$unseen = $widgetInstance->getUnseenComments('',array('ID'=>''));
			if (!empty($unseen) && is_array($unseen)) {
				$countNotif = count($unseen);
			} else {
				$countNotif = 0;
			}

		} else {
			// supporto limitato
			$out_notifs = array();
			$notifs = $widgetInstance->getModelsAsArray(0);
			if (!empty($notifs) && is_array($notifs)) {
				foreach ($notifs as $not) {
					$out_notifs[] = $this->addFields($not);
				}
			}
			$notif = $out_notifs;
			$countNotif = count($notif);
		}

		return $this->success(array('notifications'=>$notif, 'total'=>$countNotif));
	}

	/**
	 * Retrieve a single notification
	 */
	public function getOne($crmid) {
		global $adb, $table_prefix, $touchUtils;

		$moduleName = 'ModNotifications';

		$entityInstance = $touchUtils->getModuleInstance($moduleName);
		$table = $entityInstance->table_name;
		$index = $entityInstance->table_index;

		$res = $adb->pquery("SELECT * FROM $table WHERE $index = ?", array($crmid)); // crmv@164122
		if ($res && $adb->num_rows($res) > 0) {

			require_once('modules/ModNotifications/models/Comments.php');
			// get the model
			$mod = new ModNotifications_CommentsModel($adb->fetchByAssoc($res, -1, false));
			// transform it into an array
			$record = $mod->content_no_html();
			$record['crmid'] = $mod->id();
			$record['author'] = $mod->author();
			$record['seen'] = ($mod->isUnseen() ? 0 : 1);
			$record['timestamp'] = $mod->timestamp();
			$record['timestampago'] = $mod->timestampAgo();

			$record = $this->addFields($record);

			return $this->success(array('notifications'=>array($record), 'total'=>1));
		} else {
			return $this->error('Record not found');
		}

	}

	/**
	 * Adds some extra fields
	 */
	protected function addFields($record) {
		// crmv@56798
		$record['record_module'] = 'ModNotifications';
		if (empty($record['record_id'])) $record['record_id'] = $record['crmid'];
		if (empty($record['assigned_user_id']) && !empty($record['smownerid'])) $record['assigned_user_id'] = $record['smownerid'];
		// crmv@56798e
		$isInvitation = (strpos($record['notification_type'], 'invitation') !== false);
		// crmv@198545
		if (is_array($record['item'])) {
			foreach ($record['item'] as $itkey=>$item) {
				if ($itkey == 'module' && $item == 'Calendar' && $isInvitation) {
					$item = 'Events';
				}
				$record['item_'.$itkey] = $item;
			}
		}
		// crmv@198545e
		unset($record['item']);
		// related (l'elemento collegato)
		if (is_array($record['related'])) {
			foreach ($record['related'] as $itkey=>$item) {
				$record['related_'.$itkey] = $item;
			}
		}
		unset($record['related']);
		// TODO: riabilita dettagli per cambio lista
		// TODO: abilita dettagli per "collegato a"
		$record['haslist'] = ($record['haslist'] ? 1 : 0);
		$record['hasdetails'] = ($isInvitation || $record['hasdetails'] ? 1 : 0); // || $not['haslist']; // to show the button
		if (!isset($record['seen'])) $record['seen'] = 0;
		if (isset($record['timestamp'])) {
			$cleants = trim(preg_replace('/[^0-9 _:-]+/', '', $record['timestamp']));
			$record['timestamp'] = strtotime($cleants);
		}

		if (!empty($record['massedit']) && $record['item_record'] > 0) {
			$MUtils = MassEditUtils::getInstance();
			$record['rawhtml'] = $MUtils->getNotificationHtml($record['item_record']);
		}

		// crmv@202577
		if (!empty($record['masscreate']) && $record['item_record'] > 0) {
			$MUtils = MassCreateUtils::getInstance();
			$record['rawhtml'] = $MUtils->getNotificationHtml($record['item_record']);
		}
		// crmv@202577e

		return $record;
	}
}

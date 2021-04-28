<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
require_once('modules/SDK/src/Notifications/Notifications.php');

global $login, $userId, $current_user;

$module = $_REQUEST['module'];

if (!$login || !$userId) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$notif = 0;

	$widgetController = ModNotifications::getInstance(); // crmv@164122
	$widgetInstance = $widgetController->getWidget('DetailViewBlockCommentWidget');

	if ($_REQUEST['onlycount'] == 'true') {
		$widgetInstance->setDefaultCriteria(0);
		$unseen = $widgetInstance->getUnseenComments('',array('ID'=>''));
		if (!empty($unseen) && is_array($unseen)) {
			$notif = count($unseen);
		}

	} else {
		// supporto limitato
		$out_notifs = array();
		$notifs = $widgetInstance->getModelsAsArray(0);
		if (!empty($notifs) && is_array($notifs)) {
			foreach ($notifs as $not) {
				// TODO: troncare stringhe troppo lunghe
				// item
				$isInvitation = (strpos($not['notification_type'], 'invitation') !== false);
				foreach ($not['item'] as $itkey=>$item) {
					if ($itkey == 'module' && $item == 'Calendar' && $isInvitation) {
						$item = 'Events';
					}
					$not['item-'.$itkey] = $item;
				}
				unset($not['item']);
				// related (l'elemento collegato)
				if (is_array($not['related'])) {
					foreach ($not['related'] as $itkey=>$item) {
						$not['related_'.$itkey] = $item;
					}
				}
				unset($not['related']);
				// TODO: riabilita dettagli per cambio lista
				// TODO: abilita dettagli per "collegato a"
				$not['hasdetails'] = $isInvitation || $not['hasdetails']; // || $not['haslist']; // to show the button
				$out_notifs[] = $not;
			}
		}
		$notif = $out_notifs;
	}

	echo Zend_Json::encode($notif);
}
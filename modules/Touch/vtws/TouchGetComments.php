<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
/* crmv@33311 */
/* crmv@34559 - ricerca */
require_once('modules/SDK/src/Notifications/Notifications.php');

global $login, $userId, $current_user;

if (!$login || !$userId) {
	echo 'Login Failed';
} else {

	$module = $_REQUEST['module'];
	$recordid = intval($_REQUEST['record']);
	$page = intval($_REQUEST['page']);
	$search = $_REQUEST['search'];
	if (empty($page)) $page = 1;

	$pageSize = $touchInst->listPageLimit;
	$limitStart = ($page-1)*$pageSize;
	$limitEnd = ($page)*$pageSize;

	$current_user = new Users();
	$current_user->id = $userId;
	$current_user->retrieveCurrentUserInfoFromFile($userId);
	$notif = 0;

	if ($_REQUEST['onlycount'] == 'true') {
		$focus = new Notifications($current_user->id,'ModComments');
		$unseen =  $focus->getUserNotificationNo();

		if (!empty($unseen)) {
			$notif = $unseen;
		}

	} else {

		$widgetController = CRMEntity::getInstance('ModComments');
		$widgetInstance = $widgetController->getWidget('DetailViewBlockCommentWidget');
		if ($recordid <= 0) $widgetInstance->setCriteria('Page'.$page.'News');
		if (!empty($search)) $widgetInstance->setSearchKey($search);


		$context = array();
		if ($recordid > 0) $context['ID'] = $recordid;
		$notifs = $widgetInstance->getModelsAsArray($context, $total);
		$notifs_out = array();

		// adesso raggruppo in modo da avere un array con l'ultimo messaggio e come risposte tutta la conversazione appiattita
		foreach ($notifs as $k=>$v) {
			$hasunseen = false;
			$forced = false;
			$replies = $v['replies'];
			if (is_array($replies) && count($replies) > 0) {
				foreach ($replies as $rk=>$rv) {
					$replies[$rk]['leaf'] = true;
					// fix per &
					$replies[$rk]['commentcontent'] = str_replace('&amp;', '&', $replies[$rk]['commentcontent']);
					$hasunseen |= $replies[$rk]['unseen'];
					$forced |= $replies[$rk]['forced'];
				}
				$lastmessage = $replies[count($replies)-1];
			} else {
				$lastmessage = $v;
				$replies = array();
			}
			$v['leaf'] = true;
			$v['commentcontent'] = str_replace('&amp;', '&', $v['commentcontent']);
			unset($v['replies']);
			array_unshift($replies, $v);
			// fix per &
			$lastmessage['commentcontent'] = str_replace('&amp;', '&', $lastmessage['commentcontent']);
			$lastmessage['comments'] = $replies;
			$lastmessage['leaf'] = false;
			$lastmessage['unseen'] |= $v['unseen'] | $hasunseen; // crmv@TOUCH2
			$lastmessage['forced'] |= $v['forced'] | $forced; // crmv@TOUCH2

			$notifs_out[] = $lastmessage;
		}

		// riordina lista principale
		function comment_compare($v1, $v2) {
			if ($v1['unseen'] != $v2['unseen']) {
				return ($v1['unseen'] ? -1 : 1);
			} else {
				$t1 = strtotime($v1['modifiedtime']);
				$t2 = strtotime($v2['modifiedtime']);
				return ($t1 < $t2 ? 1 : ($t1 > $t2 ? -1 : 0));
			}
		}
		usort($notifs_out, "comment_compare");

		$notif = array('comments' => $notifs_out, 'total'=>$total);

	}

	echo Zend_Json::encode($notif);
}
?>
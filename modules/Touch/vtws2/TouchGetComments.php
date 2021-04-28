<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
/* crmv@33311 */
/* crmv@34559 - ricerca */
require_once('modules/SDK/src/Notifications/Notifications.php');

class TouchGetComments extends TouchWSClass {

	function process(&$request) {
		global $touchInst, $touchUtils, $current_user;

		$module = $request['module'];
		$recordid = intval($request['record']);
		$page = intval($request['page']);
		$search = $request['search'];
		$since = intval($request['since']);
		$sinceDate = ($since > 0 ? date('Y-m-d H:i:S', $since) : null);
		if (empty($page)) $page = 1;

		$pageSize = $touchInst->listPageLimit;
		$limitStart = ($page-1)*$pageSize;
		$limitEnd = ($page)*$pageSize;

		$notif = 0;

		if ($request['onlycount'] == 'true') {
			$focus = new Notifications($current_user->id,'ModComments');
			$unseen =  $focus->getUserNotificationNo();

			if (!empty($unseen)) {
				$notif = array('total'=>$unseen);
			} else {
				$notif = array('total'=>0);
			}

		} else {

			$widgetController = $touchUtils->getModuleInstance('ModComments');
			$widgetInstance = $widgetController->getWidget('DetailViewBlockCommentWidget');
			if ($recordid <= 0) $widgetInstance->setCriteria('Page'.$page.'News');
			if (!empty($search)) $widgetInstance->setSearchKey($search);


			$context = array();
			if ($recordid > 0) $context['ID'] = $recordid;
			$notifs = $widgetInstance->getModelsAsArray($context, $total, $sinceDate);
			$notifs_out = array();

			// adesso raggruppo in modo da avere un array con l'ultimo messaggio e come risposte tutta la conversazione appiattita
			foreach ($notifs as $k=>$v) {
				$hasunseen = false;
				$forced = false;
				$v['owner'] = $v['assigned_user_id'] ?: $v['smownerid'];
				$v['timestamp'] = strtotime($v['modifiedtime']);
				$v['ctimestamp'] = strtotime($v['createdtime']);
				$replies = $v['replies'];
				if (is_array($replies) && count($replies) > 0) {
					foreach ($replies as $rk=>$rv) {
						$replies[$rk]['leaf'] = true;
						$replies[$rk]['owner'] = $rv['assigned_user_id'] ?: $rv['smownerid'];
						$replies[$rk]['timestamp'] = strtotime($rv['modifiedtime']);
						$replies[$rk]['ctimestamp'] = strtotime($rv['createdtime']);
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
				$lastmessage['unseen'] |= $v['unseen'] | $hasunseen;
				$lastmessage['forced'] |= $v['forced'] | $forced;
				$lastmessage['related_to'] = $v['related_to'];
				$lastmessage['related_to_name'] = $v['related_to_name'];
				$lastmessage['related_to_module'] = ($v['related_to'] > 0 ? $touchUtils->getTouchModuleNameFromId($v['related_to']) : '');
				$lastmessage['entityname'] = $touchUtils->getEntityNameFromFields('ModComments', $lastmessage['crmid'], $lastmessage);

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

		return $this->success($notif);
	}
}

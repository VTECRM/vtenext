<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42537 - retrieves informations about the email config (accounts & folders) */
class TouchGetMessagesMeta extends TouchWSClass {

	public function process(&$request) {
		global $current_user, $touchInst, $touchUtils;
		
		if (in_array('Messages', $touchInst->excluded_modules)) return $this->error('Module not permitted');
		
		$focus = $touchUtils->getModuleInstance('Messages');

		$result = array(
			'configured' => false,
			'accounts' => array(),
		);

		$accounts = $focus->getUserAccounts($current_user->id);
		if (!is_array($accounts)) $accounts = array();

		$configured = true;

		$_REQUEST['service'] = 'Messages'; // trick to simulate cron and skip the horrible die
		$_REQUEST['app_key'] = '123';
		$foldersCache = array();
		if (count($accounts) > 1) {
			// add special fake accounts

			// global inbox
			$focus->setAccount('all');
			$result['accounts'][] = array(
				'accountid' => 'all',
				'accountname' => getTranslatedString('LBL_Folder_INBOX', 'Messages'),
				'group' => getTranslatedString('LBL_ACCOUNT_INBOXLIST', 'Messages'),
				'type' => 'inbox',
				'linkto_account' => 'all',
				'linkto_folder' => 'INBOX',
				// fake folder, never seen in the app
				'folders' => array(array(
					'foldername' => 'INBOX',
					'label' => 'INBOX',
					'depth' => 0,
					'type' => 'inbox',
					'unread' => 0,
				)),
				'unread' => $focus->getUnreadMessageCount('all'),
				'signature' => '',
			);

			// single inboxes
			$fakeid = 100000;
			foreach ($accounts as $mailacc) {
				$focus->setAccount($mailacc['id']);
				try {
					$folders = $focus->getFoldersList();
				} catch (Exception $e) {
					$folders = array();
					continue;
				}
				$foldersCache[$mailacc['id']] = $folders;
				// crmv@104788
				try {
					$specialFolders = $focus->getSpecialFolders();
				} catch (Exception $e) {
					if ($e->getMessage() != 'ERR_IMAP_CRON') {
						// throw again if it's not the cron error
						throw $e;
					}
				}
				// crmv@104788e
				$inboxName = $specialFolders['INBOX'];
				// find inbox folder
				$folderInfo = $folders[$inboxName];
				$folderInfo['foldername'] = $inboxName;
				$folderInfo['type'] = 'inbox';
				$result['accounts'][] = array(
					'accountid' => $fakeid++,
					'accountname' => $mailacc['description'],
					'group' => getTranslatedString('LBL_ACCOUNT_INBOXLIST', 'Messages'),
					'type' => 'inbox',
					'linkto_account' => $mailacc['id'],
					'linkto_folder' => $inboxName,
					'folders' => array($folderInfo),
					'unread' => $focus->getUnreadMessageCount($inboxName),
					'signature' => html_entity_decode($mailacc['signature'], ENT_QUOTES, 'UTF-8'),
				);
			}
		}
		foreach ($accounts as $mailacc) {
			$focus->setAccount($mailacc['id']);
			if (array_key_exists($mailacc['id'], $foldersCache)) {
				$folders = $foldersCache[$mailacc['id']];
			} else {
				try {
					$folders = $focus->getFoldersList();
				} catch (Exception $e) {
					$folders = array();
					continue;
				}
				$foldersCache[$mailacc['id']] = $folders;
			}
			$listFolders = array();
			foreach ($folders as $fname=>$f) {
				$type = 'folder';
				// crmv@200661
				if ($f['img'] && preg_match('/folder_([a-z]+)\.png$/i', $f['img'], $matches)) {
					$type = strtolower($matches[1]);
				} elseif ($f['vteicon'] && in_array($f['vteicon'], $focus->folderImgs)) {
					// new icons
					$type = array_search($f['vteicon'], $focus->folderImgs);
					$type = strtolower($type);
				}
				// crmv@200661e
				$listFolders[] =  array(
					'foldername' => $fname,
					'label' => $f['label'],
					'depth' => intval($f['depth']),
					'type' => $type,
					'unread' => $focus->getUnreadMessageCount($fname),
				);
			}
			$result['accounts'][] = array(
				'accountid' => $mailacc['id'],
				'accountname' => $mailacc['description'],
				'group' => getTranslatedString('LBL_ACCOUNTS', 'Messages'),
				'folders' => $listFolders,
				//'unread' => $focus->getUnreadMessageCount('any'),
				'signature' => html_entity_decode($mailacc['signature'], ENT_QUOTES, 'UTF-8'),
			);
		}
		if (count($result['accounts']) == 0) $configured = false;
	
		if ($configured) {
			// get sender addresses
			$focusEmails = $touchUtils->getModuleInstance('Emails');
			try {
				$senders = $focusEmails->getFromEmailList('','');
			} catch (Exception $e) {
				$senders = array();
			}
			$result['senders'] = array_values($senders);
			$result['fetchbody'] = ($focus->fetchBodyInCron == 'yes'); // crmv@166575
		}
	
		$result['configured'] = $configured;

		return $this->success($result);
	}
}

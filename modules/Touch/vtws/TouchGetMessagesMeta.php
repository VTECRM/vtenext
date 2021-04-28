<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42537 - retrieves informations about the email config (accounts & folders) */
global $login, $userId, $current_user, $currentModule;
global $adb, $table_prefix;

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array('Messages', $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$focus = CRMEntity::getInstance('Messages');

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
				$focus->getZendMailStorageImap();
				$folders = $focus->getFoldersList();
			} catch (Exception $e) {
				$folders = array();
				continue;
			}
			$foldersCache[$mailacc['id']] = $folders;
			$specialFolders = $focus->getSpecialFolders();
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
				$focus->getZendMailStorageImap();
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
			if (preg_match('/folder_([a-z]+)\.png$/i', $f['img'], $matches)) {
				$type = strtolower($matches[1]);
			}
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
		$focusEmails = CRMEntity::getInstance('Emails');
		try {
			$senders = $focusEmails->getFromEmailList('','');
		} catch (Exception $e) {
			$senders = array();
		}
		$result['senders'] = array_values($senders);
	}

	$result['configured'] = $configured;

	echo Zend_Json::encode($result);
}

?>
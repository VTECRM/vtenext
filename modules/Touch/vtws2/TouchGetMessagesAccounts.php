<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@107655 */

class TouchGetMessagesAccounts extends TouchWSClass {

	public function process(&$request) {
		global $current_user, $touchInst, $touchUtils;
		
		if (in_array('Messages', $touchInst->excluded_modules)) return $this->error('Module not permitted');
		
		$focus = $touchUtils->getModuleInstance('Messages');

		$accounts = $focus->getUserAccounts($current_user->id);
		if (!is_array($accounts)) $accounts = array();
		
		// avoid html+die!
		$_REQUEST['app_key'] = '12345';
		$_REQUEST['service'] = 'Messages';
		$_REQUEST['file'] = 'Settings/index';
		
		foreach ($accounts as &$account) {
			// hide the password
			if ($account['password']) $account['password'] = '***';
			$account['accountid'] = (int)$account['id'];
			$account['type'] = $account['account'];
			$account['main'] = ($account['main'] == '1');
			$account['signature'] = html_entity_decode($account['signature'], ENT_COMPAT, 'UTF-8');
			unset($account['id'], $account['account']);
			
			// get all folders
			$folders = array();
			try {
				$focus->setAccount($account['accountid']);
				$allFolders = $focus->getFoldersList('');
				foreach ($allFolders as $fname => $fdata) {
					$folders[] = array(
						'name' => $fname,
						'label' => html_entity_decode($fname, ENT_COMPAT, 'UTF-8'),
						'depth' => intval($fdata['depth']),
					);
				}
			} catch (Exception $e) {
				
			}
			$account['folders'] = $folders;
			
			// get mapped folders
			$specialFodlers = array();
			try {
				$specialFodlers = $focus->getSpecialFolders();
			} catch (Exception $e) {
				
			}
			$account['special_folders'] = $specialFodlers;
		}

		$accTypes = $focus->getAvailableAccounts();
		foreach ($accTypes as &$type) {
			$type['value'] = $type['account'];
			unset($type['account']);
		}
		
		$sslOpts = array(
			array('value' => '', 'label' => getTranslatedString('LBL_NONE')),
			array('value' => 'SSL', 'label' => 'SSL'),
			array('value' => 'TLS', 'label' => 'TLS'),
		);
		
		$result = array(
			'accounts' => $accounts,
			'picklists' => array(
				'type' => $accTypes,
				'ssl_tls' => $sslOpts,
			),
		);
		
		return $this->success($result);
	}
}

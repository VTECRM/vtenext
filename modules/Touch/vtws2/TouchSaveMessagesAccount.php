<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@107655 */

class TouchSaveMessagesAccount extends TouchWSClass {

	public function process(&$request) {
		global $current_user, $touchInst, $touchUtils;
		global $adb, $table_prefix;
		
		if (in_array('Messages', $touchInst->excluded_modules)) return $this->error('Module not permitted');
		
		$accountid = $request['accountid'];
		$data = Zend_Json::decode($request['values']);
		
		// fetch the signature, since I'm not updating it in edit mode
		if ($accountid) {
			$res = $adb->pquery("SELECT signature FROM {$table_prefix}_messages_account WHERE id = ? AND userid = ?",array($accountid,$current_user->id));
			if ($res && $adb->num_rows($res) > 0) {
				$data['signature'] = $adb->query_result_no_html($res, 0, 'signature');
			}
		} else {
			$data['signature'] = nl2br(htmlentities($data['signature'], ENT_NOQUOTES, 'UTF-8'));
		}
		
		$focus = $touchUtils->getModuleInstance('Messages');
		
		$accountid = $focus->saveAccount($accountid,
			$data['type'],
			$data['username'],$data['email'],$data['password'],
			intval($data['main']),
			$data['description'],
			$data['server'],$data['port'],$data['ssl_tls'],$data['domain'],
			$data['signature']
		);
		
		if ($accountid) {
			// avoid html+die!
			$_REQUEST['app_key'] = '12345';
			$_REQUEST['service'] = 'Messages';
			$_REQUEST['file'] = 'Settings/index';
			try {
				$focus->syncFolders($current_user->id,$accountid);
				// since the exceptions are caught, I need to verify again
				$focus->setAccount($accountid);
				$focus->getZendMailStorageImap($current_user->id);
			} catch (Exception $e) {
				// delete created the account also
				$focus->deleteAccount($accountid);
				// return the error
				if ($e->getMessage() == 'ERR_IMAP_CRON') {
					return $this->error('Unable to connect, please check the parameters');
				} else {
					return $this->error($e->getMessage());
				}
			}
			
			// now save the special folders
			$foldersOk = false;
			$result = $adb->pquery("select accountid from {$table_prefix}_messages_sfolders where accountid = ?",array($accountid));
			if ($result && $adb->num_rows($result) == 0) {
				try {
					// try to configure automatically folders
					if ($focus->autoSetSpecialFolders($accountid)) {
						$foldersOk = true;
					}
				} catch (Exception $e) {
					// do nothing
				}
			}
			
			// get all folders
			$folders = array();
			try {
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
			
			// get mapped folders
			$specialFodlers = array();
			try {
				$specialFodlers = $focus->getSpecialFolders();
			} catch (Exception $e) {
			}
			
		} else {
			return $this->error('Unable to save the account');
		}

		return $this->success(array(
			'accountid' => $accountid,
			'folders_ok' => $foldersOk,
			'folders' => $folders,
			'special_folders' => $specialFodlers
		));
	}
	
}

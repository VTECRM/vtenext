<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@107655 */

class TouchSaveMessagesFolders extends TouchWSClass {

	public function process(&$request) {
		global $current_user, $touchInst, $touchUtils;
		
		if (in_array('Messages', $touchInst->excluded_modules)) return $this->error('Module not permitted');
		
		$accountid = $request['accountid'];
		$data = Zend_Json::decode($request['values']);
		
		$focus = $touchUtils->getModuleInstance('Messages');

		$_REQUEST['app_key'] = '12345';
		$_REQUEST['service'] = 'Messages';
		$_REQUEST['file'] = 'Settings/index';
		
		try {
			$focus->setAccount($accountid);
			$oldSpecialFolders = $focus->getSpecialFolders();
		} catch (Exception $e) {
			return $this->error('Unable to connect to mail server');
		}
		
		$specialFolders = array();
		foreach($oldSpecialFolders as $special => $folder) {
			if (!empty($data[$special])) {
				$specialFolders[$special] = $data[$special];
			}
		}
		
		$focus->setSpecialFolders($specialFolders,$accountid);
		
		return $this->success();
	}
	
}

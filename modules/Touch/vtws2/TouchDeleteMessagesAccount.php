<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@107655 */

class TouchDeleteMessagesAccount extends TouchWSClass {

	public function process(&$request) {
		global $current_user, $touchInst, $touchUtils;
		
		if (in_array('Messages', $touchInst->excluded_modules)) return $this->error('Module not permitted');
		
		$accountid = $request['accountid'];
		
		$focus = $touchUtils->getModuleInstance('Messages');

		if ($focus->canUserDeleteAccount($current_user->id, $accountid)) {
			$focus->deleteAccount($accountid);
		} else {
			return $this->error('Operation non permitted');
		}
		
		return $this->success();
	}
	
}

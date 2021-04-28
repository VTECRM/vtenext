<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@91082 */

require_once('modules/Users/LoginHistory.php'); 

class TouchLogout extends TouchWSClass {


	function process(&$request) {
		global $touchInst, $current_user;
		
		// crmv@91082 - login history
		$loghistory = LoginHistory::getInstance();
		$loghistory->user_logout($current_user->user_name);
		// crmv@91082e
		
		$touchInst->destroyWSSession();
		
		return $this->success();
	}
	
}

<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@93148 */

class TouchGetWebservices extends TouchWSClass {

	function process(&$request) {
		global $touchInst;
		
		$ws = array_keys($touchInst->webservices);
		
		return $this->success(array('webservices' => $ws));
	}

}

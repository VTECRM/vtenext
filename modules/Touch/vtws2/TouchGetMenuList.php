<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42707 */

class TouchGetMenuList extends TouchWSClass {

	public function process(&$request) {
		global $touchUtils, $current_user;

		$response = $touchUtils->wsRequest($current_user->id,'getmenulist', array());
		$response = $response['result'];

		// TODO: success
		return $this->output($response);
	}
}

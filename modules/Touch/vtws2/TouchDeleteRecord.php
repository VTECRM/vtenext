<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchDeleteRecord extends TouchWSClass {

	public $validateModule = true;

	public function process(&$request) {
		global $current_user, $touchUtils;

		$module = $request['module'];
		$recordid = intval($request['record']);

		if ($recordid > 0 && $module != '') {
			// fix for stupid calendar - removed
			//if ($module == 'Calendar') $module = 'Events';
			$response = $touchUtils->wsRequest($current_user->id,'delete',
				array('id'=>vtws_getWebserviceEntityId($module, $recordid))
			);
			$record = $response['result'];
		}

		if ($response['success'] === true) {
			return $this->success();
		} elseif (!empty($response['error'])) {
			// crmv@159678
			if (is_array($response['error'])) {
				$errString = $response['error']['message'];
			} elseif (is_a($response['error'], 'Exception')) {
				$errString = $response['error']->getMessage();
			} else {
				$errString = $response['error'];
			}
			return $this->error($errString);
			// crmv@159678e
		} else {
			return $this->error('Unknown error');
		}
	}

}

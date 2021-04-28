<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * SimpleEdit: edit a single field of an existing record
 * crmv@39110
 */

class TouchSimpleEdit extends TouchWSClass {

	//public $validateModule = true;

	public function process(&$request) {
		global $touchInst,$touchUtils, $current_user;

		$module = $request['module'];
		$recordid = intval($request['recordid']);
		$fieldname = vtlib_purify($request['fieldname']);
		$updateVal = vtlib_purify($request['fieldvalue']);

		if ($module != 'ALL' && in_array($module, $touchInst->excluded_modules)) return $this->error('Module not permitted');

		$success = true;
		$message = '';

		if (empty($recordid)) {
			$success = false;
			$message = 'Invalid ID';
		} elseif (isPermitted($module, 'DetailViewAjax', $recordid) != 'yes') {
			$success = false;
			$message = 'Not Permitted';
		} else {

			// use ws to do the update
			$columns = array($fieldname=>$updateVal);
			$response = $touchUtils->wsRequest($current_user->id,'updateRecord', array('id'=>vtws_getWebserviceEntityId($module, $recordid),  'columns'=> $columns));

			if (!is_array($response) || !$response['success']) {
				$success = false;
				$message = $response['message'];
			} else {
				$response = $response['result'];
				if ($response[$fieldname] != $updateVal) {
					$success = false;
					$message = 'Unable to update';
				}
			}
		}

		return $touchInst->createOutput(array(), $message, $success);
	}
}

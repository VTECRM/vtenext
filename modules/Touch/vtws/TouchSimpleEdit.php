<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * SimpleEdit: edit a single field of an existing record
 * crmv@39110
 */
global $login, $userId, $current_user, $currentModule;
global $adb, $table_prefix;

// parametri
$module = $_REQUEST['module'];
$recordid = intval($_REQUEST['recordid']);
$fieldname = vtlib_purify($_REQUEST['fieldname']);
$updateVal = vtlib_purify($_REQUEST['fieldvalue']);

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif ($module != 'ALL' && in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

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
		$response = wsRequest($userId,'updateRecord', array('id'=>vtws_getWebserviceEntityId($module, $recordid),  'columns'=> $columns));

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

	// output
	echo Zend_Json::encode(array('success'=>$success, 'message'=>$message));
}
?>
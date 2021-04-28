<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
/* crmv@33097 */

global $login, $userId;

$module = $_REQUEST['module'];
$recordid = intval($_REQUEST['record']);

if(!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	if ($recordid > 0 && $module != '') {
		// fix for stupid calendar - removed
		//if ($module == 'Calendar') $module = 'Events';
		$response = wsRequest($userId,'delete',
			array('id'=>vtws_getWebserviceEntityId($module, $recordid))
		);
		$record = $response['result'];
	}

	if ($response['success'] === true) {
		echo "SUCCESS";
	} elseif (!empty($response['error'])) {
		echo 'ERROR::'.$response['error'];
	} else {
		echo 'ERROR::Unknown error';
	}
}
?>
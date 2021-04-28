<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42707 */

global $adb, $table_prefix, $login, $userId;

if (!$login || !$userId) {
	echo 'Login Failed';
} else {

	$response = wsRequest($userId,'getmenulist', array());
	$response = $response['result'];

	echo Zend_Json::encode($response);
}
?>
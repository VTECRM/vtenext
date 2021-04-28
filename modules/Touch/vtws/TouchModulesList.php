<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - fix vari */
/* crmv@33097 */
global $login, $userId;

if (!$login || empty($userId)) {
	echo 'Login Failed';
} else {

	$recordReturn = touchModulesList();
	$record = Zend_Json::encode($recordReturn);
	echo $record;
}
?>
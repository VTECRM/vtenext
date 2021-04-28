<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * crmv@37679
*/
require_once('modules/SDK/src/208/208Utils.php');

$action = $_REQUEST['subaction'];
$fieldid = intval($_REQUEST['fieldid']);
$crmid = intval($_REQUEST['crmid']);

$uitype208 = new EncryptedUitype();

$ret = array();

if ($action == 'decrypt') {
	$password = $_REQUEST['password'];
	if ($uitype208->isPermitted($fieldid)) {
		$decrypted = $uitype208->getValue($fieldid, $password, $crmid);
		if ($decrypted !== false) {
			$uitype208->setCachedPassword($fieldid, $password);
			$ret['success'] = true;
			$ret['value'] = $decrypted;
		} else {
			$ret['success'] = false;
			sleep(1);
		}
	} else {
		$ret['success'] = false;
		$ret['message'] = 'User is not allowed to see this field';
	}

}

echo Zend_Json::encode($ret);
exit();
?>
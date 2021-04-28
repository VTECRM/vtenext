<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb, $table_prefix, $login, $userId, $currentModule;

$module_from = vtlib_purify($_REQUEST['module_from']);
$crmid_from = intval($_REQUEST['crmid_from']);
$module_to = vtlib_purify($_REQUEST['module_to']);
$crmid_to = vtlib_purify($_REQUEST['crmid_to']);


if (!$login || !$userId) {
	echo 'Login Failed';
} else {

	$module = $currentModule = $module_from;

	if ($module_to == 'Events') $module_to = 'Calendar'; // crmv@54335
	
	unset($_REQUEST['mode']);
	$_REQUEST['parentid'] = $crmid_from;
	$_REQUEST['destination_module'] = $module_to;
	$_REQUEST['idlist'] = $crmid_to;
	$_REQUEST['no_redirect'] = true;

	// TODO: handle SDK setFile
	$file = "modules/$currentModule/updateRelations.php";
	if (!is_readable($file)) {
		$file = "modules/VteCore/updateRelations.php";
	}

	try {
		require($file);
		$success = true;
	} catch (Exception $e) {
		$success = false;
		$message = $e->getMessage();
	}

	echo Zend_Json::encode(array('success'=>$success, 'message'=>$message));
}
?>
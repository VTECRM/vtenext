<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb, $table_prefix;
global $login, $userId, $current_user, $currentModule;

$recordid = intval($_REQUEST['recordid']);
$value = intval($_REQUEST['participation']);

if (!$login || empty($userId)) {
	echo 'Login Failed';
} else {

	$success = false;

	$from = 'users';
	$_REQUEST['partecipation'] = $value;
	$_REQUEST['activityid'] = $recordid;
	$_REQUEST['userid'] = $current_user->id;

	try {
		require('modules/Calendar/SavePartecipation.php');
		$success = true;
	} catch (Exception $e) {
		$success = false;
	}

	echo Zend_Json::encode(array('success' => $success, 'invitation_answer' => $value));
}
?>
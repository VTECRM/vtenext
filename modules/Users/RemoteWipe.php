<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161368

global $current_user;

$userid = intval($_REQUEST['userid']);

if ($userid > 0) {
	if (is_admin($current_user)) {
		$focus = CRMEntity::getInstance('Users');
		$r = $focus->remoteWipe($userid);
		$output = array('success' => true);
	} else {
		$output = array('success' => false, 'error' => getTranslatedString('LBL_PERMISSION'));
	}
} else {
	$output = array('success' => false, 'error' => 'Invalid user');
}

echo Zend_Json::encode($output);
die();
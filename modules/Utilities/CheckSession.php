<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@91082 crmv@106590 */

global $current_user;

$SV = SessionValidator::getInstance();

$reason = '';
$username = $current_user ? $current_user->user_name : 0;
if ($SV->isStarted()) {
	$valid = $SV->isValid(null, $reason);
	$output = array('success' => true, 'valid' => $valid, 'updated' => false, 'user_name' => $username, 'reason' => $reason);
} else {
	$SV->refresh();
	$valid = $SV->isValid(null, $reason);
	$output = array('success' => true, 'valid' => $valid, 'updated' => true, 'user_name' => $username, 'reason' => $reason);
}

$SV->ajaxOutput($output);
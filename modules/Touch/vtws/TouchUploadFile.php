<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@71388 */
global $login, $userId, $current_user, $currentModule;

if (!$login || empty($userId)) {
	echo 'Login Failed';
} else {

	$wsclass = new TouchUploadFile();
	$wsclass->execute($_REQUEST);
}

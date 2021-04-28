<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@71387 */
global $adb, $table_prefix;
global $login, $userId, $current_user;


if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array('Leads', $touchInst->excluded_modules)) {
	echo "Module Leads not permitted";
} else {

	$wsclass = new TouchConvertLead();
	$wsclass->execute($_REQUEST);

}
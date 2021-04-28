<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function checkGeoButton(&$instance) {
	global $current_user;

	$tabid = getTabid('Geolocalization');
	if (!$tabid) return false;

	require('user_privileges/requireUserPrivileges.php');
	$permitted = ($profileTabsPermission[$tabid] == 0);

	if (vtlib_isModuleActive('Geolocalization') && $permitted) {
		return true;
	} else {
		return false;
	}

}

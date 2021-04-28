<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function checkPermissionSDKButton(&$row) {
	$permission = (vtlib_isModuleActive('MyNotes') && isPermitted('MyNotes','index') == 'yes');
	return $permission;
}
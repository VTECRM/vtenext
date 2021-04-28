<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@56023 */

require_once('modules/Settings/LoginProtectionViewer.php');
global $adb,$current_user;

if (!is_admin($current_user)) return false;

$focus = LoginProtectionViewer::getInstance();
if($focus->getLoginProtectionStatus()){
	$mode = vtlib_purify($_REQUEST['mode']);
	
	switch ($mode){
		case 'whitelist':
			$recordid = vtlib_purify($_REQUEST['id']);
			$user = CRMEntity::getInstance('Users');
			$adb->pquery("update {$user->track_login_table} set status = ?, date_whitelist = ? where id = ?",array('W',date('Y-m-d H:i:s'),$recordid));
		break;
		default:
			return false;
		break;
	}
}
?>
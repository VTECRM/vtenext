<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once 'modules/PickList/PickListUtils.php';

global $current_language;

$roleid = $_REQUEST['roleid'];
if(empty($roleid)){
	echo "role id cannot be empty";
	exit;
}

$otherRoles = getrole2picklist();
$otherRoles = array_diff($otherRoles, array($roleid=>getRoleName($roleid)));

$smarty = new VteSmarty();
$smarty->assign("ROLES",$otherRoles);
$smarty->assign("MOD", return_module_language($current_language,'PickList'));
$smarty->assign("APP",$app_strings);

$smarty->display("modules/PickList/ShowRoleSelect.tpl");
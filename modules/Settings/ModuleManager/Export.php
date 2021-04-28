<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $current_user; // crmv@37463
$module_export = $_REQUEST['module_export'];

//crmv@25233
if(is_admin($current_user) && VteSession::hasKey("authenticated_user_id") && (VteSession::hasKey("app_unique_key") && VteSession::get("app_unique_key") == $application_unique_key)) { // crmv@37463
	$modules = vtlib_getToggleModuleInfo();
	if ($modules[$module_export]['customized'] != '1') {
		exit;
	}
} else {
	exit;
}
//crmv@25233e

require_once("vtlib/Vtecrm/Package.php");
require_once("vtlib/Vtecrm/Module.php");

$package = new Vtecrm_Package();
$package->export(Vtecrm_Module::getInstance($module_export),'',"$module_export.zip",true);
exit;
?>
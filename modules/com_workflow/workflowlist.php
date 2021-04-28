<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("VTWorkflowManager.inc");
require_once("VTWorkflowApplication.inc");
require_once("VTWorkflowUtils.php");

function vtDisplayWorkflowList($adb, $request, $requestUrl, $app_strings, $current_language){
	global $theme;
	$image_path = "themes/$theme/images/";

	$module = new VTWorkflowApplication("workflowlist");
	$util = new VTWorkflowUtils();
	$mod = return_module_language($current_language, $module->name);

	if(!$util->checkAdminAccess()){
		$errorUrl = $module->errorPageUrl($mod['LBL_ERROR_NOT_ADMIN']);
		$util->redirectTo($errorUrl, $mod['LBL_ERROR_NOT_ADMIN']);
		return;
	}

	$smarty = new VteSmarty();
	$wfs = new VTWorkflowManager($adb);
	$smarty->assign("moduleNames", $util->vtGetModules($adb));
	$smarty->assign("returnUrl", $requestUrl);
	
	// crmv@77249
	if ($_REQUEST['included'] == true && !empty($_REQUEST['formodule'])) {
		$request['list_module'] = $_REQUEST['formodule'];
	}
	// crmv@77249e

	$listModule =$request['list_module'];
	$smarty->assign("listModule", $listModule);
	if($listModule==null || strtolower($listModule)=="all"){
		$smarty->assign("workflows", $wfs->getWorkflows());
	}else{
		$smarty->assign("workflows", $wfs->getWorkflowsForModule($listModule));
	}

	$smarty->assign("MOD",array_merge(
	return_module_language($current_language,'Settings'),
	return_module_language($current_language, $module->name)));
	$smarty->assign("APP", $app_strings);
	$smarty->assign("THEME", $theme);
	$smarty->assign("IMAGE_PATH",$image_path);
	$smarty->assign("MODULE_NAME", $module->label);
	$smarty->assign("PAGE_NAME", $mod['LBL_WORKFLOW_LIST']);
	$smarty->assign("PAGE_TITLE", $mod['LBL_AVAILABLE_WORKLIST_LIST']);
	$smarty->assign("module", $module);
	
	// crmv@77249
	if ($_REQUEST['included'] == true) {
		$smarty->assign("INCLUDED",true);
		$smarty->assign("FORMODULE",$_REQUEST['formodule']);	
	}
	// crmv@77249e
	
	$smarty->display("{$module->name}/ListWorkflows.tpl");
}
vtDisplayWorkflowList($adb, $_REQUEST, $_SERVER["REQUEST_URI"], $app_strings, $current_language);
?>
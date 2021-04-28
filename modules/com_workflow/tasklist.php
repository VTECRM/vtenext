<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	
	require_once("VTTaskManager.inc");
	require_once("VTWorkflowApplication.inc");
    require_once("VTWorkflowUtils.php");

	
	function vtDisplayTaskList($adb, $requestUrl, $current_language){
		global $theme, $app_strings;
		$image_path = "themes/$theme/images/";
		
		$util = new VTWorkflowUtils();
		$module = new VTWorkflowApplication("tasklist");
		$mod = return_module_language($current_language, $module->name);
	
		if(!$util->checkAdminAccess()){
			$errorUrl = $module->errorPageUrl($mod['LBL_ERROR_NOT_ADMIN']);
			$util->redirectTo($errorUrl, $mod['LBL_ERROR_NOT_ADMIN']);
			return;
		}

		$smarty = new VteSmarty();
		$tm = new VTTaskManager($adb);
		$smarty->assign("tasks", $tm->getTasks());
		$smarty->assign("moduleNames", array("Contacts", "Applications"));
		$smarty->assign("taskTypes", array("VTEmailTask", "VTDummyTask"));
		$smarty->assign("returnUrl", $requestUrl);
		
		$smarty->assign("MOD", return_module_language($current_language,'Settings'));
		$smarty->assign("APP", $app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH",$image_path);
		$smarty->assign("MODULE_NAME", $module->label);
		$smarty->assign("PAGE_NAME", 'Task List');
		$smarty->assign("PAGE_TITLE", 'List available tasks');
		$smarty->assign("moduleName", $moduleName);
	
		// crmv@77249
		if ($_REQUEST['included'] == true) {
			$smarty->assign("INCLUDED",true);
			$smarty->assign("FORMODULE",$_REQUEST['formodule']);	
		}
		// crmv@77249e
		
		$smarty->display("{$module->name}/ListTasks.tpl");
	}
	vtDisplayTaskList($adb, $_SERVER["REQUEST_URI"], $current_language);
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if($_REQUEST['module_settings'] == 'true') {
	$targetmodule = $_REQUEST['formodule'];

	//crmv@OPER4323
	$action = 'Settings';
	
	global $root_directory;
	$is_action = false;
	$in_core = false;
	$temp_arr = Array("CVS","Attic");
	$in_dir = @scandir($root_directory.'modules/'.$targetmodule);
	$res_arr = @array_intersect($in_dir,$temp_arr);
	if(count($res_arr) == 0 && !preg_match("/[\/.]/",$targetmodule)) {
		if(@in_array($action.".php",$in_dir))
			$is_action = true;
	}
	if(!$is_action) {
		$in_dir = @scandir($root_directory.'modules/VteCore');
		$res_arr = @array_intersect($in_dir,$temp_arr);
		if(count($res_arr) == 0 && !preg_match("/[\/.]/",'VteCore')) {
			if(@in_array($action.".php",$in_dir)) {
				$is_action = true;
				$in_core = true;
			}
		}
	}
	
	$sdk_action = '';
	if (isModuleInstalled('SDK')) {
		$sdk_action = SDK::getFile($targetmodule,$action);
	}
	$call_sdk = true;
	if ($sdk_action == '') {
		$sdk_action = $action;
		$call_sdk = false;
	}
	 if ($in_core && !$call_sdk) {
		$targetSettingPage = 'modules/VteCore/'.$sdk_action.'.php';
	} else {
		$targetSettingPage = 'modules/'.$targetmodule.'/'.$sdk_action.'.php';
	}
	//crmv@OPER4323e
	if(file_exists($targetSettingPage)) {
		Vtecrm_Utils::checkFileAccess($targetSettingPage);
		require_once($targetSettingPage);
	}
}
else{
	$modulemanager_uploaddir = 'cache/vtlib';
	
	if($_REQUEST['module_import'] != '') {
		require_once('modules/Settings/ModuleManager/Import.php');
		exit;
	} else if($_REQUEST['module_update'] != '') {
		require_once('modules/Settings/ModuleManager/Update.php');
		exit;
	} else if($_REQUEST['module_import_cancel'] == 'true') {
		$uploadfile = $_REQUEST['module_import_file'];
		$uploadfilename = "$modulemanager_uploaddir/$uploadfile";
		checkFileAccess($uploadfilename);
		if(file_exists($uploadfilename)) unlink($uploadfilename);
	}
	
	global $mod_strings,$app_strings,$theme;
	$smarty = new VteSmarty();
	$smarty->assign("MOD",$mod_strings);
	$smarty->assign("APP",$app_strings);
	$smarty->assign("THEME", $theme);
	$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
	
	$module_disable = $_REQUEST['module_disable'];
	$module_name = $_REQUEST['module_name'];
	$module_enable = $_REQUEST['module_enable'];
	$module_type = $_REQUEST['module_type'];
	
	if($module_name != '') {
		if($module_type == 'language') {
			if($module_enable == 'true') vtlib_toggleLanguageAccess($module_name, true);
			if($module_disable== 'true') vtlib_toggleLanguageAccess($module_name, false);
		} else {
			if($module_enable == 'true') vtlib_toggleModuleAccess($module_name, true);
			if($module_disable== 'true') vtlib_toggleModuleAccess($module_name, false);
		}
	}
	
	// Check write permissions on the required directories
	$dir_notwritable = Array();
	if(!vtlib_isDirWriteable('cache/vtlib')) $dir_notwritable[] = 'cache/vtlib';
//	if(!vtlib_isDirWriteable('cron/modules')) $dir_notwritable[] = 'cron/modules';
	if(!vtlib_isDirWriteable('modules')) $dir_notwritable[] = 'modules';
	if(!vtlib_isDirWriteable('Smarty/templates/modules')) $dir_notwritable[] = 'Smarty/templates/modules';
	
	$smarty->assign("DIR_NOTWRITABLE_LIST", $dir_notwritable);
	// END
	$smarty->assign("TOGGLE_MODINFO", vtlib_getToggleModuleInfo());
	$smarty->assign("TOGGLE_LANGINFO", vtlib_getToggleLanguageInfo());
	if($_REQUEST['mode'] !='') $mode = $_REQUEST['mode'];
	$smarty->assign("MODE", vtlib_purify($mode));

	// crmv@64542
	$smarty->assign("CAN_IMPORT_CUSTOM_MODULE", true);
	$smarty->assign("CAN_CREATE_CUSTOM_MODULE", true);
	// crmv@64542e
	
	if($_REQUEST['ajax'] != 'true')	$smarty->display('Settings/ModuleManager/ModuleManager.tpl');	
	else $smarty->display('Settings/ModuleManager/ModuleManagerAjax.tpl');
}	
?>
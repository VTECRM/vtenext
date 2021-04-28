<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$module_import_step = vtlib_purify($_REQUEST['module_import']);

require_once('vtlib/Vtecrm/Package.php');
require_once('vtlib/Vtecrm/Language.php');

global $mod_strings,$app_strings,$theme;
$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

global $modulemanager_uploaddir; // Defined in modules/Settings/ModuleManager.php

if($module_import_step == 'Step2') {
	if(!is_dir($modulemanager_uploaddir)) mkdir($modulemanager_uploaddir);
	$uploadfile = "usermodule_". time() . ".zip";
	$uploadfilename = "$modulemanager_uploaddir/$uploadfile";
	checkFileAccess($modulemanager_uploaddir);
	
	if(!move_uploaded_file($_FILES['module_zipfile']['tmp_name'], $uploadfilename)) {
		$smarty->assign("MODULEIMPORT_FAILED", "true");
	} else {
		// crmv@195213
		$error = '';
		$package = new Vtecrm_Package();
		$moduleimport_name = $package->getModuleNameFromZip($uploadfilename, $error);
		$smarty->assign("MODULEIMPORT_ERROR", $error);
		// crmv@195213e

		if($moduleimport_name == null) {
			$smarty->assign("MODULEIMPORT_FAILED", "true");
			$smarty->assign("MODULEIMPORT_FILE_INVALID", "true");
		} else {

			if(!$package->isLanguageType()) {
				$moduleInstance = Vtecrm_Module::getInstance($moduleimport_name);
				$moduleimport_exists=($moduleInstance)? "true" : "false";			
				$moduleimport_dir_name="modules/$moduleimport_name";				
				$moduleimport_dir_exists= (is_dir($moduleimport_dir_name)? "true" : "false");

				$smarty->assign("MODULEIMPORT_EXISTS", $moduleimport_exists);
				$smarty->assign("MODULEIMPORT_DIR", $moduleimport_dir_name);	
				$smarty->assign("MODULEIMPORT_DIR_EXISTS", $moduleimport_dir_exists);
			}

			$moduleimport_dep_vtversion = $package->getDependentVersion();//crmv@207991
			$moduleimport_license = $package->getLicense();

			$smarty->assign("MODULEIMPORT_FILE", $uploadfile);
			$smarty->assign("MODULEIMPORT_TYPE", $package->type());
			$smarty->assign("MODULEIMPORT_NAME", $moduleimport_name);			
			$smarty->assign("MODULEIMPORT_DEP_VTVERSION", $moduleimport_dep_vtversion);
			$smarty->assign("MODULEIMPORT_LICENSE", $moduleimport_license);
		}
	}
} else if($module_import_step == 'Step3') {
	$uploadfile = $_REQUEST['module_import_file'];
	$uploadfilename = "$modulemanager_uploaddir/$uploadfile";
	checkFileAccess($uploadfilename);

	//$overwritedir = ($_REQUEST['module_dir_overwrite'] == 'true')? true : false;
	$overwritedir = false; // Disallowing overwrites through Module Manager UI

	$importtype = $_REQUEST['module_import_type'];
	if(strtolower($importtype) == 'language') {
		$package = new Vtecrm_Language();
	} else {
		$package = new Vtecrm_Package();
	}
    $vtlib_Utils_Log = true;//crmv@208038
	// NOTE: Import function will be called from Smarty to capture the log cleanly.
	//$package->import($uploadfilename, $overwritedir);
	//unlink($uploadfilename);
	$smarty->assign("MODULEIMPORT_PACKAGE", $package);
	$smarty->assign("MODULEIMPORT_DIR_OVERWRITE", $overwritedir);
	$smarty->assign("MODULEIMPORT_PACKAGE_FILE", $uploadfilename);
}

$smarty->display("Settings/ModuleManager/ModuleImport$module_import_step.tpl");

?>
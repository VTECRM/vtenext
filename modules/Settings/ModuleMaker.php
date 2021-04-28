<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@64542 crmv@69398 */

global $adb, $table_prefix;
global $mod_strings,$app_strings, $theme;
global $current_user, $currentModule, $current_language, $default_language;

require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
require_once('modules/Settings/ModuleMaker/ModuleMakerSteps.php');
require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');

if ($_REQUEST['ajax'] == 1) {
	require('modules/Settings/ModuleMaker/ModuleMakerAjax.php');
	return;
}

$mode = $_REQUEST['mode'] ?: '';
$step = intval($_REQUEST['module_maker_step']);
$moduleid = intval($_REQUEST['moduleid']);
$prevstep = intval($_REQUEST['module_maker_prev_step']);
$savedata = intval($_REQUEST['module_maker_savedata']);

$MMUtils = new ModuleMakerUtils();

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

if (!empty($current_user->default_language)) {
	$curr_lang = $current_user->default_language;
} elseif (!empty($current_user->column_fields['default_language'])) {
	$curr_lang = $current_user->column_fields['default_language'];
} else {
	$curr_lang = $default_language;
}
$lang = explode("_",$curr_lang);
$shortlang = $lang[0];
$smarty->assign("LANGUAGE", $curr_lang);
$smarty->assign("SHORT_LANGUAGE", $shortlang);

$totalSteps = 6;

if (($mode == 'create' || $mode == 'edit') && $step > 0) {

	$MMSteps = new ModuleMakerSteps($MMUtils);
	
	// check for the useredit flag
	if ($mode == 'edit' && $moduleid > 0) {
		$userEdit = $MMUtils->hasUserEdit($moduleid);
		$smarty->assign("USEREDIT", $userEdit);
		if ($userEdit) {
			$step = 6;
			$prevstep = 0;
			$savedata = false;
		}
	}
	
	// if creating a new module, clear all the variables
	if ($mode == 'create' && $step == 1 && empty($prevstep)) {
		$MMSteps->clearAllStepsVars();
	}

	$error = false;
	if ($prevstep > 0) {
	
		// preprocess variables before saving them
		$MMSteps->preprocessStepVars($mode, $step, $prevstep, $_REQUEST);

		// save the variables of the previous step
		$MMSteps->saveStepVars($prevstep, $_REQUEST);
		
		// check validity only if going forward
		if ($prevstep < $step) {
			$error = $MMSteps->validateStepVars($prevstep);
		}
	}
	
	if (!$savedata && $step > $totalSteps) $error = 'Beyond last step';
	
	// error in last step, return to previous step, and show a warning
	if (!empty($error)) {
		--$step;
		$smarty->assign("STEP_ERROR", $error);
	} elseif ($savedata) {
		$id = 0;
		// save the module definition in the database
		$data = $MMSteps->getAllVarsForSave();

		if ($data) {
			if ($mode == 'create') {
				$id = $MMUtils->insertModule($data);
			} else {
				$id = $MMUtils->updateModule($moduleid, $data);
			}
		}
		if (!$id) {
			--$step;
			$smarty->assign("STEP_ERROR", $error);
			$error = "No saved data";
		} else {
			
			// generate the scripts if not present
			if (!$MMUtils->hasScript($id)) {
				$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
				$error = $MMGen->generate($id);
			}
		
			// clean up session
			$MMSteps->clearAllStepsVars();
			$step = '';
			$mode = '';
			
			// load the list
			$list = $MMUtils->getList();
			$smarty->assign("MODLIST", $list);
		}
	}
	
	// if entering the edit mode, load the values
	if ($step == 1 && $mode == 'edit' &&  $prevstep < $step) {
		$dbdata = $MMUtils->getModuleInfo($moduleid);
		$MMSteps->saveAllVarsFromDb($dbdata);
	}
	
	if ($step > 0) {
		// retrieve the variables
		$stepVars = $MMSteps->getStepVars($step);
		// add or modify values based on mode and current step
		$MMSteps->processStepVars($mode, $step, $prevstep, $stepVars);
		$smarty->assign("STEPVARS", $stepVars);
	}
	
	// load some specific variables for each step
	if ($step == 1) {
		if (is_file('modules/Area/Area.php')) {
			require_once('modules/Area/Area.php');
			$AManager = AreaManager::getInstance();
			$alist = $AManager->getAreaList();
		} else {
			$alist = array();
		}
		$smarty->assign("AREAS_SUPPORT", !empty($alist));
		$smarty->assign("AREAS", $alist);
		$smarty->assign("CAN_CREATE_INVENTORY", $MMUtils->canCreateInventory());
	} elseif ($step == 2) {
		$smarty->assign("NEWFIELDS", $MMSteps->getNewFields());
	} elseif ($step == 3) {
		$smarty->assign("MAXFILTERCOLUMNS", 9);
		$smarty->assign("FILTERFIELDS", $MMSteps->getFilterFields());
	} elseif ($step == 4) {
		$smarty->assign("RELATION_MODULES", $MMSteps->getRelationModules());
		$smarty->assign("RELATIONS_N1", $MMSteps->getRelations_N1());
		$step1Vars = $MMSteps->getStepVars(1);
		$smarty->assign("NEWMODULESINGLENAME", $step1Vars['mmaker_single_modlabel']);
	} elseif ($step == 5) {
		$smarty->assign("TRANS", $MMSteps->getTranslationsForGrid());
		$smarty->assign("LANGUAGES", vtlib_getToggleLanguageInfo());
		$smarty->assign("LABELS_MODULES", $MMSteps->getLabelsModules($stepVars));
	} elseif ($step == 6) {
		$smarty->assign('SHARINGACTIONS', $MMUtils->getSharingMapping());
		// crmv@205449
		$step1Vars = $MMSteps->getStepVars(1);
		$smarty->assign('IS_INVENTORY', $step1Vars['mmaker_inventory'] == 'on');
		// crmv@205449
		if ($mode == 'edit') {
			$smarty->assign("CAN_EDIT_SCRIPTS", $MMUtils->canEditScripts());
			if ($MMUtils->canEditScripts()) {
				$smarty->assign("EDITABLE_SCRIPTS", $MMUtils->getEditableFiles($moduleid));
			}
		}
	}
	
	// assign the new module name
	$smarty->assign('NEWMODULENAME', $MMSteps->getNewModuleName());
	
} elseif ($mode == 'delete') {
	
	$error = false;
	
	$info = $MMUtils->getModuleInfo($moduleid);

	if (empty($info)) {
		$error = getTranslatedString('LBL_NO_RECORD');
	} elseif ($info['installed'] == 1) {
		$error = getTranslatedString('LBL_MMAKER_CANT_DELETE_INSTALLED');
	} else {
		$r = $MMUtils->deleteModule($moduleid, false);
	}
	
	if (!empty($error)) {
		$smarty->assign("LIST_ERROR", $error);
	}
	
	// and display the list
	$list = $MMUtils->getList();
	$smarty->assign("MODLIST", $list);
	
} elseif ($mode == 'install') {

	$error = false;

	$info = $MMUtils->getModuleInfo($moduleid);

	// regenerate the scripts if not modified by user
	$userEdit = $MMUtils->hasUserEdit($moduleid);

	if (empty($info)) {
		$error = getTranslatedString('LBL_NO_RECORD');
	} elseif ($info['installed'] == 1) {
		$error = getTranslatedString('LBL_MODULE_ALREADY_INSTALLED');
	} elseif (!$userEdit) {
		$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
		$error = $MMGen->generate($moduleid);
	}

	// if no error, install it!
	if (empty($error)) {
		$error = $MMUtils->installModule($moduleid);
	}
	
	if (!empty($error)) {
		$smarty->assign("LIST_ERROR", $error);
	}
	
	// and display the list
	$step = '';
	$mode = '';
	$list = $MMUtils->getList();
	$smarty->assign("MODLIST", $list);
	
} elseif ($mode == 'uninstall') {

	$error = $MMUtils->uninstallModule($moduleid);

	if (!empty($error)) {
		$smarty->assign("LIST_ERROR", $error);
	}
	
	// and display the list
	$step = '';
	$mode = '';
	$list = $MMUtils->getList();

	$smarty->assign("MODLIST", $list);

} elseif ($mode == 'import') {
	
	if ($step == 2) {
		// process the upload
		
		if ($MMUtils->canImport()) {
			$error = $MMUtils->importModule($_FILES['mmaker_import_file']);
		} else {
			$error = getTranslatedString('LBL_NOT_ALLOWED_OPERATION');
		}
		
		if (!empty($error)) {
			$step = 1;
			$smarty->assign("IMPORT_ERROR", $error);
		} else {
			// display the list
			$step = '';
			$mode = '';
			
			$list = $MMUtils->getList();
			$smarty->assign("MODLIST", $list);
		}
	}
	
} elseif ($mode == 'export') {
	// done in Ajax
	
} else {
	
	// otherwise just display the list
	$list = $MMUtils->getList();
	$smarty->assign("MODLIST", $list);
}

$smarty->assign("CAN_IMPORT", $MMUtils->canImport());
$smarty->assign("CAN_EXPORT", $MMUtils->canExport());

$smarty->assign("STEPS", $totalSteps);
$smarty->assign("STEP", $step);
$smarty->assign("MODULEID", $moduleid);
$smarty->assign("MODE", $mode);

$smarty->display('Settings/ModuleMaker/ModuleMaker.tpl');
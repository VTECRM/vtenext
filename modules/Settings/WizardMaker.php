<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@96233 */

require_once('modules/Settings/WizardMaker/WizardMakerSteps.php');

global $adb, $table_prefix;
global $mod_strings,$app_strings, $theme;
global $current_user, $currentModule, $current_language, $default_language;


if ($_REQUEST['ajax'] == 1) {
	require('modules/Settings/WizardMaker/WizardMakerAjax.php');
	return;
}

$mode = $_REQUEST['mode'] ?: '';
$step = intval($_REQUEST['wizard_maker_step']);
$wizardid = intval($_REQUEST['wizardid']);
$prevstep = intval($_REQUEST['wizard_maker_prev_step']);
$savedata = intval($_REQUEST['wizard_maker_savedata']);

$WU = new WizardUtils();

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

$totalSteps = 4;

if (($mode == 'create' || $mode == 'edit') && $step > 0) {

	$WMSteps = new WizardMakerSteps($WU);
	
	// if creating a new wizard, clear all the variables
	if ($mode == 'create' && $step == 1 && empty($prevstep)) {
		$WMSteps->clearAllStepsVars();
	}

	$error = false;
	if ($prevstep > 0) {
	
		// preprocess variables before saving them
		$WMSteps->preprocessStepVars($mode, $step, $prevstep, $_REQUEST);

		// save the variables of the previous step
		$WMSteps->saveStepVars($prevstep, $_REQUEST);
		
		// check validity only if going forward
		if ($prevstep < $step) {
			$error = $WMSteps->validateStepVars($prevstep);
		}
	}
	
	if (!$savedata && $step > $totalSteps) $error = 'Beyond last step';
	
	// error in last step, return to previous step, and show a warning
	if (!empty($error)) {
		--$step;
		$smarty->assign("STEP_ERROR", $error);
	} elseif ($savedata) {
		$id = 0;
		// save the wizard definition in the database
		$data = $WMSteps->getAllVarsForSave();

		if ($data) {
			if ($mode == 'create') {
				$id = $WU->insertWizard($data);
			} else {
				$id = $WU->updateWizard($wizardid, $data);
			}
		}
		if (!$id) {
			--$step;
			$smarty->assign("STEP_ERROR", $error);
			$error = "No saved data";
		} else {
			
		
			// clean up session
			$WMSteps->clearAllStepsVars();
			$step = '';
			$mode = '';
			
			// load the list
			$list = $WU->getWizards();
			$smarty->assign("WIZLIST", $list);
		}
	}
	
	// if entering the edit mode, load the values
	if ($step == 1 && $mode == 'edit' &&  $prevstep < $step) {
		$dbdata = $WU->getWizardInfo($wizardid);
		$WMSteps->saveAllVarsFromDb($dbdata);
	}
	
	if ($step > 0) {
		// retrieve the variables
		$stepVars = $WMSteps->getStepVars($step);
		// add or modify values based on mode and current step
		$WMSteps->processStepVars($mode, $step, $prevstep, $stepVars);
		$smarty->assign("STEPVARS", $stepVars);
	}
	//preprint($stepVars);
	// load some specific variables for each step
	if ($step == 1) {
		
		$parentModules = $WU->getAllParentModules();
		$smarty->assign("PARENTMODULES", $parentModules);
	} elseif ($step == 2) {
		$step1Vars = $WMSteps->getStepVars(1);
		$parentModule = $step1Vars['wmaker_parentmodule'];
		
		$mainModules = $WU->getAllMainModules($parentModule);
		$smarty->assign("MAINMODULES", $mainModules);
		
	} elseif ($step == 3) {
		$rows = ceil(count($stepVars['wmaker_fields'])/2);
		// labels for mandatory and visible
		$visibleLabel = substr(strtoupper(getTranslatedString('LBL_VISIBLE')), 0, $rows);
		$mandLabel = substr(strtoupper(getTranslatedString('LBL_MANDATORY_FIELD')), 0, $rows);
		
		$visibleLabel = '<p>'.implode('</p><p>', str_split($visibleLabel)).'</p>';
		$mandLabel = '<p>'.implode('</p><p>', str_split($mandLabel)).'</p>';
		
		$smarty->assign("LBL_VISIBLE", $visibleLabel);
		$smarty->assign("LBL_MANDATORY", $mandLabel);

	} elseif ($step == 4) {
		
	}
	
} elseif ($mode == 'delete') {
	
	$error = false;
	
	$info = $WU->getWizardInfo($wizardid);

	if (empty($info)) {
		$error = getTranslatedString('LBL_NO_RECORD');
	} else {
		$r = $WU->deleteWizard($wizardid);
	}
	
	if (!empty($error)) {
		$smarty->assign("LIST_ERROR", $error);
	}
	
	// and display the list
	$list = $WU->getWizards();
	$smarty->assign("WIZLIST", $list);

} else {
	
	// otherwise just display the list
	$list = $WU->getWizards();
	$smarty->assign("WIZLIST", $list);
}

$smarty->assign("STEPS", $totalSteps);
$smarty->assign("STEP", $step);
$smarty->assign("WIZARDID", $wizardid);
$smarty->assign("MODE", $mode);

$smarty->display('Settings/WizardMaker/WizardMaker.tpl');
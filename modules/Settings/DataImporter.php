<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */

global $adb, $table_prefix;
global $mod_strings,$app_strings, $theme;
global $current_user, $currentModule, $current_language, $default_language;

require_once('modules/Settings/DataImporter/DataImporterUtils.php');
require_once('modules/Settings/DataImporter/DataImporterSteps.php');


if ($_REQUEST['ajax'] == 1) {
	require('modules/Settings/DataImporter/DataImporterAjax.php');
	return;
}

$mode = $_REQUEST['mode'] ?: '';
$step = intval($_REQUEST['data_importer_step']);
$importid = intval($_REQUEST['importid']);
$prevstep = intval($_REQUEST['data_importer_prev_step']);
$savedata = intval($_REQUEST['data_importer_savedata']);

$DIUtils = new DataImporterUtils();
$totalSteps = 7;


$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

if (($mode == 'create' || $mode == 'edit') && $step > 0) {

	$DISteps = new DataImporterSteps($DIUtils);
	
	// if creating a new importer, clear all the variables
	if ($mode == 'create' && $step == 1 && empty($prevstep)) {
		$DISteps->clearAllStepsVars();
	}
	
	$error = false;
	if ($prevstep > 0) {
	
		// preprocess variables before saving them
		$DISteps->preprocessStepVars($mode, $step, $prevstep, $_REQUEST);

		// save the variables of the previous step
		$DISteps->saveStepVars($prevstep, $_REQUEST);
		
		// check validity only if going forward
		if ($prevstep < $step) {
			$error = $DISteps->validateStepVars($prevstep);
		}
	}
	
	if (!$savedata && $step > $totalSteps) $error = 'Beyond last step';
	
	// error in last step, return to previous step, and show a warning
	if (!empty($error)) {
		--$step;
		$smarty->assign("STEP_ERROR", $error);
	} elseif ($savedata) {
		$id = 0;
		// save the importer definition in the database
		$data = $DISteps->getAllVarsForSave();

		if ($data) {
			if ($mode == 'create') {
				$id = $DIUtils->insertImporter($data);
			} else {
				$id = $DIUtils->updateImporter($importid, $data);
			}
		}
		if (!$id) {
			--$step;
			$smarty->assign("STEP_ERROR", $error);
			$error = "No saved data";
		} else {
			
			// clean up session
			$DISteps->clearAllStepsVars();
			$step = '';
			$mode = '';
			
			// load the list again, preventing the F5 bug
			header('Location: index.php?module=Settings&action=DataImporter&parenttab=Settings');
			die();
		}
	}
	
	// if entering the edit mode, load the values
	if ($step == 1 && $mode == 'edit' &&  $prevstep < $step) {
		$dbdata = $DIUtils->getImporterInfo($importid);
		$DISteps->saveAllVarsFromDb($dbdata);
	}
	
	// skip the step 4 if csv
	if ($step == 4 && $prevstep == 3) {
		$step2Vars = $DISteps->getStepVars(2);
		if ($step2Vars['dimport_sourcetype'] == 'csv') $step = 5;
	} elseif ($step == 4 && $prevstep == 5) {
		$step2Vars = $DISteps->getStepVars(2);
		if ($step2Vars['dimport_sourcetype'] == 'csv') $step = 3;
	}

	if ($step > 0) {
		// retrieve the variables
		$stepVars = $DISteps->getStepVars($step);
		// add or modify values based on mode and current step
		$error = $DISteps->processStepVars($mode, $step, $prevstep, $stepVars);
		if ($error) {
			$step = $prevstep;
			$stepVars = $DISteps->getStepVars($step);
			$smarty->assign("STEP_ERROR", $error);
		}
		$smarty->assign("STEPVARS", $stepVars);
	}
	
	// load some specific variables for each step
	if ($step == 1) {
		$mlist = $DIUtils->getImporterModules();
		$smarty->assign("DIMPORT_MODULES", $mlist);
		
		$mlist = $DIUtils->getImporterInventoryModules();
		$smarty->assign("DIMPORT_INVMODULES", $mlist);
	
	} elseif ($step == 3) {
		$step2Vars = $DISteps->getStepVars(2);
		$smarty->assign("SOURCETYPE", $step2Vars['dimport_sourcetype']);
		$smarty->assign("DBTYPES", $DIUtils->getSupportedDbType());
		$smarty->assign("CSVENCODINGS", $DIUtils->getCSVEncodings());
		$smarty->assign("CSVDELIMITERS", $DIUtils->getCSVDelimiters());
	
	} elseif ($step == 4) {
		$step1Vars = $DISteps->getStepVars(1);
		$step3Vars = $DISteps->getStepVars(3);

		$smarty->assign("DESTMODULE", $step1Vars['dimport_module']);
		$smarty->assign("DBTYPE", $step3Vars['dimport_dbtype']);		
		
		// retrieve the tables
		$smarty->assign("DBTABLES", $DISteps->retrieveAllTables());
		$smarty->assign("CAN_EDIT_QUERY", $DIUtils->canEditQuery());
	
	} elseif ($step == 5) {
		$importRow = $DISteps->getAllVarsForSave();
		$destModule = $importRow['module'];
		$smarty->assign("DESTMODULE", $destModule);
		$smarty->assign("SOURCETYPE", $importRow['srcinfo']['dimport_sourcetype']);
		$smarty->assign("HAS_HEADER", ($importRow['srcinfo']['dimport_sourcetype'] == 'database' || $importRow['srcinfo']['dimport_csvhasheader']));
		
		// all the fields
		$allFields = $DIUtils->getMappableFields($importRow);
		$smarty->assign("ALLFIELDS", $allFields);
		$smarty->assign("FIELDSPROPS", $DIUtils->extractFieldsProperties($allFields));

		// all the imports key fields, used to map uitype10 fields
		$smarty->assign("OTHERKEYS", $DIUtils->getOtherImportKeys($importRow));
		
		// the source formatters/validators
		$smarty->assign("ALLFORMATS", $DIUtils->getAvailableFormats());
			
		// the source formatters/validators
		$smarty->assign("ALLFORMULAS", $DIUtils->getAvailableFormulas());
			
		// users and group lists
		$smarty->assign('USERS_LIST', $DIUtils->getAvailableUsers($destModule));
		$smarty->assign('GROUPS_LIST', $DIUtils->getAvailableGroups($destModule));
	
	} elseif ($step == 6) {
		$smarty->assign("SCHED_VARS", $DIUtils->getSchedulingVars());
	
	} elseif ($step == 7) {
		$importRow = $DISteps->getAllVarsForSave();
		$destModule = $importRow['module'];
	
		$smarty->assign('USERS_LIST', $DIUtils->getAvailableUsers($destModule));
	}
	
} else {

	// otherwise just display the list
	
	$localInfile = $DIUtils->checkMysqlLocalInfile();
	if (!$localInfile) {
		$smarty->assign("LIST_ERROR", getTranslatedString('LBL_IMPORT_NO_LOCAL_INFILE'));
	}
	
	
	$smarty->assign("CAN_RUN_MANUALLY", $DIUtils->canRunManually());
	
	$list = $DIUtils->getList();
	$smarty->assign("IMPORTLIST", $list);
}


$smarty->assign("STEPS", $totalSteps);
$smarty->assign("STEP", $step);
$smarty->assign("IMPORTID", $importid);
$smarty->assign("MODE", $mode);

$smarty->display('Settings/DataImporter/DataImporter.tpl');
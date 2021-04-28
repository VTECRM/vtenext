<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@64542 */

require_once('vtlib/Vtecrm/Utils.php');
require_once('modules/Settings/DataImporter/DataImporterUtils.php');
require_once('modules/Settings/DataImporter/DataImporterSteps.php');


global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

$mode = 'ajax';
$importid = intval($_REQUEST['importid']);
$action = $_REQUEST['subaction'];
$raw = null;
$tpl = '';
$json = null;

$DIUtils = new DataImporterUtils();
$DISteps = new DataImporterSteps($DIUtils);
$DIAjax = new DataImporterAjax($DIUtils, $DISteps);


if ($action == 'enable_import') {
	
	$error = $DIAjax->enableImport($importid);
	$json = array('success' => empty($error), 'error' => $error);
	
} elseif ($action == 'disable_import') {
	
	$error = $DIAjax->disableImport($importid);
	$json = array('success' => empty($error), 'error' => $error);
	
} elseif ($action == 'delete_import') {

	$error = $DIAjax->deleteImport($importid);
	$json = array('success' => empty($error), 'error' => $error);

} elseif ($action == 'add_default_field' || $action == 'del_default_field') {
	$step = 5;

	$vars = $_REQUEST;
	$DISteps->preprocessStepVars($mode, $step, 0, $vars);
	$vars = $DISteps->extractStepVars($vars);
	
	if ($action == 'add_default_field') {
		$DIAjax->addDefaultField($_REQUEST['type'], $vars);
	} elseif ($action == 'del_default_field') {
		$DIAjax->delDefaultField($_REQUEST['type'], $vars, $_REQUEST['fieldno']);
	}
	
	// then prepare the vars for the output
	$DISteps->processStepVars($mode, $step, 0, $vars);
	

	$smarty->assign("STEPVARS", $vars);
	
	$importRow = $DISteps->getAllVarsForSave();
	
	$allFields = $DIUtils->getMappableFields($importRow);
	$smarty->assign("ALLFIELDS", $allFields);
	
	// users and group lists
	$smarty->assign('USERS_LIST', $DIUtils->getAvailableUsers($destModule));
	$smarty->assign('GROUPS_LIST', $DIUtils->getAvailableGroups($destModule));
	
	$tpl = 'Step5DefFields.tpl';
	//$json = array('success' => empty($error), 'error' => $error);

} elseif ($action == 'getlog') {

	$log = $DIAjax->getLastImportLog($importid);
	$error = null;
	$json = array('success' => empty($error), 'error' => $error, 'log' => $log);

} elseif ($action == 'run') {

	$error = $DIAjax->runNow($importid);
	$json = array('success' => empty($error), 'error' => $error);

} elseif ($action == 'abort') {

	$error = $DIAjax->abortImport($importid);
	$json = array('success' => empty($error), 'error' => $error);
}



// output
if (!is_null($raw)) {
	echo $raw;
	exit(); // sorry, I have to do this, some html shit is spitted out at the end of the page
} elseif (!empty($tpl)) {
	$smarty->display('Settings/DataImporter/'.$tpl);
} elseif (!empty($json)) {
	echo Zend_Json::encode($json);
	exit(); // idem
} else {
	echo "No data returned";
}


// ---------------------- CLASSES ---------------------


// assume the blocks and fields are ordered sequentially by arraykey
class DataImporterAjax {

	protected $disteps = null;
	protected $diutils = null;

	public function __construct($diutils = null, $disteps = null) {
		$this->diutils = $diutils;
		$this->disteps = $disteps;
	}

	public function enableImport($importid) {
		$info = $this->diutils->getImporterInfo($importid);
		if (empty($info)) return "Importer not found";
		
		if ($info['enabled'] == '0') {
			$this->diutils->setEnabled($importid);
		}
		return null;
	}
	
	public function disableImport($importid) {
		$info = $this->diutils->getImporterInfo($importid);
		if (empty($info)) return "Importer not found";
		
		if ($info['running'] == '1') return getTranslatedString('LBL_CANT_DISABLE_RUNNING_IMPORT');
		
		if ($info['enabled'] == '1') {
			$this->diutils->setEnabled($importid, 0);
		}
		return null;
	}
	
	public function deleteImport($importid) {
		$info = $this->diutils->getImporterInfo($importid);
		if (empty($info)) return "Importer not found";
		
		if ($info['running'] == '1') return getTranslatedString('LBL_CANT_DELETE_RUNNING_IMPORT');
		
		$this->diutils->deleteImporter($importid, 0);
		return null;
	}
	
	public function addDefaultField($type, &$vars) {
		if (!is_array($vars['dimport_deffields'])) {
			$vars['dimport_deffields'] = array('create' => array(), 'update' => array());
		}
		if ($type == 'create' || $type == 'update') {
			$vars['dimport_deffields'][$type][] = array(
				'field' => '',
				'default' => '',
			);
		}
	}
	
	public function delDefaultField($type, &$vars, $fieldno) {
		if ($type == 'create' || $type == 'update') {
			unset($vars['dimport_deffields'][$type][$fieldno]);
			$vars['dimport_deffields'][$type] = array_values($vars['dimport_deffields'][$type]);
		}
	}
	
	public function getLastImportLog($importid) {
		$path = $this->diutils->getLastLogPath($importid);
		if (file_exists($path)) {
			return file_get_contents($path);
		} else {
			return 'No data';
		}
	}
	
	public function runNow($importid) {
		if (!$this->diutils->canRunManually()) return "Not permitted";
		$info = $this->diutils->getImporterInfo($importid);
		if (empty($info)) return "Importer not found";
		if ($info['running'] == '1') return getTranslatedString('LBL_IMPORT_IS_ALREADY_RUNNING');
		
		$ok = $this->diutils->run($importid, true);
		if (!$ok) return "Error";
		return null;
	}
	
	public function abortImport($importid) {
		$info = $this->diutils->getImporterInfo($importid);
		if (empty($info)) return "Importer not found";
		
		$ok = $this->diutils->abort($importid);
		if (!$ok) return "Error";
		return null;
	}
	
}
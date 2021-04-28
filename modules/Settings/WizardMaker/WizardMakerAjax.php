<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@96233 */

require_once('modules/Settings/WizardMaker/WizardMakerSteps.php');

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

$mode = 'ajax';
$wizardid = intval($_REQUEST['wizardid']);
$action = $_REQUEST['subaction'];
$raw = null;
$tpl = '';
$json = null;

$WU = new WizardUtils();
$WMSteps = new WizardMakerSteps($WU);


if ($action == 'enable_wizard') {
	
	$ok = $WU->updateWizard($wizardid, array('enabled' => 1));
	$json = array('success' => $ok, 'error' => 'Error while updating the wizard');
	
} elseif ($action == 'disable_wizard') {
	
	$ok = $WU->updateWizard($wizardid, array('enabled' => 0));
	$json = array('success' => $ok, 'error' => 'Error while updating the wizard');
	
} elseif ($action == 'delete_wizard') {

	$ok = $WU->deleteWizard($wizardid);
	$json = array('success' => $ok, 'error' => 'Error while updating the wizard');
}


// output
if (!is_null($raw)) {
	echo $raw;
	exit(); // sorry, I have to do this, some html shit is spitted out at the end of the page
} elseif (!empty($tpl)) {
	$smarty->display('Settings/WizardMaker/'.$tpl);
} elseif (!empty($json)) {
	echo Zend_Json::encode($json);
	exit(); // idem
} else {
	echo "No data returned";
}

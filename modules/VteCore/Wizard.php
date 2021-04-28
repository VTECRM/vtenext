<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@OPER6317 crmv@96233 crmv@98866 */

// webservices
require_once 'include/Webservices/Utils.php';
require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once('include/Webservices/ModuleTypes.php');
require_once("include/Webservices/Retrieve.php");
require_once("include/Webservices/DescribeObject.php");

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', return_module_language($current_language, 'Newsletter'));
$smarty->assign('MODULE', $currentModule);
$smarty->assign('THEME', $theme);
$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);

$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
$smarty->assign('JS_GLOBAL_VARS',Zend_Json::encode($JSGlobals));

$wizardid = intval($_REQUEST['wizardid']);
$parentModule = vtlib_purify($_REQUEST['parentModule']);
$parentId = intval($_REQUEST['parentId']);

$extraParams = vtlib_purify($_REQUEST['params']);

$WU = WizardUtils::getInstance();
$WG = WizardGenerator::getInstance();
$wizardInfo = $WU->getWizardInfo($wizardid);

$pageTitle = getTranslatedString('Wizard');

$wizardFile = $wizardInfo['src'];
$wizardTpl = $wizardInfo['template'];
$wizardCfg = $wizardInfo['config'];

if ($wizardCfg) {
	$params = array();
	if ($parentModule && $parentId) {
		$params['parentModule'] = $parentModule;
		$params['parentId'] = $parentId;
		$smarty->assign('PARENT_MODULE', $parentModule);
		$smarty->assign('PARENT_ID', $parentId);
	}
	$wizardSteps = $WG->generateWizardSteps($wizardid, $wizardCfg, $params);
	$smarty->assign('WIZARD', $wizardSteps);
}

if ($wizardFile && is_readable($wizardFile)) {
	require($wizardFile);
}

// crmv@160359
require_once('vtlib/Vtecrm/Link.php');//crmv@207871
$hdrcustomlink_params = Array('MODULE'=>$currentModule);
$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERSCRIPT'), $hdrcustomlink_params);
$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
// crmv@160359e

$smarty->assign('HEADER_Z_INDEX', 10);

$smarty->assign('WIZARD_ID', $wizardid);
$smarty->assign('BROWSER_TITLE', $pageTitle);
$smarty->assign('PAGE_TITLE', $pageTitle);

if ($wizardTpl) {
	$smarty->display($wizardTpl);
} else {
	$smarty->display('Wizard.tpl');
}
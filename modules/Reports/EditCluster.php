<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@128369 */

require_once('modules/Reports/Reports.php');

global $app_strings, $mod_strings;
global $currentModule, $current_language, $current_user;
global $theme, $image_path;

$mode = '';
$reportid = intval($_REQUEST['reportid']);
$clusteridx = (isset($_REQUEST['clusteridx']) && $_REQUEST['clusteridx'] !== '' ? intval($_REQUEST['clusteridx']) : '' );
$module = vtlib_purify($_REQUEST['primodule']);

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("THEME_PATH", $theme_path);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);

$CU = CRMVUtils::getInstance();
$repObj = Reports::getInstance();

$smarty->assign("REPORTID", $reportid);
$smarty->assign("CLUSTERIDX", $clusteridx);

$smarty->assign("PRIMARYMODULE",$module);
$smarty->assign("PRIMARYMODULE_LABEL",getTranslatedString($module, $module));

$repModules = $repObj->getAvailableModules();
unset($repModules['ProductsBlock']);

$smarty->assign("REPT_MODULES",$repModules);
$smarty->assign("COMPARATORS",$repObj->getAdvFilterOptions());

$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("JS_DATEFORMAT",parse_calendardate(getTranslatedString('NTC_DATE_FORMAT', 'APP_STRINGS')));

// preload some relations and fields
$preloadChain = array($module);
$preload_js = array(
	array(
		'type' => 'modules',
		'chain' => $preloadChain,
		'data' => $repObj->getModulesListForChain($reportid, $preloadChain),
	),
	array(
		'type' => 'fields',
		'fieldstype' => 'advfilter',
		'chain' => $preloadChain,
		'data' => $repObj->getAdvFiltersFieldsListForChain($reportid, $preloadChain),
	),
);
$smarty->assign("PRELOAD_JS", Zend_Json::encode($preload_js));

$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
$smarty->assign('JS_GLOBAL_VARS', Zend_Json::encode($JSGlobals));

$smarty->display("modules/Reports/EditCluster.tpl");

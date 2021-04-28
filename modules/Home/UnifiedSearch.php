<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@187493 */

require_once('include/logging.php');
require_once('modules/CustomView/CustomView.php');
require_once('include/utils/utils.php');
global $app_strings, $mod_strings, $default_charset;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

$query_string = trim($_REQUEST['query_string']);
$curModule = vtlib_purify($_REQUEST['module']);

if (empty($query_string)) {
	$smarty->assign("ERROR", getTranslatedString('ERR_ONE_CHAR','Home'));
} else {
	$search_val = $query_string;
	$search_module = $_REQUEST['search_module'];
	$search_onlyin = getAllModulesForTag();
	$object_array = getSearchModules($search_onlyin, true);
	
	if (empty($object_array)) {
		$smarty->assign("ERROR", getTranslatedString('NoModulesSelected','Home'));
	} else {
		$smarty->assign("MOD", $mod_strings);
		$smarty->assign("APP", $app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH",$image_path);
		$smarty->assign("MODULE",$module);
		$smarty->assign("SEARCH_MODULE",vtlib_purify($_REQUEST['search_module']));
		$smarty->assign("MODULES_LIST", $object_array);
		$smarty->assign("MODULES_LIST_JS", Zend_Json::encode(array_keys($object_array)));
		$smarty->assign("QUERY_STRING",$search_val);
		$smarty->assign("SEARCH_STRING",htmlentities($search_val, ENT_QUOTES, $default_charset));
	}
}
$smarty->display('UnifiedSearchDisplay.tpl');
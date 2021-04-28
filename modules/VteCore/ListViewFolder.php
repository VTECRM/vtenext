<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@30967 - listview a cartelle */

global $adb, $table_prefix;
global $app_strings, $mod_strings, $current_language, $currentModule, $theme;

require_once('include/ListView/ListView.php');

$smarty = new VteSmarty();

$tool_buttons = Button_Check($currentModule);
unset($tool_buttons['moduleSettings']); // crmv@140887

$list_buttons = Array();

// if (isPermitted($currentModule,'Delete','') == 'yes') $list_buttons['del'] = $app_strings['LBL_MASS_DELETE'];


$folderlist = array();
$focus = CRMEntity::getInstance($currentModule);

// get list of folders
if (method_exists($focus, 'getFolderList')) {
	$folderlist = $focus->getFolderList();
} else {
	$folderlist = getEntityFoldersByName(null, $currentModule);
}

// get elements info for each folder
if (method_exists($focus, 'getFolderContent')) {
	foreach ($folderlist as $key=>$fcont) {
		$foldercontent = $focus->getFolderContent($fcont['folderid']);
		$folderlist[$key]['content'] = $foldercontent;
	}
}

$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329

$viewid = $customView->getViewId($currentModule);

$queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
if ($viewid != "0") {
	$queryGenerator->initForCustomViewById($viewid);
} else {
	$queryGenerator->initForDefaultCustomView();
}


$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign("VIEWID", $viewid);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', getTranslatedString('SINGLE_'.$currentModule));
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('BUTTONS', $list_buttons);
$smarty->assign('CHECK', $tool_buttons);
$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("OWNED_BY",getTabOwnedBy($currentModule));
//$smarty->assign("CRITERIA", $criteria);
//$smarty->assign("FIELDNAMES", $fieldnames);
//$smarty->assign("ALPHABETICAL", $alphabetical);
//$smarty->assign("SEARCHLISTHEADER", $listview_header_search);
$smarty->assign("HIDE_BUTTON_SEARCH", ($currentModule == 'Reports'));	//crmv@107103

$smarty->assign('FOLDERS_PER_ROW', 6);
$smarty->assign('FOLDERLIST', $folderlist);

// specific modules
if ($currentModule == 'Reports' || $currentModule == 'Charts') {
	$smarty->assign('HIDE_BUTTON_CREATE', true); // crmv@97862
}

$smarty_template = 'ListViewFolder.tpl';

$sdk_custom_file = 'ListViewFolderCustomisations';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
@include("modules/$currentModule/$sdk_custom_file.php");

$smarty->display($smarty_template);
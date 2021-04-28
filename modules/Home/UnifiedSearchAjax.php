<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@16312 crmv@23687 crmv@187493 */

require_once('include/utils/utils.php');
global $adb, $table_prefix, $app_strings, $mod_strings, $current_language, $default_charset, $current_user;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$search_val_html = trim($_REQUEST['query_string']);
$search_val = html_entity_decode($search_val_html, ENT_QUOTES, $default_charset);
$module = vtlib_purify($_REQUEST['smodule']);
$display = ($_REQUEST['display'] == 'true');

$LVU = ListViewUtils::getInstance();
$smarty = new VteSmarty();
$focus = CRMEntity::getInstance($module);

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("MODULE",$module);
$smarty->assign("SINGLE_MOD",$module);
$smarty->assign("DISPLAY",($display)?'block':'none');
$smarty->assign("SEARCH_STRING",$search_val_html);

$cv_res = $adb->pquery("select cvid from ".$table_prefix."_customview where viewname='All' and entitytype=?", array($module));
$viewid = $adb->query_result($cv_res,0,'cvid');
$queryGenerator = QueryGenerator::getInstance($module, $current_user);
if ($viewid != "0") {
	$queryGenerator->initForCustomViewById($viewid);
} else {
	$queryGenerator->initForDefaultCustomView();
}
$searchConditions = getUnifiedWhereConditions($module,$search_val);
$queryGenerator->addUserSearchConditions($searchConditions);
$listquery = $queryGenerator->getQuery();
if($where != ''){
	$listquery .= ' and ('.$where.')';
}
if(!(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '')) {
	$count_result = $adb->querySlave('UnifiedSearch',$listquery); // crmv@185894
	$noofrows = $adb->num_rows($count_result);
} else {
	$noofrows = vtlib_purify($_REQUEST['recordCount']);
}
$noofrows = intval($noofrows); // crmv@107453
$moduleRecordCount[$module]['count'] = $noofrows;

global $list_max_entries_per_page;
if(!empty($_REQUEST['start'])){
	$start = $_REQUEST['start'];
	if($start == 'last'){
		$count_result = $adb->querySlave('UnifiedSearch',mkCountQuery($listquery)); // crmv@185894
		$noofrows = $adb->query_result($count_result,0,"count");
		if($noofrows > 0){
			$start = ceil($noofrows/$list_max_entries_per_page);
		}
	}
	if(!is_numeric($start)){
		$start = 1;
	} elseif($start < 0){
		$start = 1;
	}
	$start = ceil($start);
}else{
	$start = 1;
}

$list_max_entries_per_page = $LVU->get_selection_options($module, $noofrows, 'list');
$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
$navigation_array = VT_getSimpleNavigationValues($start,$list_max_entries_per_page,$noofrows);
$limitStartRecord = ($start-1) * $list_max_entries_per_page;

$list_result = $adb->limitQuerySlave('UnifiedSearch',$listquery,$limitStartRecord,$list_max_entries_per_page); // crmv@185894

$moduleRecordCount[$module]['recordListRangeMessage'] = getRecordRangeMessage($list_max_entries_per_page, $limitStartRecord);

$info_message='&recordcount='.$_REQUEST['recordcount'].'&noofrows='.$_REQUEST['noofrows'].'&message='.$_REQUEST['message'].'&skipped_record_count='.$_REQUEST['skipped_record_count'];
$url_string = '&modulename='.$_REQUEST['modulename'].'&nav_module='.$module.$info_message;
$viewid = '';

$navigationOutput = $LVU->getTableHeaderSimpleNavigation($navigation_array, $url_string,$module,"UnifiedSearch",$viewid);
//crmv@42931
$controller = ListViewController::getInstance($adb, $current_user, null);
$controller->setHeaderSorting(false);
$controller->setQueryGenerator($queryGenerator);
//crmv@42931e
$listview_header = $controller->getListViewHeader($focus,$module,$url_string);
$listview_entries = $controller->getListViewEntries($focus,$module,$list_result,$navigation_array);

//Do not display the Header if there are no entires in listview_entries
if(count($listview_entries) > 0){
	$display_header = 1;
}else{
	// crmv@107453
	$display_header = 0;
	$moduleRecordCount[$module]['count'] = 0;
	$moduleRecordCount[$module]['recordListRangeMessage'] = '';
	// crmv@107453e
}
$smarty->assign("NAVIGATION", $navigationOutput);
$smarty->assign("LISTHEADER", $listview_header);
$smarty->assign("LISTENTITY", $listview_entries);
$smarty->assign("DISPLAYHEADER", $display_header);
$smarty->assign("HEADERCOUNT", count($listview_header));
$smarty->assign("ModuleRecordCount", $moduleRecordCount);

$total_record_count = $total_record_count + $noofrows;

$smarty->assign("SEARCH_CRITERIA"," ($noofrows)"); // crmv@107453
$smarty->assign("CUSTOMVIEW_OPTION",$customviewcombo_html);

$smarty->display("UnifiedSearchAjax.tpl");

unsetLVS($module);
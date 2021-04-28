<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;
global $app_strings, $mod_strings, $current_language, $currentModule, $theme, $list_max_entries_per_page;

//crmv@208173

//crmv@102334 OLD MODE : redirect to a tab list in the home page view
if (empty($_REQUEST['fetch_only']) && empty($_REQUEST['ajax']) && empty($homepage)) {
	require_once('include/utils/ModuleHomeView.php');
	$MHW = ModuleHomeView::getInstance($currentModule, $current_user->id);
	$MHW->setModHomeListId();
	include('modules/VteCore/index.php');
	return;
}
//crmv@102334

if ($_REQUEST['calc_nav'] == 'true'){
	//Retreive the List View Table Header
	$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329
	$viewid = $customView->getViewId($currentModule);
	if($viewid !='')
	$url_string .= "&viewname=".$viewid;
	if ($_REQUEST['get_all_ids'] == 'true'){
		echo '&#&#&#';
		echo get_allids(VteSession::get($currentModule.'_listquery'),$_REQUEST['ids_to_jump']);
	}
	else{
		echo '&#&#&#';
		echo get_navigation_values(VteSession::get($currentModule.'_listquery'),$url_string,$currentModule,$_REQUEST['folderid'],false,$viewid);	//crmv@83034
	}
	die();
}
$LVU = ListViewUtils::getInstance();
$category = getParentTab();
$tool_buttons = Button_Check($currentModule);
$list_buttons = Array();
if (empty($homepage)) $smarty = new VteSmarty();	//crmv@102334

// crmv@205449
if (isInventoryModule($currentModule)) {
	$tool_buttons['Import'] = 'no';
	$tool_buttons['DuplicatesHandling'] = 'no';
	$tool_buttons['Merge'] = 'no';
}
// crmv@205449e

//crmv@141557 crmv@146666
if (!isset($modhomeid)) {
	require_once('include/utils/ModuleHomeView.php');
	$MHW = ModuleHomeView::getInstance($currentModule, $current_user->id);
	$modhomeid = $MHW->getModHomeId();
}
$smarty->assign("MODHOMEID",$modhomeid);

// crmv@173746
$smarty->assign("EDITMODE", $_REQUEST['editmode']); // crmv@105193
$smarty->assign("CAN_ADD_HOME_BLOCKS", $MHW->getPermissions('CAN_ADD_HOME_BLOCKS'));
$smarty->assign("CAN_EDIT_COUNTS", $MHW->getPermissions('CAN_EDIT_COUNTS'));
$smarty->assign("CAN_ADD_HOME_VIEWS", $MHW->getPermissions('CAN_ADD_HOME_VIEWS'));
$smarty->assign("CAN_DELETE_HOME_VIEWS", $MHW->getPermissions('CAN_DELETE_HOME_VIEWS'));
$smarty->assign("HOME_BLOCK_TYPES", $MHW->getSupportedBlocks());
// crmv@173746e

//crmv@141557e crmv@146666e

// crmv@30967
if (in_array($currentModule,array('Documents','Charts','Reports','Myfiles'))){ //crmv@3079m
	$list_buttons['back'] = $app_strings['LBL_GO_BACK'];
}
// crmv@30967e

if(isPermitted($currentModule,'Delete','') == 'yes')
	$list_buttons['del'] = $app_strings['LBL_MASS_DELETE'];
if($currentModule !='Sms'){
	if(isPermitted($currentModule,'EditView','') == 'yes')
		$list_buttons['mass_edit'] = $app_strings['LBL_MASS_EDIT'];
}
//custom code start
if(in_array($currentModule,array('Leads','Accounts','Contacts','Vendors'))){ // crmv@43765
	if(isPermitted('Emails','EditView','') == 'yes')
		$list_buttons['s_mail'] = $app_strings['LBL_SEND_MAIL_BUTTON'];
	if(isPermitted('Fax','EditView','') == 'yes')
		$list_buttons['s_fax'] = $app_strings['LBL_SEND_FAX_BUTTON'];
	if(!in_array($currentModule, array('Accounts','Vendors')) && isPermitted("Sms","EditView",'') == 'yes' && vtlib_isModuleActive('Sms'))	//crmv@16703 crmv@43765
		$list_buttons['s_sms'] = $app_strings['LBL_SEND_SMS_BUTTON'];
}
//custom code end
//crmv@18592
$view_script = "<script language='javascript'>
    function set_selected()
    {
    	obj=getObj('viewname');
        len=obj.length;
        for(i=0;i<len;i++)
        {
            if(obj[i].value == '$viewid')
                obj[i].selected = true;
        }
    }
    set_selected();
    </script>";
//crmv@18592e
$focus = CRMEntity::getInstance($currentModule);
$focus->initSortbyField($currentModule);
// Custom View
$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329
$viewid = $customView->getViewId($currentModule);
$customview_html = $customView->getCustomViewCombo($viewid);
$viewinfo = $customView->getCustomViewByCvid($viewid);

// Feature available from 5.1
if(method_exists($customView, 'isPermittedChangeStatus')) {
	// Approving or Denying status-public by the admin in CustomView
	$statusdetails = $customView->isPermittedChangeStatus($viewinfo['status']);

	// To check if a user is able to edit/delete a CustomView
	$edit_permit = $customView->isPermittedCustomView($viewid,'EditView',$currentModule);
	$delete_permit = $customView->isPermittedCustomView($viewid,'Delete',$currentModule);

	$smarty->assign("CUSTOMVIEW_PERMISSION",$statusdetails);
	$smarty->assign("CV_EDIT_PERMIT",$edit_permit);
	$smarty->assign("CV_DELETE_PERMIT",$delete_permit);
}
// END
global $current_user;
$queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
if ($viewid != "0") {
	$queryGenerator->initForCustomViewById($viewid);
} else {
	$queryGenerator->initForDefaultCustomView();
}

$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);

//crmv@42752
$skipActions = false;
$append_url = '';
if ($_REQUEST['hide_list_actions'] == '1') {
	$skipActions = true;
	$url_string .= '&hide_list_actions=1';
	$append_url .= '&hide_list_actions=1';
}
if ($_REQUEST['hide_cv_follow'] == '1') {
	$smarty->assign('HIDE_CV_FOLLOW', '1');
	$url_string .= '&hide_cv_follow=1';
	$append_url .= '&hide_cv_follow=1';
}
if ($_REQUEST['hide_custom_links'] == '1') {
	$smarty->assign('HIDE_CUSTOM_LINKS', '1');
	$url_string .= '&hide_custom_links=1';
	$append_url .= '&hide_custom_links=1';
}
if ($_REQUEST['hide_list_checkbox'] == '1') {
	$smarty->assign('HIDE_LIST_CHECKBOX', '1');
	$url_string .= '&hide_list_checkbox=1';
	$append_url .= '&hide_list_checkbox=1';
}
if ($_REQUEST['hide_list_links'] == '1') {
	$url_string .= '&hide_list_links=1';
	$append_url .= '&hide_list_links=1';
}
if ($_REQUEST['hide_switchview_buttons'] == '1') {
	$url_string .= '&hide_switchview_buttons=1';
	$append_url .= '&hide_switchview_buttons=1';
}

// Enabling Module Search
if($_REQUEST['query'] == 'true') {
	//crmv@55317
	$SearchUtils = SearchUtils::getInstance();
	$listview_header_search = $controller->getBasicSearchFieldInfoList();
	$listview_header_search_new = $SearchUtils->getUnifiedSearchFieldInfoList($currentModule);
	$listview_header_search = array_merge($listview_header_search, $listview_header_search_new);
	//crmv@55317e
	$_REQUEST['search_fields'] = $listview_header_search;
	$queryGenerator->addUserSearchConditions($_REQUEST);
	$ustring = getSearchURL($_REQUEST);
	$url_string .= "&query=true$ustring";
	//crmv@159559
	$advanced_search_params = $SearchUtils->getAdvancedSearchParams($_REQUEST);
	$smarty->assign('ADVANCED_SEARCH_PARAMS', Zend_Json::encode($advanced_search_params));
	//crmv@159559e
}
$smarty->assign('SEARCH_URL', $url_string);
$smarty->assign('APPEND_URL', $append_url);
//crmv@42752e

//<<<<<<< sort ordering >>>>>>>>>>>>>
list($focus->customview_order_by,$focus->customview_sort_order) = $customView->getOrderByFilterSQL($viewid);
$sorder = $focus->getSortOrder();
$order_by = $focus->getOrderBy();
if(!getLVS($currentModule))
{
	unsetLVS();
	$modObj = new ListViewSession();
	$modObj->sorder = $sorder;
	$modObj->sortby = $order_by;
	setLVS($currentModule,get_object_vars($modObj));
}
VteSession::set($currentModule.'_ORDER_BY', $order_by);
VteSession::set($currentModule.'_SORT_ORDER', $sorder);
//<<<<<<< sort ordering >>>>>>>>>>>>>
$list_query = $queryGenerator->getQuery();
$where = $queryGenerator->getConditionalWhere();
//crmv@7634
if(isset($_REQUEST['lv_user_id'])) {
	VteSession::set('lv_user_id_'.$currentModule, $_REQUEST['lv_user_id']); // crmv@107328
} else {
	$_REQUEST['lv_user_id'] = VteSession::get('lv_user_id_'.$currentModule); // crmv@107328
}
$smarty->assign("LV_USER_PICKLIST",getUserOptionsHTML($_REQUEST['lv_user_id'],$currentModule,'','',$modhomeid));	//crmv@141557
if( $_REQUEST['lv_user_id'] == "all" || $_REQUEST['lv_user_id'] == "") { // all event (normal rule)
	
} else if ( $_REQUEST['lv_user_id'] == "mine") { // only assigned to me
	$list_where .= " and {$focus->getEntityTableInfo('table')}.smownerid = ".$current_user->id." "; // crmv@185647
} else if ( $_REQUEST['lv_user_id'] == "others") { // only assigneto others
	$list_where .= " and {$focus->getEntityTableInfo('table')}.smownerid <> ".$current_user->id." "; // crmv@185647
} else { // a selected userid
	$list_where .= " and {$focus->getEntityTableInfo('table')}.smownerid = ".intval($_REQUEST['lv_user_id'])." "; // crmv@185647 crmv@194999
}
$list_query.=$list_where;
$where.=$list_where;
//crmv@7634e
if(isset($where) && $where != '') {
	VteSession::set('export_where', $where);
} else {
	VteSession::remove('export_where');
}
if(!empty($order_by) && $order_by != '' && $order_by != null) {
	$list_query .= $focus->getFixedOrderBy($currentModule,$order_by,$sorder); //crmv@25403 crmv@127820
}
//crmv@11597
$smarty->assign("CUSTOMCOUNTS_OPTION", $LVU->get_selection_options($currentModule, $noofrows));
$list_max_entries_per_page = $LVU->get_selection_options($currentModule, $noofrows, 'list');
if ($_REQUEST['ajax'] == 'true'
	&& $_REQUEST['search']!= 'true'
	&& $_REQUEST['changecount']!= 'true'
	&& $_REQUEST['changecustomview']!= 'true'){
	if ($_REQUEST['noofrows'] != '')
		$noofrows = $_REQUEST['noofrows'];
	else {
		$lvs_noofrows = getLVSDetails($currentModule,$viewid,'noofrows');
		if ($lvs_noofrows != '') $noofrows = $lvs_noofrows;
	}
	if ($noofrows > 0){
		$list_max_entries_per_page = $LVU->get_selection_options($currentModule, $noofrows, 'list');
		$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
		$start = ListViewSession::getRequestCurrentPage($currentModule, $list_query, $viewid, $queryMode);
		// crmv@137476
		$limit_start_rec = ($start-1) * $list_max_entries_per_page;
		if (!PerformancePrefs::getBoolean('LIST_COUNT', true)) {
			$skey = $currentModule.'_'.$viewid.'_list_nrows';
			$list_result = $adb->limitQuery($list_query,$limit_start_rec,$list_max_entries_per_page+1);
			$sqlrows = $adb->num_rows($list_result);
			VteSession::set($skey,$sqlrows);
			if ($sqlrows > $list_max_entries_per_page) {
				// there is a next page
				$noofrows = $list_max_entries_per_page*($start+1);
				$list_result->_numOfRows = $list_max_entries_per_page;
			} else {
				// this is the last page
				$noofrows = $list_max_entries_per_page*($start-1) + $sqlrows;
			}
		}
		// crmv@137476e
		$navigation_array = VT_getSimpleNavigationValues($start,$list_max_entries_per_page,$noofrows);
		$limit_start_rec = ($start-1) * $list_max_entries_per_page;
		$navigationOutput = getTableHeaderSimpleNavigation($navigation_array, $url_string,$currentModule,$_REQUEST['folderid'],$viewid); // crmv@110503
		if (PerformancePrefs::getBoolean('LIST_COUNT', true)) {
			$record_string = getRecordRangeMessage($list_max_entries_per_page, $limit_start_rec,$noofrows);
			$smarty->assign("RECORD_COUNTS", $record_string);
		}
		if ($noofrows >  $list_max_entries_per_page)
			$smarty->assign("NAVIGATION", $navigationOutput);
		$smarty->assign("AJAX", 'true');
	}
}
else {
	$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
	$start = ListViewSession::getRequestCurrentPage($currentModule, $list_query, $viewid, $queryMode);
	//crmv@15530
	if ($_REQUEST['ajax'] == 'delete'){
		$res = $adb->query(replaceSelectQuery($list_query,'count(*) as cnt'));
		if ($res){
			$noofrows = $adb->query_result($res,0,'cnt');
			setLVSDetails($currentModule,$viewid,$noofrows,'noofrows');
			$_REQUEST['noofrows'] = $noofrows;
			if ($start > ceil($noofrows/$list_max_entries_per_page)){
				$start-=1;
			}
		}
	}
	$limit_start_rec = ($start-1) * $list_max_entries_per_page;
	setLVSDetails($currentModule,$viewid,$start,'start');
	$navigation_array['current'] = $start;
	$navigation_array['start'] = $start;
	$_REQUEST['start'] = $start;
	//crmv@15530 end
}
// crmv@137476
if (PerformancePrefs::getBoolean('LIST_COUNT', true)) {
	$list_result = $adb->limitQuery($list_query,$limit_start_rec,$list_max_entries_per_page);
} else {
	$skey = $currentModule.'_'.$viewid.'_list_nrows';
	if (!isset($list_result)) $list_result = $adb->limitQuery($list_query,$limit_start_rec,$list_max_entries_per_page+1);
	$sqlrows = $adb->num_rows($list_result);
	VteSession::set($skey,$sqlrows);
	if ($sqlrows > $list_max_entries_per_page) {
		$list_result->_numOfRows = $list_max_entries_per_page;
	}
}
// crmv@137476e
if (isset($_REQUEST["selected_ids"])) {
	$smarty->assign("SELECTED_IDS_ARRAY", explode(";",$_REQUEST["selected_ids"]));
	$smarty->assign("SELECTED_IDS", $_REQUEST["selected_ids"]);
}
if (isset($_REQUEST["all_ids"])) {
	$smarty->assign("ALL_IDS", $_REQUEST["all_ids"]);
}
//crmv@11597 e
//crmv@10759
$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("OWNED_BY",getTabOwnedBy($currentModule));
//crmv@10759 e

$listview_entries_other = $controller->getListViewEntriesOther($list_result->sql,$queryGenerator,$customView,$viewid,$navigation_array);	//crmv@47905

//crmv@42752
$listview_header = $controller->getListViewHeader($focus,$currentModule,$url_string,$sorder, $order_by, $skipActions, $_REQUEST['folderid']);  //crmv@83034	crmv@86355
$listview_entries = $controller->getListViewEntries($focus,$currentModule,$list_result,$navigation_array,$skipActions,$listview_entries_other);
//crmv@42752e crmv@34627e

// Convert field value to DetailView Link
if(isset($focus->detailview_links) && count($focus->detailview_links)) {
	foreach($listview_entries as $listview_recid=>$listview_row) {
		foreach($listview_row as $listview_key=>$listview_val) {
			$listview_key_header = $listview_header[$listview_key];
			preg_match('/(<[^>]+>)([^<]+)(<[^>]+>)/', $listview_key_header, $matches);
			$linktext = array_search(trim($matches[2], ' &nbsp;\t\r\n'), $mod_strings);
			if(in_array($linktext, $focus->detailview_links)) {
				$listview_row[$listview_key] =
					"<a href='index.php?action=DetailView&module=$currentModule&record=$listview_recid&parenttab=$category'>".$listview_val."</a>";
			}
		}
		$listview_entries[$listview_recid] = $listview_row;
	}
}
$fieldnames = $controller->getAdvancedSearchOptionString();
$criteria = getcriteria_options();

//crmv@3084m
$grid_search_js_array = '';
$grid_search_array = $controller->getGridSearch($focus,$currentModule,$url_string,$sorder,$order_by,$skipActions,$grid_search_js_array);
//crmv@3084me

global $theme;
// Identify this module as custom module.
if (isset($focus->IsCustomModule)){
	$custom_module = true;
}else{
	$custom_module = false;
}
$smarty->assign('CUSTOM_MODULE', $custom_module);
if($viewinfo['viewname'] == 'All') $smarty->assign('ALL', 'All');
$smarty->assign("VIEWID", $viewid);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', getTranslatedString('SINGLE_'.$currentModule));
$smarty->assign('CATEGORY', $category);
$smarty->assign('BUTTONS', $list_buttons);
$smarty->assign('CHECK', $tool_buttons);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign("LISTHEADER", $listview_header);
$smarty->assign("LISTENTITY", $listview_entries);
//crmv@3084m
$smarty->assign("GRIDSEARCHARR", $grid_search_array);
$smarty->assign("GRIDSEARCH_JS_ARRAY", $grid_search_js_array);
//crmv@3084me
$smarty->assign("SELECT_SCRIPT", $view_script);
$smarty->assign("CRITERIA", $criteria);
$smarty->assign("FIELDNAMES", $fieldnames);
$smarty->assign("CUSTOMVIEW_OPTION",$customview_html);
$smarty->assign("SKIP_SWITCHBTN",($_REQUEST['hide_switchview_buttons'] == '1'));
// crmv@30967
$folderid = intval($_REQUEST['folderid']);
$smarty->assign("FOLDERID", $folderid);
if ($folderid > 0) {
	$folderinfo = getEntityFolder($folderid);
	$smarty->assign("FOLDERINFO", $folderinfo);
}
if ($currentModule == 'Charts') {
	$smarty->assign('HIDE_BUTTON_CREATE', true);
}
// crmv@30967e
VteSession::set($currentModule.'_listquery', $list_query);
// Gather the custom link information to display
include_once('vtlib/Vtecrm/Link.php');
$customlink_params = Array('MODULE'=>$currentModule, 'ACTION'=>vtlib_purify($_REQUEST['action']), 'CATEGORY'=> $category);
$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('LISTVIEWBASIC','LISTVIEW'), $customlink_params));
// END

$smarty_ajax_template = 'ListViewEntries.tpl';
$smarty_template = 'ListView.tpl';

$sdk_custom_file = 'ListViewCustomisations';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
@include("modules/$currentModule/$sdk_custom_file.php");

// crmv@83340
if($_REQUEST['fetch_only'] == 'true') {
	if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '') {
		$outputHtml = $smarty->fetch($smarty_ajax_template);
	} else {
		$outputHtml = $smarty->fetch($smarty_template);
	}
} else {
	if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '') {
		$smarty->display($smarty_ajax_template);
	}
	/* crmv@102334 */
}
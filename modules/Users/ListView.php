<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/ListView/ListViewSession.php');
global $app_strings;
global $table_prefix;
global $list_max_entries_per_page;
global $currentModule, $current_user;
if($current_user->is_admin != 'on')
{
        die("<br><br><center>".$app_strings['LBL_PERMISSION']." <a href='javascript:window.history.back()'>".$app_strings['LBL_GO_BACK'].".</a></center>");
}

$log = LoggerManager::getLogger('user_list');

global $mod_strings;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
global $current_language;
$mod_strings = return_specified_module_language($current_language,'Users');
$category = getParentTab();
$focus = CRMEntity::getInstance('Users');
$no_of_users=UserCount();

$LVU = ListViewUtils::getInstance();

// crmv@31415
$queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
$queryGenerator->initForDefaultCustomView();
$extrafields = array('user_name', 'first_name', 'last_name', 'email1','phone_mobile', 'phone_work', 'is_admin', 'status', 'roleid');
foreach ($extrafields as $f) $queryGenerator->addField($f);

$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
// crmv@31415e

//Display the mail send status
$smarty = new VteSmarty();
if($_REQUEST['mail_error'] != '')
{
    require_once("modules/Emails/mail.php");
    $error_msg = strip_tags(parseEmailErrorString($_REQUEST['mail_error']));
	$error_msg = $app_strings['LBL_MAIL_NOT_SENT_TO_USER']. ' ' . $_REQUEST['user']. '. ' .$app_strings['LBL_PLS_CHECK_EMAIL_N_SERVER'];
	$smarty->assign("ERROR_MSG",$mod_strings['LBL_MAIL_SEND_STATUS'].' <b><font class="warning">'.$error_msg.'</font></b>');
}
// crmv@31415
if(isset($_REQUEST['query']) && $_REQUEST['query'] == 'true') {
	$listview_header_search = $controller->getBasicSearchFieldInfoList();
	$_REQUEST['search_fields'] = $listview_header_search;
	$queryGenerator->addUserSearchConditions($_REQUEST);
	$ustring = getSearchURL($_REQUEST);
	$url_string .= "&query=true$ustring";
	$smarty->assign('SEARCH_URL', $url_string);
}

$list_query = $queryGenerator->getQuery();
$list_query = str_replace("{$table_prefix}_users.status='Active'", "{$table_prefix}_users.deleted=0", $list_query);
$where = $queryGenerator->getConditionalWhere();
$list_query_count = $list_query;
// crmv@31415e

$userid = array(); 
$userid_Query = "SELECT id,user_name FROM ".$table_prefix."_users WHERE user_name IN ('admin')";
$users = $adb->pquery($userid_Query,array());
$norows = $adb->num_rows($users);
if($norows  > 0){
	for($i=0;$i<$norows ;$i++){
		$id = $adb->query_result($users,$i,'id');
		$userid[$id] = $adb->query_result($users,$i,'user_name');
	}
}
$smarty->assign("USERNODELETE",$userid);

if(!getLVS($currentModule)) {
	unsetLVS();
	$modObj = new ListViewSession();
	$modObj->sorder = $sorder;
	$modObj->sortby = $order_by;
	setLVS($currentModule,get_object_vars($modObj));
}

// crmv@193850
$sorder = $focus->getSortOrder(); 
VteSession::set('USERS_SORT_ORDER', $sorder);

$order_by = $focus->getOrderBy();
VteSession::set('USERS_ORDER_BY', $order_by);
// crmv@193850e

if(!empty($order_by)){
	$list_query .= ' ORDER BY '.$order_by.' '.$sorder;
}
//crmv@11597
$smarty->assign("CUSTOMCOUNTS_OPTION", $LVU->get_selection_options($currentModule, $noofrows));
$list_max_entries_per_page = $LVU->get_selection_options($currentModule, $noofrows, 'list');
if ($_REQUEST['calc_nav'] == 'true'){
	//Retreive the List View Table Header
	if($viewid !='')
	$url_string .= "&viewname=".$viewid;
	echo '&#&#&#';
	echo get_navigation_values($list_query_count,$url_string,$currentModule,'',true,$viewid);
	die();
}
if ($_REQUEST['ajax'] == 'true' 
	&& $_REQUEST['search']!= 'true' 
	&& $_REQUEST['changecount']!= 'true'
	&& $_REQUEST['deleteuser']!= 'true'
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
		$navigation_array = VT_getSimpleNavigationValues($start,$list_max_entries_per_page,$noofrows);
		$limit_start_rec = ($start-1) * $list_max_entries_per_page;
		$record_string = getRecordRangeMessage($list_max_entries_per_page, $limit_start_rec,$noofrows);
		$navigationOutput = $LVU->getTableHeaderSimpleNavigation($navigation_array, $url_string,$currentModule,$type,$viewid);
		$smarty->assign("RECORD_COUNTS", $record_string);
		$smarty->assign("NAVIGATION", $navigationOutput);
		$smarty->assign("AJAX", 'true');
	}
}
elseif ($_REQUEST['ajax'] == 'true' && $_REQUEST['deleteuser'] == 'true'){
	$res = $adb->query(replaceSelectQuery($list_query_count,$table_prefix.'_users.id as crmid'));
	$noofrows = $adb->num_rows($res);
	setLVSDetails($currentModule,$viewid,$noofrows,'noofrows');
	$list_max_entries_per_page = $LVU->get_selection_options($currentModule, $noofrows, 'list');
	$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
	$start = ListViewSession::getRequestCurrentPage($currentModule, $list_query, $viewid, $queryMode);
	$limit_start_rec = ($start-1) * $list_max_entries_per_page;
	if ($limit_start_rec >= $list_max_entries_per_page){
		 $start -= 1;
		 $limit_start_rec = ($start-1) * $list_max_entries_per_page;
		 setLVSDetails($currentModule,$viewid,$start,'start');
	}		
	$navigation_array = VT_getSimpleNavigationValues($start,$list_max_entries_per_page,$noofrows);
	$record_string = getRecordRangeMessage($list_max_entries_per_page, $limit_start_rec,$noofrows);
	if ($noofrows >  $list_max_entries_per_page)
		$navigationOutput = $LVU->getTableHeaderSimpleNavigation($navigation_array,$url_string,$currentModule,$type,$viewid);
	$smarty->assign("RECORD_COUNTS", $val['rec_string']);
	$smarty->assign("NAVIGATION", $val['nav_array']);	
	$navigation_array['current'] = $start;
	$navigation_array['start'] = $start;		
}
else {
	$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
	$start = ListViewSession::getRequestCurrentPage($currentModule, $list_query, $viewid, $queryMode);
	$limit_start_rec = ($start-1) * $list_max_entries_per_page;
	$navigation_array['current'] = $start;
	$navigation_array['start'] = $start;
}
$list_result = $adb->limitQuery($list_query,$limit_start_rec,$list_max_entries_per_page);
//crmv@11597 e

$skipActions = true; // crmv@84630

//Retreive the Navigation array
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("CURRENT_USERID", $current_user->id);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("CATEGORY",$category);
$smarty->assign("LIST_HEADER",$LVU->getListViewHeader($focus,"Users",$url_string,$sorder,$order_by,'','','',true));
$smarty->assign("LIST_ENTRIES",$LVU->getListViewEntries($focus,"Users",$list_result,$navigation_array,"","","EditView","Delete",'','','','',$skipActions)); // crmv@84630
$smarty->assign("USER_COUNT",$no_of_users);
$smarty->assign("RECORD_COUNTS", $record_string);
$smarty->assign("NAVIGATION", $navigationOutput);
$smarty->assign("USER_IMAGES",getUserImageNames());
$alphabetical = $LVU->AlphabeticalSearch($currentModule,'index',$focus->search_base_field,'true','basic',"","","","",$viewid);
$fieldnames = getAdvSearchfields($module);
$criteria = getcriteria_options();
$listview_header_search=getSearchListHeaderValues($focus,$currentModule,$url_string,$sorder,$order_by,"",$oCustomView);
$smarty->assign("SEARCHLISTHEADER", $listview_header_search);
$smarty->assign("CRITERIA", $criteria);
$smarty->assign("FIELDNAMES", $fieldnames);
$smarty->assign("ALPHABETICAL", $alphabetical);
$smarty->assign("MODULE",$currentModule);
$check_button = Button_Check($module); 
$check_button['moduleSettings'] = 'no'; // crmv@140887
$smarty->assign("CHECK", $check_button);
$smarty->assign("THEME", $theme);	//crmv@18549

$smarty_ajax_template = 'UserListViewContents.tpl';
$smarty_template = 'UserListView.tpl';

$sdk_custom_file = 'ListViewCustomisations';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
@include("modules/$currentModule/$sdk_custom_file.php");

if ($_REQUEST['ajax'] != '')
	$smarty->display($smarty_ajax_template);
else
	$smarty->display($smarty_template);
?>
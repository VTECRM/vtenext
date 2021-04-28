<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $app_strings, $mod_strings, $current_language, $currentModule, $theme, $table_prefix, $list_max_entries_per_page;

$focus = CRMEntity::getInstance($currentModule);
$list_max_entries_per_page = $focus->list_max_entries_per_page;
$layout = $focus->getLayoutSettings();

require_once('include/ListView/ListView.php');
require_once('modules/CustomView/CustomView.php');
//crmv@208173

$LVU = ListViewUtils::getInstance();

$smarty = new VteSmarty();

global $current_user, $current_folder, $thread, $current_account;

if (isset($_REQUEST['thread']) && !empty($_REQUEST['thread'])) {
	$thread = $_REQUEST['thread'];
}
if (isset($_REQUEST['record']) && !empty($_REQUEST['record'])) {
	$record = $_REQUEST['record'];
	$focus->id = $_REQUEST['record'];
	$retrieve = $focus->retrieve_entity_info($_REQUEST['record'], $currentModule, false);
	if ($focus->column_fields['mtype'] == 'Link' && ($current_user->id == $focus->column_fields['assigned_user_id'] || $focus->column_fields['mvisibility'] == 'Public')) { //crmv@45122
		$current_folder = 'Links';
	} elseif (!in_array($retrieve,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND','LBL_OWNER_MISSING')) && in_array($_REQUEST['record'],$focus->getRelatedModComments())) {
		$current_folder = 'Shared';
	} else {
		$current_folder = $focus->column_fields['folder'];
		$current_account = $focus->column_fields['account'];
	}
	// set default account and folder
	if ($current_account == '') {
		$current_account = $focus->getMainUserAccount();
		$current_account = $current_account['id'];
	}
	$focus->setAccount($current_account);
	$specialFolders = $focus->getSpecialFolders();
	if (empty($current_folder)) {
		$current_folder = $specialFolders['INBOX'];
	}
	// end
} else {
	if (isset($_REQUEST['folder']) && !empty($_REQUEST['folder'])) {
		$current_folder =  mb_convert_encoding($_REQUEST['folder'] , "UTF7-IMAP", "UTF-8"); //crmv@61520
	}
	if (isset($_REQUEST['account'])) {
		$current_account = $_REQUEST['account'];
	}
	// if is first click (no folder in request) autoload message
	if (empty($current_folder) && empty($current_account) && VteSession::hasKey('autoload_message') && !VteSession::isEmpty('autoload_message')) {
		$retrieve = $focus->retrieve_entity_info(VteSession::get('autoload_message'), $currentModule, false);
		if (in_array($retrieve,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND','LBL_OWNER_MISSING'))) {
			$record = '';
		} elseif (!in_array($retrieve,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND','LBL_OWNER_MISSING')) && in_array(VteSession::get('autoload_message'),$focus->getRelatedModComments())) {
			$current_folder = 'Shared';
		} else {
			$record = $focus->id = VteSession::get('autoload_message');
			$current_folder = $focus->column_fields['folder'];
			$current_account = $focus->column_fields['account'];
		}
		// if last message seen is in Links or Shared go to default folder and account
		if (in_array($current_folder,array('Links','Shared')) || $focus->column_fields['mtype'] == 'Link') { //crmv@45122
			$current_account = '';
			$current_folder = '';
			$record = '';
		}
	}
	// end
	// set default account and folder
	if ($current_account == '') {
		$current_account = $focus->getMainUserAccount();
		$current_account = $current_account['id'];
	}
	$focus->setAccount($current_account);
	if ($current_account != 'all') {
		$specialFolders = $focus->getSpecialFolders();
	}
	if (empty($current_folder)) {
		$current_folder = $specialFolders['INBOX'];
	}
	// crmv@192843 if multiaccount go to general folder
	if (!isset($_REQUEST['account']) && ($current_folder == $specialFolders['INBOX'] || $current_folder == $specialFolders['Sent'] || $current_folder == $specialFolders['Spam'])) {
		$accounts = $focus->getUserAccounts();
		if (count($accounts) > 1) {
			$current_account = 'all';
			if ($current_folder == $specialFolders['INBOX']) $current_folder = 'INBOX';
			elseif ($current_folder == $specialFolders['Sent']) $current_folder = 'Sent';
			elseif ($current_folder == $specialFolders['Spam']) $current_folder = 'Spam';
		}
	}
	// crmv@192843e
	// end
	// if is first time (no autoload message) select the first seen message
	if (empty($record)) {
		$lucky_messsage = $focus->getLuckyMessage($current_account,$current_folder);
		if (!empty($lucky_messsage)) {
			$record = $lucky_messsage;
		}
	}
	// end
	if ($layout['thread'] == '1' && !empty($record)) {
		//check if record is not the last son
		$father = $focus->getFather($record);
		if ($father) {
			$children = $focus->getChildren($father);
			if (!empty($record)) {
				$record = $children[0];
				$smarty->assign('LAST_THREAD_CLICKED', $record);
			}
		}
	}
	//crmv@125629 if there aren't account for the user
	if (empty($current_account)) {
		echo $focus->fetchConnectionError('ERR_IMAP_SERVER_EMPTY','');
		exit;
	}
	//crmv@125629e
}

//crmv@87055 crmv@OPER8279 crmv@187630
$searching = true;
$search_standard_txt = array(
	'',
	getTranslatedString('LBL_SEARCH_TITLE',$currentModule).getTranslatedString($currentModule,$currentModule),
	rawurlencode(getTranslatedString('LBL_SEARCH_TITLE',$currentModule).getTranslatedString($currentModule,$currentModule)),
	getTranslatedString('LBL_SEARCH'),
	rawurlencode(getTranslatedString('LBL_SEARCH')),
);
if (empty($_REQUEST['searchtype']) || ($_REQUEST['searchtype'] == 'BasicSearch' && in_array($_REQUEST['search_text'],$search_standard_txt))) {
	$searching = false;
}
$search_intervals = array(); // crmv@167234
if ($searching) {
	$start = intval($_REQUEST['start']);
	$search_intervals = $focus->getSearchIntervals();
	$searching_in_imap = $search_intervals[$start][2];
} elseif (!empty($focus->interval_storage)) {
	$start = intval($_REQUEST['start']);
	$search_intervals = $focus->getImapNavigationIntervals();
	if ($start > 0 && isset($_REQUEST['imap_navigation'])) {
		//$searching = true;
		$searching_in_imap = true;
	}
}
$smarty->assign('SEARCHING',$searching);
$smarty->assign('SEARCH_INTERVALS_COUNT',count($search_intervals));
//crmv@87055e crmv@OPER8279e crmv@187630e

// calc_nav
if ($_REQUEST['calc_nav'] == 'true') {
	if ($searching) die('&#&#&#{"nav_array":[],"rec_string":"","rec_string3":""}');	//crmv@87055 crmv@103872
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
		$navigation_value = $focus->get_navigation_values(VteSession::get($currentModule.'_listquery'),$url_string,$currentModule,'',false,$viewid);	//crmv@94282
		$navigation_value = Zend_Json::decode($navigation_value);
		$navigation_value['rec_string'] = $_REQUEST['noofrows'];
		$navigation_value['rec_string3'] = $focus->getStrUnreadMessageCount();
		echo Zend_Json::encode($navigation_value);
	}
	//crmv@48471
	if ($_REQUEST['reload_counts'] == 'yes' && PerformancePrefs::get('MESSAGES_UPDATE_ICON_PERFORM_IMAP_ACTIONS', '') != 'disable') {	//crmv@125629
		echo '&#&#&#';
		include('modules/Messages/Folders.php');
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('APP', $app_strings);
		$smarty->assign('FOCUS', $focus);
		echo $smarty->fetch("modules/Messages/Folders.tpl");
		echo '&#&#&#';
		include('modules/Messages/Accounts.php');
		echo $smarty->fetch("modules/Messages/Accounts.tpl");
	}
	//crmv@48471e
	die();
}
// end

// only first time
if ($_REQUEST['action'] != $currentModule.'Ajax') {
	include('modules/Messages/Accounts.php');
	include('modules/Messages/Folders.php');
}
$smarty->assign('CURRENT_FOLDER', $current_folder);
$tmp = strrpos($current_folder,$focus->getFolderSeparator($current_account)); //crmv@178164
if ($tmp !== false) {
	$label_folder = substr($current_folder,$tmp+1);
} else {
	$label_folder = $current_folder;
}
$tmp = getTranslatedString('LBL_Folder_'.$label_folder,'Messages');
if ($tmp != 'LBL_Folder_'.$label_folder) {
	$label_folder = $tmp;
//crmv@61520
} else {
	$label_folder = str_replace('&amp;', '&', $label_folder);
	$label_folder = htmlentities(mb_convert_encoding($label_folder, "ISO-8859-1", "UTF7-IMAP"), ENT_NOQUOTES, 'ISO-8859-1');
}
//crmv@61520e
$smarty->assign('RECORD', $record);
$smarty->assign('CURRENT_ACCOUNT', $current_account);
$smarty->assign('CURRENT_FOLDER_LABEL', $label_folder);
$smarty->assign('FOCUS', $focus);
$smarty->assign('SPECIAL_FOLDERS', $specialFolders);
$smarty->assign('SPECIAL_FOLDERS_ENCODED', Zend_Json::encode($specialFolders));
$smarty->assign('CURRENT_THREAD', $thread);

$category = getParentTab();

// only first time
if ($_REQUEST['action'] != $currentModule.'Ajax') {
	if (empty($accounts)) {
		$accounts = $focus->getUserAccounts();
	}
	if (count($accounts) > 1) $multiaccount = true; else $multiaccount = false;
	$smarty->assign('MULTIACCOUNT', $multiaccount);
	
	$tool_buttons = Button_Check($currentModule);
	unset($tool_buttons['moduleSettings']); // crmv@139855
	
	$list_buttons = Array();
	// crmv@30967
	if (in_array($currentModule,array('Documents','Charts','Reports'))){
		$list_buttons['back'] = $app_strings['LBL_GO_BACK'];
	}
	// crmv@30967e
		if(isPermitted($currentModule,'Delete','') == 'yes')
		$list_buttons['del'] = $app_strings['LBL_MASS_DELETE']; // crmv@167234
	if($currentModule !='Sms'){
	if(isPermitted($currentModule,'EditView','') == 'yes')
		$list_buttons['mass_edit'] = $app_strings['LBL_MASS_EDIT']; // crmv@167234
	}
	//custom code start
	if(in_array($currentModule,array('Leads','Accounts','Contacts'))){
		if(isPermitted('Emails','EditView','') == 'yes')
			$list_buttons['s_mail'] = $app_strings['LBL_SEND_MAIL_BUTTON']; // crmv@167234
		if(isPermitted('Fax','EditView','') == 'yes')
			$list_buttons['s_fax'] = $app_strings['LBL_SEND_FAX_BUTTON']; // crmv@167234
		if(isPermitted("Sms","EditView",'') == 'yes' && vtlib_isModuleActive('Sms'))	//crmv@16703
			$list_buttons['s_sms'] = $app_strings['LBL_SEND_SMS_BUTTON']; // crmv@167234
	}
	//custom code end
	//crmv@18592
	$view_script = "";
	//crmv@18592e
	$smarty->assign('BUTTONS', $list_buttons);
	$smarty->assign('CHECK', $tool_buttons);
	$smarty->assign("SELECT_SCRIPT", $view_script);
}

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
if ($searching_in_imap) $queryGenerator->addField('xuid');	//crmv@OPER8279

$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);

// Enabling Module Search
if ($searching && $_REQUEST['query'] == 'true') {
	$listview_header_search = $controller->getBasicSearchFieldInfoList();
	$_REQUEST['search_fields'] = $listview_header_search;
	$queryGenerator->addUserSearchConditions($_REQUEST);
	$ustring = getSearchURL($_REQUEST);
	$url_string .= "&query=true$ustring";
	$smarty->assign('SEARCH_URL', $url_string);
}
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
if ($layout['thread'] == '1') {
	if (empty($thread) && !in_array($current_folder,$focus->fakeFolders)) {
		$queryGenerator->fromString = " FROM {$focus->table_name} messageFather INNER JOIN {$focus->table_name} ON {$focus->table_name}.{$focus->table_index} = messageFather.lastson ";
	}
}
$list_query = $queryGenerator->getQuery();
$where = $queryGenerator->getConditionalWhere();
//crmv@7634e
if(isset($where) && $where != '') {
	VteSession::set('export_where', $where);
} else {
	VteSession::remove('export_where');
}
//crmv@87055 crmv@OPER8279
if ($searching) {
	if (!empty($search_intervals[$start][0])) $list_query .= " and {$focus->table_name}.mdate < '".date('Y-m-d',strtotime($search_intervals[$start][0]))." 00:00:00'";
	if (!empty($search_intervals[$start][1])) $list_query .= " and {$focus->table_name}.mdate >= '".date('Y-m-d',strtotime($search_intervals[$start][1]))." 00:00:00'";
} elseif (!empty($focus->interval_storage)) {
	// if not searching and use interval_storage end the navigation to the limit of interval_storage
	$interval_storage = $focus->getIntervalStorage();
	$list_query .= " and {$focus->table_name}.mdate >= '".$interval_storage['date']." 00:00:00'";
}
//crmv@87055e crmv@OPER8279e
if(!empty($order_by) && $order_by != '' && $order_by != null) {
	$list_query .= $focus->getFixedOrderBy($currentModule,$order_by,$sorder); //crmv@25403
}

//crmv@11597
$smarty->assign("CUSTOMCOUNTS_OPTION", $LVU->get_selection_options($currentModule, $noofrows));
//crmv@87055
if ($searching) {
	$list_result = $adb->query($list_query);
} else {
	//crmv@94282
	// performance fix, skip the all counting
	$list_max_entries_per_page=$list_max_entries_per_query=$focus->list_max_entries_first_page; //crmv@60748
	if (!isset($_REQUEST['start']) || $_REQUEST['start'] == 1) {
		$_REQUEST['start'] = 1;
		if ($_REQUEST['ajax'] == 'true'
		&& $_REQUEST['search']!= 'true'
		&& $_REQUEST['changecount']!= 'true'
		&& $_REQUEST['changecustomview']!= 'true'){
			$smarty->assign("AJAX", 'true');
		}
	}
	$limit_start_rec = ($_REQUEST['start']-1)*$list_max_entries_per_page;
	$navigation_array['list_max_entries_per_query'] = $list_max_entries_per_query+1;
	$list_result = $adb->limitQuery($list_query,$limit_start_rec,$navigation_array['list_max_entries_per_query']);
	//crmv@94282e
} //crmv@87055

//crmv@46154e
if (isset($_REQUEST["selected_ids"]))
{
  $smarty->assign("SELECTED_IDS_ARRAY", explode(";",$_REQUEST["selected_ids"]));
  $smarty->assign("SELECTED_IDS", $_REQUEST["selected_ids"]);
}
if (isset($_REQUEST["all_ids"]))
{
  $smarty->assign("ALL_IDS", $_REQUEST["all_ids"]);
}
//crmv@11597 e
//crmv@10759
$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("OWNED_BY",getTabOwnedBy($currentModule));
//crmv@10759 e

//crmv@34627
$module_width_fields = $queryGenerator->getModuleWidthFields();
$secondary_fields = $queryGenerator->getSecondaryFields();
if (!empty($secondary_fields) && !empty($module_width_fields)) {
	$reportid = $customView->getReportId($viewid);
	$reportmodules = $customView->getReportModules();
	foreach($module_width_fields as $module_width_field) {
		if ($module_width_field != $currentModule && in_array($module_width_field,$reportmodules)) {
			$focus_other = CRMEntity::getInstance($module_width_field);
			$instance_other = Vtecrm_Module::getInstance($module_width_field);
			$queryGeneratorOther = QueryGenerator::getInstance($module_width_field, $current_user);
			$queryGeneratorOther->setFields(array_merge(array('id'),$secondary_fields[$module_width_field]));
			$queryGeneratorOther->setReportFilter($reportid,$module_width_field,$instance_other->id);
			$list_query_other = $queryGeneratorOther->getQuery();
			$list_result_other = $adb->query($list_query_other);
			$controller_other = ListViewController::getInstance($adb, $current_user, $queryGeneratorOther);
			$listview_entries_other[$module_width_field] = $controller_other->getListViewEntries($focus_other,$module_width_field,$list_result_other,$navigation_array,true);
		}
	}
}
//crmv@34627e
$listview_header = $controller->getListViewHeader($focus,$currentModule,$url_string,$sorder, $order_by);
//crmv@94282 crmv@OPER8279
if ($searching_in_imap) {
	$lv_error = '';
	$search_params = $focus->getSearchParams($_REQUEST);
	$listview_entries = $focus->getListViewEntriesImap($search_intervals[$start][0],$search_intervals[$start][1],$search_params,$list_result,$lv_error);
	$smarty->assign("LV_ERROR", $lv_error);
} else {
	$listview_entries = $controller->getListViewEntriesLight($focus,$currentModule,$list_result,$navigation_array,false,$listview_entries_other);	//crmv@34627
}
if ($controller->override_params['has_next_page']){
	$string_pagination = $url_string;
	$string_pagination .= "&account=$current_account&folder=$current_folder&thread=$thread";
	$string_pagination.="&has_next_page=1";
	$url_pagination = 'parenttab='.$tabname.'&start='.($_REQUEST['start']+1).$string_pagination;
	$navigationOutput = '<a href="javascript:;" id="appendNextListViewEntries" onClick="appendListViewEntries_js(\''.$module.'\',\''.$url_pagination.'\');" alt="'.$app_strings['LNK_LIST_NEXT'].'" title="'.$app_strings['LNK_LIST_NEXT'].'"><img src="'.resourcever('next.gif').'" border="0" align="absmiddle" style="display:none"></a>&nbsp;';
	$smarty->assign("NAVIGATION", $navigationOutput);
}
//crmv@94282e crmv@OPER8279e

//crmv@48159
$no_message_stored = false;
if (empty($listview_entries)) {
	(!empty($focus->interval_storage)) ? $msg_empty_list = '' : $msg_empty_list = $app_strings['LBL_NO_M'].' '.strtolower($app_strings['SINGLE_Messages']).' '.strtolower($app_strings['LBL_FOUND']);
	$result = $adb->pquery("SELECT COUNT(*) AS c FROM {$focus->table_name} WHERE deleted = 0 AND mtype = ?",array('Webmail')); //crmv@171021
	if ($result) {
		$count = $adb->query_result($result,0,'c');
		if ($count == 0) {
			$no_message_stored = true;
			$msg_empty_list .= '<br />'.getTranslatedString('LBL_CHECK_CRON_CONFIGURATION','Messages');
		}
	}
	$smarty->assign("MSG_EMPTY_LIST", $msg_empty_list);
}
//crmv@48159e

//crmv@87055 crmv@104533 crmv@OPER8279
if (!$no_message_stored && ($searching || (!empty($focus->interval_storage) && !$controller->override_params['has_next_page']))) {
	$imap_navigation = !$searching;
	if ($imap_navigation && !isset($_REQUEST['imap_navigation'])) $start = 0;
	$navigationOutput = '';
	$navigationOutput.= ' <input type="hidden" id="count_results_search_intervals" value="'.count($listview_entries).'" />';
	if (isset($search_intervals[$start+1])) {
		$url_string .= "&account=$current_account&folder=$current_folder&thread=$thread";
		if ($imap_navigation) {
			$url_string .= "&imap_navigation=1";
			$navigationOutput.= ' <input type="hidden" id="imap_navigation" value="1" />';
		} elseif ($searching) {
			$navigationOutput.= ' <input type="hidden" id="navigation_search" value="1" />';
		}
		if (empty($listview_entries)) {
			VteSession::setArray(array('Messages_results_search_intervals', $start), 0);
			$navigationOutput.= sprintf(getTranslatedString('LBL_NO_RESULT_TILL','Messages'), str_replace(' 00:00','',$focus->getFriendlyDate(date('Y-m-d',strtotime($search_intervals[$start][1])))));
		} else {
			VteSession::setArray(array('Messages_results_search_intervals', $start), count($listview_entries));
			$navigationOutput.= sprintf(getTranslatedString('LBL_SEARCH_TILL','Messages'), str_replace(' 00:00','',$focus->getFriendlyDate(date('Y-m-d',strtotime($search_intervals[$start][1])))));
		}
		$navigationOutput.= '<br><button class="crmbutton small search" id="button_continue_search" onclick="appendListViewEntries_js(\''.$currentModule.'\',\'parenttab='.$tabname.'&start='.($start+1).$url_string.'\');">'.getTranslatedString('LBL_SEARCH_MORE','Messages').'</button>';
		$navigationOutput = '<br />'.$navigationOutput.'<br />';
	}
	$smarty->assign("NAVIGATION", $navigationOutput);
	$smarty->assign("IMAP_NAVIGATION", $imap_navigation);
	$results_prev_search = 0;
	foreach(VteSession::get('Messages_results_search_intervals') as $i => $val) if ($i < $start) $results_prev_search += $val;
	$smarty->assign("MESSAGES_RESULTS_PREV_SEARCH", $results_prev_search);
}
//crmv@87055e crmv@104533e crmv@OPER8279e

$fieldnames = $controller->getAdvancedSearchOptionString();
$criteria = getcriteria_options();
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
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign("LISTHEADER", $listview_header);
$smarty->assign("LISTENTITY", $listview_entries);
$smarty->assign("CRITERIA", $criteria);
$smarty->assign("FIELDNAMES", $fieldnames);
$smarty->assign("CUSTOMVIEW_OPTION",$customview_html);
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

$list_query = str_replace($queryGenerator->fromString," FROM {$focus->table_name} ",$list_query);
VteSession::set($currentModule.'_listquery', $list_query);

// Gather the custom link information to display
include_once('vtlib/Vtecrm/Link.php');//crmv@207871
$customlink_params = Array('MODULE'=>$currentModule, 'ACTION'=>vtlib_purify($_REQUEST['action']), 'CATEGORY'=> $category);
$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('LISTVIEWBASIC','LISTVIEW'), $customlink_params));
// END

//crmv@49395
$err = $focus->checkSyncUidsErrors();
if ($err) {
	$smarty->assign("NOTIFY", addslashes(getTranslatedString('LBL_PROBLEMS_MESSAGE_FETCHING','Messages')));
}
//crmv@49395e

//crmv@48501
$err = $focus->checkSendQueueErrors();
if ($err) {
	$smarty->assign("NOTIFY", addslashes(getTranslatedString('LBL_PROBLEMS_MESSAGE_SENDING','Emails')));
}
//crmv@48501e

//crmv@125629
$err = $focus->checkAccountError($current_user->id);
if ($err) $smarty->assign("ACCOUNT_ERROR", $err);
//crmv@125629e

//crmv@48693
$advfilterjs = $focus->getAdvCriteriaJS();
$smarty->assign("ADVFILTER_JAVASCRIPT",$advfilterjs);
//crmv@48693e

if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '') {
	if ($_REQUEST['appendlist'] == 'yes') {
		$smarty->display("modules/Messages/ListViewRows.tpl");
	} else {
		$smarty->display("modules/Messages/ListViewEntries.tpl");
	}
} else {
	//crmv@62394
	require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');
	if (CalendarTracking::isEnabledForTurbolift($currentModule)) {
		$smarty->assign('SHOW_TURBOLIFT_TRACKER', true);
		$smarty->assign('TRACKER_DATA', CalendarTracking::getTrackerData($currentModule, $focus->id));
	}
	//crmv@62394e
	$smarty->display('modules/Messages/ListView.tpl');
}
?>
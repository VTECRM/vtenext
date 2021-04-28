<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//echo "<pre>";
//print_r($_REQUEST);die;
global $adb, $table_prefix,$upload_maxsize;
global $app_strings, $mod_strings, $current_language, $currentModule, $theme,$default_charset;
if (isset($_REQUEST['view_mode']) && $_REQUEST['view_mode'] != ''){
	$mode = vtlib_purify($_REQUEST['view_mode']);
}
else{
	$mode = 'folder'; //default view folder
}
$smarty = new VteSmarty();
$folderlist = array();
$focus = CRMEntity::getInstance($currentModule);
$folderid = null;
if (isset($_REQUEST['folder_selected']) && $_REQUEST['folder_selected'] != ''){
	$folderid = vtlib_purify($_REQUEST['folder_selected']);
}
$myfilesid = null;
if (isset($_REQUEST['myfilesid_selected']) && $_REQUEST['myfilesid_selected'] != ''){
	$myfilesid = vtlib_purify($_REQUEST['myfilesid_selected']);
}
switch($mode){
	case 'folder':
		$folderlist = $focus->getFolderList($folderid);
		foreach ($folderlist as$key=>$fcont){
			$folderlist[$key]['count'] = $focus->getFolderCount($fcont['folderid']);
		}
		if (count($folderlist) == 0){
			$smarty->assign('EMPTY_FOLDERS','true');
		}
		break;
	case 'global':
		$folderlist = $focus->getFolderList($folderid);
		foreach ($folderlist as$key=>$fcont){
			$folderlist[$key]['content'] = $focus->getFolderFullContent($fcont['folderid']);
		}		
		break;
	case 'icon':
	case 'list':
		$folderlist = Array();
		$folderlist[$folderid]['content'] = $focus->getFolderFullContent($folderid);
		break;
	case 'convert':
		
		
		
		
		$folderlist = Array();
		$folderlist[$folderid]['content'] = $focus->getFolderFullContent($folderid);
		break;
	case 'detailview':
		$_REQUEST['record'] = $myfilesid;
		$tool_buttons = Button_Check($currentModule);
		$smarty_minidetailview = new VteSmarty();
		
		$record = $myfilesid;
		$isduplicate = vtlib_purify($_REQUEST['isDuplicate']);
		$tabid = getTabid($currentModule);
		$category = getParentTab($currentModule);
		
		if($record != '') {
			$focus->id = $record;
			$retrieve = $focus->retrieve_entity_info($record, $currentModule, false);
			if ($retrieve == 'LBL_RECORD_DELETE') {
				exit;
			}
		}
		if($isduplicate == 'true') $focus->id = '';
		
		// Identify this module as custom module.
		$smarty_minidetailview->assign('CUSTOM_MODULE', true);
		
		$smarty_minidetailview->assign('APP', $app_strings);
		$smarty_minidetailview->assign('MOD', $mod_strings);
		$smarty_minidetailview->assign('MODULE', $currentModule);
		// TODO: Update Single Module Instance name here.
		$smarty_minidetailview->assign('SINGLE_MOD', 'SINGLE_'.$currentModule);
		$smarty_minidetailview->assign('CATEGORY', $category);
		$smarty_minidetailview->assign('IMAGE_PATH', "themes/$theme/images/");
		$smarty_minidetailview->assign('THEME', $theme);
		$smarty_minidetailview->assign('ID', $focus->id);
		$smarty_minidetailview->assign('MODE', $focus->mode);
		$smarty_minidetailview->assign('RETURN_MODULE', $_REQUEST['return_module']);
		$smarty_minidetailview->assign('RETURN_ID', $_REQUEST['return_id']);
		
		$smarty_minidetailview->assign('HIDE_BUTTON_LIST', '1');
		
		$smarty_minidetailview->assign('NAME', $focus->getRecordName());	//crmv@104310
		if ($currentModule != 'Users') {
			$smarty_minidetailview->assign('UPDATEINFO',updateInfo($focus->id));
		}
		
		// Module Sequence Numbering
		$mod_seq_field = getModuleSequenceField($currentModule);
		if ($mod_seq_field != null) {
			$mod_seq_id = $focus->column_fields[$mod_seq_field['name']];
		} else {
			$mod_seq_id = $focus->id;
		}
		$smarty_minidetailview->assign('MOD_SEQ_ID', $mod_seq_id);
		// END
		
		// crmv@83877 crmv@112297
		// Field Validation Information
		$otherInfo = array();
		$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus);	//crmv@96450
		$validationArray = split_validationdataArray($validationData, $otherInfo);
		$smarty->assign("VALIDATION_DATA_FIELDNAME",$validationArray['fieldname']);
		$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",$validationArray['datatype']);
		$smarty->assign("VALIDATION_DATA_FIELDLABEL",$validationArray['fieldlabel']);
		$smarty->assign("VALIDATION_DATA_FIELDUITYPE",$validationArray['fielduitype']);
		$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",$validationArray['fieldwstype']);
		// crmv@83877e crmv@112297e
		
		$smarty_minidetailview->assign('EDIT_PERMISSION', isPermitted($currentModule, 'EditView', $record));
		$smarty_minidetailview->assign('CHECK', $tool_buttons);
		
		if(PerformancePrefs::getBoolean('DETAILVIEW_RECORD_NAVIGATION', true) && VteSession::hasKey($currentModule.'_listquery')){
			$recordNavigationInfo = ListViewSession::getListViewNavigation($focus->id);
			VT_detailViewNavigation($smarty_minidetailview,$recordNavigationInfo,$focus->id);
		}
		
		if(isPermitted($currentModule, 'EditView', $record) == 'yes')
			$smarty_minidetailview->assign('EDIT_DUPLICATE', 'permitted');
		if(isPermitted($currentModule, 'Delete', $record) == 'yes')
			$smarty_minidetailview->assign('DELETE', 'permitted');
		
		// Record Change Notification
		$focus->markAsViewed($current_user->id);
		// END
		
		$blocks = getBlocks($currentModule,'detail_view','',$focus->column_fields);
		$smarty_minidetailview->assign('BLOCKS', $blocks);

		//crmv@104568
		$panelid = getCurrentPanelId($currentModule);
		$smarty_minidetailview->assign("PANELID", $panelid);
		$panelsAndBlocks = getPanelsAndBlocks($currentModule, $record);
		$smarty_minidetailview->assign("PANEL_BLOCKS", Zend_Json::encode($panelsAndBlocks));

		if (method_exists($focus, 'getDetailTabs')) {
			$smarty_minidetailview->assign("DETAILTABS", $focus->getDetailTabs(false));
		}
		//crmv@104568e
		
		// crmv@42752
		// Gather the custom link information to display
		if ($_REQUEST['hide_custom_links'] != '1') {
			include_once('vtlib/Vtecrm/Link.php');//crmv@207871
			$customlink_params = Array('MODULE'=>$currentModule, 'RECORD'=>$focus->id, 'ACTION'=>vtlib_purify($_REQUEST['action']));
			$smarty_minidetailview->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('DETAILVIEWBASIC','DETAILVIEW','DETAILVIEWWIDGET'), $customlink_params));
		}
		// crmv@42752e
		
		$smarty_minidetailview->assign('DETAILVIEW_AJAX_EDIT', PerformancePrefs::getBoolean('DETAILVIEW_AJAX_EDIT', true));
		
		$smarty_minidetailview->assign('FOCUS', $focus);		
		$folderlist = $smarty_minidetailview->fetch('modules/Myfiles/MiniDetailView.tpl');
		break;
}
//echo "<pre>";
//print_r($folderlist);die;
/*
// get list of folders
if (method_exists($focus, 'getFolderList')) {
	$folderlist = $focus->getFolderList($folderid);
} else {
	$folderlist = getEntityFoldersByName(null, $currentModule);
}
// get elements info for each folder
if (method_exists($focus, 'getFolderFullContent')) {
	foreach ($folderlist as $key=>$fcont) {
		$foldercontent = $focus->getFolderFullContent($fcont['folderid']);
		$folderlist[$key]['content'] = $foldercontent;
	}
}
*/
$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign("THEME", $theme);
$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("STUFFID", vtlib_purify($_REQUEST['stuffid']));
$smarty->assign("MAX_FILE_SIZE",$upload_maxsize);
$smarty->assign('FOLDERS_PER_ROW', 6);
$smarty->assign('FOLDERLIST', $folderlist);
$all_folders_documents = Array();
$sql="select foldername,folderid from ".$table_prefix."_crmentityfolder where tabid = ? order by foldername";
$res=$adb->pquery($sql,array(getTabId('Documents')));
for($i=0;$i<$adb->num_rows($res);$i++){
	$fid=$adb->query_result($res,$i,"folderid");
	$all_folders_documents[$fid]=$adb->query_result($res,$i,"foldername");
}
$smarty->assign('FOLDERLISTSELECT',$all_folders_documents);
$smarty->assign('MODE', $mode);
$smarty->assign('LAST_MODE', $_REQUEST['last_view_mode']);
$smarty->assign('FOLDERID', $_REQUEST['folder_selected']);
$smarty->assign('LAST_FOLDERID', $_REQUEST['last_folder_selected']);
$smarty->assign('FOLDERNAME', $_REQUEST['folder_name']);
$smarty->assign('LAST_FOLDERNAME', $_REQUEST['last_folder_name']);
$smarty->assign('MYFILESID', $_REQUEST['myfilesid_selected']);
$smarty->assign('FILE_NAME', $_REQUEST['file_name']);
$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
$smarty->assign('JS_GLOBAL_VARS',Zend_Json::encode($JSGlobals));
$smarty->assign("LBL_CHARSET", $default_charset);
include_once('vtlib/Vtecrm/Link.php');//crmv@207871
$hdrcustomlink_params = Array('MODULE'=>$currentModule);
$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERLINK','HEADERSCRIPT', 'HEADERCSS'), $hdrcustomlink_params);
$smarty->assign('HEADERLINKS', $COMMONHDRLINKS['HEADERLINK']);
$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
$smarty->assign('HEADERCSS', $COMMONHDRLINKS['HEADERCSS']);
$smarty->assign('USERDATEFORMAT',$current_user->date_format);
$smarty->assign('DEFAULT_CHARSET', $default_charset);
$smarty->display('modules/Myfiles/ListViewFolderHome.tpl');
?>
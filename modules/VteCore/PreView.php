<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@203484 removed including file

global $mod_strings, $app_strings, $currentModule, $current_user, $theme; //crmv@203484

$tool_buttons = Button_Check($currentModule);
$smarty = new VteSmarty();

$record = $_REQUEST['record'];
$isduplicate = vtlib_purify($_REQUEST['isDuplicate']);
$tabid = getTabid($currentModule);
$category = getParentTab($currentModule);

if ($currentModule == 'Calendar' && $record != '') {
	$activitytype = getActivityType($record);
	($activitytype == 'Task') ? $module = $currentModule : $module = 'Events';
	$focus = CRMEntity::getInstance('Activity');
} else {
	$module = $currentModule;
	$focus = CRMEntity::getInstance($currentModule);
}
if($record != '') {
	$focus->id = $record;
	$retrieve = $focus->retrieve_entity_info($record, $module, false);
	if ($retrieve == 'LBL_RECORD_DELETE') {
		exit;
	}
}
//crmv@77702
if (isPermitted($currentModule, 'DetailView', $record) == 'no') {
	echo "<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>";
	echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
	echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 80%; position: relative; z-index: 10000000;'>
		<table border='0' cellpadding='5' cellspacing='0' width='98%'>
		<tbody><tr>
		<td rowspan='2' width='11%'><img src='". resourcever('denied.gif') . "' ></td>
		<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>$app_strings[LBL_PERMISSION]</span></td>
		</tr>
		<tr>
		<td class='small' align='right' nowrap='nowrap'>
		<a href=\"javascript:preView('{$currentModule}','{$record}');\">$app_strings[LBL_GO_BACK]</a><br>
		</td>
		</tr>
		</tbody></table>
		</div>";
	echo "</td></tr></table>";
	exit;
}
//crmv@77702e
if($isduplicate == 'true') $focus->id = '';

// Identify this module as custom module.
$smarty->assign('CUSTOM_MODULE', true);

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
// TODO: Update Single Module Instance name here.
$smarty->assign('SINGLE_MOD', 'SINGLE_'.$currentModule);
$smarty->assign('CATEGORY', $category);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('THEME', $theme);
$smarty->assign('ID', $focus->id);
$smarty->assign('MODE', $focus->mode);
$smarty->assign('RETURN_MODULE', $_REQUEST['return_module']);
$smarty->assign('RETURN_ID', $_REQUEST['return_id']);

// crmv@42752
if ($_REQUEST['hide_button_list'] == '1') {
	$smarty->assign('HIDE_BUTTON_LIST', '1');
}
// crmv@42752e

$smarty->assign('NAME', $focus->getRecordName());	//crmv@104310
if ($currentModule != 'Users') {
	$smarty->assign('UPDATEINFO',updateInfo($focus->id));
}

// Module Sequence Numbering
$mod_seq_field = getModuleSequenceField($currentModule);
if ($mod_seq_field != null) {
	$mod_seq_id = $focus->column_fields[$mod_seq_field['name']];
} else {
	$mod_seq_id = $focus->id;
}
$smarty->assign('MOD_SEQ_ID', $mod_seq_id);
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

$smarty->assign('EDIT_PERMISSION', isPermitted($currentModule, 'EditView', $record));
$smarty->assign('CHECK', $tool_buttons);

if(PerformancePrefs::getBoolean('DETAILVIEW_RECORD_NAVIGATION', true) && VteSession::hasKey($currentModule.'_listquery')){
	$recordNavigationInfo = ListViewSession::getListViewNavigation($focus->id);
	VT_detailViewNavigation($smarty,$recordNavigationInfo,$focus->id);
}

if(isPermitted($currentModule, 'EditView', $record) == 'yes')
	$smarty->assign('EDIT_DUPLICATE', 'permitted');
if(isPermitted($currentModule, 'Delete', $record) == 'yes')
	$smarty->assign('DELETE', 'permitted');

// Record Change Notification
$focus->markAsViewed($current_user->id);
// END

// crmv@104568
$panelid = getCurrentPanelId($module);
$smarty->assign("PANELID", $panelid);
$panelsAndBlocks = getPanelsAndBlocks($module);
$smarty->assign("PANEL_BLOCKS", Zend_Json::encode($panelsAndBlocks));
// crmv@104568e

$blocks = getBlocks($module,'detail_view','',$focus->column_fields);
$smarty->assign('BLOCKS', $blocks);

// crmv@42752
// Gather the custom link information to display
if ($_REQUEST['hide_custom_links'] != '1') {
	include_once('vtlib/Vtecrm/Link.php');
	$customlink_params = Array('MODULE'=>$currentModule, 'RECORD'=>$focus->id, 'ACTION'=>vtlib_purify($_REQUEST['action']));
	$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('DETAILVIEWBASIC','DETAILVIEW','DETAILVIEWWIDGET'), $customlink_params));
}
// crmv@42752e

//crmv@45699 crmv@104568
if (method_exists($focus, 'getDetailTabs')) {
	$smarty->assign("DETAILTABS", $focus->getDetailTabs(false));
}
//crmv@45699e crmv@104568e

$smarty->assign('DETAILVIEW_AJAX_EDIT', false);

$smarty->assign('FOCUS', $focus);

$smarty->display('PreView.tpl');
?>
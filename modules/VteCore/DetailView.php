<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file

global $mod_strings, $app_strings, $currentModule, $current_user, $theme;//crmv@203484 removed global singlepane

//crmv@203484
$VTEP = VTEProperties::getInstance();
$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
//crmv@203484e

$focus = CRMEntity::getInstance($currentModule);

$tool_buttons = Button_Check($currentModule);
$tool_buttons['moduleSettings'] = 'no'; // crmv@140887

$smarty = new VteSmarty();

$record = $_REQUEST['record'];
$isduplicate = vtlib_purify($_REQUEST['isDuplicate']);
$tabid = getTabid($currentModule);
$category = getParentTab($currentModule);

if($record != '') {
	$focus->id = $record;
	$focus->retrieve_entity_info($record, $currentModule);
}
if($isduplicate == 'true') $focus->id = '';

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

$smarty->assign('NAME', $focus->getRecordName());	//crmv@104310
$smarty->assign('UPDATEINFO',updateInfo($focus->id));

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

// TODO sbregare la doppia vista???
$smarty->assign('IS_REL_LIST', isPresentRelatedLists($currentModule));
$smarty->assign('SinglePane_View', $singlepane_view);

include('modules/VteCore/Turbolift.php'); // crmv@43864

if(isPermitted($currentModule, 'EditView', $record) == 'yes')
	$smarty->assign('EDIT_DUPLICATE', 'permitted');
if(isPermitted($currentModule, 'Delete', $record) == 'yes')
	$smarty->assign('DELETE', 'permitted');

//crmv@57221
$CU = CRMVUtils::getInstance();
$defaultView = $CU->getConfigurationLayout('default_detail_view');
$enableSwitchView = $CU->getConfigurationLayout('enable_switch_detail_view');
($enableSwitchView) ? $view = $_REQUEST['view'] : $view = null;
if (empty($view)) $view = $defaultView;
($view == 'summary') ? $summary = true : $summary = false;
$smarty->assign("SHOW_DETAILS_BUTTON", $enableSwitchView);
$smarty->assign("OLD_STYLE", $CU->getConfigurationLayout('old_style'));
//crmv@57221e

// crmv@104568
$panelid = getCurrentPanelId($currentModule);
$smarty->assign("PANELID", $panelid);
$panelsAndBlocks = getPanelsAndBlocks($currentModule, $record);
$smarty->assign("PANEL_BLOCKS", Zend_Json::encode($panelsAndBlocks));
// crmv@104568e

$smarty->assign("BLOCKS", getBlocks($currentModule,'detail_view',$view,$focus->column_fields));
$smarty->assign("SUMMARY", $summary);

// Gather the custom link information to display
include_once('vtlib/Vtecrm/Link.php');
$customlink_params = Array('MODULE'=>$currentModule, 'RECORD'=>$focus->id, 'ACTION'=>vtlib_purify($_REQUEST['action']));
$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('DETAILVIEWBASIC','DETAILVIEW','DETAILVIEWWIDGET'), $customlink_params));
// END

// Record Change Notification
$focus->markAsViewed($current_user->id);
// END

$smarty->assign('DETAILVIEW_AJAX_EDIT', PerformancePrefs::getBoolean('DETAILVIEW_AJAX_EDIT', true));

//crmv@169305 code removed

if (isInventoryModule($currentModule)) {
	$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
	$smarty->assign("ASSOCIATED_PRODUCTS",$InventoryUtils->getDetailAssociatedProducts($currentModule,$focus));
}

//crmv@44323
if (method_exists($focus, 'getExtraDetailBlock')) {
	$smarty->assign("EXTRADETAILBLOCK", $focus->getExtraDetailBlock());
}
//crmv@44323e
//crmv@45699 crmv@104568
if (method_exists($focus, 'getDetailTabs')) {
	$smarty->assign("DETAILTABS", $focus->getDetailTabs());
}
//crmv@45699e crmv@104568e

//crmv@62394
require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');
if (CalendarTracking::isEnabledForDetailview($currentModule)) {
	$smarty->assign('SHOW_DETAIL_TRACKER', true);
	$smarty->assign('TRACKER_DATA', CalendarTracking::getTrackerData($currentModule, $focus->id));
}
//crmv@62394e

//crmv@93990
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
$PMUtils = ProcessMakerUtils::getInstance();
$smarty->assign('RELATED_PROCESS', $PMUtils->getProcessRelatedTo($focus->id,'processesid'));
//crmv@93990e

//crmv@112297 crmv@142262
$condFields = array();
$conditionalsFocus = CRMEntity::getInstance('Conditionals');
$enable = $conditionalsFocus->existsConditionalPermissions($currentModule, $focus, $condFields);
if ($enable) {
	$smarty->assign('AJAXONCLICKFUNCT', 'ProcessMakerScript.checkAjaxSave');
	$smarty->assign('CONDITIONAL_FIELDS', $condFields);
}
//crmv@112297e crmv@142262e

$smarty->assign('HIDE_BUTTON_CREATE', true); // crmv@140887
$smarty->assign('OPEN_MYNOTES_POPUP', intval($_REQUEST['openNote'])); // crmv@146652

// crmv@151071
$pdfMaker = CRMEntity::getInstance('PDFMaker');
$pdfDetails = $pdfMaker->getPDFMakerDetails($currentModule, $focus->id);
if (count($pdfDetails['templates']) > 0) {
	$smarty->assign('PDFMAKER_ACTIVE', true);
}
// crmv@151071e

// crmv@167019
$RM = RelationManager::getInstance();
$relatedModules = $RM->getRelatedModules($currentModule);
if (in_array('Documents', $relatedModules) || $currentModule === 'Documents') {
	$smarty->assign('DROPAREA_ACTIVE', true);
}
// crmv@167019e

// crmv@171524 crmv@196871
$triggerQueueManager = TriggerQueueManager::getInstance();
if ($triggerQueueManager->isStompEnabled()) {
	$smarty->assign('STOMP_ENABLED', true);
	$smarty->assign('IS_FREEZED', $triggerQueueManager->checkFreezed($focus->id));
	$smarty->assign('STOMP_CONNECTION', Zend_Json::encode($triggerQueueManager->getConnectionParams('stomp')));
	$smarty->assign('IS_FETCHING', false);
	if (isset($_REQUEST['fetch_only']) && !empty($_REQUEST['fetch_only'])) {
		$smarty->assign('IS_FETCHING', true);
		if ($_REQUEST['fetch_only'] === 'navbar') {
			include_once('modules/SDK/src/Favorites/Utils.php');
			$smarty->assign('FETCH_ONLY_NAVBAR', true);
			$html = $smarty->fetch('Buttons_List_Detail.tpl');
			$data = array('success' => true, 'html' => $html);
			echo Zend_Json::encode($data);
			exit();
		}
	}
} else {
	$smarty->assign('STOMP_ENABLED', false);
}
// crmv@171524e crmv@196871e

$smarty_template = 'DetailView.tpl';

$sdk_custom_file = 'DetailViewCustomisations';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
@include("modules/$currentModule/$sdk_custom_file.php");

$smarty->display($smarty_template);

?>
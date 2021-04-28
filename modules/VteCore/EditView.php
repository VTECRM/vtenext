<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@64542 - support for inventory modules
 
global $app_strings, $mod_strings, $current_language, $currentModule, $theme, $log, $table_prefix;
global $processMakerView; // crmv@168528

require_once('include/CustomFieldUtil.php');
require_once('include/FormValidationUtil.php');

$log->info("$currentModule detail view");

$focus = CRMEntity::getInstance($currentModule);
$smarty = new VteSmarty();

$category = getParentTab($currentModule);
$record = intval($_REQUEST['record']); // crmv@37463
$isduplicate = vtlib_purify($_REQUEST['isDuplicate']);

$isInventory = isInventoryModule($currentModule);

if ($isInventory) {
	$InventoryUtils = InventoryUtils::getInstance();
	
	$currencyid=fetchCurrency($current_user->id);
	$rate_symbol = getCurrencySymbolandCRate($currencyid);
	$rate = $rate_symbol['rate'];
}

//added to fix the issue4600
$searchurl = getBasic_Advance_SearchURL();
$smarty->assign("SEARCH", $searchurl);
//4600 ends

if($record) {
	$focus->id = $record;
	$focus->mode = 'edit';
	$focus->retrieve_entity_info($record, $currentModule);
	$focus->firstname=$focus->column_fields['firstname'];
    $focus->lastname=$focus->column_fields['lastname'];
}
if($isduplicate == 'true') {
	$smarty->assign('NAME', $focus->getRecordName());	//crmv@161550
	$smarty->assign("DUPLICATE_FROM", $focus->id); // crmv@64542 - this is used for inventory modules
	$focus->id = '';
	$focus->mode = '';
}
if(empty($record) && $focus->mode != 'edit'){
	setObjectValuesFromRequest($focus);
}

$disp_view = getView($focus->mode);
//crmv@9434
$mode = $focus->mode;
//crmv@9434 end

// crmv@104568
$panelid = getCurrentPanelId($currentModule);
$smarty->assign("PANELID", $panelid);
$panelsAndBlocks = getPanelsAndBlocks($currentModule, $record);
$smarty->assign("PANEL_BLOCKS", Zend_Json::encode($panelsAndBlocks));
if ($InventoryUtils) {
	$binfo = $InventoryUtils->getInventoryBlockInfo($currentModule);
	$smarty->assign('PRODBLOCKINFO', $binfo);
	$smarty->assign('SHOWPROTAB', !$processMakerView); // crmv@168528
}

$smarty->assign("BLOCKS",getBlocks($currentModule,$disp_view,$mode,$focus->column_fields,'',$blockVisibility));	//crmv@99316
$smarty->assign('BLOCKVISIBILITY', $blockVisibility);	//crmv@99316

if($disp_view != 'edit_view') {
	//merge check - start
	$smarty->assign("MERGE_USER_FIELDS",implode(',',get_merge_user_fields($currentModule))); //crmv_utils
	//ends
}
// crmv@104568e

$smarty->assign('OP_MODE',$disp_view);
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
// TODO: Update Single Module Instance name here.
$smarty->assign('SINGLE_MOD', 'SINGLE_'.$currentModule);
$smarty->assign('CATEGORY', $category);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('ID', $focus->id);
$smarty->assign('MODE', $focus->mode);
$smarty->assign('CREATEMODE', vtlib_purify($_REQUEST['createmode']));

// crmv@42752
if ($_REQUEST['hide_button_list'] == '1') {
	$smarty->assign('HIDE_BUTTON_LIST', '1');
}
// crmv@42752e

if(isset($cust_fld))
{
	$smarty->assign("CUSTOMFIELD", $cust_fld);
}

$tool_buttons = Button_Check($currentModule);
$tool_buttons['moduleSettings'] = 'no'; // crmv@140887

$smarty->assign('CHECK', $tool_buttons);
$smarty->assign('DUPLICATE', $isduplicate);

if ($focus->mode == 'edit' || $isduplicate == 'true') { // crmv@191459
	if ($isduplicate != 'true') $smarty->assign('NAME', $focus->getRecordName());	//crmv@104310 crmv@161550 crmv@191459
	$smarty->assign('UPDATEINFO',updateInfo($record));
}

if(isset($_REQUEST['campaignid']))		 $smarty->assign("campaignid",vtlib_purify($_REQUEST['campaignid']));
if(isset($_REQUEST['return_module']))    $smarty->assign("RETURN_MODULE", vtlib_purify($_REQUEST['return_module']));
if(isset($_REQUEST['return_action']))    $smarty->assign("RETURN_ACTION", vtlib_purify($_REQUEST['return_action']));
if(isset($_REQUEST['return_id']))        $smarty->assign("RETURN_ID", vtlib_purify($_REQUEST['return_id']));
if (isset($_REQUEST['return_viewname'])) $smarty->assign("RETURN_VIEWNAME", vtlib_purify($_REQUEST['return_viewname']));

// crmv@83877 crmv@112297
// Field Validation Information
$tabid = getTabid($currentModule);
$otherInfo = array();
$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus);	//crmv@96450
$validationArray = split_validationdataArray($validationData, $otherInfo);
$smarty->assign("VALIDATION_DATA_FIELDNAME",$validationArray['fieldname']);
$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",$validationArray['datatype']);
$smarty->assign("VALIDATION_DATA_FIELDLABEL",$validationArray['fieldlabel']);
$smarty->assign("VALIDATION_DATA_FIELDUITYPE",$validationArray['fielduitype']);
$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",$validationArray['fieldwstype']);
// crmv@83877e crmv@112297e

//crmv@45699 crmv@104568
if (method_exists($focus, 'getEditTabs')) {
	$smarty->assign("EDITTABS", $focus->getEditTabs());
}
//crmv@45699e crmv@104568e

// In case you have a date field
$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("CALENDAR_DATEFORMAT", parse_calendardate($app_strings['NTC_DATE_FORMAT']));

global $adb;
// Module Sequence Numbering
$mod_seq_field = getModuleSequenceField($currentModule);
if($focus->mode != 'edit' && $mod_seq_field != null) {
	$autostr = getTranslatedString('MSG_AUTO_GEN_ON_SAVE');
	//crmv@154715
	if ($focus->isBUMCInstalled($currentModule)) {
		$smarty->assign("MOD_SEQ_ID",$autostr);
	} else {
	//crmv@154715e
		$mod_seq_string = $adb->pquery("SELECT prefix, cur_id from {$table_prefix}_modentity_num where semodule = ? and active=1",array($currentModule));
		$mod_seq_prefix = $adb->query_result($mod_seq_string,0,'prefix');
		$mod_seq_no = $adb->query_result($mod_seq_string,0,'cur_id');
		// crmv@173148
		$table_seq_field = $focus->table_name;
	    $res_seq = $adb->pquery("select tablename from {$table_prefix}_field where columnname = ?",array($mod_seq_field['column']));
	    if ($res_seq && $adb->num_rows($res_seq) == 1) {
			$table_seq_field = $adb->query_result_no_html($res_seq,0,'tablename');
	    }
	    // crmv@173148e
		if ($adb->num_rows($mod_seq_string) == 0 || $focus->checkModuleSeqNumber($table_seq_field, $mod_seq_field['column'], $mod_seq_prefix.$mod_seq_no)) {  // crmv@173148e
			//crmv@122082
			$error_str = getTranslatedString('LBL_DUPLICATE').' '.getTranslatedString($mod_seq_field['label'],$currentModule).'. '.sprintf(getTranslatedString('LBL_CLICK_TO_CONFIGURE_MODENTITYNUM'),'index.php?module=Settings&action=CustomModEntityNo&parenttab=Settings&selmodule='.$currentModule,getTranslatedString('LBL_HERE'),getTranslatedString($mod_seq_field['label'],$currentModule));
			$smarty->assign("ERROR_STR",$error_str);
			//crmv@122082e
		} else {
			$smarty->assign("MOD_SEQ_ID",$autostr);
		}
	}	//crmv@154715
} else {
	$smarty->assign("MOD_SEQ_ID", $focus->column_fields[$mod_seq_field['name']]);
}
// END

// Gather the help information associated with fields
$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));
// END

if ($isInventory) {
	$smarty->assign("CURRENCIES_LIST", $InventoryUtils->getAllCurrencies());
	if($focus->mode == 'edit') {
		$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo($currentModule, $focus->id);
		$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
	} elseif($_REQUEST['isDuplicate'] == 'true') {
		$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo($currentModule, $duplicate_from);
		$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
	} else {
		$smarty->assign("INV_CURRENCY_ID", $currencyid);
	}
}


//crmv@57221
$CU = CRMVUtils::getInstance();
$smarty->assign("OLD_STYLE", $CU->getConfigurationLayout('old_style'));
//crmv@57221e

//crmv@92272
if ($_REQUEST['mass_edit_mode'] == '1') {
	$smarty->assign('MASS_EDIT','1');
}
//crmv@92272e

//crmv@100495
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
$PMUtils = ProcessMakerUtils::getInstance();
if ($PMUtils->showRunProcessesButton($currentModule, $focus->id)) $smarty->assign('SHOW_RUN_PROCESSES_BUTTON',true);
//crmv@100495e

//crmv@112297 crmv@134058
if ($_REQUEST['disable_conditionals'] != '1') {
	$conditionalsFocus = CRMEntity::getInstance('Conditionals');
	$condFields = array();
	$smarty->assign('ENABLE_CONDITIONALS', $conditionalsFocus->existsConditionalPermissions($currentModule, $focus, $condFields));
	$smarty->assign('CONDITIONAL_FIELDS', $condFields);
}
//crmv@112297e crmv@134058e

//crmv@115268 Gather the custom link information to display
include_once('vtlib/Vtecrm/Link.php');
$customlink_params = Array('MODULE'=>$currentModule, 'RECORD'=>$focus->id, 'ACTION'=>vtlib_purify($_REQUEST['action']));
$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('EDITVIEWWIDGET'), $customlink_params));
//crmv@115268e

$smarty->assign('HIDE_BUTTON_CREATE', true); // crmv@140887

$smarty->assign('FOCUS_ON_FIELD', $_REQUEST['focus_on_field']); // crmv@198388

?>
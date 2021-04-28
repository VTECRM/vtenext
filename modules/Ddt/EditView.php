<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@161211 */
global $app_strings, $mod_strings, $current_language, $currentModule, $theme, $processMakerView;
global $table_prefix;

$focus = CRMEntity::getInstance($currentModule);
$smarty = new VteSmarty();

$category = getParentTab($currentModule);
$record = $_REQUEST['record'];
$isduplicate = vtlib_purify($_REQUEST['isDuplicate']);

//added to fix the issue4600
$searchurl = getBasic_Advance_SearchURL();
$smarty->assign("SEARCH", $searchurl);
//4600 ends

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

//crmv@18498
$currencyid = fetchCurrency($current_user->id);
$rate_symbol = getCurrencySymbolandCRate($currencyid);
$rate = $rate_symbol['rate'];
//crmv@18498e

if (!$processMakerView) {
	if($record) {
		//crmv@18498
		if(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'sotoddt') {
			$soid = $record;
			$so_focus = CRMEntity::getInstance('SalesOrder');
			$so_focus->id = $soid;
			$so_focus->retrieve_entity_info($soid,"SalesOrder");
			$focus->getConvertSalesOrderToDdt($so_focus);
			
			// Reset the value w.r.t SalesOrder Selected
			$currencyid = $so_focus->column_fields['currency_id'];
			$rate = $so_focus->column_fields['conversion_rate'];
			
			$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$so_focus);	//crmv@30721
			$txtTax = (($so_focus->column_fields['txtTax'] != '')?$so_focus->column_fields['txtTax']:'0.000');
			$txtAdj = (($so_focus->column_fields['txtAdjustment'] != '')?$so_focus->column_fields['txtAdjustment']:'0.000');
			
			$smarty->assign("MODE", $so_focus->mode);
		}
		else {
			//crmv@18498e
			$focus->id = $record;
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($record, $currentModule);
		}
	}
	if($isduplicate == 'true') {
		$duplicate_from = $focus->id;	//crmv@38845
		//crmv@18498
		$smarty->assign("DUPLICATE_FROM", $focus->id);
		$Ddt_final_details = $InventoryUtils->getFinalDetails($currentModule,$focus);	//crmv@30721
		//crmv@18498e
		$focus->id = '';
		$focus->mode = '';
	}
}
if(empty($_REQUEST['record']) && $focus->mode != 'edit'){
	setObjectValuesFromRequest($focus);
}

// crmv@104568
$panelid = getCurrentPanelId($currentModule);
$smarty->assign("PANELID", $panelid);
$panelsAndBlocks = getPanelsAndBlocks($currentModule);
$smarty->assign("PANEL_BLOCKS", Zend_Json::encode($panelsAndBlocks));
$binfo = $InventoryUtils->getInventoryBlockInfo($currentModule);
$smarty->assign('PRODBLOCKINFO', $binfo);

$disp_view = getView($focus->mode);
$smarty->assign('BLOCKS', getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields, '', $blockVisibility));
$smarty->assign('BLOCKVISIBILITY', $blockVisibility);	//crmv@99316

if ($disp_view != 'edit_view') {
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

// $smarty->assign('CHECK', Button_Check($currentModule)); // crmv@140887
$smarty->assign('DUPLICATE', $isduplicate);

if ($focus->mode == 'edit' || $isduplicate == 'true') { // crmv@191459
	if ($isduplicate != 'true') $smarty->assign('NAME', $focus->getRecordName());	//crmv@104310 crmv@161550 crmv@191459
	$smarty->assign('UPDATEINFO',updateInfo($record));
}
//crmv@18498	//crmv@30721
if ($focus->mode == 'edit') {
	$final_details = $InventoryUtils->getFinalDetails($currentModule,$focus);	//crmv@30721
} elseif ($isduplicate == 'true') { // crmv@191459
	$final_details = $Ddt_final_details;	//crmv@30721
}
//crmv@18498	//crmv@30721

if(isset($_REQUEST['return_module']))    $smarty->assign("RETURN_MODULE", vtlib_purify($_REQUEST['return_module']));
if(isset($_REQUEST['return_action']))    $smarty->assign("RETURN_ACTION", vtlib_purify($_REQUEST['return_action']));
if(isset($_REQUEST['return_id']))        $smarty->assign("RETURN_ID", vtlib_purify($_REQUEST['return_id']));
if (isset($_REQUEST['return_viewname'])) $smarty->assign("RETURN_VIEWNAME", vtlib_purify($_REQUEST['return_viewname']));

//crmv@30721
if (empty($final_details)) {
	$final_details = $InventoryUtils->getFinalDetails($currentModule, $focus);
}
$smarty->assign("FINAL_DETAILS", $final_details);
//crmv@30721e

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
		$mod_seq_string = $adb->pquery("SELECT prefix, cur_id from ".$table_prefix."_modentity_num where semodule = ? and active=1",array($currentModule));
		$mod_seq_prefix = $adb->query_result($mod_seq_string,0,'prefix');
		$mod_seq_no = $adb->query_result($mod_seq_string,0,'cur_id');
		if($adb->num_rows($mod_seq_string) == 0 || $focus->checkModuleSeqNumber($focus->table_name, $mod_seq_field['column'], $mod_seq_prefix.$mod_seq_no)) {
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

// crmv@43864
if ($_REQUEST['hide_button_list'] == '1') {
	$smarty->assign('HIDE_BUTTON_LIST', '1');
}
// crmv@43864e

//crmv@57221
$CU = CRMVUtils::getInstance();
$smarty->assign("OLD_STYLE", $CU->getConfigurationLayout('old_style'));
//crmv@57221e

//crmv@124836
if ($_REQUEST['mass_edit_mode'] == '1') {
	$smarty->assign('MASS_EDIT','1');
}
//crmv@124836e

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

$smarty->assign('SHOWPROTAB', !$processMakerView);

//crmv@18498	//crmv@38845
$smarty->assign("CURRENCIES_LIST", $InventoryUtils->getAllCurrencies());
if($focus->mode == 'edit') {
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo($currentModule, $focus->id);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
} elseif($isduplicate == 'true') { // crmv@191459
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo($currentModule, $duplicate_from);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
} else {
	$smarty->assign("INV_CURRENCY_ID", $currencyid);
}

$smarty->display("Inventory/InventoryEditView.tpl");
//crmv@18498e	//crmv@38845e
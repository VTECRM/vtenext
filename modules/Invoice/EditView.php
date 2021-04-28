<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@161211 */
require_once ('data/Tracker.php');
require_once ('include/CustomFieldUtil.php');
require_once ('include/utils/utils.php');

global $app_strings, $mod_strings, $currentModule, $log, $current_user, $processMakerView;
global $table_prefix;
$focus = CRMEntity::getInstance($currentModule);
$smarty = new VteSmarty();
//added to fix the issue4600
$searchurl = getBasic_Advance_SearchURL();
$smarty->assign("SEARCH", $searchurl);
//4600 ends

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

$currencyid = fetchCurrency($current_user->id);
$rate_symbol = getCurrencySymbolandCRate($currencyid);
$rate = $rate_symbol['rate'];

if (!$processMakerView) {
	if(isset($_REQUEST['record']) && $_REQUEST['record'] != '') {
		if(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'quotetoinvoice') {
			$quoteid = $_REQUEST['record'];
			$quote_focus = CRMEntity::getInstance('Quotes');
			$quote_focus->id = $quoteid;
			$quote_focus->retrieve_entity_info($quoteid,"Quotes");
			$focus = getConvertQuoteToInvoice($focus,$quote_focus,$quoteid);
			
			// Reset the value w.r.t Quote Selected
			$currencyid = $quote_focus->column_fields['currency_id'];
			$rate = $quote_focus->column_fields['conversion_rate'];
			
			$final_details = $InventoryUtils->getFinalDetails("Quotes",$quote_focus);	//crmv@30721
			$txtTax = (($quote_focus->column_fields['txtTax'] != '')?$quote_focus->column_fields['txtTax']:'0.000');
			$txtAdj = (($quote_focus->column_fields['txtAdjustment'] != '')?$quote_focus->column_fields['txtAdjustment']:'0.000');
			
			$smarty->assign("CONVERT_MODE", vtlib_purify($_REQUEST['convertmode']));
			$smarty->assign("MODE", $quote_focus->mode);
		}
		elseif(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'ttickettoiv') {
			include('modules/SalesOrder/convertTT2SO.php');
			$tticketid = $_REQUEST['record'];
			$tticket_focus = CRMEntity::getInstance('HelpDesk');
			$tticket_focus->id = $tticketid;
			$tticket_focus->retrieve_entity_info($tticketid,"HelpDesk");
			$focus = getConvertTTicketToSoObject($focus,$tticket_focus,$tticketid);
			if ($focus=='Err_NoAccountContact' || $focus=='Err_ContactWithoutAccount') {
				echo "<br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>".getTranslatedString($focus,'Timecards')."</em>&nbsp;&nbsp;<a href='javascript:window.history.back();'>".$app_strings['LBL_GO_BACK']."</a><br>";
				return false;
			}
			$focus->id = $tticketid;
			
			$final_details = $InventoryUtils->getFinalDetails("HelpDesk",$tticket_focus);	//crmv@30721
			$smarty->assign("MODE", $tticket_focus->mode);
			unset($_REQUEST['product_id']);
			unset($_REQUEST['return_id']);
		}
		elseif(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'sotoinvoice') {
			$soid = $_REQUEST['record'];
			$so_focus = CRMEntity::getInstance('SalesOrder');
			$so_focus->id = $soid;
			$so_focus->retrieve_entity_info($soid,"SalesOrder");
			$focus = getConvertSoToInvoice($focus,$so_focus,$soid);
			
			// Reset the value w.r.t SalesOrder Selected
			$currencyid = $so_focus->column_fields['currency_id'];
			$rate = $so_focus->column_fields['conversion_rate'];
			
			//added to set the PO number and terms and conditions
			$focus->column_fields[$table_prefix.'_purchaseorder'] = $so_focus->column_fields[$table_prefix.'_purchaseorder'];
			$focus->column_fields['terms_conditions'] = $so_focus->column_fields['terms_conditions'];
			
			$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$so_focus);	//crmv@30721
			$txtTax = (($so_focus->column_fields['txtTax'] != '')?$so_focus->column_fields['txtTax']:'0.000');
			$txtAdj = (($so_focus->column_fields['txtAdjustment'] != '')?$so_focus->column_fields['txtAdjustment']:'0.000');
			
			$smarty->assign("CONVERT_MODE", vtlib_purify($_REQUEST['convertmode']));
			$smarty->assign("MODE", $so_focus->mode);
		}
		//crmv@18498
		elseif(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'ddttoinvoice') {
			$ddtid = $_REQUEST['record'];
			$ddt_focus = CRMEntity::getInstance('Ddt');
			$ddt_focus->id = $ddtid;
			$ddt_focus->retrieve_entity_info($ddtid,"Ddt");
			$focus = $ddt_focus->getConvertDdtToInvoice($focus);
			
			// Reset the value w.r.t SalesOrder Selected
			$currencyid = $ddt_focus->column_fields['currency_id'];
			$rate = $ddt_focus->column_fields['conversion_rate'];
			
			$final_details = $InventoryUtils->getFinalDetails("Ddt",$ddt_focus);	//crmv@30721
			$txtTax = (($ddt_focus->column_fields['txtTax'] != '')?$ddt_focus->column_fields['txtTax']:'0.000');
			$txtAdj = (($ddt_focus->column_fields['txtAdjustment'] != '')?$ddt_focus->column_fields['txtAdjustment']:'0.000');
			
			$smarty->assign("CONVERT_MODE", vtlib_purify($_REQUEST['convertmode']));
			$smarty->assign("MODE", $ddt_focus->mode);
		}
		//crmv@18498e
		elseif(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'potentoinvoice') {
			$focus->mode = '';
		}
		elseif(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'update_so_val') {
			//Updating the Selected SO Value in Edit Mode
			foreach($focus->column_fields as $fieldname => $val) {
				if(isset($_REQUEST[$fieldname])){
					$value = $_REQUEST[$fieldname];
					$focus->column_fields[$fieldname] = $value;
				}
				
			}
			//Handling for dateformat in vte_invoicedate vte_field
			if($focus->column_fields['invoicedate'] != '') {
				$curr_due_date = $focus->column_fields['invoicedate'];
				$focus->column_fields['invoicedate'] = getDBInsertDateValue($curr_due_date);
			}
			
			$soid = $focus->column_fields['salesorder_id'];
			$so_focus = CRMEntity::getInstance('SalesOrder');
			$so_focus->id = $soid;
			$so_focus->retrieve_entity_info($soid,"SalesOrder");
			$focus = getConvertSoToInvoice($focus,$so_focus,$soid);
			$focus->id = $_REQUEST['record'];
			$focus->mode = 'edit';
			$focus->name=$focus->column_fields['subject'];
			
			$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$so_focus);	//crmv@30721
			// Reset the value w.r.t SalesOrder Selected
			$currencyid = $so_focus->column_fields['currency_id'];
			$rate = $so_focus->column_fields['conversion_rate'];
			$smarty->assign("SALESORDER_ID", $focus->column_fields['salesorder_id']);
		} else {
			$focus->id = $_REQUEST['record'];
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($_REQUEST['record'],"Invoice");
			$focus->name=$focus->column_fields['subject'];
		}
	}
	else
	{
		if(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'update_so_val') {
			//Updating the Selected SO Value in Create Mode
			foreach($focus->column_fields as $fieldname => $val) {
				if(isset($_REQUEST[$fieldname])) {
					$value = $_REQUEST[$fieldname];
					$focus->column_fields[$fieldname] = $value;
				}
			}
			//Handling for dateformat in vte_invoicedate vte_field
			if($focus->column_fields['invoicedate'] != '') {
				$curr_due_date = $focus->column_fields['invoicedate'];
				$focus->column_fields['invoicedate'] = getDBInsertDateValue($curr_due_date);
			}
			
			$soid = $focus->column_fields['salesorder_id'];
			$so_focus = CRMEntity::getInstance('SalesOrder');
			$so_focus->id = $soid;
			$so_focus->retrieve_entity_info($soid,"SalesOrder");
			$focus = getConvertSoToInvoice($focus,$so_focus,$soid);
			
			// Reset the value w.r.t SalesOrder Selected
			$currencyid = $so_focus->column_fields['currency_id'];
			$rate = $so_focus->column_fields['conversion_rate'];
			
			//crmv@30721
			if(isset($_REQUEST['salesorder_id']) && $_REQUEST['salesorder_id'] !='') {
				$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$so_focus);	//crmv@30721
			}
			
			$smarty->assign("SALESORDER_ID", $focus->column_fields['salesorder_id']);
			$smarty->assign("MODE", $so_focus->mode);
		}
	}
	if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
		$duplicate_from = $focus->id;	//crmv@38845
		$smarty->assign("DUPLICATE_FROM", $focus->id);
		$INVOICE_final_details = $InventoryUtils->getFinalDetails("Invoice",$focus);	//crmv@30721
		$focus->id = "";
		$focus->mode = '';
	}
}
if(empty($_REQUEST['record']) && $focus->mode != 'edit'){
	setObjectValuesFromRequest($focus);
}
if (!$processMakerView) {
	if(isset($_REQUEST['opportunity_id']) && $_REQUEST['opportunity_id'] !='') {
		$potfocus = CRMEntity::getInstance('Potentials');
		$potfocus->column_fields['potential_id'] = $_REQUEST['opportunity_id'];
		$final_details = $InventoryUtils->getFinalDetails("Potentials",$potfocus,$potfocus->column_fields['potential_id']);	//crmv@30721
	}
	if(isset($_REQUEST['product_id']) && $_REQUEST['product_id'] != '') {
		$focus->column_fields['product_id'] = $_REQUEST['product_id'];
		$log->debug("Invoice EditView: Product Id from the request is ".$_REQUEST['product_id']);
		$final_details = $InventoryUtils->getFinalDetails("Products",$focus,$focus->column_fields['product_id']);	//crmv@30721
	}
	
	if (!empty ($_REQUEST['parent_id']) && !empty ($_REQUEST['return_module'])) {
		if ($_REQUEST['return_module'] == 'Services') {
			$focus->column_fields['product_id'] = $_REQUEST['parent_id'];
			$log->debug("Service Id from the request is " . $_REQUEST['parent_id']);
			$final_details = $InventoryUtils->getFinalDetails("Services", $focus, $focus->column_fields['product_id']);	//crmv@30721
		}
	}
	
	if(isset($_REQUEST['account_id']) && $_REQUEST['account_id'] != '' && is_numeric($_REQUEST['account_id']) && ($_REQUEST['record'] == '' || $_REQUEST['convertmode'] == "potentoinvoice") && ($_REQUEST['convertmode'] != 'update_so_val') ){ //crmv@160371
		$acct_focus = CRMEntity::getInstance('Accounts');
		$acct_focus->retrieve_entity_info($_REQUEST['account_id'],"Accounts",true);
		$focus->column_fields['bill_city']=$acct_focus->column_fields['bill_city'];
		$focus->column_fields['ship_city']=$acct_focus->column_fields['ship_city'];
		$focus->column_fields['bill_street']=$acct_focus->column_fields['bill_street'];
		$focus->column_fields['ship_street']=$acct_focus->column_fields['ship_street'];
		$focus->column_fields['bill_state']=$acct_focus->column_fields['bill_state'];
		$focus->column_fields['ship_state']=$acct_focus->column_fields['ship_state'];
		$focus->column_fields['bill_code']=$acct_focus->column_fields['bill_code'];
		$focus->column_fields['ship_code']=$acct_focus->column_fields['ship_code'];
		$focus->column_fields['bill_country']=$acct_focus->column_fields['bill_country'];
		$focus->column_fields['ship_country']=$acct_focus->column_fields['ship_country'];
		$focus->column_fields['bill_pobox']=$acct_focus->column_fields['bill_pobox'];
		$focus->column_fields['ship_pobox']=$acct_focus->column_fields['ship_pobox'];
	}
}

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
//retreiving the combo values array
$comboFieldNames = Array('accounttype'=>'account_type_dom'
		,'industry'=>'industry_dom');
$comboFieldArray = getComboArray($comboFieldNames);

$disp_view = getView($focus->mode);
//crmv@9434
$mode = $focus->mode;
//crmv@9434 end

// crmv@104568
$panelid = getCurrentPanelId($currentModule);
$smarty->assign("PANELID", $panelid);
$panelsAndBlocks = getPanelsAndBlocks($currentModule);
$smarty->assign("PANEL_BLOCKS", Zend_Json::encode($panelsAndBlocks));
$smarty->assign("BLOCKS",getBlocks($currentModule,$disp_view,$mode,$focus->column_fields));
if ($InventoryUtils) {
	$binfo = $InventoryUtils->getInventoryBlockInfo($currentModule);
	$smarty->assign('PRODBLOCKINFO', $binfo);
}
// crmv@104568e

$smarty->assign("OP_MODE",$disp_view);

$smarty->assign("MODULE",$currentModule);
$smarty->assign("SINGLE_MOD",'Invoice');

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);

$log->info("Invoice view");

if (isset($focus->name))
	$smarty->assign("NAME", $focus->name);
else
	$smarty->assign("NAME", "");
	
if(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'quotetoinvoice') {
	$smarty->assign("MODE", $quote_focus->mode);
	$se_array = $InventoryUtils->getProductDetailsBlockInfo($quote_focus->mode,"Quotes",$quote_focus); // crmv@42024
}
elseif(isset($_REQUEST['convertmode']) &&  ($_REQUEST['convertmode'] == 'sotoinvoice' || $_REQUEST['convertmode'] == 'update_so_val')) {
	$smarty->assign("MODE", $focus->mode);
	$se_array = $InventoryUtils->getProductDetailsBlockInfo($focus->mode,"SalesOrder",$so_focus); // crmv@42024
	
	$txtTax = (($so_focus->column_fields['txtTax'] != '')?$so_focus->column_fields['txtTax']:'0.000');
	$txtAdj = (($so_focus->column_fields['txtAdjustment'] != '')?$so_focus->column_fields['txtAdjustment']:'0.000');
	
	$smarty->assign("MODE", $focus->mode);
	$final_details = $InventoryUtils->getFinalDetails('SalesOrder', $so_focus);	//crmv@30721
}
elseif($focus->mode == 'edit') {
	$smarty->assign("UPDATEINFO",updateInfo($focus->id));
	$smarty->assign("MODE", $focus->mode);
	$final_details = $InventoryUtils->getFinalDetails('Invoice', $focus);	//crmv@30721
}
elseif(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$smarty->assign("MODE", $focus->mode);
	$final_details = $INVOICE_final_details;	//crmv@30721
}
elseif((isset($_REQUEST['product_id']) && $_REQUEST['product_id'] != '') || (isset($_REQUEST['opportunity_id']) && $_REQUEST['opportunity_id'] != '')) {
	$InvTotal = getInventoryTotal($_REQUEST['return_module'],$_REQUEST['return_id']);
	$smarty->assign("MODE", $focus->mode);
	
	//this is to display the Product Details in first row when we create new PO from Product relatedlist
	if($_REQUEST['return_module'] == 'Products') {
		$smarty->assign("PRODUCT_ID", vtlib_purify($_REQUEST['product_id']));
		$smarty->assign("PRODUCT_NAME", getProductName($_REQUEST['product_id']));
		$smarty->assign("UNIT_PRICE", vtlib_purify($_REQUEST['product_id']));
		$smarty->assign("QTY_IN_STOCK", $InventoryUtils->getPrdQtyInStck($_REQUEST['product_id'])); // crmv@42024
		$smarty->assign("VAT_TAX", $InventoryUtils->getProductTaxPercentage("VAT", $_REQUEST['product_id']));
		$smarty->assign("SALES_TAX", $InventoryUtils->getProductTaxPercentage("Sales", $_REQUEST['product_id']));
		$smarty->assign("SERVICE_TAX", $InventoryUtils->getProductTaxPercentage("Service", $_REQUEST['product_id']));
		$smarty->assign("row_no",1);	//crmv@47104
	}
}

if(isset($cust_fld)) {
	$smarty->assign("CUSTOMFIELD", $cust_fld);
}

//crmv@30721
if (empty($final_details)) {
	$final_details = $InventoryUtils->getFinalDetails($currentModule, $focus);
}
$smarty->assign("FINAL_DETAILS", $final_details);
//crmv@30721e

if (isset ($_REQUEST['return_module']))
	$smarty->assign("RETURN_MODULE", vtlib_purify($_REQUEST['return_module']));
else
	$smarty->assign("RETURN_MODULE", "Invoice");
if (isset ($_REQUEST['return_action']))
	$smarty->assign("RETURN_ACTION", vtlib_purify($_REQUEST['return_action']));
else
	$smarty->assign("RETURN_ACTION", "index");

if (isset ($_REQUEST['return_id']))
	$smarty->assign("RETURN_ID", vtlib_purify($_REQUEST['return_id']));
if (isset ($_REQUEST['return_viewname']))
	$smarty->assign("RETURN_VIEWNAME", vtlib_purify($_REQUEST['return_viewname']));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("ID", $focus->id);

$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("CALENDAR_DATEFORMAT", parse_calendardate($app_strings['NTC_DATE_FORMAT']));

$category = getParentTab();
$smarty->assign("CATEGORY",$category);

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
		$mod_seq_string = $adb->pquery("SELECT prefix, cur_id from ".$table_prefix."_modentity_num where semodule = ? and active=1", array($currentModule));
		$mod_seq_prefix = $adb->query_result($mod_seq_string,0,'prefix');
		$mod_seq_no = $adb->query_result($mod_seq_string,0,'cur_id');
		if ($adb->num_rows($mod_seq_string) == 0 || $focus->checkModuleSeqNumber($focus->table_name, $mod_seq_field['column'], $mod_seq_prefix . $mod_seq_no)) {
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

$smarty->assign("CURRENCIES_LIST", $InventoryUtils->getAllCurrencies());
//crmv@38845
if($focus->mode == 'edit') {
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Invoice', $focus->id);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
} elseif($_REQUEST['isDuplicate'] == 'true') {
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Invoice', $duplicate_from);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
} else {
	$smarty->assign("INV_CURRENCY_ID", $currencyid);
}
//crmv@38845e

// crmv@43864
if ($_REQUEST['hide_button_list'] == '1') {
	$smarty->assign('HIDE_BUTTON_LIST', '1');
}
// crmv@43864e

$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));

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

// crmv@140887
// $check_button = Button_Check($module);
// $smarty->assign("CHECK", $check_button);
// crmv@140887e

$smarty->assign("DUPLICATE",vtlib_purify($_REQUEST['isDuplicate']));
$smarty->assign('SHOWPROTAB', !$processMakerView);

$smarty->display("Inventory/InventoryEditView.tpl");
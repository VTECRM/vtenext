<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@161211 */
require_once ('include/CustomFieldUtil.php');

global $app_strings, $mod_strings, $log, $theme, $currentModule, $current_user, $processMakerView;
global $adb, $table_prefix;
$log->debug("Inside Sales Order EditView");

$focus = CRMEntity::getInstance($currentModule);
$smarty = new VteSmarty();
//added to fix the issue4600
$searchurl = getBasic_Advance_SearchURL();
$smarty->assign("SEARCH", $searchurl);
//4600 ends

if(empty($_REQUEST['record']) && $focus->mode != 'edit'){
	setObjectValuesFromRequest($focus);
}

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

$currencyid = fetchCurrency($current_user->id);
$rate_symbol = getCurrencySymbolandCRate($currencyid);
$rate = $rate_symbol['rate'];

if (!$processMakerView) {
	if (isset ($_REQUEST['record']) && $_REQUEST['record'] != '') {
		if (isset ($_REQUEST['convertmode']) && $_REQUEST['convertmode'] == 'quotetoso') {
			$quoteid = $_REQUEST['record'];
			$quote_focus = CRMEntity::getInstance('Quotes');
			$quote_focus->id = $quoteid;
			$quote_focus->retrieve_entity_info($quoteid, "Quotes");
			$focus = getConvertQuoteToSoObject($focus, $quote_focus, $quoteid);
			$focus->id = $quoteid;
	
			// Reset the value w.r.t Quote Selected
			$currencyid = $quote_focus->column_fields['currency_id'];
			$rate = $quote_focus->column_fields['conversion_rate'];
	
			$final_details = $InventoryUtils->getFinalDetails("Quotes",$quote_focus);	//crmv@30721
			$smarty->assign("CONVERT_MODE", $_REQUEST['convertmode']);
			$smarty->assign("QUOTE_ID", $quoteid);
			$smarty->assign("MODE", $quote_focus->mode);
		}
	    elseif(isset($_REQUEST['convertmode']) &&  $_REQUEST['convertmode'] == 'ttickettoso') {
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
		elseif (isset ($_REQUEST['convertmode']) && $_REQUEST['convertmode'] == 'update_quote_val') {
			//Updating the Selected Quote Value in Edit Mode
			foreach ($focus->column_fields as $fieldname => $val) {
				if (isset ($_REQUEST[$fieldname])) {
					$value = $_REQUEST[$fieldname];
					$focus->column_fields[$fieldname] = $value;
				}
			}
			//crmv@175737 Handling for dateformat
			$result = $adb->pquery("select fieldname from {$table_prefix}_field where tabid = ? and uitype = ?", array(getTabid($currentModule),5));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					if ($focus->column_fields[$row['fieldname']] != '') {
						$focus->column_fields[$row['fieldname']] = getDBInsertDateValue($focus->column_fields[$row['fieldname']]);
					}
				}
			}
			//crmv@175737e
	
			$quoteid = $focus->column_fields['quote_id'];
			$smarty->assign("QUOTE_ID", $focus->column_fields['quote_id']);
			$quote_focus = CRMEntity::getInstance('Quotes');
			$quote_focus->id = $quoteid;
			$quote_focus->retrieve_entity_info($quoteid, "Quotes");
			$focus = getConvertQuoteToSoObject($focus, $quote_focus, $quoteid);
			$focus->id = $_REQUEST['record'];
			$focus->mode = 'edit';
			$focus->name = $focus->column_fields['subject'];
	
			// Reset the value w.r.t Quote Selected
			$currencyid = $quote_focus->column_fields['currency_id'];
			$rate = $quote_focus->column_fields['conversion_rate'];
	
		} else {
			$focus->id = $_REQUEST['record'];
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($_REQUEST['record'], "SalesOrder");
			$focus->name = $focus->column_fields['subject'];
		}
	} else {
		if (isset ($_REQUEST['convertmode']) && $_REQUEST['convertmode'] == 'update_quote_val') {
			//Updating the Select Quote Value in Create Mode
			foreach ($focus->column_fields as $fieldname => $val) {
				if (isset ($_REQUEST[$fieldname])) {
					$value = $_REQUEST[$fieldname];
					$focus->column_fields[$fieldname] = $value;
				}
			}
			//crmv@175737 Handling for dateformat
			$result = $adb->pquery("select fieldname from {$table_prefix}_field where tabid = ? and uitype = ?", array(getTabid($currentModule),5));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					if ($focus->column_fields[$row['fieldname']] != '') {
						$focus->column_fields[$row['fieldname']] = getDBInsertDateValue($focus->column_fields[$row['fieldname']]);
					}
				}
			}
			//crmv@175737e
			
			$quoteid = $focus->column_fields['quote_id'];
			$quote_focus = CRMEntity::getInstance('Quotes');
			$quote_focus->id = $quoteid;
			$quote_focus->retrieve_entity_info($quoteid, "Quotes");
			$focus = getConvertQuoteToSoObject($focus, $quote_focus, $quoteid);
	
			// Reset the value w.r.t Quote Selected
			$currencyid = $quote_focus->column_fields['currency_id'];
			$rate = $quote_focus->column_fields['conversion_rate'];
	
			//crmv@30721
			if (isset ($_REQUEST['quote_id']) && $_REQUEST['quote_id'] != '') {
				$final_details = $InventoryUtils->getFinalDetails("Quotes",$quote_focus);	//crmv@30721
			}
	
			$smarty->assign("QUOTE_ID", $focus->column_fields['quote_id']);
			$smarty->assign("MODE", $quote_focus->mode);
			$smarty->assign("CONVERT_MODE", $_REQUEST['convertmode']);
		}
	}
	
	if (isset ($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
		$duplicate_from = $focus->id;	//crmv@38845
		$smarty->assign("DUPLICATE_FROM", $focus->id);
		$SO_associated_prod = $InventoryUtils->getFinalDetails("SalesOrder",$focus);	//crmv@30721
		$focus->id = "";
		$focus->mode = '';
	}
	
	if (isset ($_REQUEST['potential_id']) && $_REQUEST['potential_id'] != '') {
		$focus->column_fields['potential_id'] = $_REQUEST['potential_id'];
		$relatedInfo = getRelatedInfo($_REQUEST['potential_id']);
		if (!empty ($relatedInfo)) {
			$setype = $relatedInfo["setype"];
			$relID = $relatedInfo["relID"];
		}
		if ($setype == 'Accounts') {
			$focus->column_fields['account_id'] = $_REQUEST['account_id'] = $relID;	//crmv@91229
		}
		elseif ($setype == 'Contacts') {
			$focus->column_fields['contact_id'] = $_REQUEST['contact_id'] = $relID;	//crmv@91229
		}
		$log->debug("Sales Order EditView: Potential Id from the request is " . $_REQUEST['potential_id']);
		$final_details = $InventoryUtils->getFinalDetails("Potentials",$focus,$focus->column_fields['potential_id']);	//crmv@30721
	}
	
	if (isset ($_REQUEST['product_id']) && $_REQUEST['product_id'] != '') {
		$focus->column_fields['product_id'] = $_REQUEST['product_id'];
		$final_details = $InventoryUtils->getFinalDetails("Products",$focus,$focus->column_fields['product_id']);	//crmv@30721
	}
	if (!empty ($_REQUEST['parent_id']) && !empty ($_REQUEST['return_module'])) {
		if ($_REQUEST['return_module'] == 'Services') {
			$focus->column_fields['product_id'] = $_REQUEST['parent_id'];
			$log->debug("Service Id from the request is " . $_REQUEST['parent_id']);
			$final_details = $InventoryUtils->getFinalDetails("Services",$focus,$focus->column_fields['product_id']);	//crmv@30721
		}
	}
	
	// Get Account address if vte_account is given
	if (isset($_REQUEST['account_id']) && $_REQUEST['account_id'] != '' && is_numeric($_REQUEST['account_id']) && $_REQUEST['record'] == '' && $_REQUEST['convertmode'] != 'update_quote_val') { //crmv@160371
		$acct_focus = CRMEntity::getInstance('Accounts');
		$acct_focus->retrieve_entity_info($_REQUEST['account_id'], "Accounts",true);
		$focus->column_fields['bill_city'] = $acct_focus->column_fields['bill_city'];
		$focus->column_fields['ship_city'] = $acct_focus->column_fields['ship_city'];
		//added to fix the issue 4526
		$focus->column_fields['bill_pobox'] = $acct_focus->column_fields['bill_pobox'];
		$focus->column_fields['ship_pobox'] = $acct_focus->column_fields['ship_pobox'];
		$focus->column_fields['bill_street'] = $acct_focus->column_fields['bill_street'];
		$focus->column_fields['ship_street'] = $acct_focus->column_fields['ship_street'];
		$focus->column_fields['bill_state'] = $acct_focus->column_fields['bill_state'];
		$focus->column_fields['ship_state'] = $acct_focus->column_fields['ship_state'];
		$focus->column_fields['bill_code'] = $acct_focus->column_fields['bill_code'];
		$focus->column_fields['ship_code'] = $acct_focus->column_fields['ship_code'];
		$focus->column_fields['bill_country'] = $acct_focus->column_fields['bill_country'];
		$focus->column_fields['ship_country'] = $acct_focus->column_fields['ship_country'];
	}
}

$theme_path = "themes/" . $theme . "/";
$image_path = $theme_path . "images/";

$disp_view = getView($focus->mode);
//crmv@9434
$mode = $focus->mode;
//crmv@9434 end

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
// crmv@104568e

$smarty->assign("OP_MODE", $disp_view);

$smarty->assign("MODULE", $currentModule);
$smarty->assign("SINGLE_MOD", 'SalesOrder');

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$category = getParentTab();
$smarty->assign("CATEGORY", $category);

$log->info("Order view");

if (isset ($focus->name))
	$smarty->assign("NAME", $focus->name);
else
	$smarty->assign("NAME", "");

if (isset ($_REQUEST['convertmode']) && ($_REQUEST['convertmode'] == 'quotetoso' || $_REQUEST['convertmode'] == 'update_quote_val')) {
	$txtTax = (($quote_focus->column_fields['txtTax'] != '') ? $quote_focus->column_fields['txtTax'] : '0.000');
	$txtAdj = (($quote_focus->column_fields['txtAdjustment'] != '') ? $quote_focus->column_fields['txtAdjustment'] : '0.000');

	$smarty->assign("MODE", $focus->mode);
	$final_details = $InventoryUtils->getFinalDetails("Quotes",$quote_focus);	//crmv@30721
}
elseif ($focus->mode == 'edit') {
	$smarty->assign("UPDATEINFO", updateInfo($focus->id));
	$smarty->assign("MODE", $focus->mode);
	$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$focus);	//crmv@30721
}
elseif (isset ($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$smarty->assign("MODE", $focus->mode);
	$final_details = $SO_associated_prod;	//crmv@30721
}
elseif ((isset ($_REQUEST['potential_id']) && $_REQUEST['potential_id'] != '' && is_numeric($_REQUEST['potential_id'])) || (isset ($_REQUEST['product_id']) && $_REQUEST['product_id'] != '' && is_numeric($_REQUEST['product_id']))) { //crmv@160371
	$InvTotal = getInventoryTotal($_REQUEST['return_module'], $_REQUEST['return_id']);
	$smarty->assign("MODE", $focus->mode);

	//this is to display the Product Details in first row when we create new PO from Product relatedlist
	if ($_REQUEST['return_module'] == 'Products') {
		$smarty->assign("PRODUCT_ID", vtlib_purify($_REQUEST['product_id']));
		$smarty->assign("PRODUCT_NAME", getProductName($_REQUEST['product_id']));
		$smarty->assign("UNIT_PRICE", vtlib_purify($_REQUEST['product_id']));
		$smarty->assign("QTY_IN_STOCK", $InventoryUtils->getPrdQtyInStck($_REQUEST['product_id'])); //crmv@42024
		$smarty->assign("VAT_TAX", $InventoryUtils->getProductTaxPercentage("VAT", $_REQUEST['product_id']));
		$smarty->assign("SALES_TAX", $InventoryUtils->getProductTaxPercentage("Sales", $_REQUEST['product_id']));
		$smarty->assign("SERVICE_TAX", $InventoryUtils->getProductTaxPercentage("Service", $_REQUEST['product_id']));
		$smarty->assign("row_no",1);	//crmv@47104
	}
}

if (isset ($cust_fld)) {
	$smarty->assign("CUSTOMFIELD", $cust_fld);
}

if (isset ($_REQUEST['return_module']))
	$smarty->assign("RETURN_MODULE", vtlib_purify($_REQUEST['return_module']));
else
	$smarty->assign("RETURN_MODULE", "SalesOrder");
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
$smarty->assign("MODULE", "SalesOrder");
$smarty->assign("ID", $focus->id);

$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("CALENDAR_DATEFORMAT", parse_calendardate($app_strings['NTC_DATE_FORMAT']));

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
$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo);
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
if ($focus->mode != 'edit' && $mod_seq_field != null) {
	$autostr = getTranslatedString('MSG_AUTO_GEN_ON_SAVE');
	//crmv@154715
	if ($focus->isBUMCInstalled($currentModule)) {
		$smarty->assign("MOD_SEQ_ID",$autostr);
	} else {
	//crmv@154715e
		$mod_seq_string = $adb->pquery("SELECT prefix, cur_id from ".$table_prefix."_modentity_num where semodule = ? and active=1", array($currentModule));
		$mod_seq_prefix = $adb->query_result($mod_seq_string, 0, 'prefix');
		$mod_seq_no = $adb->query_result($mod_seq_string, 0, 'cur_id');
		if ($adb->num_rows($mod_seq_string) == 0 || $focus->checkModuleSeqNumber($focus->table_name, $mod_seq_field['column'], $mod_seq_prefix . $mod_seq_no)) {
			//crmv@122082
			$error_str = getTranslatedString('LBL_DUPLICATE').' '.getTranslatedString($mod_seq_field['label'],$currentModule).'. '.sprintf(getTranslatedString('LBL_CLICK_TO_CONFIGURE_MODENTITYNUM'),'index.php?module=Settings&action=CustomModEntityNo&parenttab=Settings&selmodule='.$currentModule,getTranslatedString('LBL_HERE'),getTranslatedString($mod_seq_field['label'],$currentModule));
			$smarty->assign("ERROR_STR",$error_str);
			//crmv@122082e
		} else {
			$smarty->assign("MOD_SEQ_ID", $autostr);
		}
	}	//crmv@154715
} else {
	$smarty->assign("MOD_SEQ_ID", $focus->column_fields[$mod_seq_field['name']]);
}
// END

$smarty->assign("CURRENCIES_LIST", $InventoryUtils->getAllCurrencies());
//crmv@38845
if($focus->mode == 'edit') {
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('SalesOrder', $focus->id);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
} elseif($_REQUEST['isDuplicate'] == 'true') {
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('SalesOrder', $duplicate_from);
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
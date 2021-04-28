<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@30721 crmv@42024 crmv@57257 */


global $app_strings, $theme,$current_user;

$record = $_REQUEST['record'];
$mode = $_REQUEST['mode'];
$rowId = $_REQUEST['rowid'];
$module = $_REQUEST['rel_module'];
$return_module = $_REQUEST['return_module'];
$duplicate_from = $_REQUEST['duplicate_from'];
$parent_id = $_REQUEST['parent_id'];
$product_id = $_REQUEST['product_id'];
$potential_id = $_REQUEST['potential_id'];
$convert_mode = $_REQUEST['convertmode'];
$quote_id = $_REQUEST['quote_id'];
$soid = $_REQUEST['salesorder_id'];

$loadHeader = ($_REQUEST['load_header'] == '1');
$loadFooter = ($_REQUEST['load_footer'] == '1');

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$currencyid=fetchCurrency($current_user->id);
$smarty = new VteSmarty();

// crmv@184240
if ($record > 0) {
	if (isPermitted($module, 'DetailView', $record) != 'yes') {
		echo 'Not authorized';
		die();
	}
}
// crmv@184240e

$focus = CRMEntity::getInstance($module);
if($record != '' && $record != '0' && $convert_mode == ''){
	$focus->id = $record;
    $focus->mode = 'edit';
    $focus->retrieve_entity_info($record,$module);
}

$InventoryUtils = InventoryUtils::getInstance();

if(($_REQUEST['entityType'] != '' || $mode == '') && $convert_mode == '' && in_array($duplicate_from,array('',0)) && in_array($product_id,array('',0)) && in_array($parent_id,array('',0))){

	// standard create mode
	$entityType = $_REQUEST['entityType'];
	if ($entityType == '') {
		$entityType = 'Products';
		//Per cambiare il modulo Inventory di default basta aggiungere l'attributo $defaultInventoryEntityType nella classe estesa di Quotes, Invoice, ecc.
		if ($focus->defaultInventoryEntityType != '') {
			$entityType = $focus->defaultInventoryEntityType;
		}
	}
	$associated_prod = Array($rowId => Array("entityType$rowId" => $entityType, "delRow$rowId" => 'Del', "subProductArray$rowId" => Array(), "usageunit$rowId" => '', "hdnProductId$rowId" => '', "productName$rowId" => '', "hdnProductcode$rowId" => '', "productDescription$rowId" => '', "comment$rowId" => '', "qty$rowId" => 1, "listPrice$rowId" => '', "unitPrice$rowId" => '', "productTotal$rowId" => 0, "subproduct_ids$rowId" => '', "subprod_names$rowId" => '', "discount_percent$rowId" => 0, "discount_amount$rowId" => 0, "checked_discount_zero$rowId" => 'checked', "discountTotal$rowId" => 0.00, "totalAfterDiscount$rowId" => 0, "taxTotal$rowId" => 0.00, "netPrice$rowId" => 0 ));
	$final_details = $InventoryUtils->getFinalDetails($module,$focus);	//crmv@55019
	
	$smarty->assign("INV_CURRENCY_ID", $currencyid);

}elseif(!in_array($duplicate_from,array('',0))) {

	$focus->id = $duplicate_from;
	$focus->retrieve_entity_info($duplicate_from,$module);	//crmv@54919
	$associated_prod = $InventoryUtils->getAssociatedProducts($module,$focus);
	$final_details = $InventoryUtils->getFinalDetails($module,$focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo($module, $duplicate_from);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='sotoddt' && $record != '' && $record != '0') {

	$soid = $record;
	$so_focus = CRMEntity::getInstance('SalesOrder');
	$so_focus->id = $soid;
	$so_focus->retrieve_entity_info($soid,"SalesOrder");
	$associated_prod = $InventoryUtils->getAssociatedProducts("SalesOrder",$so_focus);
	$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$so_focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('SalesOrder', $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='quotetoso' && $record != '' && $record != '0') {

	$quoteid = $record;
	$quote_focus = CRMEntity::getInstance('Quotes');
	$quote_focus->id = $quoteid;
	$quote_focus->retrieve_entity_info($quoteid, "Quotes");
	$associated_prod = $InventoryUtils->getAssociatedProducts("Quotes", $quote_focus);
	$final_details = $InventoryUtils->getFinalDetails("Quotes",$quote_focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Quotes', $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='ttickettoso' && $record != '' && $record != '0') {

	$tticketid = $record;
	$tticket_focus = CRMEntity::getInstance('HelpDesk');
	$tticket_focus->id = $tticketid;
	$tticket_focus->retrieve_entity_info($tticketid,"HelpDesk");
	$associated_prod = $InventoryUtils->getAssociatedProducts("HelpDesk",$tticket_focus);
	$final_details = $InventoryUtils->getFinalDetails("HelpDesk",$tticket_focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('HelpDesk', $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='update_quote_val' && $quote_id != '' && $quote_id != '0') {

	$quote_focus = CRMEntity::getInstance('Quotes');
	$quote_focus->id = $quote_id;
	$quote_focus->retrieve_entity_info($quote_id, "Quotes");
	$associated_prod = $InventoryUtils->getAssociatedProducts("Quotes", $quote_focus, $quote_id);
	$final_details = $InventoryUtils->getFinalDetails("Quotes",$quote_focus, $quote_id);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Quotes', $quote_id);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='update_so_val' && $soid != '' && $soid != '0') {

	$so_focus = CRMEntity::getInstance('SalesOrder');
	$so_focus->id = $soid;
	$so_focus->retrieve_entity_info($soid,"SalesOrder");
	$associated_prod = $InventoryUtils->getAssociatedProducts("SalesOrder",$so_focus,$record);
	$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$so_focus, $record);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Quotes', $soid);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='ddttoinvoice' && $record != '' && $record != '0') {

	$ddtid = $record;
	$ddt_focus = CRMEntity::getInstance('Ddt');
	$ddt_focus->id = $ddtid;
	$ddt_focus->retrieve_entity_info($ddtid,"Ddt");
	$associated_prod = $InventoryUtils->getAssociatedProducts("Ddt",$ddt_focus);
	$final_details = $InventoryUtils->getFinalDetails("Ddt",$ddt_focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Ddt', $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='sotoinvoice' && $record != '' && $record != '0') {

	$soid = $record;
	$so_focus = CRMEntity::getInstance('SalesOrder');
	$so_focus->id = $soid;
	$so_focus->retrieve_entity_info($soid,"SalesOrder");
	$associated_prod = $InventoryUtils->getAssociatedProducts("SalesOrder",$so_focus);
	$final_details = $InventoryUtils->getFinalDetails("SalesOrder",$so_focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('SalesOrder', $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='quotetoinvoice' && $record != '' && $record != '0') {

	$quoteid = $record;
	$quote_focus = CRMEntity::getInstance('Quotes');
	$quote_focus->id = $quoteid;
	$quote_focus->retrieve_entity_info($quoteid,"Quotes");
	$associated_prod = $InventoryUtils->getAssociatedProducts("Quotes",$quote_focus);
	$final_details = $InventoryUtils->getFinalDetails("Quotes",$quote_focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Quotes', $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($convert_mode =='ttickettoiv' && $record != '' && $record != '0') {

	$tticketid = $record;
	$tticket_focus = CRMEntity::getInstance('HelpDesk');
	$tticket_focus->id = $tticketid;
	$tticket_focus->retrieve_entity_info($tticketid,"HelpDesk");
	$associated_prod = $InventoryUtils->getAssociatedProducts("HelpDesk",$tticket_focus);
	$final_details = $InventoryUtils->getFinalDetails("HelpDesk",$tticket_focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('HelpDesk', $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($opportunity_id !='' && $opportunity_id !='0' ){

	$potfocus = CRMEntity::getInstance('Potentials');
	$potfocus->column_fields['potential_id'] = $opportunity_id;
	$associated_prod = $InventoryUtils->getAssociatedProducts("Potentials",$potfocus,$opportunity_id);
	$final_details = $InventoryUtils->getFinalDetails("Potentials",$potfocus, $opportunity_id);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Potentials', $opportunity_id);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($potential_id !='' && $potential_id != '0'){

	$associated_prod = $InventoryUtils->getAssociatedProducts("Potentials",$focus, $potential_id);
	$final_details = $InventoryUtils->getFinalDetails("Potentials",$focus, $potential_id);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Potentials', $potential_id);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($product_id !='' && $product_id !='0' && $return_module == 'Products'){

	$focus->column_fields['product_id'] = $product_id;
    $associated_prod = $InventoryUtils->getAssociatedProducts("Products",$focus,$product_id);
    $final_details = $InventoryUtils->getFinalDetails("Products",$focus, $product_id);
	for ($i=1; $i<=count($associated_prod);$i++) {
		$associated_prod_id = $associated_prod[$i]['hdnProductId'.$i];
		$associated_prod_prices = $InventoryUtils->getPricesForProducts($currencyid,array($associated_prod_id),'Products');
		$associated_prod[$i]['listPrice'.$i] = $associated_prod_prices[$associated_prod_id];
	}
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Products', $product_id);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);

}elseif($parent_id !='' && $parent_id !='0'){

	if ($return_module == 'Services') {
	    $focus->column_fields['product_id'] = $parent_id;
	    $associated_prod = $InventoryUtils->getAssociatedProducts("Services", $focus, $parent_id);
	    $final_details = $InventoryUtils->getFinalDetails("Services", $focus, $parent_id);
		for ($i=1; $i<=count($associated_prod);$i++) {
			$associated_prod_id = $associated_prod[$i]['hdnProductId'.$i];
			$associated_prod_prices = $InventoryUtils->getPricesForProducts($currencyid,array($associated_prod_id),'Services');
			$associated_prod[$i]['listPrice'.$i] = $associated_prod_prices[$associated_prod_id];
		}
		
		$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo('Services', $parent_id);
		$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
	//crmv@55019
    } else {
    	// standard create mode
    	$entityType = $_REQUEST['entityType'];
		if ($entityType == '') {
			$entityType = 'Products';
			//Per cambiare il modulo Inventory di default basta aggiungere l'attributo $defaultInventoryEntityType nella classe estesa di Quotes, Invoice, ecc.
			if ($focus->defaultInventoryEntityType != '') {
				$entityType = $focus->defaultInventoryEntityType;
			}
		}
		$associated_prod = Array($rowId => Array("entityType$rowId" => $entityType, "delRow$rowId" => 'Del', "subProductArray$rowId" => Array(), "usageunit$rowId" => '', "hdnProductId$rowId" => '', "productName$rowId" => '', "hdnProductcode$rowId" => '', "productDescription$rowId" => '', "comment$rowId" => '', "qty$rowId" => 1, "listPrice$rowId" => '', "unitPrice$rowId" => '', "productTotal$rowId" => 0, "subproduct_ids$rowId" => '', "subprod_names$rowId" => '', "discount_percent$rowId" => 0, "discount_amount$rowId" => 0, "checked_discount_zero$rowId" => 'checked', "discountTotal$rowId" => 0.00, "totalAfterDiscount$rowId" => 0, "taxTotal$rowId" => 0.00, "netPrice$rowId" => 0 ));
		$final_details = $InventoryUtils->getFinalDetails($module,$focus);	//crmv@55019
    }
    //crmv@55019e

}elseif($mode != ''){

	$associated_prod = $InventoryUtils->getAssociatedProducts($module, $focus);
	$final_details = $InventoryUtils->getFinalDetails($module,$focus);
	
	$inventory_cur_info = $InventoryUtils->getInventoryCurrencyInfo($module, $record);
	$smarty->assign("INV_CURRENCY_ID", $inventory_cur_info['currency_id']);
}

$smarty->assign("ASSOCIATEDPRODUCTS", $associated_prod);
$smarty->assign("AVAILABLE_PRODUCTS", 'true');
$smarty->assign("MODE", $mode);
$smarty->assign("APP", $app_strings);
$smarty->assign("MODULE", $module);
$smarty->assign("CURRENCIES_LIST", $InventoryUtils->getAllCurrencies());

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);

// crmv@198024
if ($entityType == 'Products' && vtlib_isModuleActive('ConfProducts')) {
	$smarty->assign("USE_CONF_PRODUCTS", true);
} else {
	$smarty->assign("USE_CONF_PRODUCTS", false);
}
// crmv@198024e

// DISPLAY

if ($loadHeader) {
	$smarty->assign("TAXTYPE", $final_details[1]['final_details']['taxtype']);	//crmv@50153
	$smarty->display("Inventory/ProductsHeaderEdit.tpl");
}

$smarty->display("Inventory/ProductRowEdit.tpl");

if ($loadFooter) {
	echo "##%%##"; // separator
	$smarty->assign("FINAL_DETAILS", $final_details);
	$smarty->display("Inventory/ProductsFooterEdit.tpl");
}
?>
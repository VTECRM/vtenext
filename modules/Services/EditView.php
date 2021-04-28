<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require 'modules/VteCore/EditView.php';	//crmv@30447

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

// crmv@104568

//Tax handling (get the available taxes only) - starts
if($focus->mode == 'edit')
{
	$retrieve_taxes = true;
	$serviceid = $focus->id;
	$tax_details = $InventoryUtils->getTaxDetailsForProduct($serviceid,'available_associated');
}
elseif($_REQUEST['isDuplicate'] == 'true')
{
	$retrieve_taxes = true;
	$serviceid = $_REQUEST['record'];
	$tax_details = $InventoryUtils->getTaxDetailsForProduct($serviceid,'available_associated');
}
else {
	$tax_details = $InventoryUtils->getAllTaxes('available');
	$smarty->assign("PROD_MODE", "create");
}

// crmv@93286 crmv@120823 - merge taxes
$all_taxes = $InventoryUtils->getAllTaxes('available');

if (!is_array($tax_details)) $tax_details = $all_taxes;

if ($retrieve_taxes) {
	$taxids = array_map(function($tax) {
		return $tax['taxid'];
	}, $tax_details);
	
	if (is_array($all_taxes)) {
		foreach ($all_taxes as $tax) {
			if (!in_array($tax['taxid'], $taxids)) {
				$tax_details[] = $tax;
			}
		}
	}

	//For Edit and Duplicate we have to retrieve the service associated taxes and show them
	foreach ($tax_details as &$tax) {
		$tax['check_name'] = $tax['taxname'].'_check';
		
		$tax_value = $InventoryUtils->getProductTaxPercentage($tax['taxname'],$serviceid);
		if ($tax_value == '') {
			//if the tax is not associated with the product then we should get the default value and unchecked
			$tax['check_value'] = 0;
			$tax['percentage'] = $InventoryUtils->getTaxPercentage($tax['taxname']); // crmv@42024
		} else {
			$tax['check_value'] = 1;
			$tax['percentage'] = $tax_value;
		}
	}
} else {
	foreach ($tax_details as &$tax) {
		$tax['check_name'] = $tax['taxname'].'_check';
	}
}
// crmv@93286e crmv@120823e

$smarty->assign("TAX_DETAILS", $tax_details);
//Tax handling - ends

$unit_price = $focus->column_fields['unit_price'];
$price_details = $InventoryUtils->getPriceDetailsForProduct($serviceid, $unit_price, 'available',$currentModule);
$smarty->assign("PRICE_DETAILS", $price_details);

$base_currency = 'curname' . $service_base_currency;
$smarty->assign("BASE_CURRENCY", $base_currency);

$smarty->display('Inventory/InventoryEditView.tpl');
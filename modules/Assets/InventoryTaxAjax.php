<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42024 - many changes

global $theme, $app_strings, $mod_strings;

$productid = vtlib_purify($_REQUEST['productid']);
$rowid = vtlib_purify($_REQUEST['curr_row']);
$product_total = vtlib_purify($_REQUEST['productTotal']);

$InventoryUtils = InventoryUtils::getInstance();

// retrieve tax info
$tax_details = $InventoryUtils->getTaxDetailsForProduct($productid,'all');//we should pass available instead of all if we want to display only the available taxes.

// set the array for the calculations
if (is_array($tax_details)) {
	$prodTaxes = array();
	foreach ($tax_details as $td) {
		$prodTaxes[$td['taxname']] = $td['percentage'];
	}
}

// populate array for calculations
$prodinfo = array(
	'listprice' => $product_total,
	'quantity' => 1,
	'discount_percent' => 0,
	'discount_amount' => 0,
	'taxes' => $prodTaxes,
);

// calculate taxes for a single product (to have the partial values)
$prodPrices = $InventoryUtils->calcProductTotals($prodinfo);
for ($i=0; $i<count($tax_details); ++$i) {
	$tax_details[$i]['taxtotal'] = $prodPrices['taxes'][$i]['amount'];
}

// set the array for smarty
$taxdata = array(
	'totalAfterDiscount' => 'totalAfterDiscount'.$rowid,
	'totalAfterDiscount'.$rowid => $product_total,
	'taxTotal' => 'taxTotal'.$rowid,
	'taxTotal'.$rowid => $prodPrices['total_taxes'],
	'taxes' => $tax_details,
);

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('THEME', $theme);
$smarty->assign('row_no', $rowid);
$smarty->assign('data', $taxdata);

$smarty->display('Inventory/ProductTaxDetail.tpl');
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$InventoryUtils = InventoryUtils::getInstance();
	
$tax_details = $InventoryUtils->getTaxDetailsForProduct($focus->id);
for($i=0;$i<count($tax_details);$i++) {
	$tax_details[$i]['percentage'] = $InventoryUtils->getProductTaxPercentage($tax_details[$i]['taxname'],$focus->id); // crmv@42024
}
$smarty->assign("TAX_DETAILS", $tax_details);

$price_details = $InventoryUtils->getPriceDetailsForProduct($focus->id, $focus->unit_price, 'available_associated',$currentModule);
$smarty->assign("PRICE_DETAILS", $price_details);
?>
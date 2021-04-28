<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $theme, $log;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$currencyid = $_REQUEST['currencyid'];
$products_list = $_REQUEST['productsList'];
$product_ids = explode("::", $products_list);

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

$price_list = array();

if (count($product_ids) > 0) {
	$product_prices = $InventoryUtils->getPricesForProducts($currencyid, $product_ids);
}

// To get the Price Values in the same order as the Products
for ($i=0;$i<count($product_ids);++$i) {
	$product_id = $product_ids[$i];
	$price_list[] = $product_prices[$product_id];
}

$price_values = implode("::", $price_list);
echo "SUCCESS$".$price_values;

?>
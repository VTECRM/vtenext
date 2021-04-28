<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

global $theme, $log;

$themePath = "themes/" . $theme . "/";
$imagePath = $themePath . "images/";

$currencyId = $_REQUEST['currencyid'];
$productsList = $_REQUEST['productsList'];
$productIds = explode("::", $productsList);

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

$priceList = [];

if (count($productIds) > 0) {
    $productPrices = $InventoryUtils->getPricesForProducts($currencyId, $productIds);
}

// To get the Price Values in the same order as the Products
for ($i = 0; $i < count($productIds); ++$i) {
    $productId = $productIds[$i];
    $priceList[] = $productPrices[$productId];
}

$priceValues = implode("::", $priceList);
echo "SUCCESS$" . $priceValues;

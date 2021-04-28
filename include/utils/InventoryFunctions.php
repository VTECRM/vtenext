<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@64542 crmv@151308 */

/**
 * Get the modules with the products block
 */
function getInventoryModules() {
	$inventory_modules = TabdataCache::get('inventory_modules'); //crmv@140903
	if (!isset($inventory_modules)) {
		// fallback on these modules in case of error
		$inventory_modules = array('Quotes', 'SalesOrder', 'PurchaseOrder', 'Invoice', 'Ddt');
	}
	return $inventory_modules;
}

/**
 * Return true if the module has the products block
 */
function isInventoryModule($modname) {
	return in_array($modname, getInventoryModules());
}

/**
 * Get the modules that can be used as products for inventory modules
 */
function getProductModules() {
	$product_modules = TabdataCache::get('product_modules'); //crmv@140903
	if (!isset($product_modules)) {
		// fallback on these modules in case of error
		$product_modules = array('Products', 'Services');
	}
	return $product_modules;
}

/**
 * Return true if the module can be used in the products block
 */
function isProductModule($modname) {
	return in_array($modname, getProductModules());
}


// some aliases for quick access
function parseUserNumber($n) { return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function formatUserNumber($n) { return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }


// used in workflows
function handleInventoryProductRel($entity){
	$InventoryUtils = InventoryUtils::getInstance();
	$InventoryUtils->updateInventoryProductRel($entity);
}
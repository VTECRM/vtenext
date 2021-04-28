<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150748 */

/* In this file there are deprecated compatibility functions that souldn't be used for new code */
/* They are here so the new class file can be autoloaded */

/**
 * @deprecated
 * Please use the SettingsUtils::getBlockId() function now.
 */
function getSettingsBlockId($label) {
	return SettingsUtils::getBlockId($label);
}

/**
 * @deprecated
 * Please use the SettingsUtils::isModulePermitted() function now.
 */
function isModuleSettingPermitted($module) {
	return SettingsUtils::isModulePermitted($module);
}

/**
 * @deprecated
 * Please use the SettingsUtils::getBlocks() function now.
 */
function getSettingsBlocks() {
	return SettingsUtils::getBlocks();
}

/**
 * @deprecated
 * Please use the SettingsUtils::getFields() function now.
 */
function getSettingsFields() {
	return SettingsUtils::getFields();
}

/* crmv@144125 */

/**
 * @deprecated
 * Please use the EntityNameUtils class
 */
function getEntityField($module){
	$ENU = EntityNameUtils::getInstance();
	return $ENU->getEntityField($module);
}

/**
 * @deprecated
 * Please use the EntityNameUtils class
 */
function getEntityFieldNames($module) {
	$ENU = EntityNameUtils::getInstance();
	return $ENU->getFieldNames($module);
}

/**
 * @deprecated
 * Please use the EntityNameUtils class
 */
function getSqlForNameInDisplayFormat($input, $module, $glue = ' ') {
	$ENU = EntityNameUtils::getInstance();
	return $ENU->getSqlForNameInDisplayFormat($input, $module, $glue);
}

/* crmv@151308 - functions from inventory utils */ 
/* used only by personalization, remove them if nothing is using them */

function getPrdQtyInStck() 			{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getPrdReOrderLevel() 		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); } // never used
function getPrdHandler() 			{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); } // never used
function getTaxId() 				{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getTaxPercentage() 		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getProductTaxPercentage() 	{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function addInventoryHistory() 		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getAllTaxes() 						{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getTaxDetailsForProduct() 			{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function deleteInventoryProductDetails()	{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function updateInventoryProductRel()		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function saveInventoryProductDetails()		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getInventoryTaxType() 				{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getInventoryCurrencyInfo() 		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getInventoryProductTaxValue()		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getInventorySHTaxPercent() 		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getAllCurrencies() 				{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getProductBaseCurrency() 			{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getBaseConversionRateForProduct()	{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getPricesForProducts()				{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getPriceBookCurrency()				{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }

// these are from other files
function getDetailAssociatedProducts()		{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getAssociatedProducts()			{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getFinalDetails()					{ return InventoryUtils::callMethodByName(__FUNCTION__, func_get_args()); }
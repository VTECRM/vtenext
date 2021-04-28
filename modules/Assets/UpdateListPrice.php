<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	
global $adb;
global $log;
global $table_prefix;
$log->debug("Going to update the ListPrice in (modules/Products/UpdateListPrice.php).");
$record = vtlib_purify($_REQUEST['record']);
$pricebook_id = vtlib_purify($_REQUEST['pricebook_id']);
$product_id = vtlib_purify($_REQUEST['product_id']);
$listprice = vtlib_purify($_REQUEST['list_price']);
$return_action = vtlib_purify($_REQUEST['return_action']);
$return_module = vtlib_purify($_REQUEST['return_module']);

$query = "update ".$table_prefix."_pricebookproductrel set listprice=? where pricebookid=? and productid=?";
$adb->pquery($query, array($listprice, $pricebook_id, $product_id)); 
header("Location: index.php?module=$return_module&action=".$return_module."Ajax&file=$return_action&ajax=updatelistprice&record=$record");
?>
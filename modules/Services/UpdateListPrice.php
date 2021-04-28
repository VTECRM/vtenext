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
$pricebookId = vtlib_purify($_REQUEST['pricebook_id']);
$productId = vtlib_purify($_REQUEST['product_id']);
$listPrice = vtlib_purify($_REQUEST['list_price']);
$returnAction = vtlib_purify($_REQUEST['return_action']);
$returnModule = vtlib_purify($_REQUEST['return_module']);

$query = "update " . $table_prefix . "_pricebookproductrel set listprice=? where pricebookid=? and productid=?";
$adb->pquery($query, array($listPrice, $pricebookId, $productId));
?>
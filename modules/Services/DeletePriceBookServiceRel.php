<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb;
global $log;
global $table_prefix;
$returnId = vtlib_purify($_REQUEST['return_id']);
$record = vtlib_purify($_REQUEST['record']);
$returnModule = vtlib_purify($_REQUEST['return_module']);
$returnAction = vtlib_purify($_REQUEST['return_action']);

if ($returnAction != '' && $returnModule == "PriceBooks" && $returnAction == "CallRelatedList") {
    $log->info("Products :: Deleting Price Book - Delete from PriceBook RelatedList");
    $query = "delete from " . $table_prefix . "_pricebookproductrel where pricebookid=? and productid=?";
    $adb->pquery($query, array($returnId, $record));
} else {
    $log->info("Products :: Deleting Price Book");
    $query = "delete from " . $table_prefix . "_pricebookproductrel where pricebookid=? and productid=?";
    $adb->pquery($query, array($record, $returnId));
}

header("Location: index.php?module=" . $returnModule . "&action=" . $returnModule .
    "Ajax&file=$returnAction&ajax=true&record=" . $returnId);
?>
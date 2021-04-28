<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb;
global $log;
global $table_prefix;
$return_id = vtlib_purify($_REQUEST['return_id']);
$record = vtlib_purify($_REQUEST['record']);
$return_module = vtlib_purify($_REQUEST['return_module']);
$return_action = vtlib_purify($_REQUEST['return_action']);

if($return_action !='' && $return_module == "PriceBooks" && $return_action == "CallRelatedList") {
	$log->info("Products :: Deleting Price Book - Delete from PriceBook RelatedList");
	$query = "delete from ".$table_prefix."_pricebookproductrel where pricebookid=? and productid=?";
	$adb->pquery($query, array($return_id, $record)); 
} else {
	$log->info("Products :: Deleting Price Book");
	$query = "delete from ".$table_prefix."_pricebookproductrel where pricebookid=? and productid=?";
	$adb->pquery($query, array($record, $return_id)); 
}

header("Location: index.php?module=".$return_module."&action=".$return_module."Ajax&file=$return_action&ajax=delpbprorel&record=".$return_id);
?>
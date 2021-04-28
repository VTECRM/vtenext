<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function updatePotentials($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updatePotentials(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_potential set accountid=? where accountid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updatePotentials method ...");
}

function updateActivities($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateActivities(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_seactivityrel set crmid=? where crmid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateActivities method ...");
}

function updateSalesOrder($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateSalesOrder(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_salesorder set accountid=? where accountid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateSalesOrder method ...");
}

function updateQuotes($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateQuotes(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_quotes set accountid=? where accountid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateQuotes method ...");
}

function updateContacts($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateContacts(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_contactdetails set accountid=? where accountid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateContacts method ...");
}

function updateInvoice($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateInvoice(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_invoice set accountid=? where accountid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateInvoice method ...");
}

function updateTickets($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateTickets(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_troubletickets set parent_id=? where parent_id=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateTickets method ...");
}

function updateAttachments($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateAttachments(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_seattachmentsrel set crmid=? where crmid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateAttachments method ...");
}

function updateNotes($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateNotes(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_senotesrel set crmid=? where crmid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateNotes method ...");
}

function updateVisitReport($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering updateVisitReport(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_visitreport set accountid=? where accountid=?";
	$params = array($idnew,$idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting updateVisitReport method ...");
}

function deleteUpdateAccount($idold,$idnew){
	global $log,$adb,$table_prefix;
	$log->debug("Entering deleteUpdateAccount(".$idold.",".$idnew.") method ...");
	//crmv@189298 - change to pquery
	$query = "UPDATE ".$table_prefix."_crmentity set deleted='1' where crmid=?";
	$params = array($idold);
	$adb->pquery($query,$params);
	//crmv@189298e
	$log->debug("Exiting deleteUpdateAccount method ...");
}

function getOwner($id){
	global $adb,$table_prefix;
	//crmv@189298 - change to pquery
	$qry="select smownerid as id from ".$table_prefix."_crmentity where crmid=?";
	$params = array($id);
	$res = $adb->pquery($qry,$params);
	//crmv@189298e
	$num=$adb->num_rows($res);
	if ($num>0) return $adb->query_result($res,0,'id');
}

function check_merge_permission_CODE($id_crm,$id_jde){
	if (getOwner($id_crm) == getOwner($id_jde)) return true;
	return false;
}

function crmv_merge_account($id_crm,$id_jde){
	updateActivities($id_crm,$id_jde);
	updatePotentials($id_crm,$id_jde);
	updateSalesOrder($id_crm,$id_jde);
	updateQuotes($id_crm,$id_jde);
	updateContacts($id_crm,$id_jde);
	updateInvoice($id_crm,$id_jde);
	updateTickets($id_crm,$id_jde);
	updateAttachments($id_crm,$id_jde);
	updateNotes($id_crm,$id_jde);
	updateVisitReport($id_crm,$id_jde);
	deleteUpdateAccount($id_crm,$id_jde);
}
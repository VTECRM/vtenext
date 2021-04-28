<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 */

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

global $adb, $table_prefix, $log;

// Get the list of Invoice for which Recurring is enabled.


$log =& LoggerManager::getLogger('RecurringInvoice');
$log->debug("invoked RecurringInvoice");

$now = date('Y-m-d');
$sql="SELECT ".$table_prefix."_salesorder.salesorderid, recurring_frequency, start_period, end_period, last_recurring_date,
		 payment_duration, invoice_status FROM ".$table_prefix."_salesorder
		 INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_salesorder.salesorderid = ".$table_prefix."_crmentity.crmid AND ".$table_prefix."_crmentity.deleted = 0
		 INNER JOIN ".$table_prefix."_invoice_recurring_info ON ".$table_prefix."_salesorder.salesorderid = ".$table_prefix."_invoice_recurring_info.salesorderid
		 WHERE COALESCE(start_period, CAST('' as DATE)) <= ? AND COALESCE(end_period, CAST('' as DATE)) >= ?";
$result = $adb->pquery($sql, array($now, $now));

$no_of_salesorder = $adb->num_rows($result);
if($no_of_salesorder > 0) {

	for($i=0; $i<$no_of_salesorder;$i++) {
		$salesorder_id = $adb->query_result($result, $i,'salesorderid');
		$start_period = $adb->query_result($result, $i,'start_period');
		$end_period = $adb->query_result($result, $i,'end_period');
		$last_recurring_date = $adb->query_result($result, $i,'last_recurring_date');
		$recurring_frequency = $adb->query_result($result, $i,'recurring_frequency');
		if ($last_recurring_date == NULL  || $last_recurring_date == '' || $last_recurring_date == '0000-00-00') {
			$sy = date("Y", strtotime($start_period));
			$sm = date("m", strtotime($start_period));
			$sd = date("d", strtotime($start_period));
			$last_recurring_date = date('Y-m-d', strtotime($sy.'-'.$sm.'-'.($sd-1))); // Set last recurring date to a day before the start period.
		}
		list($y, $m, $d) = explode('-', $last_recurring_date);

		if (strtolower($recurring_frequency) == 'daily') {
			$next_recurring_date = date('Y-m-d', strtotime("+1 day", mktime(0, 0, 0, $m, $d, $y)));
			if(strtotime($next_recurring_date) > strtotime($end_period) || strtotime($next_recurring_date) > strtotime(date('Y-m-d'))) {
				continue;
			}
		}
		if (strtolower($recurring_frequency) == 'weekly') {
			$next_recurring_date = date('Y-m-d', strtotime("+1 week", mktime(0, 0, 0, $m, $d, $y)));
			if(strtotime($next_recurring_date) > strtotime($end_period) || strtotime($next_recurring_date) > strtotime(date('Y-m-d'))) {
				continue;
			}
		}
		if (strtolower($recurring_frequency) == 'monthly') {
			$next_recurring_date = date('Y-m-d', strtotime("+1 month", mktime(0, 0, 0, $m, $d, $y)));
			if(strtotime($next_recurring_date) > strtotime($end_period) || strtotime($next_recurring_date) > strtotime(date('Y-m-d'))) {
				continue;
			}
		}
		if (strtolower($recurring_frequency) == 'quarterly') {
			$next_recurring_date = date('Y-m-d', strtotime("+3 month", mktime(0, 0, 0, $m, $d, $y)));
			if(strtotime($next_recurring_date) > strtotime($end_period) || strtotime($next_recurring_date) > strtotime(date('Y-m-d'))) {
				continue;
			}
		}
		if (strtolower($recurring_frequency) == 'yearly') {
			$next_recurring_date = date('Y-m-d', strtotime("+1 year", mktime(0, 0, 0, $m, $d, $y)));
			if(strtotime($next_recurring_date) > strtotime($end_period) || strtotime($next_recurring_date) > strtotime(date('Y-m-d'))) {
				continue;
			}
		}

		createInvoice($salesorder_id);
		$adb->pquery("UPDATE ".$table_prefix."_invoice_recurring_info SET last_recurring_date=? WHERE salesorderid=?", array($next_recurring_date,$salesorder_id));
	}
}

/* Function to create a new Invoice using the given Sales Order id */
function createInvoice($salesorder_id) {
	require_once('include/utils/utils.php');
	require_once('data/CRMEntity.php');
	require_once('modules/Users/Users.php');

	global $log, $adb;
	global $current_user;

	// Payment duration in days
	$payment_duration_values = Array(
		'net 30 days' => '30',
		'net 45 days' => '45',
		'net 60 days' => '60'
	);

	if(!$current_user) {
		$current_user = CRMEntity::getInstance('Users');
		$current_user->id = 1;
		$current_user = $current_user->retrieve_entity_info($current_user->id, "Users");
	}
	$so_focus = CRMEntity::getInstance('SalesOrder');
	$so_focus->id = $salesorder_id;
	$so_focus->retrieve_entity_info($salesorder_id,"SalesOrder");
	foreach($so_focus->column_fields as $fieldname=>$value) {
		$so_focus->column_fields[$fieldname] = decode_html($value);
	}

	$focus = CRMEntity::getInstance('Invoice');
	// This will only fill in the basic columns from SO to Invoice and also Update the SO id in new Invoice
	$focus = getConvertSoToInvoice($focus,$so_focus,$salesorder_id);

	// Pick up the Payment due date based on the Configuration in SO
	$payment_duration = $so_focus->column_fields['payment_duration'];
	$due_duration = $payment_duration_values[trim(strtolower($payment_duration))];
	$durationinsec = mktime(0,0,0,date('m'),date('d')+$due_duration,date('Y'));

	// Cleanup focus object, to duplicate the Invoice.
	$focus->id = '';
	$focus->mode = '';
	$focus->column_fields['invoicestatus'] = $so_focus->column_fields['invoicestatus'];
	$focus->column_fields['invoicedate'] = date('Y-m-d');
	$focus->column_fields['duedate'] = date('Y-m-d', $durationinsec);

	// Additional SO fields to copy -> Invoice field name mapped to equivalent SO field name
	$invoice_so_fields = Array (
		'txtAdjustment' => 'txtAdjustment',
		'hdnSubTotal' => 'hdnSubTotal',
		'hdnGrandTotal' => 'hdnGrandTotal',
		'hdnTaxType' => 'hdnTaxType',
		'hdnDiscountPercent' => 'hdnDiscountPercent',
		'hdnDiscountAmount' => 'hdnDiscountAmount',
		'hdnS_H_Amount' => 'hdnS_H_Amount',
		'assigned_user_id' => 'assigned_user_id',
		'currency_id' => 'currency_id',
		'conversion_rate' => 'conversion_rate',
	);
	foreach($invoice_so_fields as $invoice_field => $so_field) {
		$focus->column_fields[$invoice_field] = $so_focus->column_fields[$so_field];
	}
	$focus->_salesorderid = $salesorder_id;
	$focus->_recurring_mode = 'recurringinvoice_from_so';
	$focus->save("Invoice");
}

?>
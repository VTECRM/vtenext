<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file

// Account is used to store vte_account information.
class Invoice extends CRMEntity {
	var $log;
	var $db;

	var $table_name;
	var $table_index= 'invoiceid';
	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	var $column_fields = Array();

	var $update_product_array = Array();

	var $sortby_fields = Array('subject','invoice_no','invoicestatus','smownerid','accountname','lastname');

	// This is used to retrieve related vte_fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'smownerid', 'opportunity_id', 'case_id', 'contact_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id', 'parent_name', 'member_id' );

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
	//crmv@8056
				'Invoice No'=>Array('invoice'=>'invoice_no'),
	//crmv@8056e
				'Subject'=>Array('invoice'=>'subject'),
				'Sales Order'=>Array('invoice'=>'salesorderid'),
				'Status'=>Array('invoice'=>'invoicestatus'),
				'Total'=>Array('invoice'=>'total'),
				'Assigned To'=>Array('crmentity'=>'smownerid')
				);

	var $list_fields_name = Array(
				        'Invoice No'=>'invoice_no',
				        'Subject'=>'subject',
				        'Sales Order'=>'salesorder_id',
				        'Status'=>'invoicestatus',
				        'Total'=>'hdnGrandTotal',
				        'Assigned To'=>'assigned_user_id'
				      );
	var $list_link_field= 'subject';

	var $search_fields = Array(
		//crmv@8056
				'Invoice No'=>Array('invoice'=>'invoice_no'),
		//crmv@8056e
				'Subject'=>Array('purchaseorder'=>'subject'),
				);

	var $search_fields_name = Array(
			//crmv@8056
				        'Invoice No'=>'invoice_no',
			//crmv@8056e
				        'Subject'=>'subject',
				      );

	// This is the list of vte_fields that are required.
	var $required_fields =  array("accountname"=>1);

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'crmid';
	var $default_sort_order = 'ASC';

	//var $groupTable = Array('vte_invoicegrouprelation','invoiceid');

	var $mandatory_fields = Array('subject','createdtime' ,'modifiedtime');
	var $_salesorderid;
	var $_recurring_mode;

	//crmv@10759
	var $search_base_field = 'subject';
	//crmv@10759 e
	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_invoice";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_invoice',$table_prefix.'_invoicebillads',$table_prefix.'_invoiceshipads',$table_prefix.'_invoicecf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_invoice'=>'invoiceid',$table_prefix.'_invoicebillads'=>'invoicebilladdressid',$table_prefix.'_invoiceshipads'=>'invoiceshipaddressid',$table_prefix.'_invoicecf'=>'invoiceid');
		$this->customFieldTable = Array($table_prefix.'_invoicecf', 'invoiceid');
		$this->log =LoggerManager::getLogger('Invoice');
		$this->log->debug("Entering Invoice() method ...");
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Invoice');
		$this->log->debug("Exiting Invoice method ...");
	}


	/** Function to handle the module specific save operations
	*/
	function save_module($module)
	{
		global $table_prefix,$iAmAProcess;

		//in ajax save we should not call this function, because this will delete all the existing product values
		if(isset($this->_recurring_mode) && $this->_recurring_mode == 'recurringinvoice_from_so' && isset($this->_salesorderid) && $this->_salesorderid!='') {
			// We are getting called from the RecurringInvoice cron service!
			$this->createRecurringInvoiceFromSO();

		} elseif(!empty($_REQUEST) && isset($_REQUEST['totalProductCount']) && $_REQUEST['action'] != 'InvoiceAjax' && $_REQUEST['ajxaction'] != 'DETAILVIEW' && $_REQUEST['action'] != 'MassEditSave' && !$iAmAProcess) { // crmv@138794 crmv@196424
			$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
			//Based on the total Number of rows we will save the product relationship with this entity
			$InventoryUtils->saveInventoryProductDetails($this, 'Invoice');
		}

		// Update the currency id and the conversion rate for the invoice
		$update_query = "update ".$table_prefix."_invoice set currency_id=?, conversion_rate=? where invoiceid=?";

		$update_params = array($this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id);
		$this->db->pquery($update_query, $update_params);
	}

	/**	function used to get the name of the current object
	 *	@return string $this->name - name of the current object
	 */
	function get_summary_text()
	{
		global $log;
		$log->debug("Entering get_summary_text() method ...");
		$log->debug("Exiting get_summary_text method ...");
		return $this->name;
	}

	// Function to get column name - Overriding function of base class
	function get_column_value($columnname, $fldvalue, $fieldname, $uitype, $datatype='') { // crmv@146653
		if ($columnname == 'salesorderid') {
			if ($fldvalue == '') return null;
		}
		return parent::get_column_value($columnname, $fldvalue, $fieldname, $uitype, $datatype);
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	// crmv@35693 crmv@38798 crmv@73751
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;

		$vte_inventoryproductrelInvoice = substr($table_prefix.'_inventoryproductrelInvoice',0,29);

		if ($reporttype != 'COLUMNSTOTOTAL' && $useProductJoin) {
			$productjoins = " left join {$table_prefix}_inventoryproductrel $vte_inventoryproductrelInvoice on {$table_prefix}_invoice.invoiceid = $vte_inventoryproductrelInvoice.id
			left join {$table_prefix}_products {$table_prefix}_productsInvoice on {$table_prefix}_productsInvoice.productid = ".substr("{$table_prefix}_inventoryproductrelInvoice", 0, 29).".productid
			left join {$table_prefix}_service {$table_prefix}_serviceInvoice on {$table_prefix}_serviceInvoice.serviceid = ".substr("{$table_prefix}_inventoryproductrelInvoice", 0,29).".productid ";
		}

		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_invoice","invoiceid");
		$query .= " left join {$table_prefix}_invoicecf on {$table_prefix}_invoice.invoiceid = {$table_prefix}_invoicecf.invoiceid
		left join {$table_prefix}_salesorder {$table_prefix}_salesorderInvoice on {$table_prefix}_salesorderInvoice.salesorderid={$table_prefix}_invoice.salesorderid
		left join {$table_prefix}_invoicebillads on {$table_prefix}_invoice.invoiceid={$table_prefix}_invoicebillads.invoicebilladdressid
		left join {$table_prefix}_invoiceshipads on {$table_prefix}_invoice.invoiceid={$table_prefix}_invoiceshipads.invoiceshipaddressid
		left join {$table_prefix}_inventorytotals ".substr($table_prefix.'_inventorytotalsInvoice',0,29)." on ".substr($table_prefix.'_inventorytotalsInvoice',0,29).".id = {$table_prefix}_invoice.invoiceid
		$productjoins
		left join {$table_prefix}_groups {$table_prefix}_groupsInvoice on {$table_prefix}_groupsInvoice.groupid = {$table_prefix}_crmentityInvoice.smownerid
		left join {$table_prefix}_users {$table_prefix}_usersInvoice on {$table_prefix}_usersInvoice.id = {$table_prefix}_crmentityInvoice.smownerid
		left join {$table_prefix}_contactdetails {$table_prefix}_contactdetailsInvoice on {$table_prefix}_invoice.contactid = {$table_prefix}_contactdetailsInvoice.contactid
		left join {$table_prefix}_account {$table_prefix}_accountInvoice on {$table_prefix}_accountInvoice.accountid = {$table_prefix}_invoice.accountid ";
		return $query;
	}
	// crmv@35693e crmv@38798e	crmv@73751e

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"Calendar" =>array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_invoice"=>"invoiceid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_invoice"=>"invoiceid"),
			"Accounts" => array($table_prefix."_invoice"=>array("invoiceid","accountid")),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log,$table_prefix;;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Accounts' || $return_module == 'Contacts') {
			$this->trash('Invoice',$id);
		} elseif($return_module=='SalesOrder') {
			$relation_query = 'UPDATE '.$table_prefix.'_invoice set salesorderid=0 where invoiceid=?';
			$this->db->pquery($relation_query, array($id));
		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

	/*
	 * Function to get the relations of salesorder to invoice for recurring invoice procedure
	 * @param - $salesorder_id Salesorder ID
	 */
	function createRecurringInvoiceFromSO(){
		global $adb,$table_prefix;
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
		$salesorder_id = $this->_salesorderid;
		$query1 = "SELECT * FROM ".$table_prefix."_inventoryproductrel WHERE id=?";
		$res = $adb->pquery($query1, array($salesorder_id));
		$no_of_products = $adb->num_rows($res);
		$fieldsList = $adb->getFieldsArray($res);
		$update_stock = array();
		for($j=0; $j<$no_of_products; $j++) {
			$row = $adb->query_result_rowdata($res, $j);
			$col_value = array();
			for($k=0; $k<count($fieldsList); $k++) {
				//crmv@19791
				if($fieldsList[$k]=='lineitem_id')
					$col_value[$fieldsList[$k]] = $adb->getUniqueID($table_prefix.'_inventoryproductrel');
				// crmv@179438
				elseif($fieldsList[$k]=='relmodule')
					$col_value[$fieldsList[$k]] = 'Invoice';
				// crmv@179438e
				else
					$col_value[$fieldsList[$k]] = $row[$fieldsList[$k]];
				//crmv@19791e
			}
			if(count($col_value) > 0) {
				$col_value['id'] = $this->id;
				$columns = array_keys($col_value);
				$adb->format_columns($columns);
				$values = array_values($col_value);
				$query2 = "INSERT INTO ".$table_prefix."_inventoryproductrel(". implode(",",$columns) .") VALUES (". generateQuestionMarks($values) .")";
				$adb->pquery($query2, array($values));
				$prod_id = $col_value['productid'];
				$qty = $col_value['quantity'];
				$update_stock[$col_value['sequence_no']] = $qty;
				$InventoryUtils->updateStk($prod_id,$qty,'',array(),'Invoice'); // crmv@42024
			}
		}

		$query1 = "SELECT * FROM ".$table_prefix."_inventorysubproductrel WHERE id=?";
		$res = $adb->pquery($query1, array($salesorder_id));
		$no_of_products = $adb->num_rows($res);
		$fieldsList = $adb->getFieldsArray($res);
		for($j=0; $j<$no_of_products; $j++) {
			$row = $adb->query_result_rowdata($res, $j);
			$col_value = array();
			for($k=0; $k<count($fieldsList); $k++) {
					$col_value[$fieldsList[$k]] = $row[$fieldsList[$k]];
			}
			if(count($col_value) > 0) {
				$col_value['id'] = $this->id;
				$columns = array_keys($col_value);
				$values = array_values($col_value);
				$query2 = "INSERT INTO ".$table_prefix."_inventorysubproductrel(". implode(",",$columns) .") VALUES (". generateQuestionMarks($values) .")";
				$adb->pquery($query2, array($values));
				$prod_id = $col_value['productid'];
				$qty = $update_stock[$col_value['sequence_no']];
				$InventoryUtils->updateStk($prod_id,$qty,'',array(),'Invoice'); // crmv@42024
			}
		}

		// Add the Shipping taxes for the Invoice
		$query3 = "SELECT * FROM ".$table_prefix."_inventoryshippingrel WHERE id=?";
		$res = $adb->pquery($query3, array($salesorder_id));
		$no_of_shippingtax = $adb->num_rows($res);
		$fieldsList = $adb->getFieldsArray($res);
		for($j=0; $j<$no_of_shippingtax; $j++) {
			$row = $adb->query_result_rowdata($res, $j);
			$col_value = array();
			for($k=0; $k<count($fieldsList); $k++) {
				$col_value[$fieldsList[$k]] = $row[$fieldsList[$k]];
			}
			if(count($col_value) > 0) {
				$col_value['id'] = $this->id;
				$columns = array_keys($col_value);
				$values = array_values($col_value);
				$query4 = "INSERT INTO ".$table_prefix."_inventoryshippingrel(". implode(",",$columns) .") VALUES (". generateQuestionMarks($values) .")";
				$adb->pquery($query4, array($values));
			}
		}

		//Update the netprice (subtotal), taxtype, discount, S&H charge, adjustment and total for the Invoice

		$updatequery  = " UPDATE ".$table_prefix."_invoice SET ";
		$updateparams = array();
		// Remaining column values to be updated -> column name to field name mapping
		$invoice_column_field = Array (
			'adjustment' => 'txtAdjustment',
			'subtotal' => 'hdnSubTotal',
			'total' => 'hdnGrandTotal',
			'taxtype' => 'hdnTaxType',
			'discount_percent' => 'hdnDiscountPercent',
			'discount_amount' => 'hdnDiscountAmount',
			's_h_amount' => 'hdnS_H_Amount',
		);
		$updatecols = array();
		foreach($invoice_column_field as $col => $field) {
			$updatecols[] = "$col=?";
			$updateparams[] = $this->column_fields[$field];
		}
		if (count($updatecols) > 0) {
			$updatequery .= implode(",", $updatecols);

			$updatequery .= " WHERE invoiceid=?";
			array_push($updateparams, $this->id);

			$adb->pquery($updatequery, $updateparams);
		}
	}

}

?>
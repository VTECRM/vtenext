<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file

// Account is used to store vte_account information.
class PurchaseOrder extends CRMEntity {
	var $log;
	var $db;

	var $table_name;
	var $table_index= 'purchaseorderid';
	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();
	var $entity_table;

	var $billadr_table;

	var $column_fields = Array();

	var $sortby_fields = Array('subject','tracking_no','smownerid','lastname');

	// This is used to retrieve related vte_fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'smownerid', 'opportunity_id', 'case_id', 'contact_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id', 'parent_name', 'member_id' );

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
				//  Module Sequence Numbering
				//'Order No'=>Array('crmentity'=>'crmid'),
				'Order No'=>Array('purchaseorder'=>'purchaseorder_no'),
				// END
				'Subject'=>Array('purchaseorder'=>'subject'),
				'Vendor Name'=>Array('purchaseorder'=>'vendorid'),
				'Tracking Number'=>Array('purchaseorder'=> 'tracking_no'),
				'Total'=>Array('purchaseorder'=>'total'),
				'Assigned To'=>Array('crmentity'=>'smownerid')
				);

	var $list_fields_name = Array(
				        'Order No'=>'purchaseorder_no',
				        'Subject'=>'subject',
				        'Vendor Name'=>'vendor_id',
					'Tracking Number'=>'tracking_no',
					'Total'=>'hdnGrandTotal',
				        'Assigned To'=>'assigned_user_id'
				      );
	var $list_link_field= 'subject';

	var $search_fields = Array(
				'Order No'=>Array('purchaseorder'=>'purchaseorder_no'),
				'Subject'=>Array('purchaseorder'=>'subject'),
				);

	var $search_fields_name = Array(
				        'Order No'=>'purchaseorder_no',
				        'Subject'=>'subject',
				      );
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('subject', 'vendor_id','createdtime' ,'modifiedtime');

	// This is the list of vte_fields that are required.
	var $required_fields =  array("accountname"=>1);

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'subject';
	var $default_sort_order = 'ASC';
	//crmv@10759
	var $search_base_field = 'subject';
	//crmv@10759 e

	//var $groupTable = Array('vte_pogrouprelation','purchaseorderid');
	/** Constructor Function for Order class
	 *  This function creates an instance of LoggerManager class using getLogger method
	 *  creates an instance for PearDatabase class and get values for column_fields array of Order class.
	 */
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_purchaseorder";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_purchaseorder',$table_prefix.'_pobillads',$table_prefix.'_poshipads',$table_prefix.'_purchaseordercf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_purchaseorder'=>'purchaseorderid',$table_prefix.'_pobillads'=>'pobilladdressid',$table_prefix.'_poshipads'=>'poshipaddressid',$table_prefix.'_purchaseordercf'=>'purchaseorderid');
		$this->customFieldTable = Array($table_prefix.'_purchaseordercf', 'purchaseorderid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->billadr_table = $table_prefix."_pobillads";
		$this->log =LoggerManager::getLogger('PurchaseOrder');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('PurchaseOrder');
	}

	function save_module($module) {
		global $adb,$table_prefix,$iAmAProcess;
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
		//in ajax save we should not call this function, because this will delete all the existing product values
		if(!empty($_REQUEST) && isset($_REQUEST['totalProductCount']) && $_REQUEST['action'] != 'PurchaseOrderAjax' && $_REQUEST['ajxaction'] != 'DETAILVIEW' && $_REQUEST['action'] != 'MassEditSave' && !$iAmAProcess) // crmv@138794 crmv@196424
		{
			//Based on the total Number of rows we will save the product relationship with this entity
			$InventoryUtils->saveInventoryProductDetails($this, 'PurchaseOrder', $this->update_prod_stock);
		}

		//In Ajax edit, if the status changed to Received Shipment then we have to update the product stock
		if($_REQUEST['action'] == 'PurchaseOrderAjax' && $this->update_prod_stock == 'true')
		{
			$inventory_res = $this->db->pquery("select productid, quantity from ".$table_prefix."_inventoryproductrel where id=?",array($this->id));
			$noofproducts = $this->db->num_rows($inventory_res);

			//We have to update the stock for all the products in this PO
			for($prod_count=0;$prod_count<$noofproducts;$prod_count++)
			{
				$productid = $this->db->query_result($inventory_res,$prod_count,'productid');
				$quantity = $this->db->query_result($inventory_res,$prod_count,'quantity');
				$this->db->println("Stock is going to be updated for the productid - $productid with quantity - $quantity");

				addToProductStock($productid,$quantity);
			}
		}

		// Update the currency id and the conversion rate for the purchase order
		$update_query = "update ".$table_prefix."_purchaseorder set currency_id=?, conversion_rate=? where purchaseorderid=?";
		$update_params = array($this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id);
		$adb->pquery($update_query, $update_params);
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

		if ($reporttype != 'COLUMNSTOTOTAL' && $useProductJoin) {
			$productjoins = " left join ".$table_prefix."_inventoryproductrel ".substr($table_prefix."_inventoryproductrel$secmodule",0,29)." on ".$table_prefix."_purchaseorder.purchaseorderid = ".substr($table_prefix."_inventoryproductrel$secmodule",0,29).".id
							left join ".$table_prefix."_products ".$table_prefix."_products$secmodule on ".$table_prefix."_products$secmodule.productid = ".substr($table_prefix."_inventoryproductrel$secmodule",0,29).".productid
							left join ".$table_prefix."_service ".$table_prefix."_service$secmodule on ".$table_prefix."_service$secmodule.serviceid = ".substr($table_prefix."_inventoryproductrel$secmodule",0,29).".productid ";
		}
		$query = $this->getRelationQuery($module,$secmodule,"".$table_prefix."_purchaseorder","purchaseorderid");
		$query .= "	left join ".$table_prefix."_purchaseordercf on ".$table_prefix."_purchaseorder.purchaseorderid = ".$table_prefix."_purchaseordercf.purchaseorderid
					left join ".$table_prefix."_pobillads on ".$table_prefix."_purchaseorder.purchaseorderid=".$table_prefix."_pobillads.pobilladdressid
					left join ".$table_prefix."_poshipads on ".$table_prefix."_purchaseorder.purchaseorderid=".$table_prefix."_poshipads.poshipaddressid
					left join ".$table_prefix."_inventorytotals on ".$table_prefix."_inventorytotals.id = ".$table_prefix."_purchaseorder.purchaseorderid
					left join ".$table_prefix."_inventorytotals ".substr($table_prefix."_inventorytotals$secmodule",0,29)." on ".substr($table_prefix."_inventorytotals$secmodule",0,29).".id = ".$table_prefix."_purchaseorder.purchaseorderid
					$productjoins
					left join ".$table_prefix."_users ".$table_prefix."_users$secmodule on ".$table_prefix."_users$secmodule.id = ".$table_prefix."_crmentity$secmodule.smownerid
					left join ".$table_prefix."_groups ".$table_prefix."_groups$secmodule on ".$table_prefix."_groups$secmodule.groupid = ".$table_prefix."_crmentity$secmodule.smownerid
					left join ".$table_prefix."_vendor ".$table_prefix."_vendorRel$secmodule on ".$table_prefix."_vendorRel$secmodule.vendorid = ".$table_prefix."_purchaseorder.vendorid
					left join ".$table_prefix."_contactdetails ".substr($table_prefix."_contactdetails$secmodule",0,29)." on ".substr($table_prefix."_contactdetails$secmodule",0,29).".contactid = ".$table_prefix."_purchaseorder.contactid ";

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
			"Calendar" =>array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_purchaseorder"=>"purchaseorderid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_purchaseorder"=>"purchaseorderid"),
			"Contacts" => array($table_prefix."_purchaseorder"=>array("purchaseorderid","contactid")),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log,$table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Vendors') {
			$sql_req ='UPDATE '.$table_prefix.'_crmentity SET deleted = 1 WHERE crmid= ?';
			$this->db->pquery($sql_req, array($id));
		} elseif($return_module == 'Contacts') {
			$sql_req ='UPDATE '.$table_prefix.'_purchaseorder SET contactid=0 WHERE purchaseorderid = ?';
			$this->db->pquery($sql_req, array($id));
		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

}

?>
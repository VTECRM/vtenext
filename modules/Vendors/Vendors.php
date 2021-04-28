<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/RelatedListView.php');
//crmv@203484 removed including file

class Vendors extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'vendorid';
	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();
	var $column_fields = Array();

        //Pavani: Assign value to entity_table
        var $entity_table;
        var $sortby_fields = Array();

        // This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
                                'Vendor Name'=>Array('vendor'=>'vendorname'),
                                'Phone'=>Array('vendor'=>'phone'),
                                'Email'=>Array('vendor'=>'email'),
                                'Category'=>Array('vendor'=>'category')
                                );
        var $list_fields_name = Array(
                                        'Vendor Name'=>'vendorname',
                                        'Phone'=>'phone',
                                        'Email'=>'email',
                                        'Category'=>'category'
                                     );
        var $list_link_field= 'vendorname';

	var $search_fields = Array(
                                'Vendor Name'=>Array('vendor'=>'vendorname'),
                                'Phone'=>Array('vendor'=>'phone'),
                                'Fax'=>Array('vendor'=>'fax'),
                                );
        var $search_fields_name = Array(
                                        'Vendor Name'=>'vendorname',
                                        'Phone'=>'phone',
                                        'Fax'=>'fax',
                                     );
	//Specifying required fields for vendors
        var $required_fields =  array();

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'vendorname');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'vendorname';
	var $default_sort_order = 'ASC';
	//crmv@10759
	var $search_base_field = 'vendorname';
	//crmv@10759 e
	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_vendor";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_vendor',$table_prefix.'_vendorcf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_vendor'=>'vendorid',$table_prefix.'_vendorcf'=>'vendorid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->customFieldTable = Array($table_prefix.'_vendorcf', 'vendorid');	//crmv@33507
		$this->log =LoggerManager::getLogger('vendor');
		$this->log->debug("Entering Vendors() method ...");
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Vendors');
		$this->log->debug("Exiting Vendor method ...");
	}

	function save_module($module)
	{
	}

	/**	function used to get the list of contacts which are related to the vendor
	 *	@param int $id - vendor id
	 *	@return array - array which will be returned from the function GetRelatedList
	 */
	function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		$log->debug("Entering get_contacts(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
        vtlib_setup_modulevars($related_module, $other);
		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';
		if($actions) {
			$button .= $this->get_related_buttons($this_module, $id, $related_module, $actions); // crmv@43864
		}

		$query = "SELECT case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name,".$table_prefix."_contactdetails.*, ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.smownerid,".$table_prefix."_vendorcontactrel.vendorid,".$table_prefix."_account.accountname
			from ".$table_prefix."_contactdetails
			inner join ".$table_prefix."_contactscf on ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_contactdetails.contactid
			inner join ".$table_prefix."_vendorcontactrel on ".$table_prefix."_vendorcontactrel.contactid=".$table_prefix."_contactdetails.contactid
			left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid=".$table_prefix."_crmentity.smownerid
			left join ".$table_prefix."_account on ".$table_prefix."_account.accountid = ".$table_prefix."_contactdetails.accountid
			left join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid
			where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_vendorcontactrel.vendorid = ".$id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_contacts method ...");
		return $return_value;
	}

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb,$log;
		global $table_prefix;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");

		$rel_table_arr = Array("Products"=>$table_prefix."_products","PurchaseOrder"=>$table_prefix."_purchaseorder","Contacts"=>$table_prefix."_vendorcontactrel");

		$tbl_field_arr = Array($table_prefix."_products"=>"productid",$table_prefix."_vendorcontactrel"=>"contactid",$table_prefix."_purchaseorder"=>"purchaseorderid");

		$entity_tbl_field_arr = Array($table_prefix."_products"=>"vendor_id",$table_prefix."_vendorcontactrel"=>"vendorid",$table_prefix."_purchaseorder"=>"vendorid");

		foreach($transferEntityIds as $transferId) {
			foreach($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
						" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)",
						array($transferId,$entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if($res_cnt > 0) {
					for($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result,$i,$id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?",
							array($entityId,$transferId,$id_field_value));
					}
				}
			}
		}
		//crmv@15526
		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		//crmv@15526 end
		$log->debug("Exiting transferRelatedRecords...");
	}

	// crmv@152701
	/** 
	 *Returns a list of the associated faxes
	 */
	function get_faxes($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $currentModule;

		$log->debug("Entering get_faxes(".$id.") method ...");
		
		$this_module = $currentModule;
		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$singular_modname = vtlib_toSingular($related_module);

		$button = '<input type="hidden" name="fax_directing_module"><input type="hidden" name="record">';

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."' accessyKey='F' class='crmbutton small create' onclick='fnvshobj(this,\"sendfax_cont\");sendfax(\"$this_module\",$id);' type='button' name='button' value='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."'>&nbsp;";
			}
		}

		// call standard function
		$ret = $this->get_related_list($id, $cur_tab_id, $rel_tab_id, $actions);
		
		// override button
		$ret['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_faxes method ...");
		return $ret;
	}
	// crmv@152701e
	
	/*
	 * Function to get the primary query part of a report
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */
	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true) { // crmv@146653
		global $table_prefix;
	 			$moduletable = $this->table_name;
	 			$moduleindex = $this->table_index;
	 			$modulecftable = $this->tab_name[2];
	 			$modulecfindex = $this->tab_name_index[$modulecftable];
	 			//crmv@21249
	 			$query = "from $moduletable
			        inner join $modulecftable $modulecftable on $modulecftable.$modulecfindex=$moduletable.$moduleindex
					inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=$moduletable.$moduleindex
					left join ".$table_prefix."_users ".substr($table_prefix."_users$module",0,29)." on ".substr($table_prefix."_users$module",0,29).".id = ".$table_prefix."_crmentity.smownerid
					left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
				//crmv@21249e
	            return $query;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"Products" =>array($table_prefix."_products"=>array("vendor_id","productid"),$table_prefix."_vendor"=>"vendorid"),
			"PurchaseOrder" =>array($table_prefix."_purchaseorder"=>array("vendorid","purchaseorderid"),$table_prefix."_vendor"=>"vendorid"),
			"Contacts" =>array($table_prefix."_vendorcontactrel"=>array("vendorid","contactid"),$table_prefix."_vendor"=>"vendorid"),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log;
		global $table_prefix;
		//Deleting Vendor related PO.
		$po_q = 'SELECT '.$table_prefix.'_crmentity.crmid FROM '.$table_prefix.'_crmentity
			INNER JOIN '.$table_prefix.'_purchaseorder ON '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_purchaseorder.purchaseorderid
			INNER JOIN '.$table_prefix.'_vendor ON '.$table_prefix.'_vendor.vendorid='.$table_prefix.'_purchaseorder.vendorid
			WHERE '.$table_prefix.'_crmentity.deleted=0 AND '.$table_prefix.'_purchaseorder.vendorid=?';
		$po_res = $this->db->pquery($po_q, array($id));
		$po_ids_list = array();
		for($k=0;$k < $this->db->num_rows($po_res);$k++)
		{
			$po_id = $this->db->query_result($po_res,$k,"crmid");
			$po_ids_list[] = $po_id;
			$sql = 'UPDATE '.$table_prefix.'_crmentity SET deleted = 1 WHERE crmid = ?';
			$this->db->pquery($sql, array($po_id));
		}
		//Backup deleted Vendors related Potentials.
		$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_crmentity', 'deleted', 'crmid', implode(",", $po_ids_list));
		$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);

		//Backup Product-Vendor Relation
		$pro_q = 'SELECT productid FROM '.$table_prefix.'_products WHERE vendor_id=?';
		$pro_res = $this->db->pquery($pro_q, array($id));
		if ($this->db->num_rows($pro_res) > 0) {
			$pro_ids_list = array();
			for($k=0;$k < $this->db->num_rows($pro_res);$k++)
			{
				$pro_ids_list[] = $this->db->query_result($pro_res,$k,"productid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_products', 'vendor_id', 'productid', implode(",", $pro_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
		}
		//Deleting Product-Vendor Relation.
		$pro_q = 'UPDATE '.$table_prefix.'_products SET vendor_id = 0 WHERE vendor_id = ?';
		$this->db->pquery($pro_q, array($id));

		/*//Backup Contact-Vendor Relaton
		$con_q = 'SELECT contactid FROM vte_vendorcontactrel WHERE vendorid = ?';
		$con_res = $this->db->pquery($con_q, array($id));
		if ($this->db->num_rows($con_res) > 0) {
			for($k=0;$k < $this->db->num_rows($con_res);$k++)
			{
				$con_id = $this->db->query_result($con_res,$k,"contactid");
				$params = array($id, RB_RECORD_DELETED, 'vte_vendorcontactrel', 'vendorid', 'contactid', $con_id);
				$this->db->pquery('INSERT INTO vte_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
			}
		}
		//Deleting Contact-Vendor Relaton
		$vc_sql = 'DELETE FROM vte_vendorcontactrel WHERE vendorid=?';
		$this->db->pquery($vc_sql, array($id));*/

		parent::unlinkDependencies($module, $id);
	}

}
?>
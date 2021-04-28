<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


class Assets extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'assetsid';
	var $column_fields = Array();

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array();

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array();

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array(
   		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Asset No'=>Array('assets'=>'asset_no'),
        'Asset Name'=>Array('assets'=>'assetname'),
		'Customer Name'=>Array('account'=>'account'),
        'Product Name'=>Array('products'=>'product'),
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Asset No'=>'asset_no',
        'Asset Name'=>'assetname',
		'Customer Name'=>'account',
        'Product Name'=>'product',
	);

	// Make the field link to detail view
	var $list_link_field= 'assetname';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Asset No'=>Array('assets'=>'asset_no'),
        'Asset Name'=>Array('assets'=>'assetname'),
		'Customer Name'=>Array('account'=>'account'),
		'Product Name'=>Array('products'=>'product')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Asset No'=>'asset_no',
        'Asset Name'=>'assetname',
		'Customer Name'=>'account',
		'Product Name'=>'product'
	);

	// For Popup window record selection
	var $popup_fields = Array ('assetname','account','product');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'assetname';

	// Required Information for enabling Import feature
	var $required_fields = Array('assetname'=>1);

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'assetname', 'product'); // crmv@177975
	
	//crmv@10759
	var $search_base_field = 'assetname';
	//crmv@10759 e

	var $default_order_by = 'assetname';
	var $default_sort_order='ASC';

	var $unit_price;

	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $log, $table_prefix;
		parent::__construct(); // crmv@37004
		$this->customFieldTable = Array($table_prefix.'_assetscf', 'assetsid');
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_assets',$table_prefix.'_assetscf');
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity'=>'crmid',
			$table_prefix.'_assets'=>'assetsid',
			$table_prefix.'_assetscf'=>'assetsid');
		$this->table_name = $table_prefix.'_assets';
		$this->column_fields = getColumnFields('Assets');
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	function save_module($module){
		//module specific save
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Transform the value while exporting
	 */
	function transform_export_value($key, $value) {
		if($key == 'owner') return getOwnerName($value);
		return parent::transform_export_value($key, $value);
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }


	/*
	 * Function to get the primary query part of a report
	 * @param - $module primary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true){ // crmv@146653
		global $current_user, $table_prefix;
			//crmv@21249
			$query = "from ".$table_prefix."_assets
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_assets.assetsid
				left join ".$table_prefix."_assetscf on ".$table_prefix."_assets.assetsid = ".$table_prefix."_assetscf.assetsid
				left join ".$table_prefix."_account ".$table_prefix."_accountAssets on ".$table_prefix."_accountAssets.accountid=".$table_prefix."_assets.account
				left join ".$table_prefix."_products ".$table_prefix."_productAssets on ".$table_prefix."_productAssets.productid=".$table_prefix."_assets.product
				left join ".$table_prefix."_invoice ".$table_prefix."_invoiceAssets on ".$table_prefix."_invoiceAssets.invoiceid=".$table_prefix."_assets.invoiceid
				left join ".$table_prefix."_users ".$table_prefix."_usersAssets on ".$table_prefix."_usersAssets.id=".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups ".$table_prefix."_groupsAssets on ".$table_prefix."_groupsAssets.groupid=".$table_prefix."_crmentity.smownerid";
			//crmv@21249e
			return $query;
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $current_user,$table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_assets","assetsid");
		//crmv@21249
		$query .= " left join ".$table_prefix."_assetscf on ".$table_prefix."_assets.assetsid = ".$table_prefix."_assetscf.assetsid
			left join ".$table_prefix."_account ".$table_prefix."_accountAssets on ".$table_prefix."_accountAssets.accountid=".$table_prefix."_assets.account
            left join ".$table_prefix."_products ".$table_prefix."_productAssets on ".$table_prefix."_productAssets.productid=".$table_prefix."_assets.product
            left join ".$table_prefix."_invoice ".$table_prefix."_invoiceAssets on ".$table_prefix."_invoiceAssets.invoiceid=".$table_prefix."_assets.invoiceid
            left join ".$table_prefix."_users ".$table_prefix."_usersAssets on ".$table_prefix."_usersAssets.id=".$table_prefix."_crmentity.smownerid
            left join ".$table_prefix."_groups ".$table_prefix."_groupsAssets on ".$table_prefix."_groupsAssets.groupid=".$table_prefix."_crmentity.smownerid ";
		//crmv@21249e
		return $query;
	}
	//crmv@38798e


	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log;
		parent::unlinkDependencies($module, $id);
	}

 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		require_once('include/utils/utils.php');
		global $adb,$table_prefix;

 		if($eventType == 'module.postinstall') {
			//Add Assets Module to Customer Portal
			//crmv@16644 : Sposto questa operazione direttamente nell'installazione CustomerPortal
			/*
			global $adb;
			$visible=1;
			$query = $adb->pquery("SELECT max(sequence) AS max_tabseq FROM vte_customerportal_tabs",array());
			$maxtabseq = $adb->query_result($query, 0, 'max_tabseq');
			$newTabSeq = ++$maxtabseq;
			$tabid = getTabid('Assets');
			$adb->pquery("INSERT INTO vte_customerportal_tabs(tabid, visible, sequence) VALUES(?,?,?)", array($tabid,$visible,$newTabSeq));
			*/
			//crmv@16644e
			
			include_once('vtlib/Vtecrm/Module.php');//crmv@207871

			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));

			$assetInstance = Vtecrm_Module::getInstance('Assets');
			$assetLabel = 'Assets';
			
			//adds sharing accsess
			Vtecrm_Access::setDefaultSharing($assetInstance);

			//Showing Assets module in the related modules in the More Information Tab
			$accountInstance = Vtecrm_Module::getInstance('Accounts');
			$accountInstance->setRelatedlist($assetInstance,$assetLabel,array('ADD'),'get_dependents_list'); // crmv@167234 

			$productInstance = Vtecrm_Module::getInstance('Products');
			$productInstance->setRelatedlist($assetInstance,$assetLabel,array('ADD'),'get_dependents_list'); // crmv@167234 

			$InvoiceInstance = Vtecrm_Module::getInstance('Invoice');
			$InvoiceInstance->setRelatedlist($assetInstance,$assetLabel,array('ADD'),'get_dependents_list'); // crmv@167234
			
			//crmv@16644
			$SalesorderInstance = Vtecrm_Module::getInstance('SalesOrder');
			$SalesorderInstance->setRelatedlist($assetInstance,$assetLabel,'','get_dependents_list');
			//crmv@16644e
			
			//crmv@21786
			$HelpDeskInstance = Vtecrm_Module::getInstance('HelpDesk');
			$HelpDeskInstance->setRelatedlist($assetInstance,$assetLabel,array('ADD','SELECT'),'get_related_list');
			//crmv@21786e
			
			//crmv@58540
			$docModuleInstance = Vtecrm_Module::getInstance('Documents');
			$docModuleInstance->setRelatedList($assetInstance,$assetLabel,array('select','add'),'get_documents_dependents_list');
			//crmv@58540e

		} else if($eventType == 'module.disabled') {
		// TODO Handle actions when this module is disabled.
		} else if($eventType == 'module.enabled') {
		// TODO Handle actions when this module is enabled.
		} else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
		// TODO Handle actions after this module is updated.
		}
 	}
}
?>
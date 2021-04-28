<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Ddt extends CRMEntity {
	var $db, $log, $table_prefix; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'ddtid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

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
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'ddt Name'=> Array('ddt', 'subject'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'ddt Name'=> 'subject',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'subject';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'ddt Name'=> Array('ddt', 'subject')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'ddt Name'=> 'subject'
	);

	// For Popup window record selection
	var $popup_fields = Array('subject');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'subject';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'subject';

	// Required Information for enabling Import feature
	var $required_fields = Array('subject'=>1);

	var $default_order_by = 'subject';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'subject'); // crmv@177975
	//crmv@10759
	var $search_base_field = 'subject';
	//crmv@10759 e
	function __construct() {
		global $log, $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_ddt';
		$this->customFieldTable = Array($table_prefix.'_ddtcf', 'ddtid');
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_ddt', $table_prefix.'_ddtcf');
		$this->tab_name_index = Array(
		$table_prefix.'_crmentity' => 'crmid',
		$table_prefix.'_ddt'   => 'ddtid',
	    $table_prefix.'_ddtcf' => 'ddtid');
		$this->column_fields = getColumnFields(get_class()); //crmv@146187
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	function save_module($module) {
		global $table_prefix,$iAmAProcess;
		// crmv@18498
		//in ajax save we should not call this function, because this will delete all the existing product values
		if(!empty($_REQUEST) && isset($_REQUEST['totalProductCount']) && $_REQUEST['action'] != 'DdtAjax' && $_REQUEST['ajxaction'] != 'DETAILVIEW' && $_REQUEST['action'] != 'MassEditSave' && !$iAmAProcess) // crmv@138794 crmv@196424
		{
			$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
			//Based on the total Number of rows we will save the product relationship with this entity
			$InventoryUtils->saveInventoryProductDetails($this, 'Ddt');
		}
		
		// Update the currency id and the conversion rate for the sales order
		$update_query = "update ".$table_prefix."_ddt set currency_id=?, conversion_rate=? where ddtid=?";
		$update_params = array($this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id);
		$this->db->pquery($update_query, $update_params);
		// crmv@18498e
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($moduleName, $event_type) {
		global $table_prefix;
		if($event_type == 'module.postinstall') {
			global $adb;
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));

			// crmv@64542
			$tabid = getTabid($moduleName);
			if ($tabid > 0) {
				$tabResult = $adb->pquery("SELECT tabid FROM ".$table_prefix."_tab_info WHERE tabid=? AND prefname='is_inventory'", array($tabid));
				if ($adb->num_rows($tabResult) > 0) {
					$adb->pquery("UPDATE ".$table_prefix."_tab_info SET prefvalue=? WHERE tabid=? AND prefname='is_inventory'", array(1,$tabid));
				} else {
					$adb->pquery('INSERT INTO '.$table_prefix.'_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', array($tabid, 'is_inventory', 1));
				}
			}
			// crmv@64542e

			// Initialize module sequence for the module
			$adb->pquery("INSERT into ".$table_prefix."_modentity_num values(?,?,?,?,?,?)",array($adb->getUniqueId($table_prefix."_modentity_num"),$moduleName,'DDT',1,1,1));

			require_once('vtlib/Vtecrm/Module.php');//crmv@207871
			$moduleInstance = Vtecrm_Module::getInstance('Ddt');
			$docModuleInstance = Vtecrm_Module::getInstance('Documents');
			$docModuleInstance->setRelatedList($moduleInstance,'Ddt',array('SELECT','ADD'),'get_documents_dependents_list');
			$accModuleInstance = Vtecrm_Module::getInstance('Accounts');
			$accModuleInstance->setRelatedList($moduleInstance,'Ddt',array(''),'get_dependents_list');
			$salModuleInstance = Vtecrm_Module::getInstance('SalesOrder');
			$salModuleInstance->setRelatedList($moduleInstance,'Ddt',array(''),'get_dependents_list');
			$invModuleInstance = Vtecrm_Module::getInstance('Invoice');
			$invModuleInstance->setRelatedList($moduleInstance,'Ddt',array('SELECT'),'get_related_list');
			//crmv@26896
			Vtecrm_Link::addLink($moduleInstance->id,'DETAILVIEWBASIC','Add Invoice','index.php?module=Invoice&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&record=$RECORD$&convertmode=ddttoinvoice');
			Vtecrm_Link::addLink($salModuleInstance->id,'DETAILVIEWBASIC','Add Ddt','index.php?module=Ddt&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&record=$RECORD$&convertmode=sotoddt');
			//crmv@26896e

			//crmv@69922
			// add the pdfmaker widget, since sometimes is not installed
			if (isModuleInstalled('PDFMaker')) {
				$result1 = $adb->query("SELECT module FROM ".$table_prefix."_pdfmaker GROUP BY module");
				while ($row = $adb->fetchByAssoc($result1, -1, false)) {
					$relModuleInstance = Vtecrm_Module::getInstance($row["module"]);
					if ($relModuleInstance && $relModuleInstance->id > 0) {
						Vtecrm_Link::addLink($relModuleInstance->id, 'LISTVIEWBASIC', 'PDF Export', "VTE.PDFMakerActions.getPDFListViewPopup2(this,'$"."MODULE$');", '', 1);
						Vtecrm_Link::addLink($relModuleInstance->id, 'DETAILVIEWWIDGET', 'PDFMaker', "module=PDFMaker&action=PDFMakerAjax&file=getPDFActions&record=$"."RECORD$", '', 1);
					}
				}
			}
			//crmv@69922e
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
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

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	//crmv@18498
	function getConvertDdtToInvoice($focus)
	{
		$focus->column_fields['subject'] = $this->column_fields['subject'];
		$focus->column_fields['customerno'] = $this->column_fields['customerno'];
		$focus->column_fields['duedate'] = $this->column_fields['ddt_data'];
		$focus->column_fields['salesorder_id'] = $this->column_fields['salesorderid'];
		$focus->column_fields['account_id'] = $this->column_fields['accountid'];
		if ($this->column_fields['accountid'] != '') {
			require_once('modules/Accounts/Accounts.php');
	        $account_id = $this->column_fields['accountid'];
	        $account_focus = CRMEntity::getInstance('Accounts');
	        $account_focus->id = $account_id;
	        $account_focus->retrieve_entity_info($account_id,"Accounts");

	        $focus->column_fields['bill_street'] = $account_focus->column_fields['bill_street'];
			$focus->column_fields['ship_street'] = $account_focus->column_fields['ship_street'];
			$focus->column_fields['bill_city'] = $account_focus->column_fields['bill_city'];
			$focus->column_fields['ship_city'] = $account_focus->column_fields['ship_city'];
			$focus->column_fields['bill_state'] = $account_focus->column_fields['bill_state'];
			$focus->column_fields['ship_state'] = $account_focus->column_fields['ship_state'];
			$focus->column_fields['bill_code'] = $account_focus->column_fields['bill_code'];
			$focus->column_fields['ship_code'] = $account_focus->column_fields['ship_code'];
			$focus->column_fields['bill_country'] = $account_focus->column_fields['bill_country'];
			$focus->column_fields['ship_country'] = $account_focus->column_fields['ship_country'];
			$focus->column_fields['bill_pobox'] = $account_focus->column_fields['bill_pobox'];
			$focus->column_fields['ship_pobox'] = $account_focus->column_fields['ship_pobox'];
		}
		$focus->column_fields['description'] = $this->column_fields['description'];
		$focus->column_fields['terms_conditions'] = $this->column_fields['terms_conditions'];
	    $focus->column_fields['currency_id'] = $this->column_fields['currency_id'];
	    $focus->column_fields['conversion_rate'] = $this->column_fields['conversion_rate'];
		return $focus;
	}

	function getConvertSalesOrderToDdt($so_focus)
	{
	    $this->column_fields['salesorderid'] = $so_focus->id;
		$this->column_fields['subject'] = $so_focus->column_fields['subject'];
		$this->column_fields['customerno'] = $so_focus->column_fields['customerno'];
		$this->column_fields['ddt_data'] = $so_focus->column_fields['duedate'];
		$this->column_fields['accountid'] = $so_focus->column_fields['account_id'];
		$this->column_fields['description'] = $so_focus->column_fields['description'];
		$this->column_fields['terms_conditions'] = $so_focus->column_fields['terms_conditions'];
	    $this->column_fields['currency_id'] = $so_focus->column_fields['currency_id'];
	    $this->column_fields['conversion_rate'] = $so_focus->column_fields['conversion_rate'];
	}
	//crmv@18498e
	
	// crmv@97237 - removed report function

	//crmv@47459
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;

		$vte_inventoryproductrelDdt = substr($table_prefix.'_inventoryproductrelDdt',0,29);

		if ($reporttype != 'COLUMNSTOTOTAL') {
			$productjoins = " left join {$table_prefix}_inventoryproductrel $vte_inventoryproductrelDdt on {$table_prefix}_ddt.ddtid = $vte_inventoryproductrelDdt.id
			left join {$table_prefix}_products {$table_prefix}_productsDdt on {$table_prefix}_productsDdt.productid = ".substr("{$table_prefix}_inventoryproductrelDdt", 0, 29).".productid
			left join {$table_prefix}_service {$table_prefix}_serviceDdt on {$table_prefix}_serviceDdt.serviceid = ".substr("{$table_prefix}_inventoryproductrelDdt", 0,29).".productid ";
		}

		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_ddt","ddtid");
		$query .= "
		left join {$table_prefix}_ddtcf on {$table_prefix}_ddt.ddtid = {$table_prefix}_ddtcf.ddtid		
		$productjoins
		left join {$table_prefix}_groups {$table_prefix}_groupsDdt on {$table_prefix}_groupsDdt.groupid = {$table_prefix}_crmentityDdt.smownerid
		left join {$table_prefix}_users {$table_prefix}_usersDdt on {$table_prefix}_usersDdt.id = {$table_prefix}_crmentityDdt.smownerid
		";
		return $query;
	}
	//crmv@47459e
}
?>
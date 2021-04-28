<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Services extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'serviceid';
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
		'Service No'=>Array('service'=>'service_no'),
		'Service Name'=>Array('service'=>'servicename'),
        'Commission Rate'=>Array('service'=>'commissionrate'),
		'Usage Unit'=>Array('service'=>'service_usageunit'),	//crmv@16644
		'No of Units'=>Array('service'=>'qty_per_unit'),
		'Price'=>Array('service'=>'unit_price')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Service No'=>'service_no',
		'Service Name'=>'servicename',
		'Commission Rate'=>'commissionrate',
		'Usage Unit'=>'service_usageunit',	//crmv@16644
		'No of Units'=>'qty_per_unit',
		'Price'=>'unit_price'
	);

	// Make the field link to detail view
	var $list_link_field= 'servicename';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Service No'=>Array('service'=>'service_no'),
		'Service Name'=>Array('service'=>'servicename'),
		'Price'=>Array('service'=>'unit_price')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Service No'=>'service_no',
		'Service Name'=>'servicename',
		'Price'=>'unit_price'
	);

	// For Popup window record selection
	var $popup_fields = Array ('servicename');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'servicename';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'servicename';

	// Required Information for enabling Import feature
	var $required_fields = Array('servicename'=>1);

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('servicename');

	var $default_order_by = 'servicename';
	var $default_sort_order='ASC';

	var $unit_price;
	//crmv@10759
	var $search_base_field = 'servicename';
	//crmv@10759 e
	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $log,$table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_service';
		$this->customFieldTable = Array($table_prefix.'_servicecf', 'serviceid');
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_service',$table_prefix.'_servicecf');
		$this->tab_name_index = Array(
				$table_prefix.'_crmentity'=>'crmid',
				$table_prefix.'_service'=>'serviceid',
				$table_prefix.'_servicecf'=>'serviceid',
				$table_prefix.'_producttaxrel'=>'productid');
		$this->column_fields = getColumnFields('Services');
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	// crmv@205306
	function retrieve_entity_info($record, $module, $dieOnError=true, $onlyFields = array()) {
		global $table_prefix;
		$r = parent::retrieve_entity_info($record, $module, $dieOnError, $onlyFields);
		
		$taxes = $this->retrieveTaxes($this->id);
		$this->column_fields['taxclass'] = Zend_Json::encode($taxes);
		
		return $r;
	}
	
	function retrieve_entity_info_no_html($record, $module, $dieOnError=true, $onlyFields = array()) {
		global $table_prefix;
		$r = parent::retrieve_entity_info_no_html($record, $module, $dieOnError, $onlyFields);
		
		$taxes = $this->retrieveTaxes($this->id);
		$this->column_fields['taxclass'] = Zend_Json::encode($taxes);
		
		return $r;
	}

	function retrieveTaxes($id) {
		$taxes = array();
		$inventoryUtils = InventoryUtils::getInstance();
		$tax_details = $inventoryUtils->getTaxDetailsForProduct($id) ?: array(); // crmv@138343
		foreach ($tax_details as $tax) {
			$percentage = $inventoryUtils->getProductTaxPercentage($tax['taxname'], $id);
			$taxes[$tax['taxname']] = floatval($percentage);
		}
		return $taxes;
	}
	// crmv@205306e

	function save_module($module) {
		global $table_prefix, $iAmAProcess; // crmv@205306

		// crmv@148116
		//Inserting into product_taxrel table
		if ($_REQUEST['ajxaction'] != 'DETAILVIEW' && !$iAmAProcess) { // crmv@205306
			if ($_REQUEST['action'] != 'MassEditSave' || $_REQUEST['taxclass_mass_edit_check']) {
				$this->insertTaxInformation($table_prefix . '_producttaxrel', 'Services');
			}
		}
		// crmv@148116e

		if ($_REQUEST['ajxaction'] != 'DETAILVIEW' && $_REQUEST['action'] != 'MassEditSave' && !$iAmAProcess) { //crmv@26792 // crmv@205306
			//crmv@20706
			if ($_REQUEST['action'] == 'ServicesAjax' &&  $_REQUEST['file'] == 'QuickCreate') {
				$this->insertQCPriceInformation($table_prefix . '_productcurrencyrel', 'Services');
				//crmv@115232
			} elseif ($_REQUEST['action'] == 'Import') {
				$this->insertImportPriceInformation($table_prefix . '_productcurrencyrel', 'Services');
				//crmv@115232e
			} else {
				//crmv@20706e
				$this->insertPriceInformation($table_prefix . '_productcurrencyrel', 'Services');
			}
		}

		// Update unit price value in vte_productcurrencyrel
		$this->updateUnitPrice();
	}

	/**	function to save the service tax information in vte_servicetaxrel table
	 *	@param string $tablename - vte_tablename to save the service tax relationship (servicetaxrel)
	 *	@param string $module	 - current module name
	 *	$return void
	*/
	function insertTaxInformation($tablename, $module) {
		global $adb, $log,$table_prefix;
		$log->debug("Entering into insertTaxInformation($tablename, $module) method ...");
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$tax_details = $InventoryUtils->getAllTaxes();

		$tax_per = '';
		//Save the Product - tax relationship if corresponding tax check box is enabled
		//Delete the existing tax if any
		if($this->mode == 'edit')
		{
			for($i=0;$i<count($tax_details);$i++)
			{
				$taxid = $InventoryUtils->getTaxId($tax_details[$i]['taxname']); // crmv@42024
				$sql = "delete from ".$table_prefix."_producttaxrel where productid=? and taxid=?";
				$adb->pquery($sql, array($this->id,$taxid));
			}
		}
		for($i=0;$i<count($tax_details);$i++)
		{
			$tax_name = $tax_details[$i]['taxname'];
			$tax_checkname = $tax_details[$i]['taxname']."_check";
			if($_REQUEST[$tax_checkname] == 'on' || $_REQUEST[$tax_checkname] == 1)
			{
				$taxid = $InventoryUtils->getTaxId($tax_name); // crmv@42024
				$tax_per = parseUserNumber($_REQUEST[$tax_name]); // crmv@118512
				if($_REQUEST[$tax_name] == '') // crmv@118512
				{
					$log->debug("Tax selected but value not given so default value will be saved.");
					$tax_per = $InventoryUtils->getTaxPercentage($tax_name); // crmv@42024
				}

				$log->debug("Going to save the Product - $tax_name tax relationship");

				$query = "insert into ".$table_prefix."_producttaxrel values(?,?,?)";
				$adb->pquery($query, array($this->id,$taxid,$tax_per));
			}
		}

		$log->debug("Exiting from insertTaxInformation($tablename, $module) method ...");
	}

	/**	function to save the service price information in vte_servicecurrencyrel table
	 *	@param string $tablename - vte_tablename to save the service currency relationship (servicecurrencyrel)
	 *	@param string $module	 - current module name
	 *	$return void
	*/
	function insertPriceInformation($tablename, $module)
	{
		global $adb, $log, $current_user,$table_prefix;
		$log->debug("Entering into insertPriceInformation($tablename, $module) method ...");
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		// Update the currency_id based on the logged in user's preference
		$currencyid=fetchCurrency($current_user->id);
		$adb->pquery("update ".$table_prefix."_service set currency_id=? where serviceid=?", array($currencyid, $this->id));

		$currency_details = $InventoryUtils->getAllCurrencies('all');

		//Delete the existing currency relationship if any
		if($this->mode == 'edit')
		{
			for($i=0;$i<count($currency_details);$i++)
			{
				$curid = $currency_details[$i]['curid'];
				$sql = "delete from ".$table_prefix."_productcurrencyrel where productid=? and currencyid=?";
				$adb->pquery($sql, array($this->id,$curid));
			}
		}

		$service_base_conv_rate = $InventoryUtils->getBaseConversionRateForProduct($this->id, $this->mode,$module);

		//Save the Product - Currency relationship if corresponding currency check box is enabled
		for($i=0;$i<count($currency_details);$i++)
		{
			$curid = $currency_details[$i]['curid'];
			$curname = $currency_details[$i]['currencylabel'];
			$cur_checkname = 'cur_' . $curid . '_check';
			$cur_valuename = 'curname' . $curid;
			$base_currency_check = 'base_currency' . $curid;
			if($_REQUEST[$cur_checkname] == 'on' || $_REQUEST[$cur_checkname] == 1)
			{
				$conversion_rate = $currency_details[$i]['conversionrate'];
				$actual_conversion_rate = $service_base_conv_rate * $conversion_rate;
				$converted_price = $actual_conversion_rate * parseUserNumber($_REQUEST['unit_price']); //crmv@115232
				$actual_price = parseUserNumber($_REQUEST[$cur_valuename]); //crmv@115232

				$log->debug("Going to save the Product - $curname currency relationship");

				$query = "insert into ".$table_prefix."_productcurrencyrel values(?,?,?,?)";
				$adb->pquery($query, array($this->id,$curid,$converted_price,$actual_price));

				// Update the Product information with Base Currency choosen by the User.
				if ($_REQUEST['base_currency'] == $cur_valuename) {
					$adb->pquery("update ".$table_prefix."_service set currency_id=?, unit_price=? where serviceid=?", array($curid, $actual_price, $this->id));
				}
			}
		}

		$log->debug("Exiting from insertPriceInformation($tablename, $module) method ...");
	}

	function updateUnitPrice() {
		global $table_prefix;
		$prod_res = $this->db->pquery("select unit_price, currency_id from ".$table_prefix."_service where serviceid=?", array($this->id));
		$prod_unit_price = $this->db->query_result($prod_res, 0, 'unit_price');
		$prod_base_currency = $this->db->query_result($prod_res, 0, 'currency_id');

		$query = "update ".$table_prefix."_productcurrencyrel set actual_price=? where productid=? and currencyid=?";
		$params = array($prod_unit_price, $this->id, $prod_base_currency);
		$this->db->pquery($query, $params);
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Get list view query.
	 */
	function getListQuery($module, $where='') {
		global $current_user,$table_prefix;
		$query = "SELECT ".$table_prefix."_crmentity.*, $this->table_name.*";

		// Select Custom Field Table Columns if present
		if(!empty($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$query .= " FROM $this->table_name";

		$query .= "	INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}
		// crmv@109663
		$query .= " LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
		$query .= " LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";
		// crmv@109663e

		//crmv@31775
	    $reportFilterJoin = '';
		$viewId = getLVS($module,'viewname');
		if (isset($_REQUEST['viewname']) && $_REQUEST['viewname'] != '') {
			$viewId = $_REQUEST['viewname'];
		}
		if ($viewId != '') {
		    $oCustomView = CRMEntity::getInstance('CustomView', $module); // crmv@115329
			$reportFilter = $oCustomView->getReportFilter($viewId);
			if ($reportFilter) {
				$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
				$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
			}
		}
		//crmv@31775e
		$query .= $this->getNonAdminAccessControlQuery($module,$current_user);
		$query .= "WHERE ".$table_prefix."_crmentity.deleted = 0 ".$where;
		$query = $this->listQueryNonAdminChange($query, $module);
		return $query;
	}

	/**
	 * Transform the value while exporting
	 */
	function transform_export_value($key, $value) {
		if($key == 'owner') return getOwnerName($value);
		return parent::transform_export_value($key, $value);
	}

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

	/**	function used to get the list of quotes which are related to the service
	 *	@param int $id - service id
	 *	@return array - array which will be returned from the function GetRelatedList
	 */
	function get_quotes($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$table_prefix;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_quotes(".$id.") method ...");
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

		$query = "SELECT ".$table_prefix."_crmentity.*,
			".$table_prefix."_quotes.*,
			".$table_prefix."_potential.potentialname,
			".$table_prefix."_account.accountname,
			".$table_prefix."_inventoryproductrel.productid,
			case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name
				else ".$table_prefix."_groups.groupname end as user_name
			FROM ".$table_prefix."_quotes
			INNER JOIN ".$table_prefix."_quotescf
				ON ".$table_prefix."_quotescf.quoteid = ".$table_prefix."_quotes.quoteid
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_quotes.quoteid
			INNER JOIN ".$table_prefix."_inventoryproductrel
				ON ".$table_prefix."_inventoryproductrel.id = ".$table_prefix."_quotes.quoteid
			LEFT OUTER JOIN ".$table_prefix."_account
				ON ".$table_prefix."_account.accountid = ".$table_prefix."_quotes.accountid
			LEFT OUTER JOIN ".$table_prefix."_potential
				ON ".$table_prefix."_potential.potentialid = ".$table_prefix."_quotes.potentialid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			AND ".$table_prefix."_inventoryproductrel.productid = ".$id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_quotes method ...");
		return $return_value;
	}

	/**	function used to get the list of purchase orders which are related to the service
	 *	@param int $id - service id
	 *	@return array - array which will be returned from the function GetRelatedList
	 */
	function get_purchase_orders($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$table_prefix;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_purchase_orders(".$id.") method ...");
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

		$query = "SELECT ".$table_prefix."_crmentity.*,
			".$table_prefix."_purchaseorder.*,
			".$table_prefix."_service.servicename,
			".$table_prefix."_inventoryproductrel.productid,
			case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name
				else ".$table_prefix."_groups.groupname end as user_name
			FROM ".$table_prefix."_purchaseorder
			INNER JOIN ".$table_prefix."_purchaseordercf
				ON ".$table_prefix."_purchaseordercf.purchaseorderid = ".$table_prefix."_purchaseorder.purchaseorderid
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_purchaseorder.purchaseorderid
			INNER JOIN ".$table_prefix."_inventoryproductrel
				ON ".$table_prefix."_inventoryproductrel.id = ".$table_prefix."_purchaseorder.purchaseorderid
			INNER JOIN ".$table_prefix."_service
				ON ".$table_prefix."_service.serviceid = ".$table_prefix."_inventoryproductrel.productid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			AND ".$table_prefix."_service.serviceid = ".$id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_purchase_orders method ...");
		return $return_value;
	}

	/**	function used to get the list of sales orders which are related to the service
	 *	@param int $id - service id
	 *	@return array - array which will be returned from the function GetRelatedList
	 */
	function get_salesorder($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$table_prefix;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_salesorder(".$id.") method ...");
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

		$query = "SELECT ".$table_prefix."_crmentity.*,
			".$table_prefix."_salesorder.*,
			".$table_prefix."_service.servicename AS servicename,
			".$table_prefix."_account.accountname,
			case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name
				else ".$table_prefix."_groups.groupname end as user_name
			FROM ".$table_prefix."_salesorder
			INNER JOIN ".$table_prefix."_salesordercf
				ON ".$table_prefix."_salesordercf.salesorderid = ".$table_prefix."_salesorder.salesorderid
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_salesorder.salesorderid
			INNER JOIN ".$table_prefix."_inventoryproductrel
				ON ".$table_prefix."_inventoryproductrel.id = ".$table_prefix."_salesorder.salesorderid
			INNER JOIN ".$table_prefix."_service
				ON ".$table_prefix."_service.serviceid = ".$table_prefix."_inventoryproductrel.productid
			LEFT OUTER JOIN ".$table_prefix."_account
				ON ".$table_prefix."_account.accountid = ".$table_prefix."_salesorder.accountid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			AND ".$table_prefix."_service.serviceid = ".$id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_salesorder method ...");
		return $return_value;
	}

	/**	function used to get the list of invoices which are related to the service
	 *	@param int $id - service id
	 *	@return array - array which will be returned from the function GetRelatedList
	 */
	function get_invoices($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$table_prefix;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_invoices(".$id.") method ...");
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

		$query = "SELECT ".$table_prefix."_crmentity.*,
			".$table_prefix."_invoice.*,
			".$table_prefix."_inventoryproductrel.quantity,
			".$table_prefix."_account.accountname,
			case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name
				else ".$table_prefix."_groups.groupname end as user_name
			FROM ".$table_prefix."_invoice
			INNER JOIN ".$table_prefix."_invoicecf
				ON ".$table_prefix."_invoicecf.invoiceid = ".$table_prefix."_invoice.invoiceid
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_invoice.invoiceid
			LEFT OUTER JOIN ".$table_prefix."_account
				ON ".$table_prefix."_account.accountid = ".$table_prefix."_invoice.accountid
			INNER JOIN ".$table_prefix."_inventoryproductrel
				ON ".$table_prefix."_inventoryproductrel.id = ".$table_prefix."_invoice.invoiceid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_users
				ON  ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			AND ".$table_prefix."_inventoryproductrel.productid = ".$id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_invoices method ...");
		return $return_value;
	}

	/**	function used to get the list of pricebooks which are related to the service
	 *	@param int $id - service id
	 *	@return array - array which will be returned from the function GetRelatedList
	 */
	function get_service_pricebooks($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $currentModule,$log,$mod_strings,$table_prefix;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_service_pricebooks(".$id.") method ...");

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$focus = CRMEntity::getInstance($related_module);
		$singular_modname = vtlib_toSingular($related_module);

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
            $returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";
		else
			$returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$button = '';
		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_ADD_TO'). " ". getTranslatedString($related_module) ."' class='crmbutton small create'" .
					" onclick=\"location.href = 'index.php?action=AddServiceToPriceBooks&module=$currentModule&return_module=$currentModule&return_action=DetailView&return_id=$id'\" type='button' name='button'" .
					" value='". getTranslatedString('LBL_ADD_TO'). " " . getTranslatedString($singular_modname) ."'>&nbsp;";
			}
		}

		$query = "SELECT ".$table_prefix."_crmentity.crmid,
		".$table_prefix."_pricebook.*,
		".$table_prefix."_pricebookproductrel.productid as prodid
		FROM ".$table_prefix."_pricebook
		INNER JOIN ".$table_prefix."_pricebookcf
		ON ".$table_prefix."_pricebookcf.pricebookid = ".$table_prefix."_pricebook.pricebookid
		INNER JOIN ".$table_prefix."_crmentity
		ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_pricebook.pricebookid
		INNER JOIN ".$table_prefix."_pricebookproductrel
		ON ".$table_prefix."_pricebookproductrel.pricebookid = ".$table_prefix."_pricebook.pricebookid
		WHERE ".$table_prefix."_crmentity.deleted = 0
		AND ".$table_prefix."_pricebookproductrel.productid = ".$id;
		$log->debug("Exiting get_product_pricebooks method ...");

		$return_value = GetRelatedList($currentModule, $related_module, $focus, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_service_pricebooks method ...");
		return $return_value;
	}

	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) { // crmv@146653
		global $adb, $pquery;
		global $adb, $table_prefix;

		$InventoryUtils = InventoryUtils::getInstance();

		$reltable = $table_prefix."_pricebookproductrel";
		if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);

		if (isProductModule($module) && $with_module == 'PriceBooks') {
			foreach ($with_crmid as $pricebookid) {
				$res = $adb->pquery("select * from $reltable where pricebookid = ? and productid = ?", array($pricebookid, $crmid));
				if ($res && $adb->num_rows($res) == 0) {
					$currency_id = $InventoryUtils->getPriceBookCurrency($pricebookid);
					// get listprice
					if ($with_module == 'Products') {
						$listprice = getSingleFieldValue($table_prefix."_products", 'unit_price', 'productid', $crmid);
					} else {
						$listprice = getSingleFieldValue($table_prefix."_service", 'unit_price', 'serviceid', $crmid);
					}
					$query= "insert into $reltable (pricebookid,productid,listprice,usedcurrency) values(?,?,?,?)";
					$adb->pquery($query, array($pricebookid,$crmid,$listprice,$currency_id));
				}
			}
		} else {
			return parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check); // crmv@146653
		}
	}
	// crmv@43864e


	/**	Function to display the Services which are related to the PriceBook
	 *	@param string $query - query to get the list of products which are related to the current PriceBook
	 *	@param object $focus - PriceBook object which contains all the information of the current PriceBook
	 *	@param string $returnset - return_module, return_action and return_id which are sequenced with & to pass to the URL which is optional
	 *	return array $return_data which will be formed like array('header'=>$header,'entries'=>$entries_list) where as $header contains all the header columns and $entries_list will contain all the Service entries
	 */
	function getPriceBookRelatedServices($query,$focus,$returnset='')
	{
		global $log;
		$log->debug("Entering getPriceBookRelatedServices(".$query.",focus,".$returnset.") method ...");

		global $adb;
		global $app_strings;
		global $current_language,$current_user;
		$current_module_strings = return_module_language($current_language, 'Services');

		global $list_max_entries_per_page;
		global $urlPrefix;

		global $theme;
		$pricebook_id = $_REQUEST['record'];
		$theme_path="themes/".$theme."/";
		$image_path=$theme_path."images/";

		$LVU = ListViewUtils::getInstance();
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$noofrows = $adb->query_result($adb->query(mkCountQuery($query)),0,'count');
		$module = 'PriceBooks';
		$relatedmodule = 'Services';
		if(!VteSession::getArray(array('rlvs', $module, $relatedmodule)))
		{
			$modObj = new ListViewSession();
			$modObj->sortby = $focus->default_order_by;
			$modObj->sorder = $focus->default_sort_order;
			VteSession::setArray(array('rlvs', $module, $relatedmodule), get_object_vars($modObj));
		}
		if(isset($_REQUEST['relmodule']) && $_REQUEST['relmodule']!='' && $_REQUEST['relmodule'] == $relatedmodule) {
			$relmodule = vtlib_purify($_REQUEST['relmodule']);
			if(VteSession::getArray(array('rlvs', $module, $relmodule))) {
				setSessionVar(VteSession::getArray(array('rlvs', $module, $relmodule)),$noofrows,$list_max_entries_per_page,$module,$relmodule);
			}
		}
		global $relationId;
		$start = RelatedListViewSession::getRequestCurrentPage($relationId, $query);
		$navigation_array =  VT_getSimpleNavigationValues($start, $list_max_entries_per_page,
				$noofrows);

		$limit_start_rec = ($start-1) * $list_max_entries_per_page;

		$list_result = $adb->limitQuery($query,$limit_start_rec,$list_max_entries_per_page);
		
		$header=array();
		if(isPermitted("PriceBooks","EditView","") == 'yes' || isPermitted("PriceBooks","Delete","") == 'yes')
			$header[]=$app_strings['LBL_ACTION'];
		$header[]=$current_module_strings['LBL_LIST_SERVICE_NAME'];
		if(getFieldVisibilityPermission('Services', $current_user->id, 'unit_price') == '0')
			$header[]=$current_module_strings['LBL_SERVICE_UNIT_PRICE'];
		$header[]=$current_module_strings['LBL_PB_LIST_PRICE'];

		$currency_id = $focus->column_fields['currency_id'];
		$numRows = $adb->num_rows($list_result);
		for($i=0; $i<$numRows; $i++) {
			$entity_id = $adb->query_result($list_result,$i,"crmid");
			$unit_price = 	$adb->query_result($list_result,$i,"unit_price");
			if($currency_id != null) {
				$prod_prices = $InventoryUtils->getPricesForProducts($currency_id, array($entity_id),'Services');
				$unit_price = $prod_prices[$entity_id];
			}
			$listprice = $adb->query_result($list_result,$i,"listprice");
			$field_name=$entity_id."_listprice";

			$entries = Array();
			
			$action = "";
			if(isPermitted("PriceBooks","EditView","") == 'yes')
				$action .= '<a href="javascript:;" onClick="fnvshobj(this,\'editlistprice\'); editProductListPrice(\''.$entity_id.'\',\''.$pricebook_id.'\',\''.$listprice.'\',\''.$relatedmodule.'\');"><i class="vteicon" title="'.getTranslatedString("LBL_EDIT",$module).'">create</i></a>';	//crmv@128983
			if(isPermitted("PriceBooks","Delete","") == 'yes')
			{
				if($action != "")
					$action .= '&nbsp;&nbsp;';
				$action .= '<a href="javascript:;" onClick="deletePriceBookProductRel('.$entity_id.','.$pricebook_id.');"><i class="vteicon" title="'.getTranslatedString("LBL_DELETE",$module).'">clear</i></a>';	//crmv@128983
			}
			if($action != "")
				$entries[] = $action;
			
			$entries[] = textlength_check($adb->query_result($list_result,$i,"servicename"));
			
			if(getFieldVisibilityPermission('Services', $current_user->id, 'unit_price') == '0')
				$entries[] = formatUserNumber($unit_price); // crmv@173281

			$entries[] = formatUserNumber($listprice); // crmv@173281
			
			$entries_list[] = $entries;
		}
		$navigationOutput[] =  getRecordRangeMessage($list_result, $limit_start_rec,$noofrows);
		$navigationOutput[] = $LVU->getRelatedTableHeaderNavigation($navigation_array, '',$module,$relatedmodule,$focus->id);
		$return_data = array('header'=>$header,'entries'=>$entries_list,'navigation'=>$navigationOutput);

		$log->debug("Exiting getPriceBookRelatedServices method ...");
		return $return_data;
	}

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb,$log,$table_prefix;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");

		// crmv@64542
		$rel_table_arr = Array();
		$inventoryMods = getInventoryModules();
		foreach ($inventoryMods as $mod) {
			$rel_table_arr[$mod] = $table_prefix."_inventoryproductrel";
		}
		$rel_table_arr['PriceBooks'] = $table_prefix."_pricebookproductrel";
		$rel_table_arr['Documents'] = $table_prefix."_senotesrel";
		// crmv@64542e

		$tbl_field_arr = Array($table_prefix."_inventoryproductrel"=>"id",$table_prefix."_pricebookproductrel"=>"pricebookid",$table_prefix."_senotesrel"=>"notesid");

		$entity_tbl_field_arr = Array($table_prefix."_inventoryproductrel"=>"productid",$table_prefix."_pricebookproductrel"=>"productid",$table_prefix."_senotesrel"=>"crmid");

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

		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		$log->debug("Exiting transferRelatedRecords...");
	}

	/*
	 * Function to get the primary query part of a report
	 * @param - $module primary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	// crmv@63349
	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true) { // crmv@146653
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			return $this->generateReportsQuery_tmp($module, $reportid);
		} else {
			return $this->generateReportsQuery_notmp($module, $reportid);
		}
	}

	function generateReportsQuery_notmp($module, $reportid = 0){
		global $current_user,$adb, $table_prefix;
		//crmv@33990 crmv@109663

		$tabid = getTabid($module);
		$tmptable = $table_prefix.'_rpt_innerprice';
		// delete rows from the table
		$sql = "DELETE FROM $tmptable WHERE reportid = ? AND tabid = ?";
		$adb->pquery($sql, array($reportid, $tabid));

		$sql_insert = "insert into $tmptable SELECT $reportid AS reportid, $tabid AS tabid, ".$table_prefix."_service.serviceid as crmid,
						(CASE WHEN (".$table_prefix."_service.currency_id = 1 ) THEN ".$table_prefix."_service.unit_price
							ELSE (".$table_prefix."_service.unit_price / ".$table_prefix."_currency_info.conversion_rate) END
						) AS \"".strtoupper('actual_unit_price')."\"
				FROM ".$table_prefix."_service
				LEFT JOIN ".$table_prefix."_currency_info ON ".$table_prefix."_service.currency_id = ".$table_prefix."_currency_info.id
				LEFT JOIN ".$table_prefix."_productcurrencyrel ON ".$table_prefix."_service.serviceid = ".$table_prefix."_productcurrencyrel.productid
				AND ".$table_prefix."_productcurrencyrel.currencyid = ". $current_user->currency_id;
		$adb->query($sql_insert);
		$query = "from ".$table_prefix."_service
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_service.serviceid
			left join ".$table_prefix."_servicecf on ".$table_prefix."_service.serviceid = ".$table_prefix."_servicecf.serviceid
			left join ".$table_prefix."_users ".$table_prefix."_usersServices on ".$table_prefix."_usersServices.id = ".$table_prefix."_crmentity.smownerid
			left join ".$table_prefix."_seproductsrel on ".$table_prefix."_seproductsrel.productid= ".$table_prefix."_service.serviceid
			left join ".$table_prefix."_crmentity ".$table_prefix."_crmentityRelServices on ".$table_prefix."_crmentityRelServices.crmid = ".$table_prefix."_seproductsrel.crmid and ".$table_prefix."_crmentityRelServices.deleted = 0
			left join ".$table_prefix."_account ".$table_prefix."_accountRelServices on ".$table_prefix."_accountRelServices.accountid=".$table_prefix."_seproductsrel.crmid
			left join ".$table_prefix."_leaddetails ".substr($table_prefix."_leaddetailsRelServices",0,29)." on ".substr($table_prefix."_leaddetailsRelServices",0,29).".leadid = ".$table_prefix."_seproductsrel.crmid
			left join ".$table_prefix."_potential ".$table_prefix."_potentialRelServices on ".$table_prefix."_potentialRelServices.potentialid = ".$table_prefix."_seproductsrel.crmid
			LEFT JOIN $tmptable ON $tmptable.reportid = $reportid AND $tmptable.tabid = $tabid AND $tmptable.crmid = ".$table_prefix."_service.serviceid";
		//crmv@33990e crmv@109663e
		return $query;
	}
	// crmv@63349e

	function generateReportsQuery_tmp($module){ // crmv@63349
		global $current_user,$table_prefix;
			//crmv@33990 crmv@109663
			global $adb;
			$tmptable = 'tmp_innerService'.$current_user->id;
			if (!$adb->table_exist($tmptable,true)){
				Vtecrm_Utils::CreateTable($tmptable,"serviceid I(11) NOTNULL PRIMARY,\"ACTUAL_UNIT_PRICE\" N(25,2)",true,true);
			}
			else{
				$sql = "truncate table $tmptable";
				$adb->query($sql);
			}
			$sql_insert = "insert into $tmptable SELECT ".$table_prefix."_service.serviceid,
							(CASE WHEN (".$table_prefix."_service.currency_id = 1 ) THEN ".$table_prefix."_service.unit_price
								ELSE (".$table_prefix."_service.unit_price / ".$table_prefix."_currency_info.conversion_rate) END
							) AS \"".strtoupper('actual_unit_price')."\"
					FROM ".$table_prefix."_service
					LEFT JOIN ".$table_prefix."_currency_info ON ".$table_prefix."_service.currency_id = ".$table_prefix."_currency_info.id
					LEFT JOIN ".$table_prefix."_productcurrencyrel ON ".$table_prefix."_service.serviceid = ".$table_prefix."_productcurrencyrel.productid
					AND ".$table_prefix."_productcurrencyrel.currencyid = ". $current_user->currency_id;
			$adb->query($sql_insert);
			$query = "from ".$table_prefix."_service
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_service.serviceid
				left join ".$table_prefix."_servicecf on ".$table_prefix."_service.serviceid = ".$table_prefix."_servicecf.serviceid
				left join ".$table_prefix."_users ".$table_prefix."_usersServices on ".$table_prefix."_usersServices.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_seproductsrel on ".$table_prefix."_seproductsrel.productid= ".$table_prefix."_service.serviceid
				left join ".$table_prefix."_crmentity ".$table_prefix."_crmentityRelServices on ".$table_prefix."_crmentityRelServices.crmid = ".$table_prefix."_seproductsrel.crmid and ".$table_prefix."_crmentityRelServices.deleted = 0
				left join ".$table_prefix."_account ".$table_prefix."_accountRelServices on ".$table_prefix."_accountRelServices.accountid=".$table_prefix."_seproductsrel.crmid
				left join ".$table_prefix."_leaddetails ".substr($table_prefix."_leaddetailsRelServices",0,29)." on ".substr($table_prefix."_leaddetailsRelServices",0,29).".leadid = ".$table_prefix."_seproductsrel.crmid
				left join ".$table_prefix."_potential ".$table_prefix."_potentialRelServices on ".$table_prefix."_potentialRelServices.potentialid = ".$table_prefix."_seproductsrel.crmid
				LEFT JOIN $tmptable on  $tmptable.serviceid = ".$table_prefix."_service.serviceid";
			//crmv@33990e crmv@109663e
			return $query;
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$report_type='', $hasInventoryColumns = true,$joinUitype10=true){ // crmv@146653
		global $current_user,$table_prefix;
		// crmv@29686
		if (!$hasInventoryColumns) {
			$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_service","serviceid");
		}
		// crmv@29686 e
		// crmv@109663
		if($report_type !== 'COLUMNSTOTOTAL') {
			$query .= " LEFT JOIN (
					SELECT ".$table_prefix."_service.serviceid,
							(CASE WHEN (".$table_prefix."_service.currency_id = " . $current_user->currency_id . " ) THEN ".$table_prefix."_service.unit_price
								WHEN (".$table_prefix."_productcurrencyrel.actual_price IS NOT NULL) THEN ".$table_prefix."_productcurrencyrel.actual_price
								ELSE (".$table_prefix."_service.unit_price / ".$table_prefix."_currency_info.conversion_rate) * ". $current_user->conv_rate . " END
							) AS actual_unit_price
					FROM ".$table_prefix."_service
					LEFT JOIN ".$table_prefix."_currency_info ON ".$table_prefix."_service.currency_id = ".$table_prefix."_currency_info.id
					LEFT JOIN ".$table_prefix."_productcurrencyrel ON ".$table_prefix."_service.serviceid = ".$table_prefix."_productcurrencyrel.productid
					AND ".$table_prefix."_productcurrencyrel.currencyid = ". $current_user->currency_id . "
				) innerService ON innerService.serviceid = ".$table_prefix."_service.serviceid
				left join ".$table_prefix."_servicecf on ".$table_prefix."_service.serviceid = ".$table_prefix."_servicecf.serviceid
				left join ".$table_prefix."_users ".$table_prefix."_usersServices on ".$table_prefix."_usersServices.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups ".$table_prefix."_groupsServices on ".$table_prefix."_groupsServices.groupid = ".$table_prefix."_crmentity.smownerid";
		}
		// crmv@109663e
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
			"Quotes" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_service"=>"serviceid"),
			"PurchaseOrder" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_service"=>"serviceid"),
			"SalesOrder" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_service"=>"serviceid"),
			"Invoice" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_service"=>"serviceid"),
			"PriceBooks" => array($table_prefix."_pricebookproductrel"=>array("productid","pricebookid"),$table_prefix."_service"=>"serviceid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_service"=>"serviceid"),
		);
		return $rel_tables[$secmodule];
	}
	//crmv@38798e

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log,$table_prefix;
		$this->db->pquery('DELETE from '.$table_prefix.'_seproductsrel WHERE productid=? or crmid=?',array($id,$id));

		parent::unlinkDependencies($module, $id);
	}

 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {

		require_once('include/utils/utils.php');
		global $adb;
 		global $table_prefix;
 		if($eventType == 'module.postinstall') {
			require_once('vtlib/Vtecrm/Module.php');//crmv@207871

			$moduleInstance = Vtecrm_Module::getInstance($moduleName);
			$moduleInstance->disallowSharing();

			$ttModuleInstance = Vtecrm_Module::getInstance('HelpDesk');
			$ttModuleInstance->setRelatedList($moduleInstance,'Services',array('select'));

			$leadModuleInstance = Vtecrm_Module::getInstance('Leads');
			$leadModuleInstance->setRelatedList($moduleInstance,'Services',array('select'));

			$accModuleInstance = Vtecrm_Module::getInstance('Accounts');
			$accModuleInstance->setRelatedList($moduleInstance,'Services',array('select'),'get_services');	//crmv@16644

			$conModuleInstance = Vtecrm_Module::getInstance('Contacts');
			$conModuleInstance->setRelatedList($moduleInstance,'Services',array('select'));

			$potModuleInstance = Vtecrm_Module::getInstance('Potentials');
			$potModuleInstance->setRelatedList($moduleInstance,'Services',array('select'));

			$pbModuleInstance = Vtecrm_Module::getInstance('PriceBooks');
			$pbModuleInstance->setRelatedList($moduleInstance,'Services',array('select'),'get_pricebook_services');

			$conModuleInstance = Vtecrm_Module::getInstance('Documents');
			$conModuleInstance->setRelatedList($moduleInstance,'Services',array('select','add'),'get_documents_dependents_list');

			// Initialize module sequence for the module
			$adb->pquery("INSERT into ".$table_prefix."_modentity_num values(?,?,?,?,?,?)",array($adb->getUniqueId($table_prefix."_modentity_num"),$moduleName,'SER',1,1,1));

			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));

			// crmv@64542
			$tabid = getTabid($moduleName);
			if ($tabid > 0) {
				$tabResult = $adb->pquery("SELECT tabid FROM ".$table_prefix."_tab_info WHERE tabid=? AND prefname='is_product'", array($tabid));
				if ($adb->num_rows($tabResult) > 0) {
					$adb->pquery("UPDATE ".$table_prefix."_tab_info SET prefvalue=? WHERE tabid=? AND prefname='is_product'", array(1,$tabid));
				} else {
					$adb->pquery('INSERT INTO '.$table_prefix.'_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', array($tabid, 'is_product', 1));
				}
			}
			// crmv@64542e

			//crmv@16644
			$service = Vtecrm_Module::getInstance('Services');
			if ($service) $moduleInstance->setRelatedList($service, 'Service Contracts', Array('ADD'), 'get_dependents_list');
			//crmv@16644e

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

 	//crmv@20706
	function insertQCPriceInformation($tablename, $module) 	{
		global $adb, $current_user,$table_prefix;
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$currency_details = $InventoryUtils->getAllCurrencies('all');
		$product_base_currency = fetchCurrency($current_user->id);
		$product_base_conv_rate = $InventoryUtils->getBaseConversionRateForProduct($this->id, $this->mode);
		for($i=0;$i<count($currency_details);$i++)
		{
			$curid = $currency_details[$i]['curid'];
			$curname = $currency_details[$i]['currencylabel'];
			$cur_checkname = 'cur_' . $curid . '_check';
			$cur_valuename = 'curname' . $curid;
			$base_currency_check = 'base_currency' . $curid;
			if($product_base_currency == $curid)
			{
				$conversion_rate = $currency_details[$i]['conversionrate'];
				$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;
				$converted_price = $actual_conversion_rate * $_REQUEST['unit_price'];
				$actual_price = $converted_price;

				$query = "insert into ".$table_prefix."_productcurrencyrel values(?,?,?,?)";
				$adb->pquery($query, array($this->id,$curid,$converted_price,$actual_price));

				$adb->pquery("update ".$table_prefix."_service set currency_id=?, unit_price=? where serviceid=?", array($curid, $actual_price, $this->id));
			}
		}
	}
	//crmv@20706e

	//crmv@38866 crmv@115232
	function insertImportPriceInformation($tablename, $module) {
		global $adb, $table_prefix, $current_user;

		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
		$currency_details = $InventoryUtils->getAllCurrencies('all');
		$product_base_currency = fetchCurrency($current_user->id);
		$product_base_conv_rate = $InventoryUtils->getBaseConversionRateForProduct($this->id, $this->mode);
		for($i=0;$i<count($currency_details);$i++)
		{
			$curid = $currency_details[$i]['curid'];
			$curname = $currency_details[$i]['currencylabel'];
			$cur_checkname = 'cur_' . $curid . '_check';
			$cur_valuename = 'curname' . $curid;
			$base_currency_check = 'base_currency' . $curid;
			if($product_base_currency == $curid)
			{
				$conversion_rate = $currency_details[$i]['conversionrate'];
				$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;
				$converted_price = $actual_conversion_rate * $this->column_fields['unit_price'];
				$actual_price = $converted_price;

				$query = "insert into ".$table_prefix."_productcurrencyrel values(?,?,?,?)";
				$adb->pquery($query, array($this->id,$curid,$converted_price,$actual_price));

				$adb->pquery("update ".$table_prefix."_products set currency_id=?, unit_price=? where productid=?", array($curid, $actual_price, $this->id));
			}
		}
	}
	//crmv@38866e crmv@115232e

}
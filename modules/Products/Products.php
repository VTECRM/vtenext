<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/RelatedListView.php');

class Products extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'productid';
    var $column_fields = Array();

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	var $tab_name = Array();

	var $tab_name_index = Array();



	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
		'Product Name'=>Array('products'=>'productname'),
		'Part Number'=>Array('products'=>'productcode'),
		'Commission Rate'=>Array('products'=>'commissionrate'),
		'Qty/Unit'=>Array('products'=>'qty_per_unit'),
		'Unit Price'=>Array('products'=>'unit_price')
	);
	var $list_fields_name = Array(
		'Product Name'=>'productname',
		'Part Number'=>'productcode',
		'Commission Rate'=>'commissionrate',
		'Qty/Unit'=>'qty_per_unit',
		'Unit Price'=>'unit_price'
	);

	var $list_link_field= 'productname';

	var $search_fields = Array(
		'Product Name'=>Array('products'=>'productname'),
		'Part Number'=>Array('products'=>'productcode'),
		'Unit Price'=>Array('products'=>'unit_price')
	);
	var $search_fields_name = Array(
		'Product Name'=>'productname',
		'Part Number'=>'productcode',
		'Unit Price'=>'unit_price'
	);

    var $required_fields = Array(
            'productname'=>1
    );

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();
	var $def_basicsearch_col = 'productname';

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'productname';
	var $default_sort_order = 'ASC';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'productname','imagename');
	 // Josh added for importing and exporting -added in patch2
    var $unit_price;
	//crmv@10759
	var $search_base_field = 'productname';
	//crmv@10759 e
	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $table_prefix;

		// crmv@37004
		parent::__construct();
		$this->relation_table = $table_prefix.'_seproductsrel';
		$this->relation_table_id = 'productid';
		$this->relation_table_otherid = 'crmid';
		$this->relation_table_module = '';
		$this->relation_table_othermodule = 'setype';
		// crmv@37004e

		$this->table_name = $table_prefix.'_products';
		$this->customFieldTable = Array($table_prefix.'_productcf','productid');
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_products',$table_prefix.'_productcf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_products'=>'productid',$table_prefix.'_productcf'=>'productid'); // crmv@162281
		$this->log =LoggerManager::getLogger('product');
		$this->log->debug("Entering Products() method ...");
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Products');
		$this->log->debug("Exiting Product method ...");
	}
	
	// crmv@198024
	function presaveAlterValues() {
		parent::presaveAlterValues();
		
		// add the variant fields
		$confid = intval($this->column_fields['confproductid']);
		
		if ($confid > 0 && vtlib_isModuleActive('ConfProducts')) {
			$cprod = CRMEntity::getInstance('ConfProducts');
			$struct = $cprod->getAttributes($confid);
			if (is_array($struct) && count($struct) > 0) {
				$confinfo = html_entity_decode($this->column_fields['confprodinfo'], ENT_QUOTES, 'UTF-8');
				$confinfo = Zend_Json::decode($confinfo) ?: [];
			
				$fnames = array_column($struct, 'fieldname');
				$values = array_intersect_key($this->column_fields, array_flip($fnames));
				if (count($values) > 0) {
					// now validate them!
					foreach ($struct as $field) {
						if (array_key_exists($field['fieldname'], $values)) {
							$value = $values[$field['fieldname']];
							if ($value != '' && !in_array($value, $field['values'])) {
								$values[$field['fieldname']] = ''; // clear invalid values
							}
						}
					}
				}
				$confinfo = Zend_Json::encode(array_replace($confinfo, $values));
			} else {
				$confinfo = null;
			}
		} else {
			$confinfo = null;
		}
		$this->column_fields['confprodinfo'] = $confinfo;
	}
	// crmv@198024e

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

		// crmv@102790
		//Inserting into product_taxrel table
		if ($_REQUEST['ajxaction'] != 'DETAILVIEW' && !$iAmAProcess) { // crmv@205306
			if ($_REQUEST['action'] != 'MassEditSave' || $_REQUEST['taxclass_mass_edit_check']) {
				$this->insertTaxInformation($table_prefix . '_producttaxrel', 'Products');
			}
		}
		// crmv@102790e

		if ($_REQUEST['ajxaction'] != 'DETAILVIEW' && $_REQUEST['action'] != 'MassEditSave' && !$iAmAProcess) { //crmv@26792 // crmv@205306
			//crmv@20706 //crmv@38866
			if ($_REQUEST['action'] == 'ProductsAjax' &&  $_REQUEST['file'] == 'QuickCreate') {
				$this->insertQCPriceInformation($table_prefix . '_productcurrencyrel', 'Products');
			} elseif ($_REQUEST['action'] == 'Import') {
				$this->insertImportPriceInformation($table_prefix . '_productcurrencyrel', 'Products');
			} else {
				//crmv@20706e //crmv@38866e
				$this->insertPriceInformation($table_prefix . '_productcurrencyrel', 'Products');
			}
		}

		if (isset($this->parentid) && $this->parentid != '') {
			$this->insertIntoseProductsRel($this->id, $this->parentid, $this->return_module);
		}

		// Update unit price value in vte_productcurrencyrel
		$this->updateUnitPrice();
		//Inserting into attachments
		$this->insertIntoAttachment($this->id, 'Products');
	}

	/**	function to save the product tax information in vte_producttaxrel table
	 *	@param string $tablename - vte_tablename to save the product tax relationship (producttaxrel)
	 *	@param string $module	 - current module name
	 *	$return void
	*/
	function insertTaxInformation($tablename, $module)
	{
		global $adb, $log;
		global $table_prefix;
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

	/**	function to save the product price information in vte_productcurrencyrel table
	 *	@param string $tablename - vte_tablename to save the product currency relationship (productcurrencyrel)
	 *	@param string $module	 - current module name
	 *	$return void
	*/
	function insertPriceInformation($tablename, $module)
	{
		global $adb, $log, $current_user;
		global $table_prefix;
		$log->debug("Entering into insertPriceInformation($tablename, $module) method ...");
		//removed the update of currency_id based on the logged in user's preference : fix 6490
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$currency_details = $InventoryUtils->getAllCurrencies('all');

		//Delete the existing currency relationship if any
		if($this->mode == 'edit' && $_REQUEST['action'] !== 'MassEditSave')
		{
			for($i=0;$i<count($currency_details);$i++)
			{
				$curid = $currency_details[$i]['curid'];
				$sql = "delete from ".$table_prefix."_productcurrencyrel where productid=? and currencyid=?";
				$adb->pquery($sql, array($this->id,$curid));
			}
		}

		$product_base_conv_rate = $InventoryUtils->getBaseConversionRateForProduct($this->id, $this->mode);

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
				$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;
				$converted_price = $actual_conversion_rate * parseUserNumber($_REQUEST['unit_price']); //crmv@92824
				$actual_price = parseUserNumber($_REQUEST[$cur_valuename]); //crmv@92824

				$log->debug("Going to save the Product - $curname currency relationship");

				$query = "insert into ".$table_prefix."_productcurrencyrel values(?,?,?,?)";
				$adb->pquery($query, array($this->id,$curid,$converted_price,$actual_price));

				// Update the Product information with Base Currency choosen by the User.
				if ($_REQUEST['base_currency'] == $cur_valuename) {
					$adb->pquery("update ".$table_prefix."_products set currency_id=?, unit_price=? where productid=?", array($curid, $actual_price, $this->id));
				}
			}
		}

		$log->debug("Exiting from insertPriceInformation($tablename, $module) method ...");
	}

	function updateUnitPrice() {
		global $table_prefix;
		$prod_res = $this->db->pquery("select unit_price, currency_id from ".$table_prefix."_products where productid=?", array($this->id));
		$prod_unit_price = $this->db->query_result($prod_res, 0, 'unit_price');
		$prod_base_currency = $this->db->query_result($prod_res, 0, 'currency_id');

		$query = "update ".$table_prefix."_productcurrencyrel set actual_price=? where productid=? and currencyid=?";
		$params = array($prod_unit_price, $this->id, $prod_base_currency);
		$this->db->pquery($query, $params);
	}

	function insertIntoAttachment($id,$module)
	{
		global $log, $adb;
		global $table_prefix;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");

		$file_saved = false;

		foreach($_FILES as $fileindex => $files)
		{
			if($files['name'] != '' && $files['size'] > 0)
			{
			      if($_REQUEST[$fileindex.'_hidden'] != '')
				      $files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
			      else
				      $files['original_name'] = stripslashes($files['name']);
			      $files['original_name'] = str_replace('"','',$files['original_name']);
				$file_saved = $this->uploadAndSaveFile($id,$module,$files);
			}
		}

		//Remove the deleted vte_attachments from db - Products
		if($module == 'Products' && $_REQUEST['del_file_list'] != '')
		{
			$del_file_list = explode("###",trim($_REQUEST['del_file_list'],"###"));
			foreach($del_file_list as $del_file_name)
			{
				$attach_res = $adb->pquery("select ".$table_prefix."_attachments.attachmentsid from ".$table_prefix."_attachments inner join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_seattachmentsrel.attachmentsid where crmid=? and name=?", array($id,$del_file_name));
				$attachments_id = $adb->query_result($attach_res,0,'attachmentsid');

				$del_res1 = $adb->pquery("delete from ".$table_prefix."_attachments where attachmentsid=?", array($attachments_id));
				$del_res2 = $adb->pquery("delete from ".$table_prefix."_seattachmentsrel where attachmentsid=?", array($attachments_id));
			}
		}

		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
	}

	/**	function used to get the list of pricebooks which are related to the product
	 *	@param int $id - product id
	 *	@return array - array which will be returned from the function GetRelatedList
	 */
	function get_product_pricebooks($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $log,$currentModule;//crmv@203484 removed global singlepane
        global $table_prefix;
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
		$log->debug("Entering get_product_pricebooks(".$id.") method ...");

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$focus = CRMEntity::getInstance($related_module);
		$singular_modname = vtlib_toSingular($related_module);

		$button = '';
		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_ADD_TO'). " ". getTranslatedString($related_module) ."' class='crmbutton small create'" .
					" onclick=\"location.href = 'index.php?action=AddProductToPriceBooks&module=$currentModule&return_module=$currentModule&return_action=DetailView&return_id=$id'\" type='button' name='button'" . // crmv@43864
					" value='". getTranslatedString('LBL_ADD_TO'). " " . getTranslatedString($related_module) ."'>&nbsp;";
			}
		}

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
            $returnset = '&return_module=Products&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module=Products&return_action=CallRelatedList&return_id='.$id;


		$query = "SELECT ".$table_prefix."_crmentity.crmid,
			".$table_prefix."_pricebook.*,
			".$table_prefix."_pricebookproductrel.productid as prodid
			FROM ".$table_prefix."_pricebook
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_pricebook.pricebookid
			INNER JOIN ".$table_prefix."_pricebookcf
				ON ".$table_prefix."_pricebookcf.pricebookid = ".$table_prefix."_pricebook.pricebookid
			INNER JOIN ".$table_prefix."_pricebookproductrel
				ON ".$table_prefix."_pricebookproductrel.pricebookid = ".$table_prefix."_pricebook.pricebookid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			AND ".$table_prefix."_pricebookproductrel.productid = ".$id;
		$log->debug("Exiting get_product_pricebooks method ...");

		$return_value = GetRelatedList($currentModule, $related_module, $focus, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}

	//crmv@43864
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
	//crmv@43864e

	/**	function used to get the number of vendors which are related to the product
	 *	@param int $id - product id
	 *	@return int number of rows - return the number of products which do not have relationship with vendor
	 */
	function product_novendor()
	{
		global $log;
		global $table_prefix;
		$log->debug("Entering product_novendor() method ...");
		$query = "SELECT ".$table_prefix."_products.productname, ".$table_prefix."_crmentity.deleted
			FROM ".$table_prefix."_products
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_products.productid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			AND ".$table_prefix."_products.vendor_id is NULL";
		$result=$this->db->pquery($query, array());
		$log->debug("Exiting product_novendor method ...");
		return $this->db->num_rows($result);
	}

	/**
	* Function to get Product's related Products
	* @param  integer   $id      - productid
	* returns related Products record in array format
	*/
	function get_products($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
        global $table_prefix;
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
		$log->debug("Entering get_products(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
            $returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';
		if($actions && $this->ismember_check() === 0) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			$button .= $this->get_related_buttons($this_module, $id, $related_module, $actions);	//crmv@51301
		}

		$query = "SELECT ".$table_prefix."_products.productid, ".$table_prefix."_products.productname,
			".$table_prefix."_products.productcode, ".$table_prefix."_products.commissionrate,
			".$table_prefix."_products.qty_per_unit, ".$table_prefix."_products.unit_price,
			".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.smownerid
			FROM ".$table_prefix."_products
			INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_products.productid
			INNER JOIN ".$table_prefix."_productcf ON ".$table_prefix."_productcf.productid = ".$table_prefix."_products.productid
			LEFT JOIN ".$table_prefix."_seproductsrel ON ".$table_prefix."_seproductsrel.crmid = ".$table_prefix."_products.productid AND ".$table_prefix."_seproductsrel.setype='Products'
			WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_seproductsrel.productid = $id ";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_products method ...");
		return $return_value;
	}

	/**
	* Function to get Product's related Products
	* @param  integer   $id      - productid
	* returns related Products record in array format
	*/
	function get_parent_products($id)
	{
		global $log;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_parent_products(".$id.") method ...");
		global $table_prefix;
		global $app_strings;

		$focus = CRMEntity::getInstance('Products');

		$button = '';
		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
            $returnset = '&return_module=Products&return_action=DetailView&is_parent=1&return_id='.$id;
		else
			$returnset = '&return_module=Products&return_action=CallRelatedList&is_parent=1&return_id='.$id;

		$query = "SELECT ".$table_prefix."_products.productid, ".$table_prefix."_products.productname,
			".$table_prefix."_products.productcode, ".$table_prefix."_products.commissionrate,
			".$table_prefix."_products.qty_per_unit, ".$table_prefix."_products.unit_price,
			".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.smownerid
			FROM ".$table_prefix."_products
			INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_products.productid
			INNER JOIN ".$table_prefix."_productcf ON ".$table_prefix."_productcf.productid = ".$table_prefix."_products.productid
			INNER JOIN ".$table_prefix."_seproductsrel ON ".$table_prefix."_seproductsrel.productid = ".$table_prefix."_products.productid AND ".$table_prefix."_seproductsrel.setype='Products'
			WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_seproductsrel.crmid = $id ";

		$return_value = GetRelatedList('Products','Products',$focus,$query,$button,$returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_parent_products method ...");
		return $return_value;
	}

	/**	function used to get the export query for product
	 *	@param reference $where - reference of the where variable which will be added with the query
	 *	@return string $query - return the query which will give the list of products to export
	 */
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log;
		global $table_prefix;
		$log->debug("Entering create_export_query(".$where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Products", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		// crmv@111691 crmv@109663 crmv@121509
		$query = "SELECT $fields_list FROM ".$this->table_name ."
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_products.productid
			LEFT JOIN ".$table_prefix."_productcf
				ON ".$table_prefix."_products.productid = ".$table_prefix."_productcf.productid
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid=".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_vendor
				ON ".$table_prefix."_vendor.vendorid = ".$table_prefix."_products.vendor_id
			LEFT JOIN ".$table_prefix."_productlines 
				ON ".$table_prefix."_productlines.productlineid = ".$table_prefix."_products.productlineid ";
		// crmv@111691e crmv@109663e crmv@121509e

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e

		$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0"; // crmv@111691

		if($where != "")
			$query .= " AND ($where) ";
		$query = $this->listQueryNonAdminChange($query, 'Products');
		$log->debug("Exiting create_export_query method ...");
		return $query;
	}

	/** Function to check if the product is parent of any other product
	*/
	function isparent_check(){
		global $adb, $table_prefix;
		$LVU = ListViewUtils::getInstance();
		$isparent_query = $adb->pquery($LVU->getListQuery("Products")." AND (".$table_prefix."_products.productid IN (SELECT productid from ".$table_prefix."_seproductsrel WHERE ".$table_prefix."_seproductsrel.productid = ? AND ".$table_prefix."_seproductsrel.setype='Products'))",array($this->id));
		$isparent = $adb->num_rows($isparent_query);
		return $isparent;
	}

	/** Function to check if the product is member of other product
	*/
	function ismember_check(){
		global $adb, $table_prefix;
		$LVU = ListViewUtils::getInstance();
		$ismember_query = $adb->pquery($LVU->getListQuery("Products")." AND (".$table_prefix."_products.productid IN (SELECT crmid from ".$table_prefix."_seproductsrel WHERE ".$table_prefix."_seproductsrel.crmid = ? AND ".$table_prefix."_seproductsrel.setype='Products'))",array($this->id));
		$ismember = $adb->num_rows($ismember_query);
		return $ismember;
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

		$rel_table_arr = Array("HelpDesk"=>$table_prefix."_troubletickets","Products"=>$table_prefix."_seproductsrel","Attachments"=>$table_prefix."_seattachmentsrel",
				"Quotes"=>$table_prefix."_inventoryproductrel","PurchaseOrder"=>$table_prefix."_inventoryproductrel","SalesOrder"=>$table_prefix."_inventoryproductrel",
				"Invoice"=>$table_prefix."_inventoryproductrel","PriceBooks"=>$table_prefix."_pricebookproductrel","Leads"=>$table_prefix."_seproductsrel",
				"Accounts"=>$table_prefix."_seproductsrel","Potentials"=>$table_prefix."_seproductsrel","Contacts"=>$table_prefix."_seproductsrel",
				"Documents"=>$table_prefix."_senotesrel");

		$tbl_field_arr = Array($table_prefix."_troubletickets"=>"ticketid",$table_prefix."_seproductsrel"=>"crmid",$table_prefix."_seattachmentsrel"=>"attachmentsid",
				$table_prefix."_inventoryproductrel"=>"id",$table_prefix."_pricebookproductrel"=>"pricebookid",$table_prefix."_seproductsrel"=>"crmid",
				$table_prefix."_senotesrel"=>"notesid");

		$entity_tbl_field_arr = Array($table_prefix."_troubletickets"=>"product_id",$table_prefix."_seproductsrel"=>"crmid",$table_prefix."_seattachmentsrel"=>"crmid",
				$table_prefix."_inventoryproductrel"=>"productid",$table_prefix."_pricebookproductrel"=>"productid",$table_prefix."_seproductsrel"=>"productid",
				$table_prefix."_senotesrel"=>"crmid");

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

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	// crmv@38798
	function generateReportsSecQuery($module,$secmodule,$report_type='',$hasInventoryColumns=true,$joinUitype10=true){ // crmv@146653
		global $current_user;
		global $table_prefix;
		// crmv@29686
		if (!$hasInventoryColumns) {
			$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_products","productid");
		}
		// crmv@29686e
		// crmv@109663
		//$tmptable = 'tmp_innerProduct'.$this->reportid;
		if($report_type !== 'COLUMNSTOTOTAL') {
			// crmv@175894
			$query .= " LEFT JOIN (
					SELECT ".$table_prefix."_products.productid,
							(CASE WHEN (".$table_prefix."_products.currency_id = 1 ) THEN ".$table_prefix."_products.unit_price
								ELSE (".$table_prefix."_products.unit_price / ".$table_prefix."_currency_info.conversion_rate) END
							) AS actual_unit_price
					FROM ".$table_prefix."_products
					LEFT JOIN ".$table_prefix."_currency_info ON ".$table_prefix."_products.currency_id = ".$table_prefix."_currency_info.id
					LEFT JOIN ".$table_prefix."_productcurrencyrel ON ".$table_prefix."_products.productid = ".$table_prefix."_productcurrencyrel.productid
					AND ".$table_prefix."_productcurrencyrel.currencyid = ". $current_user->currency_id . "
				) innerProduct ON innerProduct.productid = ".$table_prefix."_products.productid
				left join ".$table_prefix."_productcf on ".$table_prefix."_products.productid = ".$table_prefix."_productcf.productid
				left join ".$table_prefix."_users ".$table_prefix."_usersProducts on ".$table_prefix."_usersProducts.id = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_groups ".$table_prefix."_groupsProducts on ".$table_prefix."_groupsProducts.groupid = ".$table_prefix."_crmentity.smownerid
				left join ".$table_prefix."_vendor ".$table_prefix."_vendorRelProducts on ".$table_prefix."_vendorRelProducts.vendorid = ".$table_prefix."_products.vendor_id
				left join ".$table_prefix."_productlines ".$table_prefix."_productlinesRelProducts on ".$table_prefix."_productlinesRelProducts.productlineid = ".$table_prefix."_products.productlineid	";	//crmv@54531 crmv@64542
		}
		// crmv@109663e
		return $query;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	// crmv@96033
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"HelpDesk" => array($table_prefix."_troubletickets"=>array("product_id","ticketid"),$table_prefix."_products"=>"productid"),
			"Quotes" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_products"=>"productid"),
			"PurchaseOrder" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_products"=>"productid"),
			"SalesOrder" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_products"=>"productid"),
			"Invoice" => array($table_prefix."_inventoryproductrel"=>array("productid","id", '', 'relmodule'),$table_prefix."_products"=>"productid"),
			"Products" => array($table_prefix."_products"=>array("productid","product_id"),$table_prefix."_products"=>"productid"),
			"PriceBooks" => array($table_prefix."_pricebookproductrel"=>array("productid","pricebookid"),$table_prefix."_products"=>"productid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_products"=>"productid"),
		);
		$defaultRel = array($this->relation_table=>array($this->relation_table_id,$this->relation_table_otherid), $this->table_name=>$this->table_index);
		return $rel_tables[$secmodule] ?: $defaultRel;
	}
	// crmv@38798e crmv@96033e

	function deleteProduct2ProductRelation($record,$return_id,$is_parent){
		global $adb;
		global $table_prefix;
		if($is_parent==0){
			$sql = "delete from ".$table_prefix."_seproductsrel WHERE crmid = ? AND productid = ?";
			$adb->pquery($sql, array($record,$return_id));
		} else {
			$sql = "delete from ".$table_prefix."_seproductsrel WHERE crmid = ? AND productid = ?";
			$adb->pquery($sql, array($return_id,$record));
		}
	}

	function insertIntoseProductsRel($record_id,$parentid,$return_module){
		global $adb;
		global $table_prefix;
		$query = $adb->pquery("SELECT * from ".$table_prefix."_seproductsrel WHERE ((crmid=? and productid=?) OR (crmid=? and productid=?)) AND setype='Products'",array($record_id,$parentid,$parentid,$record_id));
		if($adb->num_rows($query)==0 && $return_module=='Products'){
			$adb->pquery("insert into ".$table_prefix."_seproductsrel values (?,?,?)",array($record_id,$parentid,$return_module));
		}
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log;
		global $table_prefix;
		//Backup Campaigns-Product Relation
		$cmp_q = 'SELECT campaignid FROM '.$table_prefix.'_campaign WHERE product_id = ?';
		$cmp_res = $this->db->pquery($cmp_q, array($id));
		if ($this->db->num_rows($cmp_res) > 0) {
			$cmp_ids_list = array();
			for($k=0;$k < $this->db->num_rows($cmp_res);$k++)
			{
				$cmp_ids_list[] = $this->db->query_result($cmp_res,$k,"campaignid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_campaign', 'product_id', 'campaignid', implode(",", $cmp_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
		}
		//we have to update the product_id as null for the campaigns which are related to this product
		$this->db->pquery('UPDATE '.$table_prefix.'_campaign SET product_id=0 WHERE product_id = ?', array($id));

		$this->db->pquery('DELETE from '.$table_prefix.'_seproductsrel WHERE productid=? or crmid=?',array($id,$id));

		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		global $table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Calendar') {
			$sql = 'DELETE FROM '.$table_prefix.'_seactivityrel WHERE crmid = ? AND activityid = ?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Leads' || $return_module == 'Accounts' || $return_module == 'Contacts' || $return_module == 'Potentials') {
			$sql = 'DELETE FROM '.$table_prefix.'_seproductsrel WHERE productid = ? AND crmid = ?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Vendors') {
			$sql = 'UPDATE '.$table_prefix.'_products SET vendor_id = 0 WHERE productid = ?';
			$this->db->pquery($sql, array($id));
		} else {
			// crmv@189616
			$relation_info = $this->setRelationTables($return_module);
			if (is_array($relation_info) && !empty($relation_info)) {
				$tablename = key($relation_info);
				$product_col = $relation_info[$tablename][0];
				$other_module_col = $relation_info[$tablename][1];
				$sql = "DELETE FROM $tablename WHERE $product_col = ? AND $other_module_col = ?";
				$params = array($id, $return_id);
				$this->db->pquery($sql, $params);
			}
			// crmv@189616e
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

	//crmv@20706
	function insertQCPriceInformation($tablename, $module)
	{
		global $adb, $current_user;
		global $table_prefix;
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

				$adb->pquery("update ".$table_prefix."_products set currency_id=?, unit_price=? where productid=?", array($curid, $actual_price, $this->id));
			}
		}
	}
	//crmv@20706e

	//crmv@38866
	function insertImportPriceInformation($tablename, $module)
	{
		global $adb, $current_user;
		global $table_prefix;
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
	//crmv@38866e
	
	// crmv@198024
	/**
	 * Return a simple array with the attributes and their values for this product
	 */
	public function getAttributes($confprodinfo = null) {
		if (is_null($confprodinfo)) {
			$confprodinfo = html_entity_decode($this->column_fields['confprodinfo'], ENT_QUOTES, 'UTF-8');
		}
		$values = Zend_Json::decode($confprodinfo) ?: [];
		return $values;
	}
	
	/**
	 * Prepare the dynamic attributes block for detail or editview
	 */
	public function addAttributesBlock($col_fields, $mode, &$return_fields, &$blockdata, &$aBlockStatus) {
		// check if we have an id
		$confid = intval($col_fields['confproductid']);
		if ($confid == 0) return;
		
		// check if module is active
		if (!vtlib_isModuleActive('ConfProducts')) return;
		
		$confprod = html_entity_decode($col_fields['confprodinfo'], ENT_QUOTES, 'UTF-8');
		$values = Zend_Json::decode($confprod) ?: [];
		$col_fields = array_replace($col_fields, $values);
		
		// get structure
		$cprod = CRMEntity::getInstance('ConfProducts');
		$struct = $cprod->getAttributes($confid);
		if (is_array($struct) && count($struct) > 0) {
		
			$blockid = getBlockId(getTabid('Products'), 'LBL_VARIANT_INFORMATION');
			foreach ($struct as $field) {
				$fieldname = $field['fieldname'];
				if ($field['uitype'] == 15) {
					$field['picklistvalues'] = implode("\n", $field['values']); // simulate dynaform
				}
				
				if ($mode == 'detail') {
					$custfld = getDetailViewOutputHtml($field['uitype'], $fieldname, $field['fieldlabel'], $col_fields, 1, '', 'Products', $field); //crmv@157799
					if (is_array($custfld)) {
						$return_fields[$blockid][] = array(
							$custfld[0] => array(
								"value"=>$custfld[1],
								"ui"=>$custfld[2],
								"options"=>$custfld["options"],
								"secid"=>$custfld["secid"],
								"link"=>$custfld["link"],
								"cursymb"=>$custfld["cursymb"],
								"salut"=>$custfld["salut"],
								"notaccess"=>$custfld["notaccess"],
								"isadmin"=>$custfld["isadmin"],
								"tablename"=>$this->table_name,
								"fldname"=>$fieldname,
								"fldid"=>0,
								"displaytype"=>1,
								"readonly"=>1,
								'mandatory'=>1
							)
						);
					}
				} elseif ($mode == 'edit') {
					$typeofdata = 'V~O';
					$fld = getOutputHtml($field['uitype'], $fieldname, $field['fieldlabel'], $field['length'], $col_fields, 1, 'Products', $mode, 1, $typeofdata, $field); //crmv@157799
					$fld[] = $fieldname;
					$return_fields[$blockid][] = $fld;
				} elseif ($mode == 'array') {
					$field['value'] = $col_fields[$fieldname];
					$return_fields[] = $field;
				}
				
			}
		}
	}
	// crmv@198024e
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file

class PriceBooks extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'pricebookid';
	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();
	var $column_fields = Array();

	var $sortby_fields = Array('bookname');

        // This is the list of fields that are in the lists.
	var $list_fields = Array(
                                'Price Book Name'=>Array('pricebook'=>'bookname'),
                                'Active'=>Array('pricebook'=>'active')
                                );

	var $list_fields_name = Array(
                                        'Price Book Name'=>'bookname',
                                        'Active'=>'active'
                                     );
	var $list_link_field= 'bookname';

	var $search_fields = Array(
                                'Price Book Name'=>Array('pricebook'=>'bookname')
                                );
	var $search_fields_name = Array(
                                        'Price Book Name'=>'bookname',
                                     );

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'bookname';
	var $default_sort_order = 'ASC';

	var $mandatory_fields = Array('bookname','currency_id','pricebook_no','createdtime' ,'modifiedtime');
	//crmv@10759
	var $search_base_field = 'bookname';
	//crmv@10759 e
	/**	Constructor which will set the column_fields in this object
	 */
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_pricebook";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_pricebook',$table_prefix.'_pricebookcf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_pricebook'=>'pricebookid',$table_prefix.'_pricebookcf'=>'pricebookid');
		$this->customFieldTable = Array($table_prefix.'_pricebookcf', 'pricebookid');
		$this->log =LoggerManager::getLogger('pricebook');
		$this->log->debug("Entering PriceBooks() method ...");
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('PriceBooks');
		$this->log->debug("Exiting PriceBook method ...");
	}

	function save_module($module)
	{
		// Update the list prices in the price book with the unit price, if the Currency has been changed
		$this->updateListPrices();
	}

	/* Function to Update the List prices for all the products of a current price book
	   with its Unit price, if the Currency for Price book has changed. */
	function updateListPrices() {
		global $log, $adb;
		global $table_prefix;
		$log->debug("Entering function updateListPrices...");
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$pricebook_currency = $this->column_fields['currency_id'];
		$prod_res = $adb->pquery("select productid from ".$table_prefix."_pricebookproductrel where pricebookid=? and usedcurrency != ?",
							array($this->id,$pricebook_currency));
		$numRows = $adb->num_rows($prod_res);
		$prod_ids = array();
		for($i=0;$i<$numRows;$i++) {
			$prod_ids[] = $adb->query_result($prod_res,$i,'productid');
		}
		if(count($prod_ids) > 0) {
			$prod_price_list = $InventoryUtils->getPricesForProducts($pricebook_currency,$prod_ids);

			for($i=0;$i<count($prod_ids);$i++) {
				$product_id = $prod_ids[$i];
				$unit_price = $prod_price_list[$product_id];
				$query = "update ".$table_prefix."_pricebookproductrel set listprice=?, usedcurrency=? where pricebookid=? and productid=?";
				$params = array($unit_price, $pricebook_currency, $this->id, $product_id);
				$adb->pquery($query, $params);
			}
		}
		$log->debug("Exiting function updateListPrices...");
	}

	/**	function used to get the products which are related to the pricebook
	 *	@param int $id - pricebook id
	 *      @return array - return an array which will be returned from the function getPriceBookRelatedProducts
	 **/
	//crmv@43864
	function get_pricebook_products($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		$log->debug("Entering get_pricebook_products(".$id.") method ...");
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

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('SELECT', $actions) && isPermitted($related_module,4, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($related_module). "' class='crmbutton small edit' type='button' name='button' onclick=\"location.href = 'index.php?action=AddProductsToPriceBook&module=$related_module&return_module=$currentModule&return_action=DetailView&return_id=$id&pricebook_id=$id';\" value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($related_module) ."'>&nbsp;";
			}
		}

		$query = 'select '.$table_prefix.'_products.productid, '.$table_prefix.'_products.productname, '.$table_prefix.'_products.productcode, '.$table_prefix.'_products.commissionrate, '.$table_prefix.'_products.qty_per_unit, '.$table_prefix.'_products.unit_price, '.$table_prefix.'_crmentity.crmid, '.$table_prefix.'_crmentity.smownerid,'.$table_prefix.'_pricebookproductrel.listprice
		from '.$table_prefix.'_products
		inner join '.$table_prefix.'_productcf on '.$table_prefix.'_productcf.productid = '.$table_prefix.'_products.productid
		inner join '.$table_prefix.'_pricebookproductrel on '.$table_prefix.'_products.productid = '.$table_prefix.'_pricebookproductrel.productid
		inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_products.productid
		inner join '.$table_prefix.'_pricebook on '.$table_prefix.'_pricebook.pricebookid = '.$table_prefix.'_pricebookproductrel.pricebookid
		where '.$table_prefix.'_pricebook.pricebookid = '.$id.' and '.$table_prefix.'_crmentity.deleted = 0';

		$return_value = getPriceBookRelatedProducts($query,$this,$returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_pricebook_products method ...");
		return $return_value;
	}

	/**	function used to get the services which are related to the pricebook
	 *	@param int $id - pricebook id
	 *      @return array - return an array which will be returned from the function getPriceBookRelatedServices
	 **/
	function get_pricebook_services($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		$log->debug("Entering get_pricebook_services(".$id.") method ...");
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

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('SELECT', $actions) && isPermitted($related_module,4, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($related_module). "' class='crmbutton small edit' type='button' name='button' onclick=\"location.href = 'index.php?action=AddServicesToPriceBook&module=$related_module&return_module=$currentModule&return_action=DetailView&pricebook_id=$id'\" value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($related_module) ."'>&nbsp;";
			}
		}

		$query = 'select '.$table_prefix.'_service.serviceid, '.$table_prefix.'_service.servicename, '.$table_prefix.'_service.commissionrate,
		'.$table_prefix.'_service.qty_per_unit, '.$table_prefix.'_service.unit_price, '.$table_prefix.'_crmentity.crmid, '.$table_prefix.'_crmentity.smownerid,
		'.$table_prefix.'_pricebookproductrel.listprice from '.$table_prefix.'_service
		inner join '.$table_prefix.'_servicecf on '.$table_prefix.'_servicecf.serviceid = '.$table_prefix.'_service.serviceid
		inner join '.$table_prefix.'_pricebookproductrel on '.$table_prefix.'_service.serviceid = '.$table_prefix.'_pricebookproductrel.productid
		inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_service.serviceid
		inner join '.$table_prefix.'_pricebook on '.$table_prefix.'_pricebook.pricebookid = '.$table_prefix.'_pricebookproductrel.pricebookid
		where '.$table_prefix.'_pricebook.pricebookid = '.$id.' and '.$table_prefix.'_crmentity.deleted = 0';

		$return_value = $other->getPriceBookRelatedServices($query,$this,$returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_pricebook_services method ...");
		return $return_value;
	}

	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) { // crmv@146653
		global $adb, $table_prefix;

		$InventoryUtils = InventoryUtils::getInstance();

		$reltable = $table_prefix."_pricebookproductrel";
		if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);

		if ($module == 'PriceBooks' && isProductModule($with_module)) {
			foreach ($with_crmid as $productid) {
				$res = $adb->pquery("select * from $reltable where pricebookid = ? and productid = ?", array($crmid, $productid));
				if ($res && $adb->num_rows($res) == 0) {
					$currency_id = $InventoryUtils->getPriceBookCurrency($crmid);
					// get listprice
					if ($with_module == 'Products') {
						$listprice = getSingleFieldValue($table_prefix."_products", 'unit_price', 'productid', $productid);
					} else {
						$listprice = getSingleFieldValue($table_prefix."_service", 'unit_price', 'serviceid', $productid);
					}
					// crmv@150533
					$id = $adb->getUniqueID($reltable);
					$query= "insert into $reltable (pbrelid,pricebookid,productid,listprice,usedcurrency) values(?,?,?,?,?)";
					$adb->pquery($query, array($id, $crmid,$productid,$listprice,$currency_id));
					// crmv@150533e
				}
			}
		} else {
			return parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check); // crmv@146653
		}
	}
	// crmv@43864e

	/**	function used to get whether the pricebook has related with a product or not
	 *	@param int $id - product id
	 *	@return true or false - if there are no pricebooks available or associated pricebooks for the product is equal to total number of pricebooks then return false, else return true
	 */
	function get_pricebook_noproduct($id)
	{
		global $log;
		global $table_prefix;
		$log->debug("Entering get_pricebook_noproduct(".$id.") method ...");

		$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_pricebook.* from ".$table_prefix."_pricebook inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_pricebook.pricebookid where ".$table_prefix."_crmentity.deleted=0";
		$result = $this->db->pquery($query, array());
		$no_count = $this->db->num_rows($result);
		if($no_count !=0)
		{
       	 	$pb_query = 'select '.$table_prefix.'_crmentity.crmid, '.$table_prefix.'_pricebook.pricebookid,'.$table_prefix.'_pricebookproductrel.productid
       	 		from '.$table_prefix.'_pricebook
       	 		inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_pricebook.pricebookid
       	 		inner join '.$table_prefix.'_pricebookproductrel on '.$table_prefix.'_pricebookproductrel.pricebookid='.$table_prefix.'_pricebook.pricebookid
       	 		where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_pricebookproductrel.productid=?';
			$result_pb = $this->db->pquery($pb_query, array($id));
			if($no_count == $this->db->num_rows($result_pb))
			{
				$log->debug("Exiting get_pricebook_noproduct method ...");
				return false;
			}
			elseif($this->db->num_rows($result_pb) == 0)
			{
				$log->debug("Exiting get_pricebook_noproduct method ...");
				return true;
			}
			elseif($this->db->num_rows($result_pb) < $no_count)
			{
				$log->debug("Exiting get_pricebook_noproduct method ...");
				return true;
			}
		}
		else
		{
			$log->debug("Exiting get_pricebook_noproduct method ...");
			return false;
		}
	}

	/*
	 * Function to get the primary query part of a report
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */
	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true) { // crmv@146653
		global $table_prefix;
	 			$moduletable = $this->table_name;
	 			$moduleindex = $this->table_index;
	 			//crmv@21249
	 			$query = "from $moduletable
					inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=$moduletable.$moduleindex
					left join ".$table_prefix."_currency_info ".$table_prefix."_currency_info$module on ".$table_prefix."_currency_info$module.id = $moduletable.currency_id
					left join ".$table_prefix."_groups ".substr($table_prefix."_groups$module",0,29)." on ".substr($table_prefix."_groups$module",0,29).".groupid = ".$table_prefix."_crmentity.smownerid
					left join ".$table_prefix."_users ".substr($table_prefix."_users$module",0,29)." on ".substr($table_prefix."_users$module",0,29).".id = ".$table_prefix."_crmentity.smownerid
					left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
	 			//crmv@21249e
	            return $query;
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){	//crmv@146653
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_pricebook","pricebookid");
		//crmv@21249
		$query .=" left join ".$table_prefix."_crmentity ".$table_prefix."_crmentityPriceBooks on ".$table_prefix."_crmentityPriceBooks.crmid=".$table_prefix."_pricebook.pricebookid and ".$table_prefix."_crmentityPriceBooks.deleted=0
				left join ".$table_prefix."_currency_info ".$table_prefix."_currency_infoPriceBooks on ".$table_prefix."_currency_infoPriceBooks.id = ".$table_prefix."_pricebook.currency_id
				left join ".$table_prefix."_users ".$table_prefix."_usersPriceBooks on ".$table_prefix."_usersPriceBooks.id = ".$table_prefix."_crmentityPriceBooks.smownerid
				left join ".$table_prefix."_groups ".$table_prefix."_groupsPriceBooks on ".$table_prefix."_groupsPriceBooks.groupid = ".$table_prefix."_crmentityPriceBooks.smownerid";
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
			"Products" => array($table_prefix."_pricebookproductrel"=>array("pricebookid","productid"),$table_prefix."_pricebook"=>"pricebookid"),
			"Services" => array($table_prefix."_pricebookproductrel"=>array("pricebookid","productid"),$table_prefix."_pricebook"=>"pricebookid"),
		);
		return $rel_tables[$secmodule];
	}

}
?>
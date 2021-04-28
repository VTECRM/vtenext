<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@151308 - support for class autoloading


// classe con metodi per la gestione dei prodotti
class InventoryUtils extends SDKExtendableUniqueClass {

	public $workingPrecision = 2;	// number of decimals to use during calculations
	public $outputPrecision = 2;	// number of decimals to use for results

	public $decimalSeparator = '.';
	public $thousandsSeparator = ',';

	public $invalidNumber = 0.0; // crmv@117880

	static public function callMethodByName($name, $arguments = array()) {
		$InventoryUtils = InventoryUtils::getInstance();
		return call_user_func_array(array($InventoryUtils, $name), $arguments);
	}

	// this function is used to check if old override funciton exists
	// new personalizations should extend the class, these functions might be removed in the future
	static protected function checkOldOverride($name) {
		$oname = $name.'_override';
		return (function_exists($oname) ? $oname : false);
	}

	// call the old overridden function
	static protected function callOldOverride($oname, $arguments = array()) {
		return call_user_func_array($oname, $arguments);
	}

	function __construct() {
		global $current_user;
		global $default_decimal_separator, $default_thousands_separator, $default_decimals_num;

		// first set values with global settings
		if (isset($default_decimal_separator)) $this->decimalSeparator = $default_decimal_separator;
		if (isset($default_thousands_separator)) $this->thousandsSeparator = $default_thousands_separator;
		if (isset($default_decimals_num)) {
			$this->workingPrecision = $default_decimals_num;
			$this->outputPrecision = $default_decimals_num;
		}

		// then load from user if available
		if ($current_user && $current_user->column_fields) {
			if (isset($current_user->column_fields['decimal_separator'])) $this->decimalSeparator = $current_user->column_fields['decimal_separator'];
			if (isset($current_user->column_fields['thousands_separator'])) $this->thousandsSeparator = $current_user->column_fields['thousands_separator'];
			if (isset($current_user->column_fields['decimals_num'])) {
				//crmv@93718
				$this->workingPrecision = ($current_user->column_fields['decimals_num'] != '' ? intval($current_user->column_fields['decimals_num']) : 2);
				$this->outputPrecision = ($current_user->column_fields['decimals_num'] != '' ? intval($current_user->column_fields['decimals_num']) : 2);
				//crmv@93718e
			}
		}

	}

	// returns a float number from the string in the user format (0 if not valid)
	/* Algorithm
	 * 1. if no decimal or thousands separator, convert it straight
	 * 2. if contains thousand sep, remove it
	 * 3. if contains dec separator, convert it to "."
	 * 4. use parsefloat
	 * 5. round up to working precision
	 */
	function parseUserNumber($number) {
		$ds = $this->decimalSeparator;
		$ts = $this->thousandsSeparator;
		$wp = $this->workingPrecision;

		// already float
		if (is_float($number) || is_int($number)) {
			return round(floatval($number), $wp);

		// string -> do the parsing
		} elseif (is_string($number)) {

			if ($ds === '' || (strpos($number, $ds) === false) && ($ts === '' || strpos($number, $ts) === false) && is_numeric($number)) return floatval($number);
			// remove thousand sep
			if ($ts != '') $number = str_replace($ts, '', $number);
			// replace dec separator
			if ($ds != '.') $number = str_replace($ds, '.', $number);

			if (is_numeric($number)) {
				return round(floatval($number), $wp);
			}
		}
		// not a valid number
		return $this->invalidNumber; // crmv@117880
	}

	// crmv@83877
	// return a string with the number formatted with the user's settings
	function formatUserNumber($number, $autoTrimDecimals = false, $outputPrecision = null) { // crmv@193848
		$ds = $this->decimalSeparator;
		$ts = $this->thousandsSeparator;
		$op = $outputPrecision ?: $this->outputPrecision; // crmv@193848

		if (!is_float($number) && !is_int($number)) {
			$number = floatval($number); // just convert from string
		}
		
		// if the decimal part is 0 (or close enough anyway), don't show it in the formatted number
		if ($autoTrimDecimals && abs($number - (int)$number) <= 1e-10) {
			$op = 0;
		}

		return number_format($number, $op, $ds, $ts);
	}
	// crmv@83877e

	// crmv@48527
	// split a discount string in single float values and return them as an array
	// mode: 0 = db format, 1 = user format
	function parseMultiDiscount($discounts, $inputMode = 0, $outputMode = 1) {
		$list = array();

		if ($discounts[0] != '+' && $discounts[0] != '-') $discounts = '+'.$discounts;
		$discounts = preg_split('/([-+])/', $discounts, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$discounts = array_chunk($discounts, 2);

		foreach ($discounts as $d) {
			if ($inputMode == 0) {
				$val = $d[1];
			} elseif ($inputMode == 1) {
				$val = $this->parseUserNumber($d[1]);
			}
			$floatval = floatval($d[0] . $val);
			if ($outputMode == 0) {
				$val = $floatval;
			} elseif ($outputMode == 1) {
				$val = $this->formatUserNumber($floatval);
			}
			$list[] = $val;

		}
		
		return $list;
	}

	// join an array of discounts into a string, optionally converting to/from user format
	// mode like before
	function joinMultiDiscount($list, $inputMode = 1, $outputMode = 0) {
		$ret = '';
		$len = count($list);
		$i = 0;
		foreach ($list as $n) {
			if ($inputMode == 0) {
				$floatval = floatval($n);
			} elseif ($inputMode == 1) {
				$floatval = $this->parseUserNumber($n);
			}

			$sign = ($floatval < 0 ? '-' : ($i > 0 ? '+' : ''));
			$floatval = abs($floatval);

			if ($outputMode == 0) {
				$val = $floatval;
			} elseif ($outputMode == 1) {
				$val = $this->formatUserNumber($floatval);
			}

			$ret .= $sign . $val;
			++$i;
		}
		return $ret;
	}
	// crmv@48527e

	/**
	 * This function returns the Product detail block values in array format.
	 * Input Parameter are $module - module name, $focus - module object, $num_of_products - no.of vte_products associated with it  * $associated_prod = associated product details
	 * column vte_fields/
	 */
	function getProductDetailsBlockInfo($mode,$module,$focus='',$num_of_products='',$associated_prod='') {
		global $log;
		$log->debug("Entering getProductDetailsBlockInfo(".$mode.",".$module.",".$num_of_products.",".$associated_prod.") method ...");

		$productDetails = Array();
		$productBlock = Array();
		if ($num_of_products=='') {
			$num_of_products = getNoOfAssocProducts($module,$focus);
		}
		$productDetails['no_products'] = $num_of_products;
		if ($associated_prod=='') {
			$productDetails['product_details'] = $this->getAssociatedProducts($module,$focus);
		} else {
			$productDetails['product_details'] = $associated_prod;
		}

		if ($focus != '') {
			$productBlock[] = Array('mode'=>$focus->mode);
			$productBlock[] = $productDetails['product_details'];
			$productBlock[] = Array('taxvalue' => $focus->column_fields['txtTax']);
			$productBlock[] = Array('taxAdjustment' => $focus->column_fields['txtAdjustment']);
			$productBlock[] = Array('hdnSubTotal' => $focus->column_fields['hdnSubTotal']);
			$productBlock[] = Array('hdnGrandTotal' => $focus->column_fields['hdnGrandTotal']);
		} else {
			$productBlock[] = Array(Array());
		}
		$log->debug("Exiting getProductDetailsBlockInfo method ...");
		return $productBlock;
	}

	/**
	 * This function updates the stock information once the product is ordered.
	 * Param $productid - product id
	 * Param $qty - product quantity in no's
	 * Param $mode - mode type
	 * Param $ext_prod_arr - existing vte_products
	 * Param $module - module name
	 * return type void
	 */
	function updateStk($product_id,$qty,$mode,$ext_prod_arr,$module) {
		// this function did nothing, so it's removed
		return;
	}

	/**This function is used to get the quantity in stock of a given product
	 *Param $product_id - product id
	*Returns type numeric
	*/
	function getPrdQtyInStck($product_id) {
		global $log;
		$log->debug("Entering getPrdQtyInStck(".$product_id.") method ...");
		global $adb, $table_prefix;
		$query1 = "SELECT qtyinstock FROM ".$table_prefix."_products WHERE productid = ?";
		$result=$adb->pquery($query1, array($product_id));
		$qtyinstck= $adb->query_result($result,0,"qtyinstock");
		$log->debug("Exiting getPrdQtyInStck method ...");
		return $qtyinstck;
	}

	/**This function is used to get the reorder level of a product
	 *Param $product_id - product id
	 *Returns type numeric
	*/
	function getPrdReOrderLevel($product_id) {
		global $log;
		$log->debug("Entering getPrdReOrderLevel(".$product_id.") method ...");
		global $adb, $table_prefix;
		$query1 = "SELECT reorderlevel FROM ".$table_prefix."_products WHERE productid = ?";
		$result=$adb->pquery($query1, array($product_id));
		$reorderlevel= $adb->query_result($result,0,"reorderlevel");
		$log->debug("Exiting getPrdReOrderLevel method ...");
		return $reorderlevel;
	}

	/**This function is used to get the handler for a given product
	 *Param $product_id - product id
	 *Returns type numeric
	 */
	function getPrdHandler($product_id) {
		global $log;
		$log->debug("Entering getPrdHandler(".$product_id.") method ...");
		global $adb, $table_prefix;
		$query1 = "SELECT handler FROM ".$table_prefix."_products WHERE productid = ?";
		$result=$adb->pquery($query1, array($product_id));
		$handler= $adb->query_result($result,0,"handler");
		$log->debug("Exiting getPrdHandler method ...");
		return $handler;
	}

	/**	function to get the taxid
	 *	@param string $type - tax type (VAT or Sales or Service)
	 *	return int   $taxid - taxid corresponding to the Tax type from vte_inventorytaxinfo vte_table
	 */
	function getTaxId($type) {
		global $adb, $log, $table_prefix;
		$log->debug("Entering into getTaxId($type) function.");

		$res = $adb->pquery("SELECT taxid FROM {$table_prefix}_inventorytaxinfo WHERE taxname=?", array($type));
		$taxid = $adb->query_result($res,0,'taxid');

		$log->debug("Exiting from getTaxId($type) function. return value=$taxid");
		return $taxid;
	}

	// crmv@205306
	/**	
	 * function to get the tax label
	 */
	function getTaxLabel($type) {
		global $adb, $log, $table_prefix;
		$log->debug("Entering into getTaxLabel($type) function.");

		$res = $adb->pquery("SELECT taxlabel FROM {$table_prefix}_inventorytaxinfo WHERE taxname=?", array($type));
		$taxlabel = $adb->query_result($res, 0, 'taxlabel');

		$log->debug("Exiting from getTaxLabel($type) function. return value=$taxlabel");
		return $taxlabel;
	}
	// crmv@205306e

	/**	function to get the taxpercentage
	 *	@param string $type       - tax type (VAT or Sales or Service)
	 *	return int $taxpercentage - taxpercentage corresponding to the Tax type from vte_inventorytaxinfo vte_table
	 */
	function getTaxPercentage($type) {
		global $adb, $log, $table_prefix;
		$log->debug("Entering into getTaxPercentage($type) function.");

		$taxpercentage = '';

		$res = $adb->pquery("SELECT percentage FROM {$table_prefix}_inventorytaxinfo WHERE taxname = ?", array($type));
		$taxpercentage = $adb->query_result($res,0,'percentage');

		$log->debug("Exiting from getTaxPercentage($type) function. return value=$taxpercentage");
		return $taxpercentage;
	}

	/**	function to get the product's taxpercentage
	 *	@param string $type       - tax type (VAT or Sales or Service)
	 *	@param id  $productid     - productid to which we want the tax percentage
	 *	@param id  $default       - if 'default' then first look for product's tax percentage and product's tax is empty then it will return the default configured tax percentage, else it will return the product's tax (not look for default value)
	 *	return int $taxpercentage - taxpercentage corresponding to the Tax type from vte_inventorytaxinfo vte_table
	 */
	function getProductTaxPercentage($type,$productid,$default='') {
		global $adb, $log, $table_prefix;
		$log->debug("Entering into getProductTaxPercentage($type,$productid) function.");

		$taxpercentage = '';

		$res = $adb->pquery("SELECT taxpercentage
			FROM ".$table_prefix."_inventorytaxinfo
			INNER JOIN {$table_prefix}_producttaxrel ON {$table_prefix}_inventorytaxinfo.taxid = {$table_prefix}_producttaxrel.taxid
			WHERE ".$table_prefix."_producttaxrel.productid = ?
			AND ".$table_prefix."_inventorytaxinfo.taxname = ?", array($productid, $type));
		$taxpercentage = $adb->query_result($res,0,'taxpercentage');

		//This is to retrive the default configured value if the taxpercentage related to product is empty
		if ($taxpercentage == '' && $default == 'default') {
			$taxpercentage = $this->getTaxPercentage($type);
		}

		$log->debug("Exiting from getProductTaxPercentage($productid,$type) function. return value=$taxpercentage");
		return $taxpercentage;
	}

	/**	Function used to add the history entry in the relevant tables for PO, SO, Quotes and Invoice modules
	 *	@param string 	$module		- current module name
	 *	@param int 	$id		- entity id
	 *	@param string 	$relatedname	- parent name of the entity ie, required field venor name for PO and account name for SO, Quotes and Invoice
	 *	@param float 	$total		- grand total value of the product details included tax
	 *	@param string 	$history_fldval	- history field value ie., quotestage for Quotes and status for PO, SO and Invoice
	 */
	function addInventoryHistory($module, $id, $relatedname, $total, $history_fldval) {
		global $log, $adb, $table_prefix;
		$log->debug("Entering into function addInventoryHistory($module, $id, $relatedname, $total, $history_fieldvalue)");

		$history_table_array = Array(
			"PurchaseOrder"=>$table_prefix."_postatushistory",
			"SalesOrder"=>$table_prefix."_sostatushistory",
			"Quotes"=>$table_prefix."_quotestagehistory",
			"Invoice"=>$table_prefix."_invoicestatushistory"
		);

		$histid = $adb->getUniqueID($history_table_array[$module]);
		$modifiedtime = $adb->formatDate(date('Y-m-d H:i:s'), true);
		$query = "insert into $history_table_array[$module] values(?,?,?,?,?,?)";
		$qparams = array($histid,$id,$relatedname,$total,$history_fldval,$modifiedtime);
		$adb->pquery($query, $qparams);

		$log->debug("Exit from function addInventoryHistory");
	}

	/**	Function used to get the list of Tax types as a array
	 *	@param string $available - available or empty where as default is all, if available then the taxes which are available now will be returned otherwise all taxes will be returned
	 *  @param string $sh - sh or empty, if sh passed then the shipping and handling related taxes will be returned
	 *  @param string $mode - edit or empty, if mode is edit, then it will return taxes including desabled.
	 *  @param string $id - crmid or empty, getting crmid to get tax values..
	 *	return array $taxtypes - return all the tax types as a array
	 */
	function getAllTaxes($available='all', $sh='',$mode='',$id='') {
		global $adb, $log, $table_prefix;
		$log->debug("Entering into the function getAllTaxes($available,$sh,$mode,$id)");
		$taxtypes = Array();
		if($sh != '' && $sh == 'sh') {
			$tablename = $table_prefix.'_shippingtaxinfo';
			$value_table=$table_prefix.'_inventoryshippingrel';
		} else {
			$tablename = $table_prefix.'_inventorytaxinfo';
			$value_table=$table_prefix.'_inventoryproductrel';
		}

		if ($mode == 'edit' && $id != '' ) {
			//Getting total no of taxes

			$result_ids=array();
			$result=$adb->pquery("select taxname,taxid from $tablename",array());
			$noofrows=$adb->num_rows($result);

			$inventory_tax_val_result=$adb->pquery("select * from $value_table where id=?",array($id));

			//Finding which taxes are associated with this (SO,PO,Invoice,Quotes) and getting its taxid.
			for ($i=0;$i<$noofrows;$i++) {

				$taxname=$adb->query_result($result,$i,'taxname');
				$taxid=$adb->query_result($result,$i,'taxid');

				$tax_val=$adb->query_result($inventory_tax_val_result,0,$taxname);
				if($tax_val != '') {
					array_push($result_ids,$taxid);
				}

			}
			//We are selecting taxes using that taxids. So It will get the tax even if the tax is disabled.
			$where_ids='';
			if (count($result_ids) > 0) {
				$insert_str = str_repeat("?,", count($result_ids)-1);
				$insert_str .= "?";
				$where_ids="taxid in ($insert_str) or";
			}

			$res = $adb->pquery("select * from $tablename  where $where_ids  deleted=0 order by taxid",$result_ids);

		} else {
			//This where condition is added to get all products or only availble products
			if ($available != 'all' && $available == 'available') {
				$where = " where $tablename.deleted=0";
			}

			$res = $adb->pquery("select * from $tablename $where order by deleted",array());
		}

		$noofrows = $adb->num_rows($res);
		for ($i=0;$i<$noofrows;++$i) {
			$taxtypes[$i]['taxid'] = $adb->query_result($res,$i,'taxid');
			$taxtypes[$i]['taxname'] = $adb->query_result($res,$i,'taxname');
			$taxtypes[$i]['taxlabel'] = $adb->query_result($res,$i,'taxlabel');
			$taxtypes[$i]['percentage'] = floatval($adb->query_result($res,$i,'percentage'));
			$taxtypes[$i]['percentage_fmt'] = $this->formatUserNumber($taxtypes[$i]['percentage']); // crmv@118512
			$taxtypes[$i]['deleted'] = $adb->query_result($res,$i,'deleted');
		}
		$log->debug("Exit from the function getAllTaxes($available,$sh,$mode,$id)");

		return $taxtypes;
	}

	/**	Function used to get all the tax details which are associated to the given product
	 *	@param int $productid - product id to which we want to get all the associated taxes
	 *	@param string $available - available or empty or available_associated where as default is all, if available then the taxes which are available now will be returned, if all then all taxes will be returned otherwise if the value is available_associated then all the associated taxes even they are not available and all the available taxes will be retruned
	 *	@return array $tax_details - tax details as a array with productid, taxid, taxname, percentage and deleted
	 */
	function getTaxDetailsForProduct($productid, $available='all') {
		global $log, $adb, $table_prefix;
		$log->debug("Entering into function getTaxDetailsForProduct($productid)");
		$tax_details = array(); //crmv@115802
		if($productid != '')
		{
			//where condition added to avoid to retrieve the non available taxes
			$where = '';
			if($available != 'all' && $available == 'available')
			{
				$where = ' and '.$table_prefix.'_inventorytaxinfo.deleted=0';
			}
			if($available != 'all' && $available == 'available_associated')
			{
				//crmv@14612
				//			$query = "SELECT vte_producttaxrel.*, vte_inventorytaxinfo.* FROM vte_inventorytaxinfo left JOIN vte_producttaxrel ON vte_inventorytaxinfo.taxid = vte_producttaxrel.taxid WHERE vte_producttaxrel.productid = ? or vte_inventorytaxinfo.deleted=0 GROUP BY vte_inventorytaxinfo.taxid";
				$query = "SELECT ".$table_prefix."_producttaxrel.*, ".$table_prefix."_inventorytaxinfo.* FROM ".$table_prefix."_inventorytaxinfo left JOIN ".$table_prefix."_producttaxrel ON ".$table_prefix."_inventorytaxinfo.taxid = ".$table_prefix."_producttaxrel.taxid WHERE ".$table_prefix."_producttaxrel.productid = ?";
				//crmv@14612 end
			}
			else
			{
				$query = "SELECT ".$table_prefix."_producttaxrel.*, ".$table_prefix."_inventorytaxinfo.* FROM ".$table_prefix."_inventorytaxinfo INNER JOIN ".$table_prefix."_producttaxrel ON ".$table_prefix."_inventorytaxinfo.taxid = ".$table_prefix."_producttaxrel.taxid WHERE ".$table_prefix."_producttaxrel.productid = ? $where";
			}
			$params = array($productid);

			$res = $adb->pquery($query, $params);
			for($i=0;$i<$adb->num_rows($res);$i++)
			{
				$tax_details[$i]['productid'] = $adb->query_result($res,$i,'productid');
				$tax_details[$i]['taxid'] = $adb->query_result($res,$i,'taxid');
				$tax_details[$i]['taxname'] = $adb->query_result($res,$i,'taxname');
				$tax_details[$i]['taxlabel'] = $adb->query_result($res,$i,'taxlabel');
				$tax_details[$i]['percentage'] = floatval($adb->query_result($res,$i,'taxpercentage'));
				$tax_details[$i]['percentage_fmt'] = $this->formatUserNumber($tax_details[$i]['percentage']); // crmv@118512
				$tax_details[$i]['deleted'] = $adb->query_result($res,$i,'deleted');
			}
		}
		else
		{
			$log->debug("Product id is empty. we cannot retrieve the associated products.");
		}

		$log->debug("Exit from function getTaxDetailsForProduct($productid)");
		return $tax_details;
	}

	/**	Function used to delete the Inventory product details for the passed entity
	 *	@param int $objectid - entity id to which we want to delete the product details from REQUEST values where as the entity will be Purchase Order, Sales Order, Quotes or Invoice
	 *	@param string $return_old_values - string which contains the string return_old_values or may be empty, if the string is return_old_values then before delete old values will be retrieved
	 *	@return array $ext_prod_arr - if the second input parameter is 'return_old_values' then the array which contains the productid and quantity which will be retrieved before delete the product details will be returned otherwise return empty
	 */
	function deleteInventoryProductDetails($focus)	{
		global $log, $adb,$updateInventoryProductRel_update_product_array, $table_prefix;
		$log->debug("Entering into function deleteInventoryProductDetails(".$focus->id.").");

		$product_info = $adb->pquery("SELECT productid, quantity, sequence_no, incrementondel from ".$table_prefix."_inventoryproductrel WHERE id=?",array($focus->id));
		$numrows = $adb->num_rows($product_info);
		for($index = 0;$index <$numrows;$index++){
			$productid = $adb->query_result($product_info,$index,'productid');
			$sequence_no = $adb->query_result($product_info,$index,'sequence_no');
			$qty = $adb->query_result($product_info,$index,'quantity');
			$incrementondel = $adb->query_result($product_info,$index,'incrementondel');

			if($incrementondel){
				$focus->update_product_array[$focus->id][$sequence_no][$productid]= $qty;
				$sub_prod_query = $adb->pquery("SELECT productid from ".$table_prefix."_inventorysubproductrel WHERE id=? AND sequence_no=?",array($focus->id,$sequence_no));
				if($adb->num_rows($sub_prod_query)>0){
					for($j=0;$j<$adb->num_rows($sub_prod_query);$j++){
						$sub_prod_id = $adb->query_result($sub_prod_query,$j,"productid");
						$focus->update_product_array[$focus->id][$sequence_no][$sub_prod_id]= $qty;
					}
				}

			}
		}
		$updateInventoryProductRel_update_product_array = $focus->update_product_array;
		$adb->pquery("delete from ".$table_prefix."_inventoryproductrel where id=?", array($focus->id));
		$adb->pquery("delete from ".$table_prefix."_inventorysubproductrel where id=?", array($focus->id));
		$adb->pquery("delete from ".$table_prefix."_inventoryshippingrel where id=?", array($focus->id));
		$adb->pquery("delete from ".$table_prefix."_inventorytotals where id=?", array($focus->id)); // crmv@67929

		$log->debug("Exit from function deleteInventoryProductDetails(".$focus->id.")");
	}

	function updateInventoryProductRel($entity)	{
		global $log, $adb,$updateInventoryProductRel_update_product_array, $table_prefix;
		$entity_id = vtws_getIdComponents($entity->getId());
		$entity_id = $entity_id[1];
		$update_product_array = $updateInventoryProductRel_update_product_array;
		$log->debug("Entering into function updateInventoryProductRel(".$entity_id.").");

		if(!empty($update_product_array)){
			foreach($update_product_array as $id=>$seq){
				foreach($seq as $seq=>$product_info)
				{
					foreach($product_info as $key=>$index){
						$updqtyinstk= $this->getPrdQtyInStck($key);
						$upd_qty = $updqtyinstk+$index;
						updateProductQty($key, $upd_qty);
					}
				}
			}
		}
		$adb->pquery("UPDATE ".$table_prefix."_inventoryproductrel SET incrementondel=1 WHERE id=?",array($entity_id));

		$product_info = $adb->pquery("SELECT productid,sequence_no, quantity from ".$table_prefix."_inventoryproductrel WHERE id=?",array($entity_id));
		$numrows = $adb->num_rows($product_info);
		for($index = 0;$index <$numrows;$index++){
			$productid = $adb->query_result($product_info,$index,'productid');
			$qty = $adb->query_result($product_info,$index,'quantity');
			$sequence_no = $adb->query_result($product_info,$index,'sequence_no');
			$qtyinstk= $this->getPrdQtyInStck($productid);
			$upd_qty = $qtyinstk-$qty;
			updateProductQty($productid, $upd_qty);
			$sub_prod_query = $adb->pquery("SELECT productid from ".$table_prefix."_inventorysubproductrel WHERE id=? AND sequence_no=?",array($entity_id,$sequence_no));
			if($adb->num_rows($sub_prod_query)>0){
				for($j=0;$j<$adb->num_rows($sub_prod_query);$j++){
					$sub_prod_id = $adb->query_result($sub_prod_query,$j,"productid");
					$sqtyinstk= $this->getPrdQtyInStck($sub_prod_id);
					$supd_qty = $sqtyinstk-$qty;
					updateProductQty($sub_prod_id, $supd_qty);
				}
			}
		}

		$log->debug("Exit from function updateInventoryProductRel(".$entity_id.")");
	}

	function calcProductTotals($prodinfo) {
		$workingPrecision = $this->workingPrecision;
		$outputPrecision = $this->outputPrecision;

		$result = array();

		// starting price
		$baseprice = round(floatval($prodinfo['listprice']), $workingPrecision);
		// quantity
		$quantity = round(floatval($prodinfo['quantity']), $workingPrecision);

		// price with quantity
		$price = round($baseprice * $quantity, $workingPrecision);
		$result['price_qty'] = round($price, $outputPrecision);

		// price with discount discount (direct)
		if (!empty($prodinfo['discount_amount'])) {
			$discAmount = round(floatval($prodinfo['discount_amount']), $workingPrecision);
			$result['discounts'] = array();
			$result['discounts'][] = array(
				'percentage' => 0.0,
				'starting_price' => $price,
				'amount' => $discAmount,
				'final_price' => $price-$discAmount,
			);
			$price -= $discAmount;
		}

		// price with discount discount (percent)
		if (!empty($prodinfo['discount_percent'])) {
			// crmv@48527
			$discounts = $this->parseMultiDiscount($prodinfo['discount_percent'], 0, 0); // crmv@48699
			$result['discounts'] = array();
			foreach ($discounts as $d) {
				$disc_perc = $d/100.0;
				$disc_amount = round($price*$disc_perc, $workingPrecision+2); // crmv@193848
				if ($disc_perc) {
					$result['discounts'][] = array(
						'percentage' => $disc_perc*100.0,
						'starting_price' => $price,
						'amount' => $disc_amount,
						'final_price' => $price-$disc_amount,
					);
					$price -= $disc_amount;
				}
			}
			$price = round($price, $workingPrecision); // crmv@193848
			// crmv@48527e
		}

		$result['total_discount'] = $result['price_qty'] - $price;
		$result['price_discount'] = round($price, $outputPrecision);

		// price with taxes (round amount at every round)
		$result['total_taxes'] = 0.0;
		$result['total_taxes_perc'] = 0.0;
		if (!empty($prodinfo['taxes']) && count($prodinfo['taxes']) > 0) {
			$totalTax = $totalTaxPerc = 0.0;
			$result['taxes'] = array();
			foreach ($prodinfo['taxes'] as $taxname => $taxperc) {
				$taxPercRound = round($taxperc, $workingPrecision);
				$totalTaxPerc += $taxPercRound;
				$taxAmount = round($price*$taxPercRound/100, $workingPrecision);
				$totalTax += $taxAmount;
				$result['taxes'][] = array(
					'percentage' => $taxPercRound,
					'amount' => $taxAmount,
				);
			}
//			if ($totalTax > 0) { //crmv@43358
				$price += $totalTax;
				$result['total_taxes'] = $totalTax;
				$result['total_taxes_perc'] = $totalTaxPerc;
//			} //crmv@43358e
		}
		$result['price_taxes'] = round($price, $outputPrecision);

		return $result;
	}

	// calcola i totali, sconti, tasse per un record
	function calcInventoryTotals($totalinfo) {
		$workingPrecision = $this->workingPrecision;
		$outputPrecision = $this->outputPrecision;

		$result = array();
		$total = round($totalinfo['nettotal'], $workingPrecision);
		$result['price_nettotal'] = $total;

		// calc discount
		$result['discounts'] = array();

		// price with discount discount (direct)
		if (!empty($totalinfo['discount_amount']) && floatval($totalinfo['discount_amount']) != 0 ) { //crmv@65329
			$discAmount = round(floatval($totalinfo['discount_amount']), $workingPrecision);
			$result['discounts'][] = array(
				'percentage' => 0.0,
				'starting_price' => $total,
				'amount' => $discAmount,
				'final_price' => $total-$discAmount,
			);
			$total -= $discAmount;
		}
		// price with discount discount (percent)
		if (!empty($totalinfo['discount_percent']) && floatval($totalinfo['discount_percent']) != 0 ) { //crmv@65329
			// crmv@48527
			$discounts = $this->parseMultiDiscount($totalinfo['discount_percent'], 0, 0); // crmv@48699
			foreach ($discounts as $d) {
				$disc_perc = $d/100.0;
				$disc_amount = round($total*$disc_perc, $workingPrecision+2); // crmv@193848
				if ($disc_perc) {
					$result['discounts'][] = array(
						'percentage' => $disc_perc*100.0,
						'starting_price' => $total,
						'amount' => $disc_amount,
						'final_price' => $total-$disc_amount,
					);
					$total -= $disc_amount;
				}
			}
 			$price = round($price, $workingPrecision); // crmv@193848
			// crmv@48527e
		}
		$result['total_discount'] = $result['price_nettotal'] - $total;
		$result['price_discount'] = round($total, $outputPrecision);

		// calc taxes (round amount at every round)
		$result['total_taxes'] = 0.0;
		$result['total_taxes_perc'] = 0.0;
		if (!empty($totalinfo['taxes']) && count($totalinfo['taxes']) > 0) {
			$totalTax = $totalTaxPerc = 0.0;
			$result['taxes'] = array();
			foreach ($totalinfo['taxes'] as $taxname => $taxperc) {
				$taxPercRound = round($taxperc, $workingPrecision);
				$totalTaxPerc += $taxPercRound;
				$taxAmount = round($total*$taxPercRound/100, $workingPrecision);
				$totalTax += $taxAmount;

				$result['taxes'][] = array(
					'percentage' => $taxPercRound,
					'amount' => $taxAmount,
				);
			}

//			if ($totalTax > 0) { //crmv@43358
				$total += $totalTax;
				$result['total_taxes'] = $totalTax;
				$result['total_taxes_perc'] = $totalTaxPerc;
//			} //crmv@43358
		}
		$result['price_taxes'] = round($total, $outputPrecision);

		// calc sh charges
		if (!empty($totalinfo['s_h_amount'])) {
			$total += round(floatval($totalinfo['s_h_amount']), $workingPrecision);
		}
		$result['price_shcharges'] = round($total, $outputPrecision);

		// calc SH taxes (round at every round)
		$result['total_shtaxes'] = 0.0;
		$result['total_shtaxes_perc'] = 0.0;
		if (!empty($totalinfo['shtaxes']) && count($totalinfo['shtaxes']) > 0) {
			$totalTax = $totalTaxPerc = 0.0;
			$result['shtaxes'] = array();
			$shtaxTotal = round(floatval($totalinfo['s_h_amount']), $workingPrecision);
			foreach ($totalinfo['shtaxes'] as $taxname => $taxperc) {
				$taxPercRound = round($taxperc, $workingPrecision);
				$totalTaxPerc += $taxPercRound;
				$taxAmount = round($shtaxTotal*$taxPercRound/100, $workingPrecision);
				$totalTax += $taxAmount;
				$result['shtaxes'][] = array(
					'percentage' => $totalTaxPerc,
					'amount' => $taxAmount,
				);
			}
//			if ($totalTax > 0) { //crmv@43358
				$total += $totalTax;
				$result['total_shtaxes'] = $totalTax;
				$result['total_shtaxes_perc'] = $totalTaxPerc;
//			} //crmv@43358
		}
		$result['price_shtaxes'] = round($total, $outputPrecision);

		// calc adjustment
		if (!empty($totalinfo['adjustment'])) {
			$total += round(floatval($totalinfo['adjustment']), $workingPrecision);
		}
		$result['price_adjustment'] = round($total, $outputPrecision);

		return $result;
	}

	// crmv@67929
	/**
	 * Utility function to create an array from a property in a array of object-like elements
	 */
	static public function arrayPluck(&$array, $keyname) {
		return array_map(function($v) use ($keyname) {
			return $v[$keyname];
		}, $array);
	}
	// crmv@67929e

	/**	Function used to save the Inventory product details for the passed entity
	 *	@param object reference $focus - object reference to which we want to save the product details from REQUEST values where as the entity will be Purchase Order, Sales Order, Quotes or Invoice
	 *	@param string $module - module name
	 *	@param $update_prod_stock - true or false (default), if true we have to update the stock for PO only
	 *	@param $updateDemand - +/-/''(empty) 
	 *	@param $returnTotals - true/false if true skip delete/update/insert into db and return only informations of total
	 *	@return void or total informations if $returnTotals is true
	 */
	//crmv@144872
	function saveInventoryProductDetails(&$focus, $module, $update_prod_stock='false', $updateDemand='', $returnTotals=false) {
		
		if (($override = self::checkOldOverride(__FUNCTION__))) return self::callOldOverride($override, func_get_args()); //crmv@35654
		
		global $log, $adb, $table_prefix;
		$returnTotalsInfo = array();
		$id=$focus->id;
		$log->debug("Entering into function saveInventoryProductDetails($module).");
		//Added to get the convertid
		if(isset($_REQUEST['convert_from']) && $_REQUEST['convert_from'] !='')
		{
			$id = $_REQUEST['return_id'];
		}
		elseif(isset($_REQUEST['duplicate_from']) && $_REQUEST['duplicate_from'] !='')
		{
			$id = $_REQUEST['duplicate_from'];
		}
		
		$ext_prod_arr = Array();
		if ($focus->mode == 'edit') {
			$return_old_values = '';
			if ($module != 'PurchaseOrder') {
				$return_old_values = 'return_old_values';
			}
			
			//we will retrieve the existing product details and store it in a array and then delete all the existing product details and save new values, retrieve the old value and update stock only for SO, Quotes and Invoice not for PO
			//$ext_prod_arr = deleteInventoryProductDetails($focus->id,$return_old_values);
			if (!$returnTotals) $this->deleteInventoryProductDetails($focus);
		}
		$tot_no_prod = $_REQUEST['totalProductCount'];
		$prod_seq=1;
		$prodTotal = 0.0;
		$allTaxes = array(); //crmv@67929
		
		for ($i=1; $i<=$tot_no_prod; ++$i) {
			//if the product is deleted then we should avoid saving the deleted products
			if($_REQUEST["deleted".$i] == 1)
				continue;

			$prod_id = $_REQUEST['hdnProductId'.$i];
			if(isset($_REQUEST['productDescription'.$i]))
				$description = $_REQUEST['productDescription'.$i];
				
			$comment = $_REQUEST['comment'.$i];
			$qty = $this->parseUserNumber($_REQUEST['qty'.$i]);
			$listprice = $this->parseUserNumber($_REQUEST['listPrice'.$i]);
			
			
			//we have to update the Product stock for PurchaseOrder if $update_prod_stock is true
			if ($module == 'PurchaseOrder' && $update_prod_stock == 'true')	{
				addToProductStock($prod_id,$qty);
			} elseif ($module == 'SalesOrder')	{
				if ($updateDemand == '-') {
					deductFromProductDemand($prod_id, $qty);
				} elseif($updateDemand == '+') {
					addToProductDemand($prod_id, $qty);
				}
			}
			
			$linetotal = floatval($_REQUEST['netPriceInput'.$i]);
			if (!$returnTotals) $lineitem_id = $adb->getUniqueID($table_prefix.'_inventoryproductrel');
			
			$prodinfo = array(
					'listprice' => $listprice,
					'quantity' => $qty,
					'discount_percent' => null,
					'discount_amount' => null,
					'taxes' => array(),
			);
			
			// build the base query
			$columns = array("lineitem_id","id", "productid", 'relmodule', "sequence_no", "quantity", "listprice", "comment", "description");
			$qparams = array($lineitem_id,$focus->id,$prod_id,$module, $prod_seq,$qty,$listprice,$comment,$description);

			// set discounts
			if ($_REQUEST['discount_type'.$i] == 'percentage') {
				$columns[] = 'discount_percent';
				// crmv@48527
				$discountDb = $this->parseMultiDiscount($_REQUEST['discount_percentage'.$i], 1, 0);
				$discountDb = $this->joinMultiDiscount($discountDb, 0, 0);
				$qparams[] = $prodinfo['discount_percent'] = $discountDb; // crmv@48699
				// crmv@48527e
			} elseif ($_REQUEST['discount_type'.$i] == 'amount') {
				$columns[] = 'discount_amount';
				$qparams[] = $prodinfo['discount_amount'] = $this->parseUserNumber($_REQUEST['discount_amount'.$i]);
			}
			
			// set taxes
			if ($_REQUEST['taxtype'] == 'group') {
				$all_available_taxes = $this->getAllTaxes('available','','edit',$id);
				for($tax_count=0; $tax_count<count($all_available_taxes); ++$tax_count) {
					$tax_name = $all_available_taxes[$tax_count]['taxname'];
					$tax_val = $all_available_taxes[$tax_count]['percentage'];
					$request_tax_name = $tax_name."_group_percentage";
					if (isset($_REQUEST[$request_tax_name])) {
						$tax_val = $this->parseUserNumber($_REQUEST[$request_tax_name]);
						// do not set taxes for calculations
						$columns[] = $tax_name;
						$qparams[] = $tax_val;
					}
				}
				
			} else {
				$taxes_for_product = $this->getTaxDetailsForProduct($prod_id,'all');
				for ($tax_count=0; $tax_count<count($taxes_for_product); ++$tax_count) {
					$tax_name = $taxes_for_product[$tax_count]['taxname'];
					$request_tax_name = $tax_name."_percentage".$i;
					$tax_val = $this->parseUserNumber($_REQUEST[$request_tax_name]);
					$prodinfo['taxes'][$tax_name] = $tax_val;
					$columns[] = $tax_name;
					$qparams[] = $tax_val;
				}
			}
			
			// and add the calculated fields
			
			$prodPrices = $this->calcProductTotals($prodinfo);
			if (is_array($prodPrices)) {
				if (array_key_exists('price_taxes', $prodPrices)) {
					$columns[] = 'linetotal';
					$qparams[] = $prodPrices['price_taxes'];
				}
				
				if (array_key_exists('price_discount', $prodPrices)) {
					$columns[] = 'total_notaxes';
					$qparams[] = $prodPrices['price_discount'];
				}
				
				//crmv@67929
				// add the tax totals
				if (is_array($prodinfo['taxes']) && is_array($prodPrices['taxes'])) {
					if ($_REQUEST['taxtype'] != 'group') {
						$prodTaxes = array_combine(array_keys($prodinfo['taxes']), self::arrayPluck($prodPrices['taxes'], 'amount'));
						$allTaxes['tax_total'] += array_sum($prodTaxes); // crmv@69568
						// do the update for the row
						$columns[] = 'tax_total';
						$qparams[] = array_sum($prodTaxes);
						
						// calculate the totals
						foreach ($prodTaxes as $taxname => $tax) {
							$allTaxes[$taxname] += floatval($tax);
						}
					}
				}
				//crmv@67929e
				
			}
			
			// update the total price
			$prodTotal += $prodPrices['price_taxes'];
			
			// insert the product
			if (!$returnTotals) {
				$adb->format_columns($columns);
				$query = "insert into ".$table_prefix."_inventoryproductrel (". implode(",",$columns) .") values(".generateQuestionMarks($columns).")";
				
				$adb->pquery($query,$qparams);
				
				// insert sub-product
				$sub_prod_str = $_REQUEST['subproduct_ids'.$i];
				if (!empty($sub_prod_str)) {
					$sub_prod = explode(":",$sub_prod_str);
					for($j=0;$j<count($sub_prod);$j++){
						$query ="insert into ".$table_prefix."_inventorysubproductrel(id, sequence_no, productid) values(?,?,?)";
						$qparams = array($focus->id,$prod_seq,$sub_prod[$j]);
						$adb->pquery($query,$qparams);
					}
				}
				$prod_seq++;
				
				if ($module != 'PurchaseOrder')	{
					//update the stock with existing details
					$this->updateStk($prod_id,$qty,$focus->mode,$ext_prod_arr,$module);
				}
			}
		}
		
		
		// calculate the totals, don't get the from request
		$totalinfo = array(
				'nettotal' => $prodTotal,
				's_h_amount' => 0.0,
				'discount_percent' => null,
				'discount_amount' => null,
				'adjustment' => 0.0,
				'taxes' => array(),
				'shtaxes' => array(),
		);
		
		$updatequery  = "update {$focus->table_name} set ";
		$updateparams = $updatequeryList = array();
		
		//for discount percentage or discount amount
		if ($_REQUEST['discount_type_final'] == 'percentage') {
			$updatequeryList[] = "discount_percent=?";
			$updatequeryList[] = "discount_amount=0";
			// crmv@48527
			$discountDb = $this->parseMultiDiscount($_REQUEST['discount_percentage_final'], 1, 0);
			$discountDb = $this->joinMultiDiscount($discountDb, 0, 0);
			$totalinfo['discount_percent'] = $discountDb;
			array_push($updateparams, $discountDb);
			// crmv@48527e
			$returnTotalsInfo['hdnDiscountPercent'] = $totalinfo['discount_percent'];
			$returnTotalsInfo['hdnDiscountAmount'] = 0;
			
		} elseif ($_REQUEST['discount_type_final'] == 'amount') {
			$updatequeryList[] = "discount_amount=?";
			$updatequeryList[] = "discount_percent=0";
			$totalinfo['discount_amount'] = $this->parseUserNumber($_REQUEST['discount_amount_final']);
			array_push($updateparams, $totalinfo['discount_amount']);
			$returnTotalsInfo['hdnDiscountPercent'] = $totalinfo['discount_amount'];
			$returnTotalsInfo['hdnDiscountAmount'] = 0;
		// crmv@185582
		} else {
			$updatequeryList[] = "discount_amount=0";
			$updatequeryList[] = "discount_percent=0";
		}
		// crmv@185582e
		
		$updatequeryList[] = "s_h_amount=?";
		$totalinfo['s_h_amount'] = $this->parseUserNumber($_REQUEST['shipping_handling_charge']);
		array_push($updateparams, $totalinfo['s_h_amount']);
		$returnTotalsInfo['hdnS_H_Amount'] = $totalinfo['s_h_amount'];
		
		// taxtype
		$updatequeryList[] = "taxtype=?";
		array_push($updateparams, $_REQUEST['taxtype']);
		$returnTotalsInfo['hdnTaxType'] = $_REQUEST['taxtype'];
		
		// taxes
		if ($_REQUEST['taxtype'] == 'group') {
			$tax_details = $this->getAllTaxes('available','','edit',$focus->id);
			foreach ($tax_details as $taxinfo) {
				$tax_name = $taxinfo['taxname'];
				$totalinfo['taxes'][$tax_name] = $this->parseUserNumber($_REQUEST[$tax_name.'_group_percentage']);
			}
		}
		
		// sh taxes
		//to save the S&H tax details in vte_inventoryshippingrel table
		$sh_tax_details = $this->getAllTaxes('all','sh');
		$sh_query_fields = array("id");
		$sh_query_values = array($focus->id);
		for ($i=0; $i<count($sh_tax_details); ++$i) {
			$tax_name = $sh_tax_details[$i]['taxname']."_sh_percent";
			if ($_REQUEST[$tax_name] != '') {
				$sh_query_fields[] = $sh_tax_details[$i]['taxname'];
				$sh_query_values[] = $this->parseUserNumber($_REQUEST[$tax_name]);
				$totalinfo['shtaxes'][$sh_tax_details[$i]['taxname']] = $this->parseUserNumber($_REQUEST[$tax_name]);
			}
		}
		
		// adjustment
		$adjustment = $this->parseUserNumber($_REQUEST['adjustment']);
		if (empty($adjustment)) {
			$adjustment = null;
		} else {
			$adjustment = floatval($_REQUEST['adjustmentType'] . $adjustment);
		}
		$totalinfo['adjustment'] = $adjustment;
		$updatequeryList[] = "adjustment=?";
		array_push($updateparams, $adjustment);
		$returnTotalsInfo['txtAdjustment'] = $totalinfo['adjustment'];
		
		// calc totals
		$totalPrices = $this->calcInventoryTotals($totalinfo);
		
		// total
		$updatequeryList[] = "total=?";
		array_push($updateparams, $totalPrices['price_adjustment']);
		$returnTotalsInfo['hdnGrandTotal'] = $totalPrices['price_adjustment'];
		
		// subtotal
		$updatequeryList[] = "subtotal=?";
		array_push($updateparams, $prodTotal);
		$returnTotalsInfo['hdnSubTotal'] = $prodTotal;
		
		if ($returnTotals) {
			$log->debug("Exit from function saveInventoryProductDetails($module).");
			return $returnTotalsInfo;
		}
		
		// finalize query
		$updatequery .= implode(',', $updatequeryList) . " where {$focus->table_index} = ?";
		array_push($updateparams, $focus->id);
		
		// execute it
		$res = $adb->pquery($updatequery,$updateparams);
		
		// execute query for sh taxes
		$res = $adb->pquery("SELECT id FROM {$table_prefix}_inventoryshippingrel WHERE id = ?", array($focus->id));
		if ($res && $adb->num_rows($res) == 0) {
			$sh_query = "insert into {$table_prefix}_inventoryshippingrel (".implode(',',$sh_query_fields).") values (".generateQuestionMarks($sh_query_values).")";
			$adb->pquery($sh_query, $sh_query_values);
		}
		
		//crmv@67929
		// sum the taxes
		if ($_REQUEST['taxtype'] == 'group' && is_array($totalPrices['taxes'])) {
			$totTax = array_combine(array_keys($totalinfo['taxes']), self::arrayPluck($totalPrices['taxes'], 'amount'));
			// calculate the totals
			foreach ($totTax as $taxname => $tax) {
				$allTaxes[$taxname] += floatval($tax);
			}
			$allTaxes['tax_total'] = array_sum($totTax);
		}
		
		// sum the S&H taxes
		if (is_array($totalPrices['shtaxes'])) {
			$totTax = array_combine(array_keys($totalinfo['shtaxes']), self::arrayPluck($totalPrices['shtaxes'], 'amount'));
			// calculate the totals
			foreach ($totTax as $shtaxname => $tax) {
				$allTaxes[$shtaxname] += floatval($tax);
			}
			$allTaxes['shtax_total'] = array_sum($totTax);
		}
		
		// insert values for taxes totals
		if (is_array($allTaxes) && count($allTaxes) > 0) {
			$columns = array_keys($allTaxes);
			$adb->pquery("INSERT INTO {$table_prefix}_inventorytotals (id, ".implode(',', $columns).") VALUES (?, ".generateQuestionMarks($allTaxes).")", array($focus->id, $allTaxes));
		}
		//crmv@67929e
		
		$log->debug("Exit from function saveInventoryProductDetails($module).");
	}
	//crmv@144872e

	/**	function used to get the tax type for the entity (PO, SO, Quotes or Invoice)
	 *	@param string $module - module name
	 *	@param int $id - id of the PO or SO or Quotes or Invoice
	 *	@return string $taxtype - taxtype for the given entity which will be individual or group
	 */
	function getInventoryTaxType($module, $id) {
		global $log, $adb;

		$log->debug("Entering into function getInventoryTaxType($module, $id).");

		//crmv@18498
		$focus = CRMEntity::getInstance($module);
		$res = $adb->pquery("select taxtype from {$focus->table_name} where {$focus->table_index} = ?", array($id));
		//crmv@18498e
		$taxtype = $adb->query_result($res,0,'taxtype');

		$log->debug("Exit from function getInventoryTaxType($module, $id).");

		return $taxtype;
	}

	/**	function used to get the price type for the entity (PO, SO, Quotes or Invoice)
	 *	@param string $module - module name
	 *	@param int $id - id of the PO or SO or Quotes or Invoice
	 *	@return string $pricetype - pricetype for the given entity which will be unitprice or secondprice
	 */
	function getInventoryCurrencyInfo($module, $id) {
		global $log, $adb, $table_prefix;

		$log->debug("Entering into function getInventoryCurrencyInfo($module, $id).");

		//crmv@18498
		$focus = CRMEntity::getInstance($module);
		//crmv@60012
		$res = $adb->pquery("select currency_id, ".$table_prefix."_currency_info.conversion_rate as conv_rate, ".$table_prefix."_currency_info.* from $focus->table_name
			inner join ".$table_prefix."_currency_info on $focus->table_name.currency_id = ".$table_prefix."_currency_info.id
			where $focus->table_index = ?", array($id));
		//crmv@60012
			//crmv@18498e
		$currency_info = array();
		$currency_info['currency_id'] = $adb->query_result($res,0,'currency_id');
		$currency_info['conversion_rate'] = $adb->query_result($res,0,'conv_rate');
		$currency_info['currency_name'] = $adb->query_result($res,0,'currency_name');
		$currency_info['currency_code'] = $adb->query_result($res,0,'currency_code');
		$currency_info['currency_symbol'] = $adb->query_result($res,0,'currency_symbol');

		$log->debug("Exit from function getInventoryCurrencyInfo($module, $id).");

		return $currency_info;
	}

	/**	function used to get the taxvalue which is associated with a product for PO/SO/Quotes or Invoice
	 *	@param int $id - id of PO/SO/Quotes or Invoice
	 *	@param int $productid - product id
	 *	@param string $taxname - taxname to which we want the value
	 *	@return float $taxvalue - tax value
	 */
	function getInventoryProductTaxValue($id, $productid, $taxname)	{
		global $log, $adb, $table_prefix;
		$log->debug("Entering into function getInventoryProductTaxValue($id, $productid, $taxname).");

		$res = $adb->pquery("select $taxname from ".$table_prefix."_inventoryproductrel where id = ? and productid = ?", array($id, $productid));
		$taxvalue = $adb->query_result($res,0,$taxname);

		if($taxvalue == '')
			$taxvalue = '0.00';

		$log->debug("Exit from function getInventoryProductTaxValue($id, $productid, $taxname).");

		return $taxvalue;
	}

	/**	function used to get the shipping & handling tax percentage for the given inventory id and taxname
	 *	@param int $id - entity id which will be PO/SO/Quotes or Invoice id
	 *	@param string $taxname - shipping and handling taxname
	 *	@return float $taxpercentage - shipping and handling taxpercentage which is associated with the given entity
	 */
	function getInventorySHTaxPercent($id, $taxname) {
		global $log, $adb, $table_prefix;
		$log->debug("Entering into function getInventorySHTaxPercent($id, $taxname)");

		$res = $adb->pquery("select $taxname from ".$table_prefix."_inventoryshippingrel where id= ?", array($id));
		$taxpercentage = floatval($adb->query_result($res,0,$taxname));

		$log->debug("Exit from function getInventorySHTaxPercent($id, $taxname)");

		return $taxpercentage;
	}

	/**	Function used to get the list of all Currencies as a array
	 *  @param string available - if 'all' returns all the currencies, default value 'available' returns only the currencies which are available for use.
	 *	return array $currency_details - return details of all the currencies as a array
	 */
	function getAllCurrencies($available='available') {
		global $adb, $log, $table_prefix;
		$log->debug("Entering into function getAllCurrencies($available)");

		$sql = "select * from ".$table_prefix."_currency_info";
		if ($available != 'all') {
			$sql .= " where currency_status='Active' and deleted=0";
		}
		$res=$adb->pquery($sql, array());
		$noofrows = $adb->num_rows($res);

		for($i=0;$i<$noofrows;$i++)
		{
			$currency_details[$i]['currencylabel'] = $adb->query_result($res,$i,'currency_name');
			$currency_details[$i]['currencycode'] = $adb->query_result($res,$i,'currency_code');
			$currency_details[$i]['currencysymbol'] = $adb->query_result($res,$i,'currency_symbol');
			$currency_details[$i]['curid'] = $adb->query_result($res,$i,'id');
			$currency_details[$i]['conversionrate'] = $adb->query_result($res,$i,'conversion_rate');
			$currency_details[$i]['curname'] = 'curname' . $adb->query_result($res,$i,'id');
		}

		$log->debug("Entering into function getAllCurrencies($available)");
		return $currency_details;
	}

	/**	Function used to get all the price details for different currencies which are associated to the given product
	 *	@param int $productid - product id to which we want to get all the associated prices
	 *  @param decimal $unit_price - Unit price of the product
	 *  @param string $available - available or available_associated where as default is available, if available then the prices in the currencies which are available now will be returned, otherwise if the value is available_associated then prices of all the associated currencies will be retruned
	 *	@return array $price_details - price details as a array with productid, curid, curname
	 */
	function getPriceDetailsForProduct($productid, $unit_price, $available='available', $itemtype='Products')
	{
		global $log, $adb, $table_prefix;
		$log->debug("Entering into function getPriceDetailsForProduct($productid)");
		if($productid != '')
		{
			$product_currency_id = $this->getProductBaseCurrency($productid, $itemtype);
			$product_base_conv_rate = $this->getBaseConversionRateForProduct($productid,'edit',$itemtype);
			// Detail View
			if ($available == 'available_associated') {
				$query = "select ".$table_prefix."_currency_info.*, ".$table_prefix."_productcurrencyrel.converted_price, ".$table_prefix."_productcurrencyrel.actual_price
				from ".$table_prefix."_currency_info
				inner join ".$table_prefix."_productcurrencyrel on ".$table_prefix."_currency_info.id = ".$table_prefix."_productcurrencyrel.currencyid
				where ".$table_prefix."_currency_info.currency_status = 'Active' and ".$table_prefix."_currency_info.deleted=0
				and ".$table_prefix."_productcurrencyrel.productid = ? and ".$table_prefix."_currency_info.id != ?";
				$params = array($productid, $product_currency_id);
			} else { // Edit View
				$query = "select ".$table_prefix."_currency_info.*, ".$table_prefix."_productcurrencyrel.converted_price, ".$table_prefix."_productcurrencyrel.actual_price
				from ".$table_prefix."_currency_info
				left join ".$table_prefix."_productcurrencyrel
				on ".$table_prefix."_currency_info.id = ".$table_prefix."_productcurrencyrel.currencyid and ".$table_prefix."_productcurrencyrel.productid = ?
				where ".$table_prefix."_currency_info.currency_status = 'Active' and ".$table_prefix."_currency_info.deleted=0";
				$params = array($productid);
			}

			$res = $adb->pquery($query, $params);
			for($i=0;$i<$adb->num_rows($res);$i++)
			{
				$price_details[$i]['productid'] = $productid;
				$price_details[$i]['currencylabel'] = $adb->query_result($res,$i,'currency_name');
				$price_details[$i]['currencycode'] = $adb->query_result($res,$i,'currency_code');
				$price_details[$i]['currencysymbol'] = $adb->query_result($res,$i,'currency_symbol');
				$currency_id = $adb->query_result($res,$i,'id');
				$price_details[$i]['curid'] = $currency_id;
				$price_details[$i]['curname'] = 'curname' . $adb->query_result($res,$i,'id');
				$cur_value = $adb->query_result($res,$i,'actual_price');

				// Get the conversion rate for the given currency, get the conversion rate of the product currency to base currency.
				// Both together will be the actual conversion rate for the given currency.
				$conversion_rate = $adb->query_result($res,$i,'conversion_rate');
				$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;

				if ($cur_value == null || $cur_value == '') {
					$price_details[$i]['check_value'] = false;
					if	($unit_price != null) {
						$cur_value = convertFromMasterCurrency($unit_price, $actual_conversion_rate);
					} else {
						$cur_value = '0';
					}
				} else {
					$price_details[$i]['check_value'] = true;
				}
				$price_details[$i]['curvalue'] = $cur_value;
				$price_details[$i]['curvalue_display'] = $this->formatUserNumber($cur_value); //crmv@98748
				$price_details[$i]['conversionrate'] = $actual_conversion_rate;

				$is_basecurrency = false;
				if ($currency_id == $product_currency_id) {
					$is_basecurrency = true;
				}
				$price_details[$i]['is_basecurrency'] = $is_basecurrency;
			}
		}
		else
		{
			if($available == 'available') { // Create View
				global $current_user;

				$user_currency_id = fetchCurrency($current_user->id);

				$query = "select ".$table_prefix."_currency_info.* from ".$table_prefix."_currency_info
				where ".$table_prefix."_currency_info.currency_status = 'Active' and ".$table_prefix."_currency_info.deleted=0";
				$params = array();

				$res = $adb->pquery($query, $params);
				for($i=0;$i<$adb->num_rows($res);$i++)
				{
					$price_details[$i]['currencylabel'] = $adb->query_result($res,$i,'currency_name');
					$price_details[$i]['currencycode'] = $adb->query_result($res,$i,'currency_code');
					$price_details[$i]['currencysymbol'] = $adb->query_result($res,$i,'currency_symbol');
					$currency_id = $adb->query_result($res,$i,'id');
					$price_details[$i]['curid'] = $currency_id;
					$price_details[$i]['curname'] = 'curname' . $adb->query_result($res,$i,'id');

					// Get the conversion rate for the given currency, get the conversion rate of the product currency(logged in user's currency) to base currency.
					// Both together will be the actual conversion rate for the given currency.
					$conversion_rate = $adb->query_result($res,$i,'conversion_rate');
					$user_cursym_convrate = getCurrencySymbolandCRate($user_currency_id);
					$product_base_conv_rate = 1 / $user_cursym_convrate['rate'];
					$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;

					$price_details[$i]['check_value'] = false;
					$price_details[$i]['curvalue'] = '0';
					$price_details[$i]['curvalue_display'] = $this->formatUserNumber(0); // crmv@98748
					$price_details[$i]['conversionrate'] = $actual_conversion_rate;

					$is_basecurrency = false;
					if ($currency_id == $user_currency_id) {
						$is_basecurrency = true;
					}
					$price_details[$i]['is_basecurrency'] = $is_basecurrency;
				}
			} else {
				$log->debug("Product id is empty. we cannot retrieve the associated prices.");
			}
		}

		$log->debug("Exit from function getPriceDetailsForProduct($productid)");
		return $price_details;
	}

	/**	Function used to get the base currency used for the given Product
	 *	@param int $productid - product id for which we want to get the id of the base currency
	 *  @return int $currencyid - id of the base currency for the given product
	 */
	function getProductBaseCurrency($productid,$module='Products') {
		global $adb, $log, $table_prefix;
		if ($module == 'Services') {
			$sql = "select currency_id from ".$table_prefix."_service where serviceid=?";
		} else {
			$sql = "select currency_id from ".$table_prefix."_products where productid=?";
		}
		$params = array($productid);
		$res = $adb->pquery($sql, $params);
		$currencyid = $adb->query_result($res, 0, 'currency_id');
		return $currencyid;
	}

	/**	Function used to get the conversion rate for the product base currency with respect to the CRM base currency
	 *	@param int $productid - product id for which we want to get the conversion rate of the base currency
	 *  @param string $mode - Mode in which the function is called
	 *  @return number $conversion_rate - conversion rate of the base currency for the given product based on the CRM base currency
	 */
	function getBaseConversionRateForProduct($productid, $mode='edit', $module='Products') {
		global $adb, $log, $current_user, $table_prefix;

		if ($mode == 'edit') {
			if ($module == 'Services') {
				$sql = "select conversion_rate from ".$table_prefix."_service inner join ".$table_prefix."_currency_info
				on ".$table_prefix."_service.currency_id = ".$table_prefix."_currency_info.id where ".$table_prefix."_service.serviceid=?";
			} else {
				$sql = "select conversion_rate from ".$table_prefix."_products inner join ".$table_prefix."_currency_info
				on ".$table_prefix."_products.currency_id = ".$table_prefix."_currency_info.id where ".$table_prefix."_products.productid=?";
			}
			$params = array($productid);
		} else {
			$sql = "select conversion_rate from ".$table_prefix."_currency_info where id=?";
			$params = array(fetchCurrency($current_user->id));
		}

		$res = $adb->pquery($sql, $params);
		$conv_rate = $adb->query_result($res, 0, 'conversion_rate');

		return 1 / $conv_rate;
	}

	/**	Function used to get the prices for the given list of products based in the specified currency
	 *	@param int $currencyid - currency id based on which the prices have to be provided
	 *	@param array $product_ids - List of product id's for which we want to get the price based on given currency
	 *  @return array $prices_list - List of prices for the given list of products based on the given currency in the form of 'product id' mapped to 'price value'
	 */
	function getPricesForProducts($currencyid, $product_ids, $module='Products') {
		global $adb,$log,$current_user, $table_prefix;

		$price_list = array();
		if (count($product_ids) > 0) {
			if ($module == 'Services') {
				$query = "SELECT ".$table_prefix."_currency_info.id, ".$table_prefix."_currency_info.conversion_rate, " .
					$table_prefix."_service.serviceid AS productid, ".$table_prefix."_service.unit_price, " .
					$table_prefix."_productcurrencyrel.actual_price " .
					"FROM ".$table_prefix."_service " .
					"left join ".$table_prefix."_productcurrencyrel on ".$table_prefix."_service.serviceid = ".$table_prefix."_productcurrencyrel.productid " .
					"inner join ".$table_prefix."_currency_info on ".$table_prefix."_currency_info.id = ".$table_prefix."_productcurrencyrel.currencyid " .
					"where ".$table_prefix."_service.serviceid in (". generateQuestionMarks($product_ids) .") and ".$table_prefix."_currency_info.id = ?";
			} else {
				$query = "SELECT ".$table_prefix."_currency_info.id, ".$table_prefix."_currency_info.conversion_rate, " .
					$table_prefix."_products.productid, ".$table_prefix."_products.unit_price, " .
					$table_prefix."_productcurrencyrel.actual_price " .
					"FROM ".$table_prefix."_products " .
					"left join ".$table_prefix."_productcurrencyrel on ".$table_prefix."_products.productid = ".$table_prefix."_productcurrencyrel.productid " .
					"inner join ".$table_prefix."_currency_info on ".$table_prefix."_currency_info.id = ".$table_prefix."_productcurrencyrel.currencyid " .
					"where ".$table_prefix."_products.productid in (". generateQuestionMarks($product_ids) .") and ".$table_prefix."_currency_info.id = ?";
			}
			$params = array($product_ids, $currencyid);
			$result = $adb->pquery($query, $params);

			for($i=0;$i<$adb->num_rows($result);$i++)
			{
				$product_id = $adb->query_result($result, $i, 'productid');
				if(getFieldVisibilityPermission($module,$current_user->id,'unit_price') == '0') {
					$actual_price = $adb->query_result($result, $i, 'actual_price');

					if ($actual_price == null || $actual_price == '') {
						$unit_price = $adb->query_result($result, $i, 'unit_price');
						$product_conv_rate = $adb->query_result($result, $i, 'conversion_rate');
						$product_base_conv_rate = $this->getBaseConversionRateForProduct($product_id,'edit',$module);
						$conversion_rate = $product_conv_rate * $product_base_conv_rate;

						$actual_price = $unit_price * $conversion_rate;
					}
					$price_list[$product_id] = floatval($actual_price);
				} else {
					$price_list[$product_id] = '';
				}
			}
		}
		return $price_list;
	}

	/**	Function used to get the currency used for the given Price book
	 *	@param int $pricebook_id - pricebook id for which we want to get the id of the currency used
	 *  @return int $currencyid - id of the currency used for the given pricebook
	 */
	function getPriceBookCurrency($pricebook_id) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select currency_id from ".$table_prefix."_pricebook where pricebookid=?", array($pricebook_id));
		$currency_id = $adb->query_result($result,0,'currency_id');
		return $currency_id;
	}
	
	// crmv@104568
	function getInventoryBlockInfo($module) {
		global $adb, $table_prefix;
		
		$binfo = false;
		$tabid = getTabid($module);
		$res = $adb->pquery("SELECT blockid, panelid FROM {$table_prefix}_blocks WHERE tabid = ? AND blocklabel = ?", array($tabid, 'LBL_RELATED_PRODUCTS'));
		if ($res && $adb->num_rows($res) > 0) {
			$binfo = $adb->fetchByAssoc($res, -1, false);
		}
		
		return $binfo;
	}
	// crmv@104568e

	/** This function returns a HTML output of associated vte_products for a given entity (Quotes,Invoice,Sales order or Purchase order)
	 * Param $module - module name
	 * Param $focus - module object
	 * Return type string
	 */
	function getDetailAssociatedProducts($module,$focus) {

		if (($override = self::checkOldOverride(__FUNCTION__))) return self::callOldOverride($override, func_get_args()); //crmv@35654

		global $log,$adb,$table_prefix;
		global $mod_strings, $app_strings, $current_user, $default_charset; //crmv@16267
		global $theme;

		$log->debug("Entering getDetailAssociatedProducts(".$module.",focus) method ...");

		$theme_path = "themes/".$theme."/";
		$image_path = $theme_path."images/";

		$smarty = new VteSmarty();
		$smarty->assign("MOD", $mod_strings);
		$smarty->assign("APP", $app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH", $image_path);
		$smarty->assign("MODULE", $module);

		$smarty->assign("COLSPAN", ($module == 'PurchaseOrder' ? 1 : 2));
		
		// crmv@195745
		$data = $this->getDetailAssociatedProductsArray($module, $focus);

		$taxtype = $data['taxtype'];
		$currencytype = $data['currencytype'];
		// crmv@195745e

		$smarty->assign('ID', $focus->id);
		$smarty->assign('ACCOUNTID', $focus->column_fields['account_id']);
		$smarty->assign('CURRENCY_NAME', $currencytype['currency_name']);
		$smarty->assign('CURRENCY_SYMBOL', $currencytype['currency_symbol']);
		$smarty->assign('TAXTYPE', $taxtype);
		
		// crmv@104568
		$panelid = getCurrentPanelId($module);
		$smarty->assign("PANELID", $panelid);
		$binfo = $this->getInventoryBlockInfo($module);
		$smarty->assign('PRODBLOCKINFO', $binfo);
		// crmv@104568e

		// crmv@195745
		$smarty->assign('PRODUCT_DETAILS', $data['products']);
		$smarty->assign('FINAL_DETAILS', $data['final_details']);
		// crmv@195745e

		// generate html
		$output = $smarty->fetch('Inventory/ProductDetailsDetailView.tpl');
		return $output;
	}
	
	// crmv@195745
	public function getDetailAssociatedProductsArray($module,$focus, $cleanKeys = false) {
		
		$prodDetails = $this->getAssociatedProducts($module, $focus);
		
		if ($cleanKeys) {
			// remove the id from the keys
			foreach ($prodDetails as $id => $prodData) {
				if (is_array($prodData)) {
					$newData = array();
					foreach ($prodData as $pkey => $pvalue) {
						$newData[preg_replace("/{$id}$/", '', $pkey)] = $pvalue;
					}
					$prodDetails[$id] = $newData;
				}
			}
		}
		
		$finalDetails = $this->getFinalDetails($module, $focus);
		
		$result = array(
			'taxtype' => $this->getInventoryTaxType($module,$focus->id),
			'currencytype' => $this->getInventoryCurrencyInfo($module, $focus->id),
			'products' => $prodDetails,
			'final_details' => $finalDetails[1]['final_details'],
		);

		return $result;
	}
	
	/*
	 * Put all the data retrieved with the getDetailAssociatedProductsArray in the request, 
	 * so then you can call Quotes::save_module to trigger the save
	 */
	public function populateRequestFromData($module, $data, $focus) {

		// set the fields for the record
		$_REQUEST = $focus->column_fields ?: array();
		
		// remove some things
		unset($_REQUEST['modifiedtime']);
		unset($_REQUEST['createdtime']);
		unset($_REQUEST['currency_id']);
		unset($_REQUEST['conversion_rate']);
		unset($_REQUEST['description']);
		unset($_REQUEST['hdnDiscountAmount']);
		unset($_REQUEST['hdnDiscountPercent']);
		unset($_REQUEST['hdnGrandTotal']);
		unset($_REQUEST['hdnS_H_Amount']);
		unset($_REQUEST['hdnSubTotal']);
		unset($_REQUEST['txtAdjustment']);
		
		// basic values
		$_REQUEST['module'] = $module;
		$_REQUEST['record'] = $focus->id;
		$_REQUEST['action'] = 'Save';
		$_REQUEST['button'] = 'Save';
		$_REQUEST['mode'] = 'edit';
		$_REQUEST['parenttab'] = 'Sales';
		$_REQUEST['isDuplicate'] = false;
		
		// other values
		$_REQUEST['taxtype'] = $data['taxtype'] ?: 'individual';
		$_REQUEST['currency'] = $data['currencytype']['currency_id'] ?: 1;
		$_REQUEST['inventory_currency'] = $data['currencytype']['currency_id'] ?: 1;
			
		// products
		$_REQUEST['totalProductCount'] = count($data['products']) ?: 0;
		foreach ($data['products'] as $id => $product) {
			$_REQUEST['deleted'.$id] = 0;
			$_REQUEST['productName'.$id] = $product['productName'];
			$_REQUEST['hdnProductId'.$id] = $product['hdnProductId'];
			$_REQUEST['unit_cost'.$id] = $product['unit_cost'];
			$_REQUEST['hdnProductcode'.$id] = $product['hdnProductcode'];
			$_REQUEST['productDescription'.$id] = $product['productDescription'];
			$_REQUEST['qty'.$id] = $this->formatUserNumber($product['qty']);
			$_REQUEST['listPrice'.$id] = $this->formatUserNumber($product['listPrice']);
			$_REQUEST['discount_type'.$id] = $product['discount_type'];
			$_REQUEST['discount'.$id] = 'on';
			$_REQUEST['discount_percentage'.$id] = ($product['discount_type'] == 'percentage' ? $product['discount_percent'] : '0');
			$_REQUEST['discount_amount'.$id] = $this->formatUserNumber($product['discount_amount']);
			$_REQUEST['hdnTaxTotal'.$id] = $this->formatUserNumber($product['taxTotal']);
			$_REQUEST['netTotalPricedb'.$id] = $this->formatUserNumber($product['netTotalPricedb']);
			$_REQUEST['netPriceInput'.$id] = $product['netPrice'];
			if ($_REQUEST['taxtype'] == 'individual' && is_array($product['taxes'])) {
				foreach ($product['taxes'] as $ptax) {
					$_REQUEST[$ptax['taxname'].'_percentage'.$id] = $this->formatUserNumber($ptax['percentage']);
				}
			}
		}
		
		// final details
		$discountType = $data['final_details']['discount_type_final'];
		$_REQUEST['adjustment'] = abs($data['final_details']['adjustment']);
		$_REQUEST['adjustmentType'] = $data['final_details']['adjustment'] >= 0 ? '+' : '-';
		$_REQUEST['subtotal'] = $this->formatUserNumber($data['final_details']['hdnSubTotal']);
		$_REQUEST['total'] = $data['final_details']['grandTotal'];
		$_REQUEST['discount_type_final'] = $discountType;
		$_REQUEST['discount_final'] = 'on';
		$_REQUEST['discount_percentage_final'] = ($discountType == 'percentage' ? $data['final_details']['discount_percentage_final'] : '0');
		$_REQUEST['discount_amount_final'] = ($discountType == 'amount' ? $this->formatUserNumber($data['final_details']['discount_amount_final']) : '0');
		$_REQUEST['shipping_handling_charge'] = $this->formatUserNumber($data['final_details']['shipping_handling_charge']);
		
	}
	// crmv@195745e

	/** This function returns the detailed list of vte_products associated to a given entity or a record.
	 * Param $module - module name
	 * Param $focus - module object
	 * Param $seid - sales entity id
	 * Return type is an object array
	 */
	//crmv@44323 crmv@49398 crmv@55228
	function getAssociatedProducts($module,$focus,$seid='') {

		if (($override = self::checkOldOverride(__FUNCTION__))) return self::callOldOverride($override, func_get_args()); //crmv@35654

		global $log, $adb, $table_prefix, $current_user;
		$log->debug("Entering getAssociatedProducts(".$module.",focus,".$seid."='') method ...");

		$output = '';
		$product_Detail = Array();

		// DG 15 Aug 2006
		// Add "ORDER BY sequence_no" to retain add order on all inventoryproductrel items
		if (isInventoryModule($module)) {	//crmv@18498

			$taxtype = $this->getInventoryTaxType($module, $focus->id);

			//crmv@16267
			$query="SELECT
				case when ".$table_prefix."_products.productid is not null then ".$table_prefix."_products.productname else ".$table_prefix."_service.servicename end as productname,
				case when ".$table_prefix."_products.productid is not null then ".$table_prefix."_products.productcode else ".$table_prefix."_service.service_no end as productcode,
				case when ".$table_prefix."_products.productid is not null then ".$table_prefix."_products.usageunit else ".$table_prefix."_service.service_usageunit end as usageunit,
				case when ".$table_prefix."_products.productid is not null then ".$table_prefix."_products.unit_price else ".$table_prefix."_service.unit_price end as unit_price,
				case when ".$table_prefix."_products.productid is not null then ".$table_prefix."_products.qtyinstock else 0 end as qtyinstock,
				case when ".$table_prefix."_products.productid is not null then 'Products' else 'Services' end as entitytype,
				{$table_prefix}_products.unit_cost,
				".$table_prefix."_inventoryproductrel.description AS product_description,
				".$table_prefix."_inventoryproductrel.*
				FROM ".$table_prefix."_inventoryproductrel
				LEFT JOIN ".$table_prefix."_products ON ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid
				LEFT JOIN ".$table_prefix."_service ON ".$table_prefix."_service.serviceid=".$table_prefix."_inventoryproductrel.productid
				WHERE id=?
				ORDER BY sequence_no";
			//crmv@16267e
			$params = array($focus->id);

		// crmv@150773			
		} elseif($module == 'Potentials') {
			$query="SELECT
				".$table_prefix."_products.productname,
				".$table_prefix."_products.productcode,
				".$table_prefix."_products.unit_price,
				".$table_prefix."_products.qtyinstock,
				{$table_prefix}_products.unit_cost,
				".$table_prefix."_seproductsrel.*,
				".$table_prefix."_products.description AS product_description
				FROM ".$table_prefix."_products
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid
				INNER JOIN ".$table_prefix."_seproductsrel ON ".$table_prefix."_seproductsrel.productid=".$table_prefix."_products.productid
				WHERE ".$table_prefix."_seproductsrel.crmid=?";
			$params = array($seid);

		} elseif($module == 'HelpDesk') {
			$query="SELECT
				".$table_prefix."_products.productid,
				".$table_prefix."_products.productcode,
				".$table_prefix."_products.productname,
				".$table_prefix."_products.description,
				".$table_prefix."_products.unit_price,
				".$table_prefix."_products.qtyinstock,
				".$table_prefix."_inventoryproductrel.*,
				{$table_prefix}_products.unit_cost,
				FROM ".$table_prefix."_inventoryproductrel " .
				" INNER JOIN ".$table_prefix."_products ON ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid " .
				" INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_products.productid=".$table_prefix."_crmentity.crmid " .
				" WHERE id=".$focus->id." and deleted=0 ORDER BY sequence_no";

		} elseif($module == 'Products') {
			$query="SELECT
				".$table_prefix."_products.productid,
				".$table_prefix."_products.productcode,
				".$table_prefix."_products.productname,
				".$table_prefix."_products.unit_price,
				".$table_prefix."_products.qtyinstock,
				{$table_prefix}_products.unit_cost,
				".$table_prefix."_products.description AS product_description,
				'Products' AS entitytype
				FROM ".$table_prefix."_products
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid
				WHERE ".$table_prefix."_crmentity.deleted=0 AND productid=?";
			$params = array($seid);

		} elseif($module == 'Services') {
			$query="SELECT
				".$table_prefix."_service.serviceid AS productid,
				'NA' AS productcode,
				".$table_prefix."_service.servicename AS productname,
				".$table_prefix."_service.unit_price AS unit_price,
				'NA' AS qtyinstock,
				".$table_prefix."_service.description AS product_description,
				'Services' AS entitytype
				FROM ".$table_prefix."_service
				INNER JOIN ".$table_prefix."_crmentity  ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_service.serviceid
				WHERE ".$table_prefix."_crmentity.deleted=0 AND serviceid=?";
			$params = array($seid);
		}
		// crmv@150773e

		$result = $adb->pquery($query, $params);
		$num_rows=$adb->num_rows($result);

		VteSession::concat('query_show', $result->sql."\n");	//crmv@show_query

		for ($i=1; $i<=$num_rows; ++$i)
		{
			$productRow = $adb->fetchByAssoc($result);
			$options = array('taxtype' => $taxtype);
			
			$product = $this->processProductSqlRow($productRow, $i, $options, $module, $focus);
			$product_Detail[$i] = $product;			
		}
		
		$log->debug("Exiting getAssociatedProducts method ...");

		return $product_Detail;
	}
	
	function processProductSqlRow($row, $index, $options, $module, &$focus) {
		global $adb, $table_prefix;
		
		$taxtype = null;
		$skipSubProducts = false;
		if (is_array($options)) {
			if (isset($options['taxtype'])) $taxtype = $options['taxtype'];
			if ($options['skip_subproducts']) $skipSubProducts = true;
		}
		
		$i = $index;
		$output = array();
		
		$hdnProductId = $row['productid'];
		$hdnProductcode = $row['productcode'];
		$productname = $row['productname'];
		$productdescription = $row['product_description'];
		$comment = $row['comment'];
		$qtyinstock = $row['qtyinstock'];
		$qty = $row['quantity'];
		$unitprice = $row['unit_price'];
		$listprice = $row['listprice'];
		$entitytype = $row['entitytype'];
		$usageunit = $row['usageunit'];	//crmv@16267
		$linetotal = $row['linetotal']; //crmv@31780
		$lineitemid = $row['lineitem_id']; //crmv@33097
		$discount_percent = $row['discount_percent'];
		$discount_amount = $row['discount_amount'];
		$unit_cost = $row['unit_cost']; // crmv@44323

		if (!empty($entitytype)) {
			$output['entityType'.$i] = $entitytype;
		}

		if ($listprice == '') $listprice = $unitprice;
		if ($qty =='') $qty = 1;

		//calculate productTotal
		$productTotal = $qty*$listprice;

		//Set Delete link only from second row
		if ($i != 1) {
			$output['delRow'.$i] = "Del";
		}

		if (!$skipSubProducts) {
			if(empty($focus->mode) && $seid!=''){
				$sub_prod_query = $adb->pquery("SELECT crmid as prod_id from ".$table_prefix."_seproductsrel WHERE productid=? AND setype='Products'",array($seid));
			} else {
				$sub_prod_query = $adb->pquery("SELECT productid as prod_id from ".$table_prefix."_inventorysubproductrel WHERE id=? AND sequence_no=?",array($focus->id,$i));
			}

			$subprodid_str='';
			$subprodname_str='';
			$subProductArray = array();
			if ($adb->num_rows($sub_prod_query)>0) {
				for ($j=0; $j<$adb->num_rows($sub_prod_query); ++$j) {
					$sprod_id = $adb->query_result($sub_prod_query,$j,'prod_id');
					$sprod_name = $subProductArray[] = getProductName($sprod_id);
					$str_sep = "";
					if($j>0) $str_sep = ":";
					$subprodid_str .= $str_sep.$sprod_id;
					$subprodname_str .= $str_sep." - ".$sprod_name;
				}
			}
			$subprodname_str = str_replace(":","<br>",$subprodname_str);

			$output['subProductArray'.$i] = $subProductArray;
			$output['subproduct_ids'.$i] = $subprodid_str;
			$output['subprod_names'.$i] = $subprodname_str;
		}
		
		$output['usageunit'.$i] = $usageunit;	//crmv@16267
		$output['hdnProductId'.$i] = $hdnProductId;
		$output['productName'.$i]= str_replace('&quot;', '"', from_html($productname)); // crmv@107331
		
		/* Added to fix the issue Product Pop-up name display*/
		if($_REQUEST['action'] == 'CreateSOPDF' || $_REQUEST['action'] == 'CreatePDF' || $_REQUEST['action'] == 'SendPDFMail' || $_REQUEST['mode'] == 'edit') //crmv@64379
			$output['productName'.$i]= htmlspecialchars($output['productName'.$i], ENT_NOQUOTES); // crmv@107331
		
		$output['hdnProductcode'.$i] = $hdnProductcode;
		$output['productDescription'.$i]= from_html($productdescription);
		$output['comment'.$i]= $comment;
		$output['unit_cost'.$i]= $unit_cost; // crmv@44323

		if($module != 'PurchaseOrder' && $focus->object_name != 'Order') {
			$output['qtyInStock'.$i] = floatval($qtyinstock);
		}
		$output['qty'.$i] = floatval($qty);
		$output['listPrice'.$i] = floatval($listprice);
		$output['unitPrice'.$i] = floatval($unitprice);
		$output['productTotal'.$i] = floatval($productTotal);
		

		//Based on the discount percent or amount we will show the discount details

		//To avoid NaN javascript error, here we assign 0 initially to' %of price' and 'Direct Price reduction'(for Each Product)
		$output['discount_percent'.$i] = 0;
		$output['discount_amount'.$i] = 0;

		// crmv@48527 crmv@48699
		// convert discount percent
		if (!empty($discount_percent)) {
			$discount_percent_user = $this->parseMultiDiscount($discount_percent, 0, 0);
			$discount_percent_user = $this->joinMultiDiscount($discount_percent_user, 0, 1);
		} else {
			$discount_percent_user = '';
		}
		// crmv@48527e crmv@48699e

		$prodinfo = array(
			'listprice' => $listprice,
			'quantity' => $qty,
			'discount_percent' => $discount_percent,
			'discount_amount' => $discount_amount,
			'taxes' => array(),
		);

		// populate tax info
		//First we will get all associated taxes as array
		$tax_details = $this->getTaxDetailsForProduct($hdnProductId,'all');
		for ($tax_count=0; $tax_count<count($tax_details); ++$tax_count) {
			$tax_name = $tax_details[$tax_count]['taxname'];
			$tax_value = '0.00';

			//condition to avoid this function call when create new PO/SO/Quotes/Invoice from Product module
			if ($focus->id != '' && $taxtype == 'individual') {
				//if individual then show the entered tax percentage
				$tax_value = $this->getInventoryProductTaxValue($focus->id, $hdnProductId, $tax_name);
			} else {
				//if group tax then we have to show the default value when change to individual tax
				//if the above function not called then assign the default associated value of the product
				$tax_value = $tax_details[$tax_count]['percentage'];
			}

			$prodinfo['taxes'][$tax_name] = $tax_value;
		}

		// calculate prices
		$prodPrices = $this->calcProductTotals($prodinfo);

		if (!empty($discount_percent)) {
			$output['discount_type'.$i] = "percentage";
			$output['discount_percent'.$i] = $discount_percent_user;
			$output['checked_discount_percent'.$i] = ' checked';
			$output['style_discount_percent'.$i] = '';
			$output['style_discount_amount'.$i] = ' style="display:none"';

			$discount_info_message = '';
			$op = count($prodPrices['discounts']) > 1 ? $this->outputPrecision+2 : $this->outputPrecision; // crmv@193848
			foreach ($prodPrices['discounts'] as $discInfo) {
				// crmv@48527
				$discount_info_message .= $this->formatUserNumber($discInfo['percentage'])."% ".getTranslatedString('LBL_LIST_OF')." ".$this->formatUserNumber($discInfo['starting_price'], false, $op)." = ".$this->formatUserNumber($discInfo['amount'], false, $op)."\\n"; // crmv@193848
			}

		} elseif ($discount_amount != 'NULL' && $discount_amount != '') {
			$output['discount_type'.$i] = "amount";
			$output['discount_amount'.$i] = $discount_amount;
			$output['checked_discount_amount'.$i] = ' checked';
			$output['style_discount_amount'.$i] = '';
			$output['style_discount_percent'.$i] = ' style="display:none"';
			$discount_info_message = getTranslatedString('LBL_DIRECT_AMOUNT_DISCOUNT')." = ".$this->formatUserNumber($prodPrices['discounts'][0]['amount']);
		} else {
			$output['checked_discount_zero'.$i] = ' checked';
			$output['style_discount_percent'.$i] = ' style="display:none"';
			$output['style_discount_amount'.$i] = ' style="display:none"';
			$discount_info_message = getTranslatedString('LBL_NO_DISCOUNT_FOR_THIS_LINE_ITEM');
		}

		$output['discountInfoMessage'.$i] = $discount_info_message;
		$output['discountTotal'.$i] = $prodPrices['total_discount'];
		$output['totalAfterDiscount'.$i] = $prodPrices['price_discount'];
		$output['lineTotal'.$i] = $linetotal; //crmv@31780
		$output['lineItemId'.$i] = $lineitemid; //crmv@33097

		// calculate margin	: crmv@44323 crmv@55228
		$total_cost = $unit_cost * floatval($qty);
		$totalAfterDiscount = $output['totalAfterDiscount'.$i];

		if ($totalAfterDiscount != 0) {
			$margin = ($totalAfterDiscount - $total_cost) / $totalAfterDiscount;
			$output['margin'.$i] = round($margin, 2);//crmv@208151
		}
		// crmv@44323e crmv@55228e

		// crmv@31780

		//Now retrieve the tax values from the current query with the name
		$tax_info_message = getTranslatedString('LBL_TOTAL_AFTER_DISCOUNT')." = ".$this->formatUserNumber($prodPrices['price_discount'])." \\n";
		for ($tax_count=0; $tax_count<count($tax_details); ++$tax_count) {
			$tax_name = $tax_details[$tax_count]['taxname'];
			$tax_label = $tax_details[$tax_count]['taxlabel'];

			//condition to avoid this function call when create new PO/SO/Quotes/Invoice from Product module
			if ($focus->id != '' && $taxtype == 'individual') {
				//if individual then show the entered tax percentage
				$tax_value = floatval($this->getInventoryProductTaxValue($focus->id, $hdnProductId, $tax_name));
			} else {
				//if group tax then we have to show the default value when change to individual tax
				//if the above function not called then assign the default associated value of the product
				$tax_value = $tax_details[$tax_count]['percentage'];
			}

			$output['taxes'][$tax_count]['taxname'] = $tax_name;
			$output['taxes'][$tax_count]['taxlabel'] = $tax_label;
			$output['taxes'][$tax_count]['percentage'] = $tax_value;
			$tax_info_message .= "$tax_label : ".$this->formatUserNumber($tax_value)."% = ".$this->formatUserNumber($prodPrices['taxes'][$tax_count]['amount'])." \\n";
		}
		$tax_info_message .= "\\n".getTranslatedString('LBL_TOTAL_TAX_AMOUNT')." = ".$this->formatUserNumber($prodPrices['total_taxes']);

		$output['taxTotal'.$i] = $prodPrices['total_taxes'];
		$output['taxesInfoMessage'.$i] = $tax_info_message;

		//if condition is added to call this function when we create PO/SO/Quotes/Invoice from Product module
		if (isInventoryModule($module) && $taxtype == 'individual')	{ //crmv@18498
			// get the price after the taxes
			$netPrice = $prodPrices['price_taxes'];
		} else {
			$netPrice = $prodPrices['price_discount'];
		}

		$output['netPrice'.$i] = $netPrice;
		// crmv@31780e

		return $output;
	}
	// crmv@49398e

	//crmv@30721
	function getFinalDetails($module, $focus, $record=''){

		if (($override = self::checkOldOverride(__FUNCTION__))) return self::callOldOverride($override, func_get_args()); //crmv@35654

		global $adb, $table_prefix;
		global $mod_strings, $app_strings;

		if ($record == '' && $focus->id != '') {
			$record = $focus->id;
		}
		if ($record != '' && isInventoryModule($module)) {
			$taxtype = $this->getInventoryTaxType($module, $record);
		}

		//First we should get all available taxes and then retrieve the corresponding tax values
		if ($focus->mode != 'edit') {
			$tax_details = $this->getAllTaxes('available');
			$shtax_details = $this->getAllTaxes('available','sh');
		} else {
			$tax_details = $this->getAllTaxes('available','','edit',$record);
			$shtax_details = $this->getAllTaxes('available','sh','edit',$record);
		}

		// crmv@48527
		if (!empty($focus->column_fields['hdnDiscountPercent'])) {
			$totalDiscountDb = $focus->column_fields['hdnDiscountPercent']; // crmv@48699
			$discountUser = $this->parseMultiDiscount($focus->column_fields['hdnDiscountPercent'], 0, 0);
			$discountUser = $this->joinMultiDiscount($discountUser, 0, 1);
			$focus->column_fields['hdnDiscountPercent'] = $discountUser;
		}
		// crmv@48527e

		// populate array for calculations
		$totalinfo = array(
			'nettotal' => floatval($focus->column_fields['hdnSubTotal']),
			's_h_amount' => floatval($focus->column_fields['hdnS_H_Amount']),
			'discount_percent' => $totalDiscountDb,
			'discount_amount' => $focus->column_fields['hdnDiscountAmount'],
			'adjustment' => floatval($focus->column_fields['txtAdjustment']),
			'taxes' => array(),
			'shtaxes' => array(),
		);

		// set taxes (group)
		if ($taxtype == 'group') {
			for ($tax_count=0; $tax_count<count($tax_details); ++$tax_count) {
				$tax_name = $tax_details[$tax_count]['taxname'];

				$result = $adb->pquery("SELECT * FROM {$table_prefix}_inventoryproductrel INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_inventoryproductrel.productid WHERE deleted = 0 AND id = ?",array($record));
				$tax_percent = floatval($adb->query_result($result,0,$tax_name));

				$totalinfo['taxes'][$tax_name] = $tax_percent;
			}
		}

		// set taxes (SH)
		for ($shtax_count=0; $shtax_count<count($shtax_details); ++$shtax_count) {
			$shtax_name = $shtax_details[$shtax_count]['taxname'];
			$shtax_percent = $shtax_details[$shtax_count]['percentage'];
			if ($record > 0 && isInventoryModule($module)) {
				//if condition is added to call this function when we create PO/SO/Quotes/Invoice from Product module
				$shtax_percent = $this->getInventorySHTaxPercent($record,$shtax_name);
			}

			$totalinfo['shtaxes'][$shtax_name] = $shtax_percent;
		}

		// calculate totals
		$totalPrices = $this->calcInventoryTotals($totalinfo);

		//set the taxtype
		$product_Detail[1]['final_details']['taxtype'] = $taxtype;

		//Get the Final Discount, S&H charge, Tax for S&H and Adjustment values
		//To set the Final Discount details
		$product_Detail[1]['final_details']['discount_type_final'] = 'zero';

		$subTotal = ($focus->column_fields['hdnSubTotal'] != '') ? $focus->column_fields['hdnSubTotal'] : 0;

		$product_Detail[1]['final_details']['hdnSubTotal'] = floatval($subTotal);
		$discountPercent = ($focus->column_fields['hdnDiscountPercent'] != '') ? $focus->column_fields['hdnDiscountPercent'] : 0;
		$discountAmount = ($focus->column_fields['hdnDiscountAmount'] != '') ? $focus->column_fields['hdnDiscountAmount'] : 0;

		//To avoid NaN javascript error, here we assign 0 initially to' %of price' and 'Direct Price reduction'(For Final Discount)
		$product_Detail[1]['final_details']['discount_percentage_final'] = 0;
		$product_Detail[1]['final_details']['discount_amount_final'] = 0;

		$final_discount_info = getTranslatedString('LBL_FINAL_DISCOUNT_AMOUNT').":\\n";
		if ($focus->column_fields['hdnDiscountPercent'] != '0' && !empty($discountPercent)) {
			$product_Detail[1]['final_details']['discount_type_final'] = "percentage";
			$product_Detail[1]['final_details']['discount_percentage_final'] = $discountPercent;
			$product_Detail[1]['final_details']['checked_discount_percentage_final'] = ' checked';
			$product_Detail[1]['final_details']['style_discount_percentage_final'] = '';
			$product_Detail[1]['final_details']['style_discount_amount_final'] = ' style="display:none"';
			$op = count($totalPrices['discounts']) > 1 ? $this->outputPrecision+2 : $this->outputPrecision; // crmv@193848
			foreach ($totalPrices['discounts'] as $discInfo) {
				// crmv@48527
				$final_discount_info .= $this->formatUserNumber($discInfo['percentage'])."% {$app_strings['LBL_LIST_OF']} ".$this->formatUserNumber($discInfo['starting_price'], false, $op)." = ".$this->formatUserNumber($discInfo['amount'], false, $op)."\\n"; // crmv@193848
			}

		} elseif($focus->column_fields['hdnDiscountAmount'] != '0' && !empty($discountAmount)) { // crmv@81758
			$product_Detail[1]['final_details']['discount_type_final'] = 'amount';
			$product_Detail[1]['final_details']['discount_amount_final'] = floatval($discountAmount);
			$product_Detail[1]['final_details']['checked_discount_amount_final'] = ' checked';
			$product_Detail[1]['final_details']['style_discount_amount_final'] = '';
			$product_Detail[1]['final_details']['style_discount_percentage_final'] = ' style="display:none"';
			$final_discount_info .= $this->formatUserNumber($product_Detail[1]['final_details']['discount_amount_final']);
		} else {
			$final_discount_info .= $this->formatUserNumber(0.0);
		}

		$product_Detail[1]['final_details']['discountTotal_final'] = $totalPrices['total_discount'];
		$product_Detail[1]['final_details']['discountInfoMessage'] = $final_discount_info;

		//To set the Final Tax values
		$tax_info_message = $app_strings['LBL_TOTAL_AFTER_DISCOUNT']." = ".$this->formatUserNumber($totalPrices['price_discount'])." \\n";
		//suppose user want to change individual to group or vice versa in edit time the we have to show all taxes. so that here we will store all the taxes and based on need we will show the corresponding taxes
		for ($tax_count=0; $tax_count<count($tax_details); ++$tax_count) {
			$tax_name = $tax_details[$tax_count]['taxname'];
			$tax_label = $tax_details[$tax_count]['taxlabel'];

			//if taxtype is individual and want to change to group during edit time then we have to show the all available taxes and their default values
			//Also taxtype is group and want to change to individual during edit time then we have to provide the asspciated taxes and their default tax values for individual products
			if($taxtype == 'group') {
				$result = $adb->pquery("SELECT * FROM {$table_prefix}_inventoryproductrel INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_inventoryproductrel.productid WHERE deleted = 0 AND id = ?",array($record));
				$tax_percent = floatval($adb->query_result($result,0,$tax_name));
			} else {
				$tax_percent = floatval($tax_details[$tax_count]['percentage']);
			}

			$product_Detail[1]['final_details']['taxes'][$tax_count]['taxname'] = $tax_name;
			$product_Detail[1]['final_details']['taxes'][$tax_count]['taxlabel'] = $tax_label;
			$product_Detail[1]['final_details']['taxes'][$tax_count]['percentage'] = $tax_percent;
			$product_Detail[1]['final_details']['taxes'][$tax_count]['amount'] = $totalPrices['taxes'][$tax_count]['amount'];
			$tax_info_message .= "$tax_label : ".$this->formatUserNumber($tax_percent)." % = ".$this->formatUserNumber($totalPrices['taxes'][$tax_count]['amount'])." \\n";
		}
		$tax_info_message .= "\\n ".$app_strings['LBL_TOTAL_TAX_AMOUNT']." = ".$this->formatUserNumber($totalPrices['total_taxes']);
		$product_Detail[1]['final_details']['tax_totalamount'] = $totalPrices['total_taxes'];
		$product_Detail[1]['final_details']['taxesInfoMessage'] = $tax_info_message;

		//To set the Shipping & Handling charge
		$shCharge = ($focus->column_fields['hdnS_H_Amount'] != '') ? $focus->column_fields['hdnS_H_Amount'] : 0;
		$product_Detail[1]['final_details']['shipping_handling_charge'] = floatval($shCharge);

		//To set the Shipping & Handling tax values
		$shtax_info_message = getTranslatedString('LBL_SHIPPING_AND_HANDLING_CHARGE')." = ".$this->formatUserNumber(floatval($shCharge))." \\n";
		for ($shtax_count=0; $shtax_count<count($shtax_details); ++$shtax_count) {
			$shtax_name = $shtax_details[$shtax_count]['taxname'];
			$shtax_label = $shtax_details[$shtax_count]['taxlabel'];
			$shtax_percent = $shtax_details[$shtax_count]['percentage'];
			if($record > 0 && isInventoryModule($module)) {
				//if condition is added to call this function when we create PO/SO/Quotes/Invoice from Product module
				$shtax_percent = $this->getInventorySHTaxPercent($record,$shtax_name);
			}
			$product_Detail[1]['final_details']['sh_taxes'][$shtax_count]['taxname'] = $shtax_name;
			$product_Detail[1]['final_details']['sh_taxes'][$shtax_count]['taxlabel'] = $shtax_label;
			$product_Detail[1]['final_details']['sh_taxes'][$shtax_count]['percentage'] = $shtax_percent;
			$product_Detail[1]['final_details']['sh_taxes'][$shtax_count]['amount'] = $totalPrices['shtaxes'][$shtax_count]['amount'];
			$shtax_info_message .= "$shtax_label : ".$this->formatUserNumber(floatval($shtax_percent))." % = ".$this->formatUserNumber($totalPrices['shtaxes'][$shtax_count]['amount'])." \\n";
		}
		$shtax_info_message .= "\\n ".getTranslatedString('LBL_TOTAL_TAX_AMOUNT')." = ".$this->formatUserNumber($totalPrices['total_shtaxes']);

		$product_Detail[1]['final_details']['shtaxesInfoMessage'] = $shtax_info_message;
		$product_Detail[1]['final_details']['shtax_totalamount'] = $totalPrices['total_shtaxes'];

		//To set the Adjustment value
		$adjustment = ($focus->column_fields['txtAdjustment'] != '') ? $focus->column_fields['txtAdjustment'] : 0;
		$product_Detail[1]['final_details']['adjustment'] = floatval($adjustment);

		//To set the grand total
		$product_Detail[1]['final_details']['grandTotal'] = $totalPrices['price_adjustment'];

		return $product_Detail;
	}
	//crmv@30721e

	// crmv@195745
	/**
	 * Clone the full products block + totals, subtotals.. from $record1 to $record2
	 * The 2 modules must have all the required inventory fields
	 */
	public function cloneProductsBlock($module1, $record1, $module2, $record2) {
		global $adb, $table_prefix;
		
		// products block
		// remove existing rows
		$adb->pquery("DELETE FROM {$table_prefix}_inventoryproductrel WHERE id = ?", array($record2));
		// copy them	
		$res = $adb->pquery("SELECT * FROM {$table_prefix}_inventoryproductrel WHERE id = ? ORDER BY lineitem_id", array($record1));
		$count = $adb->num_rows($res);
		if ($count > 0) {
			$newids = $adb->getMultiUniqueID($table_prefix.'_inventoryproductrel', $count);
			$rows = array();
			$i = 0;
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$row['lineitem_id'] = $newids[$i++];
				$row['id'] = $record2;
				$row['relmodule'] = $module2;
				$rows[] = $row;
			}
			// insert them all!
			$adb->bulkInsert($table_prefix.'_inventoryproductrel', array_keys($rows[0]), $rows);
		}
		
		// subprods
		// remove existing
		$adb->pquery("DELETE FROM {$table_prefix}_inventorysubproductrel WHERE id = ?", array($record2));
		// copy them
		$res = $adb->pquery("SELECT * FROM {$table_prefix}_inventorysubproductrel WHERE id = ?", array($record1));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$row['id'] = $record2;
				$adb->pquery("INSERT INTO {$table_prefix}_inventorysubproductrel VALUES (".generateQuestionMarks($row).")", $row);
			}
		}
		
		// inventory totals
		$res = $adb->pquery("SELECT * FROM {$table_prefix}_inventorytotals WHERE id = ?", array($record1));
		$row = $adb->fetchByAssoc($res, -1, false);
		if ($row) {
			// remove existing row
			$adb->pquery("DELETE FROM {$table_prefix}_inventorytotals WHERE id = ?", array($record2));
			// add new one
			$row['id'] = $record2;
			$adb->pquery("INSERT INTO {$table_prefix}_inventorytotals VALUES (".generateQuestionMarks($row).")", $row);
		}
		
		// inventory shipping
		$res = $adb->pquery("SELECT * FROM {$table_prefix}_inventoryshippingrel WHERE id = ?", array($record1));
		$row = $adb->fetchByAssoc($res, -1, false);
		if ($row) {
			// remove existing row
			$adb->pquery("DELETE FROM {$table_prefix}_inventoryshippingrel WHERE id = ?", array($record2));
			// add new one
			$row['id'] = $record2;
			$adb->pquery("INSERT INTO {$table_prefix}_inventoryshippingrel VALUES (".generateQuestionMarks($row).")", $row);
		}
		
		$focus1 = CRMEntity::getInstance($module1);
		$focus2 = CRMEntity::getInstance($module2);
		
		// main table 
		$res = $adb->pquery(
			"SELECT discount_percent, discount_amount, s_h_amount, taxtype, adjustment, total, subtotal 
			FROM {$focus1->table_name} 
			WHERE {$focus1->table_index} = ?", 
			array($record1)
		);
		$row = $adb->fetchByAssoc($res, -1, false);
		if ($row) {
			$adb->pquery(
				"UPDATE {$focus2->table_name}
				SET discount_percent = ?, discount_amount = ?, s_h_amount = ?, taxtype = ?, adjustment = ?, total = ?, subtotal = ? 
				WHERE {$focus2->table_index} = ?",
				array($row, $record2)
			);
		}
		
	}
	
	/**
	 * Get the product block rows with names like the ones in the FakeModule
	 */
	public function getProductBlockRows($module, $crmid, $tableFieldsFormat = false) {
		$focus = CRMEntity::getInstance($module);
		$focus->id = $crmid;
		$rows = $this->getAssociatedProducts($module, $focus);

		$nicerows = array();
		
		foreach ($rows as $idx => $row) {
			$discount = '';
			if ($row['discount_percent'.$idx] != '') {
				$discount = $row['discount_percent'.$idx] .'%';
			} elseif ($row['discount_amount'.$idx] != '') {
				$discount = $row['discount_amount'.$idx];
			}
			$nicerow = array(
				'id' => $crmid,
				'productid' => $row['hdnProductId'.$idx],
				'quantity' => $row['qty'.$idx],
				'listprice' => $row['listPrice'.$idx],
				'discount' => $discount,
				'total_notaxes' => $row['totalAfterDiscount'.$idx],
				'comment' => $row['comment'.$idx],
				'description' => $row['productDescription'.$idx],
				'linetotal' => $row['lineTotal'.$idx],
				'extra' => [
					// other information not mapped as a field, but still useful
					'entity_type' => $row['entityType'.$idx],
					'product_name' => $row['productName'.$idx],
				]
			);
			if (is_array($row['taxes'])) {
				foreach ($row['taxes'] as $tax) {
					$nicerow[$tax['taxname']] = $tax['percentage'];
				}
			}
			
			if ($tableFieldsFormat) {
				$nicerows[] = array('id' => $row['lineItemId'.$idx], 'row' => $nicerow);
			} else {
				$nicerows[] = $nicerow;
			}
		}

		return $nicerows;
	}
	
	/**
	 * Add a single product to an inventory record and recalculate the total
	 * Fields are like the ones returned by getAssociatedProducts
	 */
	public function addProductToRecord($module, $crmid, $prodinfo) {
		return $this->addProductsToRecord($module, $crmid, array($prodinfo));
	}
	
	/**
	 * Add several products to an inventory record and recalculate the total
	 * Fields are like the ones returned by getAssociatedProducts
	 */
	public function addProductsToRecord($module, $crmid, $products) {
	
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($crmid, $module);
		$focus->mode = 'edit';
		$focus->id = $crmid;
	
		$data = $this->getDetailAssociatedProductsArray($module, $focus, true);
		
		$lastid = count($data['products']);
		foreach ($products as $prod) {
			$data['products'][++$lastid] = $prod;
		}
		
		// save original data
		$old_request = $_REQUEST;
		$old_post = $_POST;
		$old_get = $_GET;
		
		$this->populateRequestFromData($module, $data, $focus);
		$ret = $this->saveInventoryProductDetails($focus, $module); //, false, '', true);
		
		// and restore it
		$_REQUEST = $old_request;
		$_POST = $old_post;
		$_GET = $old_get;
	}
	
	/**
	 * Remove a single row from an inventory record and recalculate the total
	 */
	public function removeRowFromRecord($module, $crmid, $lineitemid) {
		return $this->removeRowsFromRecord($module, $crmid, array($lineitemid));
	}
	
	/**
	 * Remove specific rows from an inventory record and recalculate the total
	 */
	public function removeRowsFromRecord($module, $crmid, $lineitemids) {
		
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($crmid, $module);
		$focus->mode = 'edit';
		$focus->id = $crmid;
	
		$data = $this->getDetailAssociatedProductsArray($module, $focus, true);
		
		$newprods = array();
		$nidx = 1;
		foreach ($data['products'] as $k => $prod) {
			if (!in_array($prod['lineItemId'], $lineitemids)) {
				$newprods[$nidx++] = $prod;
			}
		}
		$data['products'] = $newprods;
		
		// save original data
		$old_request = $_REQUEST;
		$old_post = $_POST;
		$old_get = $_GET;
		
		$this->populateRequestFromData($module, $data, $focus);
		$ret = $this->saveInventoryProductDetails($focus, $module);
		
		// and restore it
		$_REQUEST = $old_request;
		$_POST = $old_post;
		$_GET = $old_get;
	}
	
	/**
	 * Like removeRowFromRecord, but select the row using its index (0-based)
	 */
	public function removeRowFromRecordByIndex($module, $crmid, $index) {
		return $this->removeRowsFromRecordByIndex($module, $crmid, array($index));
	}
	
	/**
	 * Similar to removeRowsFromRecord, but removes rows according to their index (0-based)
	 * in the array of products
	 */
	public function removeRowsFromRecordByIndex($module, $crmid, $idxs) {
	
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($crmid, $module);
		$focus->mode = 'edit';
		$focus->id = $crmid;
	
		$data = $this->getDetailAssociatedProductsArray($module, $focus, true);
		
		$newprods = array();
		$nidx = 1;
		foreach ($data['products'] as $k => $prod) {
			if (!in_array($k-1, $idxs)) {
				$newprods[$nidx++] = $prod;
			}
		}
		$data['products'] = $newprods;
		
		// save original data
		$old_request = $_REQUEST;
		$old_post = $_POST;
		$old_get = $_GET;
		
		$this->populateRequestFromData($module, $data, $focus);
		$ret = $this->saveInventoryProductDetails($focus, $module);
		
		// and restore it
		$_REQUEST = $old_request;
		$_POST = $old_post;
		$_GET = $old_get;
	}
	// crmv@195745e
	
}
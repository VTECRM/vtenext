<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@30447
require_once('modules/VteCore/EditView.php');

$encode_val=vtlib_purify($_REQUEST['encode_val']);
$decode_val=base64_decode($encode_val);

$saveimage=isset($_REQUEST['saveimage'])?vtlib_purify($_REQUEST['saveimage']):"false";
$errormessage=isset($_REQUEST['error_msg'])?vtlib_purify($_REQUEST['error_msg']):"false";
$image_error=isset($_REQUEST['image_error'])?vtlib_purify($_REQUEST['image_error']):"false";

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

if($record) {
    $product_base_currency = $InventoryUtils->getProductBaseCurrency($focus->id,$currentModule);
} else {
	$product_base_currency = fetchCurrency($current_user->id);
}

if($image_error=="true")
{
	$explode_decode_val=explode("&",$decode_val);
	for($i=1;$i<count($explode_decode_val);$i++)
	{
		$test=$explode_decode_val[$i];
		$values=explode("=",$test);
		$field_name_val=$values[0];
		$field_value=$values[1];
		$focus->column_fields[$field_name_val]=$field_value;
	}
}

if (isset($_REQUEST['vendorid']) && is_null($focus->vendorid)) {
	$focus->vendorid = $_REQUEST['vendorid'];
}

// crmv@104568
if ($disp_view != 'edit_view') {
	//merge check - start
	$smarty->assign("MERGE_USER_FIELDS",implode(',',get_merge_user_fields($currentModule))); //crmv_utils
	//ends
}
// crmv@104568e

if($focus->id != '')
	$smarty->assign("ROWCOUNT", getImageCount($focus->id));


//Tax handling (get the available taxes only) - starts
if($focus->mode == 'edit') {
	$retrieve_taxes = true;
	$productid = $focus->id;
	$tax_details = $InventoryUtils->getTaxDetailsForProduct($productid,'available_associated');
} elseif($_REQUEST['isDuplicate'] == 'true') {
	$retrieve_taxes = true;
	$productid = $_REQUEST['record'];
	$tax_details = $InventoryUtils->getTaxDetailsForProduct($productid,'available_associated');
}

// crmv@93286 crmv@120823 - merge taxes
$all_taxes = $InventoryUtils->getAllTaxes('available');

if (!is_array($tax_details)) $tax_details = $all_taxes;

if ($retrieve_taxes) {
	$taxids = array_map(function($tax) {
		return $tax['taxid'];
	}, $tax_details);
	
	if (is_array($all_taxes)) {
		foreach ($all_taxes as $tax) {
			if (!in_array($tax['taxid'], $taxids)) {
				$tax_details[] = $tax;
			}
		}
	}

	//For Edit and Duplicate we have to retrieve the product associated taxes and show them
	foreach ($tax_details as &$tax) {
		$tax['check_name'] = $tax['taxname'].'_check';
		
		$tax_value = $InventoryUtils->getProductTaxPercentage($tax['taxname'],$productid);
		if ($tax_value == '') {
			//if the tax is not associated with the product then we should get the default value and unchecked
			$tax['check_value'] = 0;
			$tax['percentage'] = $InventoryUtils->getTaxPercentage($tax['taxname']); // crmv@42024
		} else {
			$tax['check_value'] = 1;
			$tax['percentage'] = $tax_value;
		}
	}
} else {
	foreach ($tax_details as &$tax) {
		$tax['check_name'] = $tax['taxname'].'_check';
	}
}
// crmv@93286e crmv@120823e

$smarty->assign("TAX_DETAILS", $tax_details);
//Tax handling - ends

$unit_price = $focus->column_fields['unit_price'];
$price_details = $InventoryUtils->getPriceDetailsForProduct($productid, $unit_price, 'available',$currentModule);
$smarty->assign("PRICE_DETAILS", $price_details);

$base_currency = 'curname' . $product_base_currency;
$smarty->assign("BASE_CURRENCY", $base_currency);

if(isset($focus->id) && $_REQUEST['isDuplicate'] != 'true')
	$is_parent = $focus->isparent_check();
else
	$is_parent = 0;
$smarty->assign("IS_PARENT",$is_parent);

if($_REQUEST['return_module']=='Products' && isset($_REQUEST['return_action'])){
	$return_name = getProductName($_REQUEST['return_id']);
	$smarty->assign("RETURN_NAME", $return_name);
}

if($errormessage==2)
{
	$msg =$mod_strings['LBL_MAXIMUM_LIMIT_ERROR'];
        $errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";
}
else if($errormessage==3)
{
        $msg = $mod_strings['LBL_UPLOAD_ERROR'];
        $errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";

}
else if($errormessage=="image")
{
        $msg = $mod_strings['LBL_IMAGE_ERROR'];
        $errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";
}
else if($errormessage =="invalid")
{
        $msg = $mod_strings['LBL_INVALID_IMAGE'];
        $errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";
}
else
{
	$errormessage="";
}
if($errormessage!="")
{
	$smarty->assign("ERROR_MESSAGE",$errormessage);
}

// Added to set product active when creating a new product
$mode=$focus->mode;
if($mode != "edit" && $_REQUEST['isDuplicate'] != "true")
	$smarty->assign("PROD_MODE", "create");


$smarty->display('Inventory/InventoryEditView.tpl');
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix;//crmv@203484 removed global singlepane
global $log;
//crmv@203484
$VTEP = VTEProperties::getInstance();
$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
//crmv@203484e
$idlist = vtlib_purify($_POST['idlist']);
$returnmodule = vtlib_purify($_REQUEST['return_module']);
$pricebook_id = vtlib_purify($_REQUEST['pricebook_id']);
$productid = vtlib_purify($_REQUEST['product_id']);
$parenttab = getParentTab();

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

if(isset($_REQUEST['pricebook_id']) && $_REQUEST['pricebook_id']!='')
{
	$currency_id = $InventoryUtils->getPriceBookCurrency($pricebook_id);
	//split the string and store in an array
	$storearray = explode(";",$idlist);
	foreach($storearray as $id)
	{
		if($id != '') {
			$lp_name = $id.'_listprice';
			$list_price = parseUserNumber($_REQUEST[$lp_name]); // crmv@173281
			//Updating the vte_pricebook product rel vte_table
			$log->info("Products :: Inserting vte_products to price book");
			// crmv@150533
			$relid = $adb->getUniqueID($table_prefix."_pricebookproductrel");
			$query= "insert into ".$table_prefix."_pricebookproductrel (pbrelid,pricebookid,productid,listprice,usedcurrency) values(?,?,?,?,?)";
			$adb->pquery($query, array($relid,$pricebook_id,$id,$list_price,$currency_id));
			// crmv@150533e
		}
	}
	if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
		header("Location: index.php?module=PriceBooks&action=DetailView&record=$pricebook_id&parenttab=$parenttab");
	else
		header("Location: index.php?module=PriceBooks&action=CallRelatedList&record=$pricebook_id&parenttab=$parenttab");
}
elseif(isset($_REQUEST['product_id']) && $_REQUEST['product_id']!='')
{
	//split the string and store in an array
	$storearray = explode(";",$idlist);
	foreach($storearray as $id)
	{
		if($id != '') {
			$currency_id = $InventoryUtils->getPriceBookCurrency($id);
			$lp_name = $id.'_listprice';
			$list_price = parseUserNumber($_REQUEST[$lp_name]); // crmv@173281
			//Updating the vte_pricebook product rel vte_table
			$log->info("Products :: Inserting PriceBooks to Product");
			// crmv@150533
			$relid = $adb->getUniqueID($table_prefix."_pricebookproductrel");
			$query= "insert into ".$table_prefix."_pricebookproductrel (pbrelid,pricebookid,productid,listprice,usedcurrency) values(?,?,?,?,?)";
			$adb->pquery($query, array($relid,$id,$productid,$list_price,$currency_id));
			// crmv@150533e
		}
	}
	if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
		header("Location: index.php?module=Products&action=DetailView&record=$productid&parenttab=$parenttab");
	else
		header("Location: index.php?module=Products&action=CallRelatedList&record=$productid&parenttab=$parenttab");
}

?>
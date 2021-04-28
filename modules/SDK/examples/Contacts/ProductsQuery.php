<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//mostro solo i prodotti collegati all'Azienda

/* crmv@208173 */

global $adb, $table_prefix;
$condition = "''";
$products = array();
$accountid = $_REQUEST['from_crmid'];
$tmp_query = "
	SELECT
	  ".$table_prefix."_products.productid
	FROM ".$table_prefix."_products
	  INNER JOIN ".$table_prefix."_seproductsrel
	    ON ".$table_prefix."_seproductsrel.productid = ".$table_prefix."_products.productid
	      AND ".$table_prefix."_seproductsrel.setype = 'Accounts'
	  INNER JOIN ".$table_prefix."_crmentity
	    ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_seproductsrel.crmid
	  INNER JOIN ".$table_prefix."_account
	    ON ".$table_prefix."_account.accountid = ".$table_prefix."_crmentity.crmid
	  INNER JOIN ".$table_prefix."_contactdetails
	    ON ".$table_prefix."_contactdetails.accountid = ".$table_prefix."_account.accountid
	WHERE deleted = 0
	    AND ".$table_prefix."_contactdetails.contactid = ?";
$result_tmp = $adb->query($tmp_query, array($accountid));
if ($result_tmp && $adb->num_rows($result_tmp)>0) {
	while($row = $adb->fetchByAssoc($result_tmp)) {
		$products[] = $row['productid'];
	}
	if (!empty($products)) {
		$condition = implode(',',$products);
	}
	$query .= " and ".$table_prefix."_products.productid in ($condition)";
} else {
	die('Nessun prodotto collegato all\'azienda.');
}
//$sdk_show_all_button = true;
?>
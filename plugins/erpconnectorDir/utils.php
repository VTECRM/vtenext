<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function str_format(&$key,&$item){
	$key = decode_html($key);
}
if (!function_exists('replaceSelectQuery')){
	function replaceSelectQuery($query,$replace = "count(*) AS count",$group_by=false)
	{
	    // Remove all the \n, \r and white spaces to keep the space between the words consistent.
	    // This is required for proper pattern matching for words like ' FROM ', 'ORDER BY', 'GROUP BY' as they depend on the spaces between the words.
	    $query = preg_replace("/[\n\r\s]+/"," ",$query);

	    //Strip of the current SELECT fields and replace them by "select count(*) as count"
	    // Space across FROM has to be retained here so that we do not have a clash with string "from" found in select clause
	    $query = "SELECT $replace ".substr($query, stripos($query,' FROM '),strlen($query));

	    //Strip of any "GROUP BY" clause
	//    if ($group_by){
	//    	if(stripos($query,'GROUP BY') > 0)
	//		$query = substr($query, 0, stripos($query,'GROUP BY'));
	//	}
	    //Strip of any "ORDER BY" clause
	    if(strripos($query,'ORDER BY') > 0)
		$query = substr($query, 0, strripos($query,'ORDER BY'));

	    //That's it
	    return( $query);
	}
}
function fputcsv2 ($fh, array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = true) {
    $delimiter_esc = preg_quote($delimiter, '/');
    $enclosure_esc = preg_quote($enclosure, '/');

    $output = array();
    foreach ($fields as $field) {
        if ($field === null && $mysql_null) {
            $output[] = 'NULL';
            continue;
        }

        $output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? (
            $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure
        ) : $field;
    }
    fwrite($fh, join($delimiter, $output) . "\n");
}
function sanitize_array_sql(&$item,&$key){
	global $adb;
	if(is_string($item)) {
//		$item = str_replace("\n",'\$NL$/',$item);
		if($item == '') {
			$item = $adb->database->Quote($item);
		}
		else {
			$item = "'".$adb->sql_escape_string($item). "'";
		}
	}
	if($item === null) {
		$item = "NULL";
	}
}
function crmv_get_valuta($value){
	global $adb,$table_prefix;
	$sql = "select id from ".$table_prefix."_currency_info where currency_code = ?";
	$params = Array($value);
	$res = $adb->pquery($sql,$params);
	if ($res && $adb->num_rows($res)>0){
		$value = $adb->query_result_no_html($res,0,'id');
	}
	else{
		$value = 0;
	}
	return $value;
}
function get_existing_entity_runtime_unique_offline($module,$fields_key,$row){
	global $adb,$log,$table_prefix;
	if (!VteSession::hasKeyArray(array($module."_offline", 'query_unique'))){
		$LVU = ListViewUtils::getInstance();
		$fields_key_inverse = array_flip($fields_key);
		$sql = "select tablename,columnname,fieldname from ".$table_prefix."_field where tabid = ?";
		$params[] = getTabid($module);
		$params[] = $fields_key;
		$sql.=" and fieldname in (".generateQuestionMarks($fields_key).")";
		$res = $adb->pquery($sql,$params);
		$external_code_expression = '';
		$params = Array();
		if ($res){
			while($r = $adb->fetchByAssoc($res,-1,false)){
				$external_code_expression.=" and ".$r['tablename'].".".$r['columnname']." = ?";
				$fieldnames[] = $fields_key_inverse[$r['fieldname']];
			}
		}
		if ($module == 'SalesOrder'){
			$qry = 'SELECT crmid FROM '.$table_prefix.'_salesorder '.
				  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_salesorder.salesorderid '.
				  'INNER JOIN '.$table_prefix.'_sobillads ON '.$table_prefix.'_salesorder.salesorderid = '.$table_prefix.'_sobillads.sobilladdressid '.
				  'INNER JOIN '.$table_prefix.'_soshipads ON '.$table_prefix.'_salesorder.salesorderid = '.$table_prefix.'_soshipads.soshipaddressid '.
				  'INNER JOIN '.$table_prefix.'_salesordercf ON '.$table_prefix.'_salesordercf.salesorderid = '.$table_prefix.'_salesorder.salesorderid '.
				  'WHERE '.$table_prefix.'_crmentity.deleted = 0';
			$qry.=" $external_code_expression";
		}
		elseif ($module == 'Products'){
			$qry = 'SELECT crmid FROM '.$table_prefix.'_products '.
				  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_products.productid '.
				  'INNER JOIN '.$table_prefix.'_productcf ON '.$table_prefix.'_products.productid = '.$table_prefix.'_productcf.productid '.
				  'WHERE '.$table_prefix.'_crmentity.deleted = 0';
			$qry.=" $external_code_expression";
		}
		elseif ($module == 'Accounts'){
			$qry = 'SELECT crmid FROM '.$table_prefix.'_account '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_account.accountid '.
			  'INNER JOIN '.$table_prefix.'_accountbillads ON '.$table_prefix.'_account.accountid = '.$table_prefix.'_accountbillads.accountaddressid '.
			  'INNER JOIN '.$table_prefix.'_accountshipads ON '.$table_prefix.'_account.accountid = '.$table_prefix.'_accountshipads.accountaddressid '.
			  'INNER JOIN '.$table_prefix.'_accountscf ON '.$table_prefix.'_account.accountid = '.$table_prefix.'_accountscf.accountid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
			$qry.=" $external_code_expression";
		}
		elseif ($module == 'Vendors'){
			$qry = 'SELECT crmid FROM '.$table_prefix.'_vendor '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_vendor.vendorid '.
			  'INNER JOIN '.$table_prefix.'_vendorcf ON '.$table_prefix.'_vendor.vendorid = '.$table_prefix.'_vendorcf.vendorid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
			$qry.=" $external_code_expression";
		}
		elseif ($module == 'Invoice'){
			$qry = 'SELECT crmid FROM '.$table_prefix.'_invoice '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_invoice.invoiceid '.
			  'INNER JOIN '.$table_prefix.'_invoicebillads ON '.$table_prefix.'_invoice.invoiceid = '.$table_prefix.'_invoicebillads.invoicebilladdressid '.
			  'INNER JOIN '.$table_prefix.'_invoiceshipads ON '.$table_prefix.'_invoice.invoiceid = '.$table_prefix.'_invoiceshipads.invoiceshipaddressid '.
			  'INNER JOIN '.$table_prefix.'_invoicecf ON '.$table_prefix.'_invoice.invoiceid = '.$table_prefix.'_invoicecf.invoiceid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
			$qry.=" $external_code_expression";
		}
		elseif ($module == 'Scadenziario'){
			$qry = 'SELECT crmid FROM '.$table_prefix.'_scadenziario '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_scadenziario.scadenziarioid '.
			  'INNER JOIN '.$table_prefix.'_scadenziariocf ON '.$table_prefix.'_scadenziario.scadenziarioid = '.$table_prefix.'_scadenziariocf.scadenziarioid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
			$qry.=" $external_code_expression";
		}
		elseif ($module == 'Quotes'){
			$qry = 'SELECT crmid FROM '.$table_prefix.'_quotes '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_quotes.quoteid '.
			  'INNER JOIN '.$table_prefix.'_quotesbillads ON '.$table_prefix.'_quotes.quoteid = '.$table_prefix.'_quotesbillads.quotebilladdressid '.
			  'INNER JOIN '.$table_prefix.'_quotesshipads ON '.$table_prefix.'_quotes.quoteid = '.$table_prefix.'_quotesshipads.quoteshipaddressid '.
			  'INNER JOIN '.$table_prefix.'_quotescf ON '.$table_prefix.'_quotes.quoteid = '.$table_prefix.'_quotescf.quoteid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
			$qry.=" $external_code_expression";
		}
		else{
			$qry = $LVU->getListQuery($module,$external_code_expression);
			$qry = replaceSelectQuery($qry,'crmid');
		}
		VteSession::setArray(array($module."_offline", 'query_unique'), $qry);
		VteSession::setArray(array($module."_offline", 'query_fieldnames'), $fieldnames);
		VteSession::setArray(array($module."_offline", 'query_params'), $params);
	}
	$qry = VteSession::getArray(array($module."_offline", 'query_unique'));
	$params = VteSession::getArray(array($module."_offline", 'query_params'));
	foreach (VteSession::getArray(array($module."_offline", 'query_fieldnames')) as $fname){;		$params[] = $row[$fname];
	}
	$res=$adb->pquery($qry,$params);
	if ($res && $adb->num_rows($res)>0){
		return $adb->query_result_no_html($res,0,'crmid');
	}
	return false;
}
function crmv_is_empty_import($result){
	if (
	$result['records_created'] == 0
	&& $result['records_updated'] == 0
	&& $result['records_deleted'] == 0
	){
		return true;
	}
	return false;
}
// crmv@48699
function calculate_inventory_totals($module,$focus,$sql_file_update,$values){
	global $adb, $IUtils;
	$id = $values[0]['id'];
	$subtotal = 0;
	foreach($values as $value) {
  		$subtotal += $value['price_taxes'];
	}
	$grandTotal = $subtotal;

	// get other prices
	$res = $adb->pquery("select discount_percent, discount_amount, adjustment, s_h_amount from {$focus->table_name} where {$focus->table_index} = ?", array($id));
	$row = $adb->FetchByAssoc($res, -1, false);

	$totalinfo = array(
		'nettotal' => floatval($subtotal),
		's_h_amount' => floatval($row['s_h_amount']),
		'discount_percent' => $row['discount_percent'],
		'discount_amount' => $row['discount_amount'],
		'adjustment' => floatval($row['adjustment']),
		'taxes' => array(),
		'shtaxes' => array(),
	);

	// calculate totals
	$totalPrices = $IUtils->calcInventoryTotals($totalinfo);
	if ($totalPrices) {
		$grandTotal = $totalPrices['price_adjustment'];
	}

	$sql = "update $focus->table_name set subtotal = ?, total = ? where $focus->table_index = ?";
	$params = array($subtotal,$grandTotal,$id);
	$sql=$adb->convert2Sql($sql,$adb->flatten_array($params));
	fwrite($sql_file_update,$sql.";\n");
}
// crmv@48699e
?>
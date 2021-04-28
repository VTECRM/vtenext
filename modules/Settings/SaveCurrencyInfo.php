<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix;

$currency_name = $_REQUEST['currency_name'];
$conversion_rate= $_REQUEST['conversion_rate'];

// crmv@42266
// retrieve currency code and symbol from db
$CurrencyUtils = CurrencyUtils::getInstance();
$currInfo = $CurrencyUtils->getCurrencyInfoFromName($currency_name);
$currency_code = $currInfo['currency_code'];
$currency_symbol= $currInfo['currency_symbol'];
// crmv@42266e

if(isset($_REQUEST['currency_status']) && $_REQUEST['currency_status'] != '')
	$currency_status= $_REQUEST['currency_status'];
else
	$currency_status= 'Active';

if(isset($_REQUEST['record']) && $_REQUEST['record']!='') {
	$cur_status_res = $adb->pquery("select currency_status from ".$table_prefix."_currency_info where id=?", array($_REQUEST['record']));
	$old_cur_status = $adb->query_result($cur_status_res,0,'currency_status');

	if($currency_status != $old_cur_status && $currency_status == 'Inactive') {
		$transfer_cur_id = $_REQUEST['transfer_currency_id'];
		if($transfer_cur_id != null) transferCurrency($_REQUEST['record'], $transfer_cur_id);
	}

	$sql = "update ".$table_prefix."_currency_info set currency_name =?, currency_code =?, currency_symbol =?, conversion_rate =?,currency_status=? where id =?";
	$params = array($currency_name, $currency_code, $currency_symbol, $conversion_rate, $currency_status, $_REQUEST['record']);
} else {
	$params = array($adb->getUniqueID($table_prefix."_currency_info"), $currency_name, $currency_code, $currency_symbol, $conversion_rate, $currency_status,'0','0');
    $sql = "INSERT INTO {$table_prefix}_currency_info (id, currency_name, currency_code, currency_symbol, conversion_rate, currency_status, defaultid, deleted) values (".generateQuestionMarks($params).")";
}
$adb->pquery($sql, $params);

header("Location: index.php?module=Settings&action=CurrencyListView&parenttab=".vtlib_purify($_REQUEST['parenttab']));

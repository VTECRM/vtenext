<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

global $adb;
global $mod_strings;
global $app_strings;
global $theme;
global $table_prefix;
global $current_language;
$theme_path="themes/".$theme."/";

$delete_currency_id = $_REQUEST['id'];
$sql = "select currency_name from {$table_prefix}_currency_info where id=?";
$result = $adb->pquery($sql, array($delete_currency_id));
$delete_currencyname = $adb->query_result($result,0,"currency_name");

$sql = "select * from {$table_prefix}_currency_info where currency_status = ? and deleted=0";
$result = $adb->pquery($sql, array('Active'));
$allCurrencies = [];
$temprow = $adb->fetch_array($result);
do
{
    if($delete_currency_id != $temprow["id"]){
        $allCurrencies[] = [
            'currencyid' => $temprow["id"],
            'currencyname' => getTranslatedCurrencyString($temprow["currency_name"])
        ];
    }
}while($temprow = $adb->fetch_array($result));

$smarty = new VteSmarty();
$smarty->assign("THEME", $theme);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("APP", $app_strings);
$smarty->assign('DELETE_CURRENCY_ID', $delete_currency_id);
$smarty->assign('TRANSLATED_CURRENCY', getTranslatedCurrencyString($delete_currencyname));
$smarty->assign('ALL_CURRENCIES', $allCurrencies);

$smarty->display('Settings/CurrencyDeleteStep1.tpl');
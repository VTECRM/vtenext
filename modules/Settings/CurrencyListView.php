<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix;
global $mod_strings,$app_strings,$theme;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

$parenttab = getParentTab();
$result = $adb->query("SELECT * FROM {$table_prefix}_currency_info WHERE deleted=0");
$temprow = $adb->fetch_array($result);
$cnt=1;

$currency = Array();
do {
	$currency_element = Array();
	$currency_element['name'] = $temprow["currency_name"];
	$currency_element['code'] = $temprow["currency_code"];
	$currency_element['symbol'] = $temprow["currency_symbol"];
	$currency_element['crate'] = $temprow["conversion_rate"];
	$currency_element['status'] = $temprow["currency_status"];
	
	if($temprow["defaultid"] != '-11') {
		$currency_element['name'] = '<a href=index.php?module=Settings&action=CurrencyEditView&parenttab='.$parenttab.'&record='.$temprow["id"].'&detailview=detail_view>'.getTranslatedCurrencyString($temprow["currency_name"]).'</a>';
		$currency_element['tool']= '<a href=index.php?module=Settings&action=CurrencyEditView&parenttab='.$parenttab.'&record='.$temprow["id"].'><i class="vteicon md-sm" title="'.$app_strings['LBL_EDIT_BUTTON_LABEL'].'">create</i></a>&nbsp;<i class="vteicon md-sm md-link" onClick="fnvshobj(this,\'currencydiv\');VTE.Settings.Currency.deleteCurrency(\''.$temprow['id'].'\');" title="'.$app_strings['LBL_DELETE_BUTTON_LABEL'].'">delete</i>';
	} else {
		$currency_element['tool']= '';
	}
	$currency[] = $currency_element; 
	$cnt++;
} while($temprow = $adb->fetch_array($result));

$smarty->assign("PARENTTAB",$parenttab);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("MOD",$mod_strings);
$smarty->assign("CURRENCY_LIST",$currency);

if($_REQUEST['ajax'] !='')
	$smarty->display("CurrencyListViewEntries.tpl");
else
	$smarty->display("CurrencyListView.tpl");

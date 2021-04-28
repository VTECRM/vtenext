<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $theme;
global $adb, $table_prefix;

$result = $adb->query("select tandc from ".$table_prefix."_inventory_tandc");
$inventory_tandc = $adb->query_result($result,0,'tandc');

$smarty = new VteSmarty();

if(!isset($_REQUEST['inv_terms_mode'])) {
	$inventory_tandc = nl2br($inventory_tandc);
}
	
if (isset($inventory_tandc)) {
	$smarty->assign("INV_TERMSANDCONDITIONS",$inventory_tandc);
}

if(isset($_REQUEST['inv_terms_mode']) && $_REQUEST['inv_terms_mode'] != '') {
	$smarty->assign("INV_TERMS_MODE",$_REQUEST['inv_terms_mode']);
} else {
	$smarty->assign("INV_TERMS_MODE",'view');
}
	
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty->assign("THEME", $theme);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

$smarty->display("Settings/InventoryTerms.tpl");

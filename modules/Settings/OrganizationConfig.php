<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $theme;
global $adb, $table_prefix;

$result = $adb->query("SELECT * FROM {$table_prefix}_organizationdetails");

$organization_name = $adb->query_result($result,0,'organizationname');
$organization_address= $adb->query_result($result,0,'address');
$organization_city = $adb->query_result($result,0,'city');
$organization_state = $adb->query_result($result,0,'state');
$organization_code = $adb->query_result($result,0,'code');
$organization_country = $adb->query_result($result,0,'country');
$organization_phone = $adb->query_result($result,0,'phone');
$organization_fax = $adb->query_result($result,0,'fax');
$organization_website = $adb->query_result($result,0,'website');
//Handle for allowed organation logo/logoname likes UTF-8 Character
$organization_logo = decode_html($adb->query_result($result,0,'logo'));
$organization_logoname = decode_html($adb->query_result($result,0,'logoname'));

$smarty = new VteSmarty();

if (isset($organization_name))
	$smarty->assign("ORGANIZATIONNAME",$organization_name);
if (isset($organization_address))
	$smarty->assign("ORGANIZATIONADDRESS",$organization_address);
if (isset($organization_city))
	$smarty->assign("ORGANIZATIONCITY",$organization_city);
if (isset($organization_state))
	$smarty->assign("ORGANIZATIONSTATE",$organization_state);
if (isset($organization_code))
	$smarty->assign("ORGANIZATIONCODE",$organization_code);
if (isset($organization_country))
    $smarty->assign("ORGANIZATIONCOUNTRY",$organization_country);
if (isset($organization_phone))
	$smarty->assign("ORGANIZATIONPHONE",$organization_phone);
if (isset($organization_fax))
	$smarty->assign("ORGANIZATIONFAX",$organization_fax);
if (isset($organization_website))
	$smarty->assign("ORGANIZATIONWEBSITE",$organization_website);
if (isset($organization_logo))
	$smarty->assign("ORGANIZATIONLOGO",$organization_logo);
	
//------------------crmvillage 510 release start----------------------
$organization_banking = $adb->query_result($result,0,'crmv_banking');
$organization_vat_registration_number = $adb->query_result($result,0,'crmv_vat_registration_number');
$organization_rea = $adb->query_result($result,0,'crmv_rea');
$organization_issued_capital = $adb->query_result($result,0,'crmv_issued_capital');

if (isset($organization_banking))
	$smarty->assign("ORGANIZATIONBANKING",$organization_banking);
if (isset($organization_vat_registration_number))
	$smarty->assign("ORGANIZATIONVAT",$organization_vat_registration_number);

if (isset($organization_rea))
	$smarty->assign("ORGANIZATIONREA",$organization_rea);

if (isset($organization_issued_capital))
	$smarty->assign("ORGANIZATIONCAPITAL",$organization_issued_capital);
//------------------crmvillage 510 release stop-----------------------

$path = "storage/logo";
$dir_handle = @opendir($path) or die("Unable to open directory $path");

while ($file = readdir($dir_handle)) {
	$filetyp =str_replace(".",'',strtolower(substr($file, -4)));
	if($organization_logoname==$file) {    
        if ($filetyp == 'jpeg' OR $filetyp == 'jpg' OR $filetyp == 'png') {
			if($file!="." && $file!="..") {
				$organization_logopath= $path;
				$logo_name=$file;
			}
        }
	}	
}
closedir($dir_handle);

if (isset($organization_logopath))
	$smarty->assign("ORGANIZATIONLOGOPATH",$path);
if (isset($organization_logoname))
	$smarty->assign("ORGANIZATIONLOGONAME",$logo_name);
	
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

$smarty->display('Settings/CompanyInfo.tpl');

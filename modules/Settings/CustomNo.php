<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('user_privileges/CustomQuotesNo.php');
require_once('user_privileges/CustomSalesOrderNo.php');
global $app_strings;
global $mod_strings;
global $currentModule;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
global $current_language;

$smarty = new VteSmarty();

$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH",$image_path);

/*
if($singlepane_view == 'true')
	$viewstatus = 'enabled';
else
	$viewstatus = 'disabled';

$smarty->assign("ViewStatus", $viewstatus);
*/
$mode = $_REQUEST["mode"];

if ($mode=="quotes")
{
   $smarty->assign("str", $quo_str);
   $smarty->assign("no", $quo_no);
} else {
   $smarty->assign("str", $sal_str);
   $smarty->assign("no", $sal_no);
}

$smarty->assign("MODE", $mode);

$smarty->display('Settings/CustomNo.tpl');

?>
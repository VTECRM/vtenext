<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	

global $mod_strings;
global $app_strings;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

require_once('include/home.php');

// crmv@172864
$stuffid = $_REQUEST['stuffid'];
$stufftype = $_REQUEST['stufftype'];
// crmv@172864e

$homeObj=new Homestuff();
$smarty=new VteSmarty();

$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$homeselectedframe = $homeObj->getSelectedStuff($stuffid,$stufftype);

$smarty->assign("tablestuff",$homeselectedframe);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->display("Home/MainHomeBlock.tpl");

?>
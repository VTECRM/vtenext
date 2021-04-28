<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Portal/Portal.php');

global $app_strings,$mod_strings,$theme;
global $adb, $table_prefix;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

$smarty->assign("APP", $app_strings);
$smarty->assign("MOD", $mod_strings);

$portalid = intval($_REQUEST['record']);
$portalname = '';
$portalurl = '';

if ($portalid > 0) {
	$result = $adb->pquery("select * from ".$table_prefix."_portal where portalid = ?", array($portalid));
	$portalname = $adb->query_result($result,0,'portalname');
	$portalurl = $adb->query_result($result,0,'portalurl');	
	/* to remove http:// from portal url*/
	$portalurl = preg_replace("/http:\/\//i","",$portalurl);	
}

$smarty->assign('PORTALID', $portalid);
$smarty->assign('PORTALNAME', $portalname);
$smarty->assign('PORTALURL', $portalurl);

$smarty->display('modules/Portal/EditPopup.tpl');
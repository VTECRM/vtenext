<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $theme;

$groupInfo = getAllGroupInfo();

$list_header = array($mod_strings['LBL_LIST_TOOLS'],$mod_strings['LBL_GROUP_NAME'],$mod_strings['LBL_DESCRIPTION']);
$return_data = array();

foreach($groupInfo as $groupId=>$groupInfo) {
	$standCustFld = array();
	$standCustFld['groupid']= $groupId;	
	$standCustFld['groupname']= $groupInfo[0];
	$standCustFld['description']= $groupInfo[1];
	$return_data[]=$standCustFld;
}

$smarty = new VteSmarty();

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty->assign("LIST_HEADER",$list_header);
$smarty->assign("LIST_ENTRIES",$return_data);
$smarty->assign("PROFILES", $standCustFld);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign('GRPCNT', count($return_data));
$smarty->assign("THEME", $theme); 

$smarty->display("ListGroup.tpl");

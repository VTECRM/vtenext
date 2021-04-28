<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $app_strings, $mod_strings, $theme, $current_language;
global $adb, $table_prefix;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

$smarty->assign("UMOD", $mod_strings);

$smod_strings = return_module_language($current_language,'Settings');
$smarty->assign("APP", $app_strings);
$smarty->assign("MOD", $smod_strings);
$smarty->assign("MODULE", 'Settings');
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("PARENTTAB", $_REQUEST['parenttab']);
$smarty->assign("THEME", $theme);

$return_data=array();

$sql = "SELECT * FROM {$table_prefix}_emailtemplates WHERE parentid = 0 ORDER BY templateid DESC"; // crmv@151466
$result = $adb->query($sql);
while ($temprow = $adb->fetch_array($result)) {
	$templatearray=array();
	$templatearray['templatename'] = $temprow["templatename"];
	$templatearray['templateid'] = $temprow["templateid"];
	$templatearray['description'] = $temprow["description"];
	$templatearray['foldername'] = $temprow["foldername"];
	$return_data[]=$templatearray;
}
$smarty->assign("TEMPLATES",$return_data);

$smarty->display("ListEmailTemplates.tpl");

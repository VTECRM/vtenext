<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $mod_strings;
global $app_strings;
global $app_list_strings;

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

// Look up for modules for which sharing access is enabled.
// NOTE: Accounts and Contacts has been couple, so we need to elimiate Contacts also
//crmv@13979	crmv@47243
$othermodules = getSharingModuleList(Array('Messages'));
//crmv@13979e	crmv@47243e
if(!empty($othermodules)) {
	foreach($othermodules as $moduleresname) {
		$custom_access[$moduleresname] = getAdvSharingRuleList($moduleresname);
	}
}

$smarty->assign("MODSHARING", $custom_access);

$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME", $theme);
$smarty->display("AdvRuleDetailView.tpl");
?>
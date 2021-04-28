<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;
global $mod_strings, $app_strings, $current_language, $theme;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("UMOD", $mod_strings);
$smod_strings = return_module_language($current_language,'Settings');
$smarty->assign("MOD", $smod_strings);
$smarty->assign("MODULE", 'Settings');
$smarty->assign("IMAGE_PATH", $image_path);

if(isset($_REQUEST['templateid']) && $_REQUEST['templateid']!='') {
	$tempid = $_REQUEST['templateid'];
	$sql = "SELECT * FROM {$table_prefix}_emailtemplates WHERE templateid=?";
	$result = $adb->pquery($sql, array($tempid));
	$emailtemplateResult = $adb->fetch_array($result);
}
$smarty->assign("FOLDERNAME", $emailtemplateResult["foldername"]);

$smarty->assign("TEMPLATENAME", $emailtemplateResult["templatename"]);
$smarty->assign("DESCRIPTION", $emailtemplateResult["description"]);
$smarty->assign("TEMPLATEID", $emailtemplateResult["templateid"]);

$smarty->assign("SUBJECT", $emailtemplateResult["subject"]);
$smarty->assign("BODY", decode_html($emailtemplateResult["body"]));

$smarty->assign("TEMPLATETYPE", $emailtemplateResult["templatetype"]);	//crmv@22700
//crmv@80155
$smarty->assign("USE_SIGNATURE", intval($emailtemplateResult["use_signature"]));
$smarty->assign("OVERWRITE_MESSAGE", intval($emailtemplateResult["overwrite_message"]));

$res = $adb->pquery("SELECT fieldid FROM {$table_prefix}_field WHERE fieldname = ?", array('bu_mc'));
if ($res && $adb->num_rows($res) > 0) {
	$smarty->assign("BU_MC_ENABLED", true);
	$smarty->assign("BU_MC", implode(', ', explode(' |##| ', $emailtemplateResult["bu_mc"])));
}

if ($preview) {
	$smarty->display("PreviewEmailTemplate.tpl");
} else {
	$smarty->display("DetailViewEmailTemplate.tpl");
}
//crmv@80155e

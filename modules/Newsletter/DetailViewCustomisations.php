<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@104558
require_once('data/Tracker.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/database/PearDatabase.php');
global $adb;
global $log;
global $mod_strings;
global $app_strings;
global $current_language;
global $theme;
global $table_prefix;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$log->info("Inside Email Template Detail View");

if(isset($focus->column_fields['templateemailid']) && $focus->column_fields['templateemailid']!='')
{
	$log->info("The templateid is set");
	$tempid = $focus->column_fields['templateemailid'];
	$sql = "select * from ".$table_prefix."_emailtemplates where templateid=?";
	$result = $adb->pquery($sql, array($tempid));
	$emailtemplateResult = $adb->fetch_array($result);
}
$smarty->assign("FOLDERNAME", $emailtemplateResult["foldername"]);

$smarty->assign("TEMPLATENAME", $emailtemplateResult["templatename"]);
$smarty->assign("DESCRIPTION", $emailtemplateResult["description"]);
$smarty->assign("TEMPLATEID", $emailtemplateResult["templateid"]);

$smarty->assign("SUBJECT", $emailtemplateResult["subject"]);
// crmv@153002 - body loaded in iframe

$smarty->assign("TEMPLATETYPE", $emailtemplateResult["templatetype"]);	//crmv@22700
//crmv@80155
$smarty->assign("USE_SIGNATURE", intval($emailtemplateResult["use_signature"]));
$smarty->assign("OVERWRITE_MESSAGE", intval($emailtemplateResult["overwrite_message"]));

$res = $adb->query("select * from ".$table_prefix."_field where fieldname = 'bu_mc'");
if ($res && $adb->num_rows($res) > 0) {
	$smarty->assign("BU_MC_ENABLED", true);
	$smarty->assign("BU_MC", implode(', ', explode(' |##| ', $emailtemplateResult["bu_mc"])));
}

//$smarty->display("DetailViewEmailTemplate.tpl");
//crmv@104558e
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// ITS4YOU TT0093 VlMe N

require_once('data/Tracker.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/database/PearDatabase.php');
global $adb;
global $log;
global $mod_strings;
global $app_strings;
global $current_language, $current_user;
global $theme;
global $table_prefix;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$record = $_REQUEST["record"];

$sql = "SELECT setype FROM ".$table_prefix."_crmentity WHERE crmid = '".$record."'";
$relmodule = $adb->getOne($sql,0,"setype");

$log->info("Inside Email Template Detail View");

$smarty = new VteSmarty();

$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("MOD", $mod_strings);

$smarty->assign("MODULE", $relmodule);
$smarty->assign("IMAGE_PATH", $image_path);

$smarty->assign("ID", $_REQUEST["record"]);

require('user_privileges/requireUserPrivileges.php'); // crmv@39110

if(is_dir("include/mpdf")) //crmv@30066
{
	if($is_admin == true || $profileGlobalPermission[2]==0 || $profileGlobalPermission[1]==0 || $profileTabsPermission[getTabId("PDFMaker")]==0)
		$smarty->assign("ENABLE_PDFMAKER",'true');
}

$smarty->assign('PDFMAKER_MOD',return_module_language($current_language,"PDFMaker"));

if(!VteSession::hasKey("template_languages") || VteSession::get("template_languages")=="") {
	$temp_res = $adb->query("SELECT label, prefix FROM ".$table_prefix."_language WHERE active=1");
	while($temp_row = $adb->fetchByAssoc($temp_res)) {
		$template_languages[$temp_row["prefix"]]=$temp_row["label"];
	}
	VteSession::set("template_languages", $template_languages);
}

$smarty->assign('TEMPLATE_LANGUAGES',VteSession::get("template_languages"));
$smarty->assign('CURRENT_LANGUAGE',$current_language);


$userid=0;
if(VteSession::hasKey("authenticated_user_id"))
$userid = VteSession::get("authenticated_user_id");

$status_sql="SELECT * FROM ".$table_prefix."_pdfmaker_userstatus
           INNER JOIN ".$table_prefix."_pdfmaker ON ".$table_prefix."_pdfmaker.templateid = ".$table_prefix."_pdfmaker_userstatus.templateid
           WHERE userid=? AND module=?";
$status_res=$adb->pquery($status_sql,array($userid,$relmodule));
$status_arr = array();
if($adb->num_rows($status_res)>0)
{
	while($status_row = $adb->fetchByAssoc($status_res))
	{
		$status_arr[$status_row["templateid"]]["is_active"] = $status_row["is_active"];
		$status_arr[$status_row["templateid"]]["is_default"] = $status_row["is_default"];
	}
}
$temp_sql = "SELECT templateid, filename AS templatename
           FROM ".$table_prefix."_pdfmaker
           WHERE module = '".$relmodule."' ORDER BY filename";

$temp_result = $adb->query($temp_sql);

$status_template=array();
$set_default=false;
while($temp_row = $adb->fetchByAssoc($temp_result)){
	if(isset($status_arr[$temp_row['templateid']]))
	{
		if($status_arr[$temp_row['templateid']]["is_active"]=="0")
			continue;
		elseif($status_arr[$temp_row['templateid']]["is_default"]=="1")
		{
			$status_template[$temp_row['templateid']] = $temp_row['templatename'];
		}
		else
			$use_template[$temp_row['templateid']] = $temp_row['templatename'];
	} else
		$use_template[$temp_row['templateid']] = $temp_row['templatename'];
}

if(count($status_template)>0)
	$use_template = (array) $status_template + (array) $use_template;

if(count($use_template)>0)
	$no_templates_exist = 0;
else
	$no_templates_exist = 1;

$smarty->assign('CRM_TEMPLATES',$use_template);
$smarty->assign('CRM_TEMPLATES_EXIST',$no_templates_exist);

// crmv@195354
$VTEP = VTEProperties::getInstance();
$smarty->assign('ENABLE_RTF', $VTEP->getProperty('modules.pdfmaker.enable_rtf'));
// crmv@195354e

$category = getParentTab();
$smarty->assign("CATEGORY",$category);
$smarty->display("modules/PDFMaker/PDFMakerActions.tpl");
?>
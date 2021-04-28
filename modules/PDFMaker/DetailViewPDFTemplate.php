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

$log->info("Inside Email Template Detail View");

$smarty = new VteSmarty();

$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("MOD", $mod_strings);

$smarty->assign("MODULE", 'Tools');
$smarty->assign("IMAGE_PATH", $image_path);

if(isset($_REQUEST['templateid']) && $_REQUEST['templateid']!='')
{
  	$log->info("The templateid is set");
  	$tempid = $_REQUEST['templateid'];

  	$sql = "SELECT ".$table_prefix."_pdfmaker.*, ".$table_prefix."_pdfmaker_settings.*
              FROM ".$table_prefix."_pdfmaker 
              LEFT JOIN ".$table_prefix."_pdfmaker_settings
                ON ".$table_prefix."_pdfmaker_settings.templateid = ".$table_prefix."_pdfmaker.templateid
             WHERE ".$table_prefix."_pdfmaker.templateid=?";
        
  	$result = $adb->pquery($sql, array($tempid));
  	$pdftemplateResult = $adb->fetch_array($result);

    $smarty->assign("FILENAME", $pdftemplateResult["filename"]);
    $smarty->assign("DESCRIPTION", $pdftemplateResult["description"]);
    $smarty->assign("TEMPLATEID", $pdftemplateResult["templateid"]);
    $smarty->assign("MODULENAME", getTranslatedString($pdftemplateResult["module"],$pdftemplateResult["module"]));	//crmv@25443
    $smarty->assign("BODY", decode_html($pdftemplateResult["body"]));
    $smarty->assign("HEADER", decode_html($pdftemplateResult["header"]));
    $smarty->assign("FOOTER", decode_html($pdftemplateResult["footer"]));
    
    $smarty->assign("SELECT_FORMAT", $pdftemplateResult["format"]);
    $smarty->assign("SELECT_ORIENTATION", $mod_strings[$pdftemplateResult["orientation"]]);
    
    $sql = "SELECT is_active, is_default FROM ".$table_prefix."_pdfmaker_userstatus WHERE templateid=? AND userid=?";
    $result = $adb->pquery($sql,array($_REQUEST['templateid'],$current_user->id));
    if($adb->num_rows($result)>0)
    {
      $status_row = $adb->fetchByAssoc($result);      
      if($status_row["is_active"]=="1")
      {
        $is_active = $app_strings["Active"];
        $activateButton = $mod_strings["LBL_SETASINACTIVE"];  
      }
      else
      {
        $is_active = $app_strings["Inactive"];
        $activateButton = $mod_strings["LBL_SETASACTIVE"];
      }
      
      if($status_row["is_default"]=="1")
      {
        $is_default = '<i class="vteicon checkok md-sm" title="yes">check</i>';
        $defaultButton = $mod_strings["LBL_UNSETASDEFAULT"];
      }
      else
      {
        $is_default = '<i class="vteicon checkko md-sm" title="no">clear</i>';
        $defaultButton = $mod_strings["LBL_SETASDEFAULT"];
      }
    }
    else
    {
      $is_active = $app_strings["Active"];
      $is_default = '<i class="vteicon checkko md-sm" title="no">clear</i>';
      $activateButton = $mod_strings["LBL_SETASINACTIVE"];
      $defaultButton = $mod_strings["LBL_SETASDEFAULT"];
    }
    
    $smarty->assign("IS_ACTIVE", $is_active);
    $smarty->assign("IS_DEFAULT", $is_default);  
    $smarty->assign("ACTIVATE_BUTTON", $activateButton);
    $smarty->assign("DEFAULT_BUTTON", $defaultButton);
}

//PDF MARGIN SETTINGS
$Margins = array("top" => $pdftemplateResult["margin_top"], 
                 "bottom" => $pdftemplateResult["margin_bottom"], 
                 "left" => $pdftemplateResult["margin_left"], 
                 "right" => $pdftemplateResult["margin_right"]);


$smarty->assign("MARGINS",$Margins);
include_once("version.php");

//$sql = "SELECT version_type FROM vte_pdfmaker_license";
//$version_type = ucfirst($adb->getOne($sql,0,"version_type"));
$smarty->assign("MODULE",$currentModule); //crmv

$smarty->assign("VERSION",$version_type." ".$version);

if(isPermitted($currentModule,"EditView") == 'yes')
{
  $smarty->assign("EDIT","permitted");
	
  $smarty->assign("EXPORT","yes");
  $smarty->assign("IMPORT","yes");
}

if(isPermitted($currentModule,"Delete") == 'yes' && $version_type != "deactivate")
{
	$smarty->assign("DELETE","permitted");
}

$category = getParentTab();
$smarty->assign("CATEGORY",$category);
$smarty->display("modules/PDFMaker/DetailViewPDFTemplate.tpl");

?>
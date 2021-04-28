<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// ITS4YOU TT0093 VlMe N

require_once('include/database/PearDatabase.php');

global $adb, $current_user,$table_prefix;

$smarty = new VteSmarty();

$orderby="templateid";
$dir="asc";

if(isset($_REQUEST["dir"]) && $_REQUEST["dir"]=="desc")
  $dir="desc";

if(isset($_REQUEST["orderby"])){
  switch($_REQUEST["orderby"]){
    case "name":
      $orderby="filename";            
      break;
    
    case "module":
      $orderby="module";      
      break;
      
    case "description":
      $orderby="description";      
      break;  
  }
}

include_once("version.php");

//$version_type = 'professional';	//crmv
$license_key = '';

$smarty->assign("VERSION_TYPE",$version_type);
$smarty->assign("VERSION",ucfirst($version_type)." ".$version);
$smarty->assign("LICENSE_KEY",$license_key);

$to_update = "false";
/*
$sql = "SELECT id FROM vte_pdfmaker_releases WHERE updated=0";
$nums = $adb->num_rows($adb->query($sql));
if($nums > 0)
  $to_update = "true";  
*/ 
$smarty->assign("TO_UPDATE",$to_update);  

$status_sql="SELECT * FROM ".$table_prefix."_pdfmaker_userstatus  
             INNER JOIN ".$table_prefix."_pdfmaker ON ".$table_prefix."_pdfmaker.templateid = ".$table_prefix."_pdfmaker_userstatus.templateid
             WHERE userid=?"; 
$status_res=$adb->pquery($status_sql,array($current_user->id));
$status_arr = array();
while($status_row = $adb->fetchByAssoc($status_res))
{
  $status_arr[$status_row["templateid"]]["is_active"] = $status_row["is_active"];
  $status_arr[$status_row["templateid"]]["is_default"] = $status_row["is_default"]; 
}

$sql = "SELECT templateid, description, filename, module 
        FROM ".$table_prefix."_pdfmaker 
        ORDER BY ".$orderby." ".$dir;
$result = $adb->pquery($sql, array());

$edit="Edit  ";
$del="Del  ";
$bar="  | ";
$cnt=1;

$return_data = Array();
$num_rows = $adb->num_rows($result);

$editing = false;
$deleting = false;
if(isPermitted($currentModule,"EditView") == 'yes' )
{
	$smarty->assign("EXPORT","yes");
    if ($version_type != "deactivate")
    {
        $editing = true;
    	$smarty->assign("EDIT","permitted");
        $smarty->assign("IMPORT","yes");
    }
}

if(isPermitted($currentModule,"Delete") == 'yes' && $version_type != "deactivate"){
	$deleting = true;
	$smarty->assign("DELETE","permitted");
}

for($i=0;$i < $num_rows; $i++)
{	
  $pdftemplatearray=array();
  $suffix="";
  $templateid = $adb->query_result($result,$i,'templateid');
  if(isset($status_arr[$templateid]))
  {
    if($status_arr[$templateid]["is_active"]=="0")
      $pdftemplatearray['status']=0;
    else
    {
      $pdftemplatearray['status']=1;
      if($status_arr[$templateid]["is_default"]=="1")
        $suffix=" ".$mod_strings["LBL_DEFAULT"];    
    }
  }
  else
    $pdftemplatearray['status']=1;
    
  $pdftemplatearray['status_lbl'] = ($pdftemplatearray['status']==1 ? $app_strings["Active"] : $app_strings["Inactive"]);  
  
  $pdftemplatearray['templateid'] = $templateid;
  $pdftemplatearray['description'] = $adb->query_result($result,$i,'description');
  $pdftemplatearray['module'] = getTranslatedString($adb->query_result($result,$i,'module'),$adb->query_result($result,$i,'module'));	//crmv@25443
  $pdftemplatearray['filename'] = "<a href=\"index.php?action=DetailViewPDFTemplate&module=PDFMaker&templateid=".$templateid."&parenttab=Tools\">".$adb->query_result($result,$i,'filename').$suffix."</a>";
  if($editing) {
	$pdftemplatearray['edit'] = "<a href=\"index.php?action=EditPDFTemplate&module=PDFMaker&templateid=".$templateid."&parenttab=Tools\">".$app_strings["LBL_EDIT_BUTTON"]."</a> | "
                             ."<a href=\"index.php?action=EditPDFTemplate&module=PDFMaker&templateid=".$templateid."&isDuplicate=true&parenttab=Tools\">".$app_strings["LBL_DUPLICATE_BUTTON"]."</a>";
  }
  $return_data []= $pdftemplatearray;	
}

require_once('include/utils/UserInfoUtil.php');
global $app_strings;
global $mod_strings;
global $theme,$default_charset;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

global $current_language;

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("PARENTTAB", getParentTab());
$smarty->assign("IMAGE_PATH",$image_path);

$smarty->assign("ORDERBY",$orderby);
$smarty->assign("DIR",$dir);

$smarty->assign("PDFTEMPLATES",$return_data);
$category = getParentTab();
$smarty->assign("CATEGORY",$category);

if(is_admin($current_user)){
  $smarty->assign('IS_ADMIN','1');
}

$smarty->assign('MODULE', $currentModule);	//crmv@24718
    
$smarty->display("modules/PDFMaker/ListPDFTemplates.tpl");

if ($_REQUEST["deactivate"]=="failed")
{
    echo "<script>alert('".$mod_strings["LBL_DEACTIVATE_ERROR"]."');</script>";
}

?>
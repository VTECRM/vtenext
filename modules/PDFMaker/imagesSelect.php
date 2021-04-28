<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/utils/utils.php');
global $app_strings,$current_user,$theme,$adb,$table_prefix;

$image_path = 'themes/'.$theme.'/images/';
$language = VteSession::get('authenticated_user_language');
$pdf_strings = return_module_language($language, "PDFMaker");

$id = $_REQUEST["return_id"];
$sql="SELECT CASE WHEN ".$table_prefix."_products.productid != '' THEN ".$table_prefix."_products.productname ELSE ".$table_prefix."_service.servicename END AS productname, 
        ".$table_prefix."_inventoryproductrel.productid, ".$table_prefix."_inventoryproductrel.sequence_no, ".$table_prefix."_attachments.attachmentsid, name, path
      FROM ".$table_prefix."_inventoryproductrel
      LEFT JOIN ".$table_prefix."_seattachmentsrel
        ON ".$table_prefix."_seattachmentsrel.crmid=".$table_prefix."_inventoryproductrel.productid
      LEFT JOIN ".$table_prefix."_attachments 
        ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_seattachmentsrel.attachmentsid
      LEFT JOIN ".$table_prefix."_products 
        ON ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid
      LEFT JOIN ".$table_prefix."_service 
        ON ".$table_prefix."_service.serviceid=".$table_prefix."_inventoryproductrel.productid    
      WHERE ".$table_prefix."_inventoryproductrel.id=? ORDER BY ".$table_prefix."_inventoryproductrel.sequence_no";
$res=$adb->pquery($sql,array($id));
$products=array();
while($row=$adb->fetchByAssoc($res)) {
  $products[$row["productid"]."#_#".$row["productname"]."#_#".$row["sequence_no"]][$row["attachmentsid"]]["path"]=$row["path"];
  $products[$row["productid"]."#_#".$row["productname"]."#_#".$row["sequence_no"]][$row["attachmentsid"]]["name"]=$row["name"];    
}

$saved_sql="SELECT productid, sequence, attachmentid, width, height FROM ".$table_prefix."_pdfmaker_images WHERE crmid=?";
$saved_res=$adb->pquery($saved_sql,array($id));
$saved_products=array();
$saved_wh=array();
while($saved_row=$adb->fetchByAssoc($saved_res)){
  $saved_products[$saved_row["productid"]."_".$saved_row["sequence"]] = $saved_row["attachmentid"];                                
  $saved_wh[$saved_row["productid"]."_".$saved_row["sequence"]]["width"] = ($saved_row["width"]>0 ? $saved_row["width"] : "");
  $saved_wh[$saved_row["productid"]."_".$saved_row["sequence"]]["height"] = ($saved_row["height"]>0 ? $saved_row["height"] : "");  
}

$imgHTML="";
foreach($products as $productnameid=>$data)
{
  list($productid, $productname, $seq) = explode("#_#", $productnameid, 3);
  $prodImg="";
  $i=0;
  $noCheck=' checked="checked" ';
  $width="100";
  $height="";
  foreach($data as $attid=>$images)
  {
    if($attid!="")
    {   
      if($i==3)
        $prodImg.="</tr><tr>";  
      $checked="";
      if(isset($saved_products[$productid."_".$seq]) && $saved_products[$productid."_".$seq]==$attid) {
        $checked=' checked="checked" ';
        $noCheck="";
        $width=$saved_wh[$productid."_".$seq]["width"];
        $height=$saved_wh[$productid."_".$seq]["height"];        
      }
      $prodImg.='<td valign="middle"><input type="radio" name="img_'.$productid.'_'.$seq.'" value="'.$attid.'"'.$checked.'/>
                 <img align="absmiddle" src="'.$images["path"].$attid.'_'.$images["name"].'" alt="'.$images["name"].'" title="'.$images["name"].'" style="max-width:50px;max-height:50px;">
                 </td>';
      $i++;
    }   
  }   
     
  $imgHTML.='<tr><td class="detailedViewHeader" style="padding-top:5px;padding-bottom:5px;"><b>'.$productname.'</b>';
  if($i>0) {
    $imgHTML.='&nbsp;&nbsp;&nbsp;<input type="text" maxlength="3" name="width_'.$productid.'_'.$seq.'" value="'.$width.'" class="small" style="width:40px">&nbsp;x&nbsp;
              <input type="text" maxlength="3" name="height_'.$productid.'_'.$seq.'" value="'.$height.'" class="small" style="width:40px">';
  }           
  $imgHTML.='</td></tr>
             <tr><td class="dvtCellInfo">';  
    $imgHTML.='<table cellpadding="0" cellspacing="1"><tr><td><input type="radio" name="img_'.$productid.'_'.$seq.'" value="no_image"'.$noCheck.'/>';
    $imgHTML.='<img src="'.resourcever('denied.gif').'" width="30" align="absmiddle" title="'.$pdf_strings["LBL_NO_IMAGE"].'" alt="'.$pdf_strings["LBL_NO_IMAGE"].'"/></td>';  
  $imgHTML.=$prodImg."</tr></table>
            </td></tr>"; 
}
// crmv@171581
echo '
<form name="PDFImagesForm" method="post" action="index.php">
<input type="hidden" name="__csrf_token" value="'.RequestHandler::getCSRFToken().'">
<input type="hidden" name="module" value="PDFMaker" />
<input type="hidden" name="pid" value="'.$_REQUEST["return_id"].'" />
<table border=0 cellspacing=0 cellpadding=5 width=100%>
<tr>
	<td width="100%" align="left" class="small level3Bg" id="PDFImagesDiv_Handle" style="cursor:move;">'.$pdf_strings["LBL_PRODUCT_IMAGE"].'</td>
</tr>
</table>
<div class="closebutton" onclick="hideFloatingDiv(\'PDFImagesDiv\')"></div>
<div style="max-height:350px; overflow:auto;">
	<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
		<tr><td class="small">
			<table border=0 cellspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
				'.$imgHTML.'
			</table>
		</td></tr>
	</table>
</div>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
<tr><td align=center class="small">
	<input type="button" value="'.$app_strings["LBL_SAVE_BUTTON_LABEL"].'" class="crmbutton small create" onclick="VTE.PDFMakerActions.savePDFImages();"/>&nbsp;&nbsp;
	<input type="button" name="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" value="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" class="crmbutton small cancel" onclick="hideFloatingDiv(\'PDFImagesDiv\')" />
</td></tr>
</table>
</form>'; 
exit;
?>
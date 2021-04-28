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
        ".$table_prefix."_inventoryproductrel.sequence_no, ".$table_prefix."_inventoryproductrel.productid
      FROM ".$table_prefix."_inventoryproductrel
      LEFT JOIN ".$table_prefix."_products 
        ON ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid 
      LEFT JOIN ".$table_prefix."_service 
        ON ".$table_prefix."_service.serviceid=".$table_prefix."_inventoryproductrel.productid
      WHERE id=? order by sequence_no";
$res=$adb->pquery($sql,array($id));

$saved_sql="SELECT productid, sequence, show_header, show_subtotal FROM ".$table_prefix."_pdfmaker_breakline WHERE crmid=?";
$saved_res=$adb->pquery($saved_sql,array($id));
$saved_products=array();
while($saved_row=$adb->fetchByAssoc($saved_res)){
  $saved_products[$saved_row["productid"]."_".$saved_row["sequence"]] = $saved_row["sequence"];
  
  $header_checked="";
  $subtotal_checked="";
  if($saved_row["show_header"]=="1")
    $header_checked=' checked="checked"';      
  if($saved_row["show_subtotal"]=="1")
    $subtotal_checked=' checked="checked"';  
}

$products="";
$num_rows = $adb->num_rows($res);
$checked_no=0;
for($i=0;$i<$num_rows;$i++)
{
	$seq=$adb->query_result($res,$i,"sequence_no");
	$productid = $adb->query_result($res,$i,"productid"); 
  
  $checked="";
  if(isset($saved_products[$productid."_".$seq])) {
    $checked=' checked="checked" ';
    $checked_no++;
  }
  
  $product_name= $adb->query_result($res,$i,"productname");
  $products.='<tr>
              <td class="dvtCellInfo" width="5%"><input type="checkbox" name="'.$productid.'_'.$seq.'"'.$checked.' onClick="VTE.PDFMakerActions.checkIfAny();"/></td>
              <td class="dvtCellInfo" width="95%">'.$product_name.'</td>
              </tr>';
}

if($checked_no==0) {
  $header_checked=' disabled="disabled"';
  $subtotal_checked=' disabled="disabled"';
}
// crmv@171581
echo '
<form name="PDFBreaklineForm" method="post" action="index.php">
<input type="hidden" name="__csrf_token" value="'.RequestHandler::getCSRFToken().'">
<input type="hidden" name="module" value="PDFMaker" />
<input type="hidden" name="pid" value="'.$_REQUEST["return_id"].'" />
<table border=0 cellspacing=0 cellpadding=5 width=100%>
<tr>
	<td width="100%" align="left" class="small level3Bg" id="PDFBreaklineDiv_Handle" style="padding:5px; cursor:move;">'.$pdf_strings["LBL_PRODUCT_BREAKLINE"].'</td>
</tr>
</table>
<div class="closebutton" onclick="hideFloatingDiv(\'PDFBreaklineDiv\')"></div>
<div style="max-height:350px; overflow:auto;">
	<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
		<tr><td class="small">
			<table border=0 cellspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
				<tr>
				  <td class="detailedViewHeader" style="padding-top:5px;padding-bottom:5px;"><img src="'.resourcever('enabled.gif').'" border="0" align="absmiddle" alt="Checkboxes"/></td>
				  <td class="detailedViewHeader" style="padding-top:5px;padding-bottom:5px;"><b>'.$pdf_strings["LBL_GLOBAL_SETTINGS"].'</b></td>
				</tr>
				<tr>
				  <td class="dvtCellInfo" width="5%"><input type="checkbox" name="show_header"'.$header_checked.'/></td>
				  <td class="dvtCellInfo" width="95%">'.$pdf_strings["LBL_SHOW_HEADER"].'</td>
				</tr>
				<tr>
				  <td class="dvtCellInfo" width="5%"><input type="checkbox" name="show_subtotal"'.$subtotal_checked.'/></td>
				  <td class="dvtCellInfo" width="95%">'.$pdf_strings["LBL_SHOW_SUBTOTAL"].'</td>
				</tr>
			</table>
		</td></tr>
	</table>
	<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
	    <tr><td class="small">
	        <table border=0 cellspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
	            <tr>
	              <td class="detailedViewHeader" style="padding-top:5px;padding-bottom:5px;"><img src="modules/PDFMaker/img/bl.png" border="0" align="absmiddle" title="'.$pdf_strings["LBL_BREAKLINE_DESC"].'"/></td>
	              <td class="detailedViewHeader" style="padding-top:5px;padding-bottom:5px;"><b>'.$app_strings["Products"].'</b></td>
	            </tr>
	            '.$products.'
	        </table>
	    </td></tr>
	</table>
</div>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
<tr><td align=center class="small">
	<input type="button" value="'.$app_strings["LBL_SAVE_BUTTON_LABEL"].'" class="crmbutton small create" onclick="VTE.PDFMakerActions.savePDFBreakline();"/>&nbsp;&nbsp;
	<input type="button" name="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" value="'.$app_strings["LBL_CANCEL_BUTTON_LABEL"].'" class="crmbutton small cancel" onclick="hideFloatingDiv(\'PDFBreaklineDiv\')" />
</td></tr>
</table>
</form>
'; 
exit;
?>
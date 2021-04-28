<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$db="adb";
$salt="site_URL";
$currThis="this";
$retid="return_id";
global $table_prefix;
require_once("modules/PDFMaker/InventoryPDF.php");  
include("include/mpdf/mpdf.php"); //crmv@30066

$relmodule = getSalesEntityType($_REQUEST['pid']); 

$modFocus = CRMEntity::getInstance($relmodule);  
if(isset($_REQUEST["pid"])) 
{
  $modFocus->retrieve_entity_info($_REQUEST["pid"],$relmodule);
  $modFocus->id = $_REQUEST["pid"];
} 

$commontemplateids = trim($_REQUEST["commontemplateid"],";");  
$Templateids = explode(";",$commontemplateids);
$name="";
foreach ($Templateids AS $templateid)
{
	$PDFContent = PDFContent::getInstance($templateid, $relmodule, $modFocus, $_REQUEST["language"]); //crmv@34738
	$pdf_content = $PDFContent->getContent();    
	$Settings = $PDFContent->getSettings();    
	if($name=="")      
		$name = $PDFContent->getFilename();
	
	$header_html = html_entity_decode($pdf_content["header"],ENT_COMPAT,"utf-8");
	$body_html = html_entity_decode($pdf_content["body"],ENT_COMPAT,"utf-8");
	$footer_html = html_entity_decode($pdf_content["footer"],ENT_COMPAT,"utf-8");
	
	//$encoding = $Settings["encoding"];
	
	if ($Settings["orientation"] == "landscape")
		$format = $Settings["format"]."-L";
	else
		$format = $Settings["format"];
	
	if (!isset($mpdf))
	{
		/*
		if($encoding=="auto"){
		$mpdf=new mPDF("utf-8",$format,'','',$Settings["margin_left"],$Settings["margin_right"],$Settings["margin_top"],$Settings["margin_bottom"],10,10); 
		$mpdf->SetAutoFont();
		}
		else
		$mpdf=new mPDF($encoding,$format,'','',$Settings["margin_left"],$Settings["margin_right"],$Settings["margin_top"],$Settings["margin_bottom"],10,10);        
		*/
		$mpdf=new mPDF('',$format,'','Arial',$Settings["margin_left"],$Settings["margin_right"],0,0,$Settings["margin_top"],$Settings["margin_bottom"]);  
		$mpdf->SetAutoFont(); 
		@$mpdf->SetHTMLHeader($header_html);
	}
	else
	{
		@$mpdf->SetHTMLHeader($header_html);
		@$mpdf->WriteHTML('<pagebreak sheet-size="'.$format.'" margin-left="'.$Settings["margin_left"].'mm" margin-right="'.$Settings["margin_right"].'mm" margin-top="0mm" margin-bottom="0mm" margin-header="'.$Settings["margin_top"].'mm" margin-footer="'.$Settings["margin_bottom"].'mm" />');
	}
	
	@$mpdf->SetHTMLFooter($footer_html);
	@$mpdf->WriteHTML($body_html);
}

if($name=="")
{
	$result=$$db->query("SELECT fieldname FROM ".$table_prefix."_field WHERE uitype=4 AND tabid=".getTabId($relmodule));
	$fieldname=$$db->query_result($result,0,"fieldname");
	if(isset($modFocus->column_fields[$fieldname]) && $modFocus->column_fields[$fieldname]!="")
	{
	    $name = generate_cool_uri($modFocus->column_fields[$fieldname]);
	}
	else
	{
	    $name = $_REQUEST["commontemplateid"].$_REQUEST["pid"].date("ymdHi");
	    $name = str_replace(";","_",$name);
	}
}

$mpdf->Output('storage/'.$name.'.pdf');

pdfAttach($$currThis,"Emails","$name.pdf",$$retid);
@unlink("cache/$name.pdf");
?>
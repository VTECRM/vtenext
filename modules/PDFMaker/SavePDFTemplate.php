<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// ITS4YOU TT0093 VlMe N

require_once('include/utils/utils.php');
global $adb, $current_user,$table_prefix;
// $adb->setDebug(TRUE);

$modulename = from_html($_REQUEST["modulename"]);
$templateid = vtlib_purify($_REQUEST["templateid"]);

eval(Users::m_de_cryption());
eval($hash_version[7]);

$filename = vtlib_purify($_REQUEST["filename"]);
$description = from_html($_REQUEST["description"]);
$body = fck_from_html($_REQUEST["body"]);
$pdf_format = from_html($_REQUEST["pdf_format"]);
$pdf_orientation = from_html($_REQUEST["pdf_orientation"]);
$is_active = from_html($_REQUEST["is_active"]);
$is_default = (isset($_REQUEST["is_default"]) ? "1" : "0");

$mode = '';
if(isset($templateid) && $templateid !='')
{
	$mode = 'edit';

	//crmv@19166
	$sql = "update ".$table_prefix."_pdfmaker set filename =?, module =?, description =? where templateid =?";
	$params = array($filename, $modulename, $description, $templateid);
	$adb->pquery($sql, $params);
	$adb->updateClob($table_prefix.'_pdfmaker','body',"templateid=$templateid",$body);
	//crmv@19166e

	$sql2 = "DELETE FROM ".$table_prefix."_pdfmaker_settings WHERE templateid =?";
	$params2 = array($templateid);
	$adb->pquery($sql2, $params2);
}
else
{
	$templateid = $adb->getUniqueID($table_prefix.'_pdfmaker');
	//crmv@19166
	$sql3 = "insert into ".$table_prefix."_pdfmaker (filename,module,description,body,deleted,templateid) values (?,?,?,?,?,?)";
	$params3 = array($filename, $modulename, $description, $adb->getEmptyClob(true), 0, $templateid);
	$adb->pquery($sql3, $params3);
	$adb->updateClob($table_prefix.'_pdfmaker','body',"templateid=$templateid",$body);
	//crmv@19166e
}

if ($_REQUEST["margin_top"] > 0) $margin_top = $_REQUEST["margin_top"]; else $margin_top = 0;
if ($_REQUEST["margin_bottom"] > 0) $margin_bottom = $_REQUEST["margin_bottom"]; else $margin_bottom = 0;
if ($_REQUEST["margin_left"] > 0) $margin_left = $_REQUEST["margin_left"]; else $margin_left = 0;
if ($_REQUEST["margin_right"] > 0) $margin_right = $_REQUEST["margin_right"]; else $margin_right = 0;

$dec_point = $_REQUEST["dec_point"];
$dec_decimals = $_REQUEST["dec_decimals"];
$dec_thousands = ($_REQUEST["dec_thousands"]!=" " ? $_REQUEST["dec_thousands"]:"sp");
$compliance = ($_REQUEST["compliance"] != '' ? $_REQUEST["compliance"] : ''); // crmv@172422

$header=$_REQUEST["header_body"];
$footer=$_REQUEST["footer_body"];

$encoding = (isset($_REQUEST["encoding"]) ? $_REQUEST["encoding"] : "auto");
$nameOfFile = $_REQUEST["nameOfFile"];

$sql4 = "INSERT INTO ".$table_prefix."_pdfmaker_settings (templateid, margin_top, margin_bottom, margin_left, margin_right, format, orientation, decimals, decimal_point, thousands_separator, header, footer, encoding, file_name, compliance) 
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$params4 = array($templateid, $margin_top, $margin_bottom, $margin_left, $margin_right, $pdf_format, $pdf_orientation, $dec_decimals, $dec_point, $dec_thousands, $header, $footer, $encoding, $nameOfFile, $compliance); // crmv@172422
$adb->pquery($sql4, $params4);

//ignored picklist values
//crmv@19166
$adb->query("DELETE FROM ".$table_prefix."_pdfmaker_ignpickvals");
if ($_REQUEST["ignore_picklist_values"] != '') {
	$pvvalues=explode(",", $_REQUEST["ignore_picklist_values"]);
	foreach($pvvalues as $value) {
		$adb->pquery("INSERT INTO {$table_prefix}_pdfmaker_ignpickvals(value) VALUES(?)", array($value));//crmv@208173
	}
}
//crmv@19166e
// end ignored picklist values

//crmv@27623 crmv@101257
if ($mode != 'edit') {
	$sql6 = "INSERT INTO ".$table_prefix."_pdfmaker_userstatus(templateid, userid, is_active, is_default) VALUES(?,?,?,?)";
	$adb->pquery($sql6, array($templateid, $current_user->id, $is_active, 0));
} else {
	//crmv@108577
	$sql="SELECT templateid
		  FROM {$table_prefix}_pdfmaker_userstatus      
		  WHERE templateid=? AND userid=?";
	$result = $adb->pquery($sql,array($templateid,$current_user->id));
	if ($adb->num_rows($result) > 0) {
		$sql6 = "UPDATE ".$table_prefix."_pdfmaker_userstatus set is_active =?, is_default =? WHERE templateid=? AND userid =?";
		$adb->pquery($sql6, array($is_active, $is_default,$templateid, $current_user->id));
	} else {
		$sql6 = "INSERT INTO ".$table_prefix."_pdfmaker_userstatus (templateid, userid, is_active, is_default) VALUES (?,?,?,?)";
		$adb->pquery($sql6, array($templateid, $current_user->id, $is_active, $is_default));
	}
	//crmv@108577e
}
//crmv@27623e crmv@101257e

$tabid = getTabId($modulename); 

$sql7 = "SELECT * FROM ".$table_prefix."_links WHERE tabid = ? AND linktype = 'LISTVIEWBASIC' AND linklabel = 'PDF Export'";
$result7 = $adb->pquery($sql7, array($tabid));
$num_rows7 = $adb->num_rows($result7);

if ($num_rows7 == 0)
{
    $linkid = $adb->getUniqueID($table_prefix.'_links');
    
    $sql8 = "INSERT INTO ".$table_prefix."_links (linkid, tabid, linktype, linklabel, linkurl, sequence) VALUES(?,?,?,?,?,?)";
    $adb->pquery($sql8, array($linkid, $tabid, "LISTVIEWBASIC", "PDF Export", "VTE.PDFMakerActions.getPDFListViewPopup2(this,'$"."MODULE$');",1));
}

$sql9 = "SELECT * FROM ".$table_prefix."_links WHERE tabid = ? AND linktype = 'DETAILVIEWWIDGET' AND linklabel = 'PDFMaker'";
$result9 = $adb->pquery($sql9, array($tabid));
$num_rows9 = $adb->num_rows($result9);

if ($num_rows9 == 0)
{
    $linkid = $adb->getUniqueID($table_prefix.'_links');
    
    $sql10 = "INSERT INTO ".$table_prefix."_links (linkid, tabid, linktype, linklabel, linkurl, sequence) VALUES(?,?,?,?,?,?)";
    $adb->pquery($sql10, array($linkid, $tabid, "DETAILVIEWWIDGET", "PDFMaker", "module=PDFMaker&action=PDFMakerAjax&file=getPDFActions&record=$"."RECORD$",1));
}

if ($Header_Img["error"] == "true" || $Footer_Img["error"] == "true")
{
    header("Location:index.php?module=PDFMaker&action=EditPDFTemplate&parenttab=Tools&eheader=".$Header_Img["error"]."&efooter=".$Footer_Img["error"]."&templateid=".$templateid);
}
elseif(isset($_REQUEST["redirect"]) && $_REQUEST["redirect"]=="false" )
{
  header("Location:index.php?module=PDFMaker&action=EditPDFTemplate&parenttab=Tools&applied=true&templateid=".$templateid);
}
else
{
  header("Location:index.php?module=PDFMaker&action=DetailViewPDFTemplate&parenttab=Tools&templateid=".$templateid);
}

exit;
?>
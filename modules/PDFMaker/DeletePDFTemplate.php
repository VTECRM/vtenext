<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// ITS4YOU TT0093 VlMe N
global $table_prefix;
$id_array = array();

if (isset($_REQUEST['templateid']) && $_REQUEST['templateid'] != "")
{
    $templateid = $_REQUEST['templateid'];
    $sql = "delete from ".$table_prefix."_pdfmaker where templateid=?";
	$adb->pquery($sql, array($templateid));
	
	$sql = "delete from ".$table_prefix."_pdfmaker_settings where templateid=?";
	$adb->pquery($sql, array($templateid));
}
else
{
    $idlist = $_REQUEST['idlist'];
    $id_array=explode(';', $idlist);

    for($i=0; $i < count($id_array)-1; $i++) {
    	$sql = "delete from ".$table_prefix."_pdfmaker where templateid=?";
    	$adb->pquery($sql, array($id_array[$i]));
    	
    	$sql = "delete from ".$table_prefix."_pdfmaker_settings where templateid=?";
    	$adb->pquery($sql, array($id_array[$i]));
    }
}

header("Location:index.php?module=PDFMaker&action=ListPDFTemplates&parenttab=Tools");

?>
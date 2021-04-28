<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("modules/PDFMaker/InventoryPDF.php");
require_once("include/mpdf/mpdf.php"); //crmv@30066
global $table_prefix;
$db="adb";
$salt="site_URL";
$cu="current_user";

//$userId = $$cu->id;

$moduleName = $_REQUEST["pmodule"];
$language = $_REQUEST["language"];

$focus = CRMEntity::getInstance('Documents'); // crmv@39106
$focus->parentid = $_REQUEST["pid"];

$modFocus = CRMEntity::getInstance($moduleName);  
if(isset($focus->parentid)) 
{
	$modFocus->retrieve_entity_info($focus->parentid,$moduleName);
	$modFocus->id = $focus->parentid;
} 

$result=$$db->query("SELECT fieldname FROM ".$table_prefix."_field WHERE uitype=4 AND tabid=".getTabId($moduleName));
$fieldname=$$db->query_result($result,0,"fieldname");
if(isset($modFocus->column_fields[$fieldname]) && $modFocus->column_fields[$fieldname]!="")
{
	$file_name = generate_cool_uri($modFocus->column_fields[$fieldname]).".pdf";
}
else
{
	$file_name = "doc_".$focus->parentid.date("ymdHi").".pdf";
}

$focus->column_fields["notes_title"] = $_REQUEST["notes_title"];
$focus->column_fields["assigned_user_id"] = $$cu->id;
$focus->column_fields["filename"] = $file_name;
$focus->column_fields["notecontent"] = $_REQUEST["notecontent"]; 
$focus->column_fields["filetype"] = "application/pdf"; 
$focus->column_fields["filesize"] = 0; // crmv@78635
$focus->column_fields["filelocationtype"] = "I"; 
$focus->column_fields["fileversion"] = '';
$focus->column_fields["filestatus"] = "on";
$focus->column_fields["folderid"] = $_REQUEST["folderid"];

$focus->save("Documents");

createPDFAndSaveFile($_REQUEST["template_ids"],$focus,$modFocus,$file_name,$moduleName,$language);

//crmv@25443
if ($_REQUEST['return_action'] == 'CreatePDFFromTemplate') {
	echo '
	<script type="text/javascript" src="include/js/jquery.js"></script>
  	<script type="text/javascript" src="include/js/general.js"></script>
	<script>
		jQuery(document).ready(function() {
			closePopup();
			parent.document.location = \'index.php?module=Documents&action=DetailView&parenttab=Support&record='.$focus->id.'\';
		});
	</script>';
} else {
	header("Location: index.php?module=Documents&action=DetailView&parenttab=Support&record=".$focus->id);
}
//crmv@25443e
?>
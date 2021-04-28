<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@39106
require_once("modules/PDFMaker/PDFMaker.php");

// INPUT
$relmodule = vtlib_purify($_REQUEST["relmodule"]);

if (isset($_REQUEST["idslist"]) && $_REQUEST["idslist"]!="") {  //generating from listview
    $Records = explode(";", rtrim($_REQUEST["idslist"],";"));
} elseif (isset($_REQUEST['record'])) {
    $Records = array($_REQUEST["record"]);
}

$commontemplateids = trim($_REQUEST["commontemplateid"],";");
$Templateids = explode(";",$commontemplateids);

$language = $_REQUEST["language"];

// OUTPUT
$pdfmaker = new PDFMaker();
$name = $pdfmaker->generatePDFForEmail($Records, $relmodule, $Templateids, $language);
echo $name;

// EXIT
exit();
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');

$scannername = $_REQUEST['scannername'];
$scannerinfo = new Vtenext_MailScannerInfo($scannername);//crmv@207843

$folderinfo = Array();
foreach($_REQUEST as $key=>$value) {
	$matches = Array();
	if(preg_match("/folder_([0-9]+)/", $key, $matches)) {
		$folderinfo[$value] = Array('folderid'=>$matches[1], 'enabled'=>1);
	}
}
$scannerinfo->enableFoldersForScan($folderinfo);

//crmv@56233
$spam_folder = vtlib_purify($_REQUEST['spam_folder']);
$scannerinfo->setSpamFolder($spam_folder);
//crmv@56233e

include('modules/Settings/MailScanner/MailScannerInfo.php');
?>
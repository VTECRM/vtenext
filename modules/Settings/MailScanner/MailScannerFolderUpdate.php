<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailBox.php');

global $app_strings, $mod_strings, $currentModule, $theme, $current_language;

$smarty = new VteSmarty();
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH","themes/$theme/images/");

$scannername = $_REQUEST['scannername'];
$scannerinfo = new Vtenext_MailScannerInfo($scannername);//crmv@207843

$mailbox = new Vtenext_MailBox($scannerinfo);//crmv@207843
$isconnected = $mailbox->connect();
if($isconnected) {
	$folders = $mailbox->getFolders();
	$scannerinfo->updateFolderInfo($folders);
}

$smarty->assign("SCANNERINFO", $scannerinfo->getAsMap());
$smarty->assign("FOLDERINFO", $scannerinfo->getFolderInfo());

$smarty->display('MailScanner/MailScannerFolder.tpl');

?>
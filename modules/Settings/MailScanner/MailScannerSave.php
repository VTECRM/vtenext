<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailBox.php');


$scannername = vtlib_purify(trim($_REQUEST['mailboxinfo_scannername']));
//crmv@18062
//if(!empty($scannername) && !validateAlphaNumericInput($scannername)) {
//	$scannername = '';
//}
//crmv@18062e
$server     = vtlib_purify(trim($_REQUEST['mailboxinfo_server']));
if(!empty($server) && !validateServerName($server)) {
	$server = '';
}
$username   = vtlib_purify(trim($_REQUEST['mailboxinfo_username']));
//if(!empty($username) && !validateAlphanumericInput($username)) {	//crmv@45088 && !validateEmailId($username)
//	$username = '';
//}

$newscannerinfo = new Vtenext_MailScannerInfo(false, false);//crmv@207843
$newscannerinfo->scannername = $scannername;
$newscannerinfo->server     = $server;
$newscannerinfo->protocol   = vtlib_purify(trim($_REQUEST['mailboxinfo_protocol']));
$newscannerinfo->username   = $username;
$newscannerinfo->password   = trim($_REQUEST['mailboxinfo_password']); // crmv@114904
$newscannerinfo->ssltype    = vtlib_purify(trim($_REQUEST['mailboxinfo_ssltype']));
$newscannerinfo->sslmethod  = vtlib_purify(trim($_REQUEST['mailboxinfo_sslmethod']));
$newscannerinfo->searchfor  = vtlib_purify(trim($_REQUEST['mailboxinfo_searchfor']));
$newscannerinfo->markas     = vtlib_purify(trim($_REQUEST['mailboxinfo_markas']));
$newscannerinfo->succ_moveto     = vtlib_purify(trim($_REQUEST['mailboxinfo_succ_moveto']));		//crmv@2043m
$newscannerinfo->no_succ_moveto     = vtlib_purify(trim($_REQUEST['mailboxinfo_no_succ_moveto']));	//crmv@2043m
$newscannerinfo->isvalid    =($_REQUEST['mailboxinfo_enable'] == 'true')? true : false;

// crmv@178441
$newscannerinfo->is_pec = ($_REQUEST['is_pec'] == 'on') ? true : false;
$newscannerinfo->imap_params = Zend_Json::decode($_REQUEST['imap_params']);
// crmv@178441e

// Rescan all folders on next run?
$rescanfolder = ($_REQUEST['mailboxinfo_rescan_folders'] == 'true')? true : false;

$isconnected = false;

$scannerinfo = new Vtenext_MailScannerInfo(trim($_REQUEST['hidden_scannername']));//crmv@207843

//crmv@43764
if ($scannerinfo->scannerid && $newscannerinfo->password == '') {
	$newscannerinfo->password = $scannerinfo->password;
}
//crmv@43764e

if(!$scannerinfo->compare($newscannerinfo)) {
	$mailbox = new Vtenext_MailBox($newscannerinfo);//crmv@207843

	$isconnected = $mailbox->connect();
	if($isconnected) $newscannerinfo->connecturl = $mailbox->_imapurl;

} else {
	$isconnected = true;
	$scannerinfo->isvalid = $newscannerinfo->isvalid; // Copy new value
	$newscannerinfo = $scannerinfo;
}

//crmv@56233
if ($_REQUEST['savemode'] == '') {
	$result = $adb->pquery("select * from {$table_prefix}_mailscanner where scannername = ?",array($scannername));
	if ($result && $adb->num_rows($result) > 0) {
		global $app_strings, $mod_strings, $currentModule, $theme, $current_language;
	
		$smarty = new VteSmarty();
		$smarty->assign("MOD", return_module_language($current_language,'Settings'));
		$smarty->assign("CMOD", $mod_strings);
		$smarty->assign("APP", $app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH","themes/$theme/images/");
	
		$smarty->assign("SAVEMODE", vtlib_purify(trim($_REQUEST['savemode']))); // crmv@168897
		$smarty->assign("SCANNERINFO", $newscannerinfo->getAsMap());
		$smarty->assign("FOLDERINFO", $scannerinfo->getFolderInfo());	//crmv@2043m
		$smarty->assign("CONNECTFAIL", getTranslatedString('LBL_MAILSCANNER_NAME_DUPLICATED','Settings'));
		$smarty->display('MailScanner/MailScannerEdit.tpl');
		
		exit;
	}
}
//crmv@56233e

if(!$isconnected) {
	global $app_strings, $mod_strings, $currentModule, $theme, $current_language;

	$smarty = new VteSmarty();
	$smarty->assign("MOD", return_module_language($current_language,'Settings'));
	$smarty->assign("CMOD", $mod_strings);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("THEME", $theme);
	$smarty->assign("IMAGE_PATH","themes/$theme/images/");

	$smarty->assign("SAVEMODE", vtlib_purify(trim($_REQUEST['savemode']))); // crmv@168897
	$smarty->assign("SCANNERINFO", $newscannerinfo->getAsMap());
	$smarty->assign("FOLDERINFO", $scannerinfo->getFolderInfo());	//crmv@2043m
	$smarty->assign("CONNECTFAIL", getTranslatedString('LBL_UNABLE_TO_CONNECT_MAILSCANNER','Settings'));
	$smarty->display('MailScanner/MailScannerEdit.tpl');
} else {

	$mailServerChanged = $scannerinfo->update($newscannerinfo);
	
	$scannerinfo->updateAllFolderRescan($rescanfolder);

	// Update lastscan on all the available folders.
	if($mailServerChanged && $mailbox) {
		$folders = $mailbox->getFolders();
		foreach($folders as $folder) $scannerinfo->updateLastscan($folder);
	}

	require('modules/Settings/MailScanner/MailScannerInfo.php');
}
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 crmv@56233 */

require('config.inc.php');

require_once('include/utils/utils.php');
require_once('include/logging.php');
require_once('include/database/PearDatabase.php');

require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailBox.php');
require_once('modules/Settings/MailScanner/core/MailScanner.php');
require_once('modules/Settings/MailScanner/core/MailScannerSpam.php');

//Added as sometimes the php.ini file used for command line php and
//for Apache php is different.
require_once('include/install/language/en_us.lang.php');
if(!function_exists('imap_open')) {
	echo $installationStrings['LBL_NO'].' '.$installationStrings['LBL_IMAP_SUPPORT'];
} elseif(!function_exists('openssl_encrypt')) {
	echo $installationStrings['LBL_NO'].' '.$installationStrings['LBL_OPENSSL_SUPPORT'];
}

// impersonate admin
global $current_user;
if (!$current_user) {
	$current_user = CRMEntity::getInstance('Users');
	$current_user->id = 1;
}

Vtenext_MailScanner::performanceLog("Starting mailscanner service", 'mailscanner_service'); // crmv@170905 crmv@207843

/**
 * Helper function for triggering the scan.
 */
function service_MailScanner_performScanNow($scannerinfo, $debug) {
	/** If the scanner is not enabled, stop. */
	if($scannerinfo->isvalid) {
		echo "Scanning " . $scannerinfo->scannername. " in progress\n";
		
		Vtenext_MailScanner::performanceLog("Scanning " . $scannerinfo->scannername. " in progress", 'mailscanner_server'); // crmv@170905 crmv@207843

		/** Start the scanning. */
		$scanner = new Vtenext_MailScanner($scannerinfo); //crmv@207843
		$scanner->debug = $debug;
		$scanner->performScanNow();
		
		Vtenext_MailScanner::performanceLog("Scanning " . $scannerinfo->scannername. " completed in {tac}", 'mailscanner_server', true); // crmv@170905 crmv@207843

		echo "\nScanning " . $scannerinfo->scannername. " completed\n";

	} else {
		echo "Failed! [{$scannerinfo->scannername}] is not enabled for scanning!";
	}
}

/** Turn-off this if not required. */
$debug = true;

/** Pick up the mail scanner for scanning. */
if(isset($_REQUEST['scannername'])) {

	// Target scannername specified?
	$scannername = vtlib_purify($_REQUEST['scannername']); // crmv@37463
	$scannerinfo = new Vtenext_MailScannerInfo($scannername);//crmv@207843
	
	$mailScannerSpam = new Vtecrm_MailScannerSpam();
	$mailScannerSpam->processQueue($scannername);

	service_MailScanner_performScanNow($scannerinfo, $debug);

} else {

	// Scan all the configured mailscanners?
	$mailScannerSpam = new Vtecrm_MailScannerSpam();
	$mailScannerSpam->processQueue();

	$scannerinfos = Vtenext_MailScannerInfo::listAll();//crmv@207843
	if(empty($scannerinfos)) {

		echo "No mailbox configured for scanning!";

	} else {
		foreach($scannerinfos as $scannerinfo) {
			service_MailScanner_performScanNow($scannerinfo, $debug);
		}
	}
}

Vtenext_MailScanner::performanceLog("Mailscanner service finished in {tac}", 'mailscanner_service', true); // crmv@170905 crmv@207843

?>
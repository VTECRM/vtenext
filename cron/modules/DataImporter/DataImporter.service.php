<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */

require('config.inc.php');
require_once('modules/Settings/DataImporter/DataImporterCron.php');

$importid = intval($_REQUEST['importid']);
if ($importid <= 0) {
	echo "No valid import ID provided.\n";
	return;
}

$dcron = new DataImporterCron($importid);
$r = $dcron->process();
if (!$r) {
	echo "Error during the automatic import.\n";
}
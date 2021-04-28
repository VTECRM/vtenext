<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */

require('config.inc.php');
require_once('modules/Settings/DataImporter/DataImporterCron.php');

try {
	$dcron = new DataImporterCron();
	$dcron->check();
} catch (Exception $e) {
	echo "Exception: ".$e->getMessage();
}

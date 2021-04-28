<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 */

require('config.inc.php');

global $application_unique_key;

$previousBulkSaveMode = $BULK_SAVE_MODE;
$BULK_SAVE_MODE = true;

VteSession::set("app_unique_key", $application_unique_key); //for fast notification
require_once 'modules/Import/controllers/Import_Data_Controller.php';

// check table
if (Vtecrm_Utils::CheckTable($table_prefix.'_import_queue')) {//crmv@198038
	Import_Data_Controller::runScheduledImport();
}

$BULK_SAVE_MODE = $previousBulkSaveMode;

?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once 'modules/Import/api/Request.php';
require_once 'modules/Import/controllers/Import_Index_Controller.php';
require_once 'modules/Import/controllers/Import_ListView_Controller.php';
require_once 'modules/Import/controllers/Import_Controller.php';

global $current_user;

$previousBulkSaveMode = $BULK_SAVE_MODE;
$BULK_SAVE_MODE = true;

$requestObject = new Import_API_Request($_REQUEST);

Import_Index_Controller::process($requestObject, $current_user);

$BULK_SAVE_MODE = $previousBulkSaveMode;

?>
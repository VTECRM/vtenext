<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@27589 crmv@192078 */

require('../config.inc.php');
chdir($root_directory);

// crmv@91979
require_once('include/MaintenanceMode.php');
if (MaintenanceMode::check()) {
	MaintenanceMode::display();
	die();
}
// crmv@91979e

require_once('include/utils/utils.php');
require_once('modules/Users/RecoverPwd.php');

RequestHandler::validateCSRFToken(); // crmv@171581

$RP = new RecoverPwd();
$RP->process($_REQUEST, $_POST);
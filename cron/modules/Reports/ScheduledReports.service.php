<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@139057 */

require_once("modules/Reports/Reports.php");
require_once("modules/Reports/ScheduledReports.php");

// Turn-off PHP error reporting.
//try { error_reporting(0); } catch(Exception $e) { }

$SR = ScheduledReports::getInstance();
$SR->runScheduledReports();
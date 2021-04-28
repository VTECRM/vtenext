<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42264

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

// Get the list of Invoice for which Recurring is enabled.

global $log;
$log =& LoggerManager::getLogger('Messages');
$log->debug("invoked Messages");

$focus = CRMEntity::getInstance('Messages');
$focus->fetchPop3();

$log->debug("end Messages procedure");
?>
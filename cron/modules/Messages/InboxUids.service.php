<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42264

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

ini_set('memory_limit','256M');

global $log;
$log =& LoggerManager::getLogger('Messages');
$log->debug("invoked Messages");

$_REQUEST['service'] = 'Messages';
$focus = CRMEntity::getInstance('Messages');
$focus->syncUidsInbox();

$log->debug("end Messages procedure");
?>
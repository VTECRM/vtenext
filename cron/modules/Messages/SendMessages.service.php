<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@48501 */

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

ini_set('memory_limit','256M');

global $log;
$log =& LoggerManager::getLogger('Messages');
$log->debug("invoked sending emails procedure");

$_REQUEST['service'] = 'Messages';	//crmv@53929
$focus = CRMEntity::getInstance('Emails');
$focus->processSendingQueue($_REQUEST['ustart'],$_REQUEST['uend']);	//crmv@71322

$log->debug("end sending emails procedure");
?>
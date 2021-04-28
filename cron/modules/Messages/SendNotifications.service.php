<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@129149 */

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

$VTEP = VTEProperties::getInstance();
$send_mail_queue = $VTEP->getProperty('modules.emails.send_mail_queue');
if (!$send_mail_queue) return; // exit the file

ini_set('memory_limit','256M');

global $log;
$log =& LoggerManager::getLogger('Messages');
$log->debug("invoked sending notification emails procedure");

$_REQUEST['service'] = 'Messages';
$focus = CRMEntity::getInstance('Emails');
$focus->processSendNotQueue();

$log->debug("end sending notification emails procedure");
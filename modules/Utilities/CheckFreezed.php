<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@171524 */

$record = intval($_REQUEST['crmid']);

$success = true;
$error = null;
$isFreezed = false;
$stompConnection = null;

$VTEP = VTEProperties::getInstance();

$triggerQueueManager = TriggerQueueManager::getInstance();
$isFreezed = $triggerQueueManager->checkFreezed($record);

$json = array('success' => $success, 'error' => $error, 'is_freezed' => $isFreezed);

echo Zend_Json::encode($json);
exit();
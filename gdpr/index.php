<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

require_once('config.php');

$action = filter_var($_REQUEST['action'], FILTER_SANITIZE_STRING);

$SM = GDPR\SessionManager::getInstance();
$GPDRManager = GDPR\GDPRManager::getInstance($CFG, $SM, $_REQUEST);
	
$GPDRManager->processAction($action);
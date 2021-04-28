<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150024 */

require_once("modules/Users/Users.php");
require_once("modules/Targets/DynamicTargets.php");

global $current_user;
$current_user = Users::getActiveAdminUser();

$DT = DynamicTargets::getInstance();
$DT->runDynamicTargets();
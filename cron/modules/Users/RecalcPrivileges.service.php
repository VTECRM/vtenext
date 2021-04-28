<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@74560 */

require('config.inc.php');
require_once('modules/Users/CreateUserPrivilegeFile.php');

$SR = new SharingPrivileges();
$SR->recalcFromCron();
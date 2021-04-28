<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@176547 */

// optional syncid to pass
$syncid = intval($_REQUEST['syncid']);

require_once('modules/VteSync/VteSync.php');
$vsync = VteSync::getInstance();
$vsync->runCron($syncid);
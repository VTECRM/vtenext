<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@157490 */
$serverConfigUtils = ServerConfigUtils::getInstance();
$server = $serverConfigUtils->getConfiguration('email',array('server'),'server_type',true);
if (!empty($server)) {
	die('ok');
}
die('no');
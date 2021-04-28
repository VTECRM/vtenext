<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require("PortalConfig.php"); // crmv@198415
require_once('nusoap/nusoap.php'); // crmv@148761

global $Server_Path;
global $client;

$client = new nusoap_client($Server_Path."/vteservice.php?service=customerportal", false, $proxy_host, $proxy_port, $proxy_username, $proxy_password); // crmv@80441 crmv@148761 crmv@181168

//We have to overwrite the character set
$client->soap_defencoding = $default_charset;

<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


global $Server_Path;
global $Portal_Path;

//This is the vte server path ie., the url to access the vte server in browser
$Server_Path = "";

//This is the customer portal path ie., url to access the customer portal in browser
$Authenticate_Path = "";

include('../config.inc.php');
global $site_URL;
if ($site_URL)
	$Server_Path = $site_URL;
global $PORTAL_URL;
if ($PORTAL_URL)
	$Authenticate_Path = $PORTAL_URL;	
	
//Give a temporary directory path which is used when we upload attachment
$upload_dir = '/tmp';

//These are the Proxy Settings parameters
$proxy_host = ''; //Host Name of the Proxy
$proxy_port = ''; //Port Number of the Proxy
$proxy_username = ''; //User Name of the Proxy
$proxy_password = ''; //Password of the Proxy

//The character set to be used as character encoding for all soap requests
$default_charset = 'UTF-8';//'ISO-8859-1';

$default_language = 'it_it';

// default module to open after login
$default_module = 'HelpDesk'; // crmv@173271

/*crmv@57342*/
$languages = Array('en_us'=>'US English','it_it'=>'IT Italian');

//set to true to enable registration
$enable_registration = false; // crmv@173271

$welcome_page = '';
/*crmv@57342e*/

// crmv@171581
global $csrf_secret;
$csrf_secret = sha1($csrf_secret);
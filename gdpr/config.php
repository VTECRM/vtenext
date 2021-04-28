<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

define('BASEPATH', dirname(realpath(__FILE__)) . '/');

session_start();

require_once(BASEPATH.'include/vtwsclib/VTEWSClient.php');//crmv@208028
require_once(BASEPATH.'include/smarty/libs/Smarty.class.php');
require_once(BASEPATH.'classes/GDPRManager.php');
require_once(BASEPATH.'classes/SessionManager.php');
require_once(BASEPATH.'classes/Redirect.php');
require_once(BASEPATH.'classes/SmartyConfig.php');

$CFG = new stdClass();

$CFG->webservice_endpoint = '';
$CFG->webservice_username = '';
$CFG->webservice_accesskey = '';
$CFG->default_language = '';
$CFG->website_logo = '';

$vteConfigFile = '../config.inc.php';

if (is_file($vteConfigFile) && is_readable($vteConfigFile)) {
	require_once($vteConfigFile);
	chdir($root_directory);
	require_once('include/database/PearDatabase.php');
	
	$vteProp = VTEProperties::getInstance();
	
	$generalSettings = $vteProp->get('services.gdpr.general_settings');
	
	$sessionManager = GDPR\SessionManager::getInstance();
	
	if (!empty($_REQUEST['bid'])) {
		$reqBid = base64_decode(urldecode($_REQUEST['bid']));
		$sessionManager->set('bid', $reqBid);
		$businessId = $reqBid;
	} else {
		if (!$sessionManager->hasKey('bid')) {
			$businessId = $generalSettings['default_business'];
			$sessionManager->set('bid', $businessId);
		} else {
			$businessId = $sessionManager->get('bid');
		}
	}
	
	$config = $vteProp->get("services.gdpr.config.business.{$businessId}");
	
	if (empty($config)) {
		$businessId = $generalSettings['default_business'];
		$config = $vteProp->get("services.gdpr.config.business.{$businessId}");
	}
	
	if (!empty($config)) {
		$CFG = new stdClass();
		$CFG->webservice_endpoint = $config['webservice_endpoint'];
		$CFG->webservice_username = $config['webservice_username'];
		$CFG->webservice_accesskey = $config['webservice_accesskey'];
		$CFG->default_language = $config['default_language'];
		$CFG->website_logo = $config['website_logo'];
	}
}

chdir(BASEPATH);

$language = $CFG->default_language;
require_once(BASEPATH.'lang.php');
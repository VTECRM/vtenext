<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42264

// set current dir to vte root
chdir(dirname(__FILE__)."/..");

require('config.inc.php');

// crmv@91979
require_once('include/MaintenanceMode.php');
if (MaintenanceMode::check()) {
	MaintenanceMode::displayCron();
	die();
}
// crmv@91979e

require_once('include/utils/utils.php');
require_once('include/utils/CronUtils.php');

SDK::getUtils();	//crmv@sdk-18503

global $application_unique_key;

// populate _REQUEST if invoked from command line
if ($argv) {
	
	$request = array();
	
	for ($index = 0; $index < count($argv); ++$index) {
		$value = $argv[$index];
		if (strpos($value, '=') === false) continue;

		$keyval = explode('=', $value);
		if (!isset($request[$keyval[0]])) {
			$request[$keyval[0]] = $keyval[1];
		}
	}

	/* If app_key is not set, pick the value from cron configuration */
	if (empty($request['app_key'])) $request['app_key'] = $application_unique_key;

} elseif ($_REQUEST['app_key'] != $application_unique_key) {
	// invocation from browser is ok only if appkey is provided
	echo "Access denied!";
	exit;
}

$CM = new CronManager($request);
if ($request['debug'] == '1') $CM->logToStdout = true;

if (intval($request['cronid']) > 0) {
	// crmv@181265
	$force = ($request['force'] == '1');
	$CM->run(intval($request['cronid']), $force);
	// crmv@181265e
} else {
	$CM->run();
}
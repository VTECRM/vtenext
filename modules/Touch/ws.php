<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * file principale per webservice Touch
 */

// hide error messages, the answer should always be json
@ini_set('display_errors', 0);

// hide warning if apc is enabled (APC outputs an annoying warning randomly)
if (extension_loaded('apc') && ini_get('apc.enabled') && ini_get('display_errors')) {
	$curErrorLevel = error_reporting();
	if ($curErrorLevel & E_WARNING) {
		@error_reporting($curErrorLevel & ~E_WARNING);
	}
}

// crmv@33311
// imposto parametri di risposta
header("Access-Control-Allow-Headers: X-Requested-With, Touch-Session-Id");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Expose-Headers: Touch-Session-Id");
if (!empty($_SERVER['HTTP_ORIGIN'])) {
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	header("Access-Control-Allow-Credentials: true");
} else {
	header("Access-Control-Allow-Origin: *");
}
// crmv@33311e

// crmv@73256 - check for the options request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') die();
// crmv@73256e

if (!isset($root_directory)) {
	require_once('../../config.inc.php');
	chdir($root_directory);
}

global $current_user;

require_once('modules/Touch/Touch.php');

// crmv@91979
require_once('include/MaintenanceMode.php');
if (MaintenanceMode::check()) {
	MaintenanceMode::displayTouchWS();
	die();
}
// crmv@91979e

SDK::getUtils();	//crmv@128133

$userId = null;
$touchInst = Touch::getInstance();
$touchUtils = TouchUtils::getInstance();
$touchCache = TouchCache::getInstance(); // crmv@56798

$wsname = substr($_REQUEST['wsname'], 0, 64); // l'azione da compiere
$wsversion = substr($_REQUEST['wsversion'], 0, 8);
if ($wsversion == 'latest') $wsversion = $touchInst->version;
$legacyMode = (empty($wsversion) || version_compare($wsversion, '2.0', '<'));

if ($legacyMode) {

	// Compatibility mode for old app
	// It will be removed soon
	global $userId, $login;

	require_once('modules/Touch/TouchUtilsLegacy.php');
	$filename = $touchInst->getWSFile($wsname, $wsversion);

	if (!empty($filename) && is_readable($filename) && is_file($filename)) {
		if ($wsname != 'Login') {
			$result = $touchInst->checkLogin($_REQUEST['username'], $_REQUEST['password']);
			$userId = $result['userid'];

			// metto in sessione una variabile utile
			$_SESSION["app_unique_key"] = 'WSMobile_'.time();

			// utente
			if ($result['success'] && $userId > 0) {
				$current_user = CRMEntity::getInstance('Users');
				$current_user->id = $userId;
				$current_user->retrieveCurrentUserInfoFromFile($userId);

				if ($current_user->column_fields['status'] != 'Active') {
					$touchInst->outputFailure('Invalid credentials');
					die();
				}
				$login = true;

				// lingua
				if (!empty($current_user->column_fields['default_language'])) {
					$default_language = $current_language = $current_user->column_fields['default_language'];
				}
			} else {
				$touchInst->outputFailure('Invalid credentials');
				die();
			}
		}
		
		//auditing
		// crmv@202301
		require_once('modules/Settings/AuditTrail.php');
		$AuditTrail = new AuditTrail();
		$AuditTrail->processTouchWS($_REQUEST);
		// crmv@202301e

		// old system
		$classfile = $touchInst->getWSClassFile($wsname, $wsversion);
		if (is_readable($classfile) && is_file($classfile)) {
			require_once($classfile);
		}

		require($filename);

	} else {
		$touchInst->outputFailure('Unknown webservice');
	}

} else {

	// new system
	$touchInst->startWSSession();
	$touchInst->executeWS($wsname, $wsversion, $_REQUEST);
}
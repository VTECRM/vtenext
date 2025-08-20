<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@170283 crmv@255566 crmv@203130 */

require('../../config.inc.php');
chdir($root_directory);

// crmv@246249
require_once('include/MaintenanceMode.php');
if (MaintenanceMode::check()) {
	MaintenanceMode::displayRestApi();
	exit();
}
// crmv@246249e

require_once('include/utils/utils.php');
SDK::getUtils();

$allowHeaders = ['Authorization', 'Content-Type']; // crmv@341216

$VP = VTEProperties::getInstance();
if ($VP->get('performance.app_debug')) {
	$allowHeaders = array_merge($allowHeaders, [
		'Touch-Session-Id', 'Touch-Check-Time',
		'Touch-Version-Id', 'X-App-Package',
		'X-App-Version', 'X-Otp',
	]);
}

header("Access-Control-Allow-Headers: " . implode(', ', $allowHeaders));
header("Access-Control-Allow-Credentials: true"); // crmv@341216
header("Access-Control-Allow-Methods: POST, OPTIONS"); // GET
header("Access-Control-Allow-Origin: *");

// crmv@341219
header("X-Frame-Options: DENY"); // REST API are not frame-able by default
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: frame-ancestors 'none'");
// crmv@341219e

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') die();

$contentType = '';
if (function_exists('getallheaders')) {
	$http_headers = getallheaders();
	foreach($http_headers as $h => $v) {
		if (strtolower($h) == 'content-type') {
			list($contentType,) = array_map('trim', explode(";", $v));
			break;
		}
	}
}
//crmv@185658
elseif(PHP_SAPI == 'fpm-fcgi' && !function_exists('getallheaders')){
	function getallheaders(){
		$headers = array();
		$copy_server = array(
			'CONTENT_TYPE'   => 'Content-Type',
			'CONTENT_LENGTH' => 'Content-Length',
			'CONTENT_MD5'    => 'Content-Md5',
		);
		foreach ($_SERVER as $key => $value) {
			if (substr($key, 0, 5) === 'HTTP_') {
				$key = substr($key, 5);
				if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
					$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
					$headers[$key] = $value;
				}
			} elseif (isset($copy_server[$key])) {
				$headers[$copy_server[$key]] = $value;
			}
		}
		if (!isset($headers['Authorization'])) {
			if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
				$headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
			} elseif (isset($_SERVER['PHP_AUTH_USER'])) {
				$basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
				$headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
			} elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
				$headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
			}
		}
		return $headers;
	}
	
	$http_headers = getallheaders();
	foreach($http_headers as $h => $v) {
		if (strtolower($h) == 'content-type') {
			$contentType = strtolower($v);
			break;
		}
	}
}
//crmv@185658e
if ($contentType == 'application/json') {
	$_REQUEST = $_POST = Zend_Json::decode(file_get_contents('php://input'));
}

require_once('include/RestApi/Server.php');
require_once('include/RestApi/Client.php');
require_once('include/RestApi/InternalClient.php');
use RestService\Server;

require_once('include/RestApi/v1/VTERestApi.php');
$restApi = VTERestApi::getInstance();


// crmv@341217
global $adb, $root_directory;
/** @var PearDatabase $adb */

// must be always set to false, otherwise sensitive stack traces with vte path will be returned
$adb->setDieOnError(false);

// must be always set to false, otherwise uncaught errors will generate sensitive messages with the sql query and database info
$adb->setExceptOnError(false);

// this will also catch fatal errors and parse errors, also avoiding xdebug pages
try {
	$server = Server::create('/vtews', $restApi);
	// $server->setDebugMode(true); // prints the debug trace, line number and file if a exception has been thrown.

	$methods = $restApi->getMethods();
	if (!empty($methods)) {
		foreach($methods as $row) {
			$server->addPostRoute($row['rest_name'],$row['rest_name']);
		}
	}
	$server->run();
	
} catch (\Error $e) {
	$strip = function($x) use (&$root_directory) { return str_replace($root_directory, '', $x); };
	$err = [
		'success' => false,
		'error' => $e->getCode(),
		'message' => $e->getMessage(),
		'file' => $strip($e->getFile()),
		// 'trace' => array_map($strip, $e->getTrace()),
	];
	
	if (!$server) {
		header("Content-Type: application/json; charset=UTF-8", true, 500);
		echo Zend_Json::encode($err);
		return;
	}
	
	$server->getClient()->sendResponse('500', $err);
}
// crmv@341217e

<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@170283 */
header("Access-Control-Allow-Headers: content-type, accept, authorization");
header("Access-Control-Allow-Methods: POST"); // GET, OPTIONS
header("Access-Control-Allow-Origin: *");

require('../../config.inc.php');
chdir($root_directory);
require_once('include/utils/utils.php');
SDK::getUtils();

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

$server = Server::create('/vtews', $restApi);
// $server->setDebugMode(true); // prints the debug trace, line number and file if a exception has been thrown.

$methods = $restApi->getMethods();
if (!empty($methods)) {
	foreach($methods as $row) {
		$server->addPostRoute($row['rest_name'],$row['rest_name']);
	}
}
$server->run();
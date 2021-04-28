<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("config.inc.php");
require_once('include/utils/utils.php');
require_once("include/HTTP_Session/Session.php");
require_once 'include/Webservices/Utils.php';
require_once("include/Webservices/State.php");
require_once("include/Webservices/OperationManager.php");
require_once("include/Webservices/SessionManager.php");
require_once("include/Zend/Json.php");
require_once('include/logging.php');
require_once "include/language/$default_language.lang.php";

$API_VERSION = "0.22";

// crmv@91979
require_once('include/MaintenanceMode.php');
if (MaintenanceMode::check()) {
	MaintenanceMode::displayWS();
	die();
}
// crmv@91979e

// crmv@146653
if (PHP_MAJOR_VERSION >= 7) {
	set_error_handler(function ($errno, $errstr) {
		return (strpos($errstr, 'Declaration of') === 0);
	}, E_WARNING);
}
// crmv@146653e

//crmv@sdk-18503 crmv@128133
require_once('modules/SDK/SDK.php');
SDK::getUtils();
//crmv@sdk-18503e crmv@128133e

global $seclog,$log;
$seclog =& LoggerManager::getLogger('SECURITY');
$log =& LoggerManager::getLogger('webservice');

// set timezone info
global $default_timezone;
if (!empty($default_timezone) && function_exists('date_default_timezone_set')) {
	@date_default_timezone_set($default_timezone);
}

function getRequestParamsArrayForOperation($operation){
	global $operationInput;
	return $operationInput[$operation];
}

function setResponseHeaders() {
	header('Content-type: application/json');
}

function writeErrorOutput($operationManager, $error){
	
	setResponseHeaders();
	$state = new State();
	$state->success = false;
	$state->error = $error;
	unset($state->result);
	$output = $operationManager->encode($state);
	wsLog('output',$output); //crmv@OPER10174
	echo $output;
}

function writeOutput($operationManager, $data){
	
	setResponseHeaders();
	$state = new State();
	$state->success = true;
	$state->result = $data;
	unset($state->error);
	$output = $operationManager->encode($state);
	wsLog('output',$output); //crmv@OPER10174
	echo $output;
}

//crmv@OPER10174 crmv@173186 crmv@176614
function wsLog($title,$str='',$new=false) {
	global $site_URL;
	// skip internal calls (ex. ProcessMaker)
	if (stripos($_SERVER['HTTP_REFERER'],$site_URL) !== false) return;
	// log the call (if disabled, it won't do anything)
	VTESystemLogger::log('webservices', $title, $str, $new);
}
//crmv@OPER10174e crmv@173186e crmv@176614e

$operation = vtws_getParameter($_REQUEST, "operation");
$operation = strtolower($operation);
$format = vtws_getParameter($_REQUEST, "format","json");
$sessionId = vtws_getParameter($_REQUEST,"sessionName");

$sessionManager = new SessionManager();
$operationManager = new OperationManager($adb,$operation,$format,$sessionManager);

try{
	if(!$sessionId || strcasecmp($sessionId,"null")===0){
		$sessionId = null;
	}
	
	wsLog('request',print_r($_REQUEST,true),true); //crmv@OPER10174
	
	$input = $operationManager->getOperationInput();
	$adoptSession = false;
	if(strcasecmp($operation,"extendsession")===0){
		if(isset($input['operation'])){
			// Workaround fix for PHP 5.3.x: $_REQUEST doesn't have PHPSESSID
			if(isset($_REQUEST['PHPSESSID'])) {
				$sessionId = vtws_getParameter($_REQUEST,"PHPSESSID");
			} else {
				// NOTE: Need to evaluate for possible security issues
				$sessionId = vtws_getParameter($_COOKIE,'PHPSESSID');
			}
			// END
			$adoptSession = true;
		}else{
			writeErrorOutput($operationManager,new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,"Authencation required"));
			return;
		}
	}
	$sid = $sessionManager->startSession($sessionId,$adoptSession);
	
	if(!$sessionId && !$operationManager->isPreLoginOperation()){
		writeErrorOutput($operationManager,new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,"Authencation required"));
		return;
	}
	
	if(!$sid){
		writeErrorOutput($operationManager, $sessionManager->getError());
		return;
	}
	
	$userid = $sessionManager->get("authenticatedUserId");
	
	if($userid){
		
		$seed_user = CRMEntity::getInstance('Users');
		$current_user = $seed_user->retrieveCurrentUserInfoFromFile($userid);
		
	}else{
		$current_user = null;
	}
	
	wsLog('',"userId:{$current_user->id} sessionName:$sessionId clientIP:".getIp()); //crmv@OPER10174 crmv@173186
	
	$operationInput = $operationManager->sanitizeOperation($input);
	$includes = $operationManager->getOperationIncludes();
	
	foreach($includes as $ind=>$path){
		require_once($path);
	}
	$rawOutput = $operationManager->runOperation($operationInput,$current_user);
	writeOutput($operationManager, $rawOutput);
}catch(WebServiceException $e){
	writeErrorOutput($operationManager,$e);
}catch(Exception $e){
	writeErrorOutput($operationManager, new WebServiceException(WebServiceErrorCode::$INTERNALERROR,"Unknown Error while processing request"));
}
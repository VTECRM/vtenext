<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("include/HTTP_Session/Session.php");
require_once 'include/Webservices/Utils.php';
require_once("include/Webservices/State.php");
require_once("include/Webservices/OperationManager.php");
require_once("include/Webservices/SessionManager.php");
require_once("include/Zend/Json.php");
require_once('include/logging.php');
require_once "include/language/$default_language.lang.php";

$API_VERSION = "0.22";

global $seclog,$log;
$seclog =& LoggerManager::getLogger('SECURITY');
$log =& LoggerManager::getLogger('webservice');

function getRequestParamsArrayForOperation($operation){
	global $operationInput;
	return $operationInput[$operation];
}

function setResponseHeaders() {
	header('Content-type: application/json');
}

function writeErrorOutput($operationManager, $error){
	return array('success'=>false,'error'=>$error);
}

function writeOutput($operationManager, $data){
	return array('success'=>true,'result'=>$data);
}

function wsRequest($userid,$operation,$operationInput,$format='json') {
	global $adb,$seclog,$log;

	$operation = strtolower($operation);
	$sessionId = vtws_getParameter($_REQUEST,"sessionName");

	$sessionManager = new SessionManager();
	$operationManager = new OperationManager($adb,$operation,$format,$sessionManager);

	try{
		if($userid){
			$seed_user = new Users();
			$wsuser = $seed_user->retrieveCurrentUserInfoFromFile($userid);
			$wsuser->id = $userid;
		}else{
			$wsuser = null;
		}
		$includes = $operationManager->getOperationIncludes();
		foreach($includes as $ind=>$path){
			require_once($path);
		}
		$rawOutput = $operationManager->runOperation($operationInput,$wsuser);
		return writeOutput($operationManager, $rawOutput);
	}catch(WebServiceException $e){
		return writeErrorOutput($operationManager,$e);
	}catch(Exception $e){
		return writeErrorOutput($operationManager, new WebServiceException(WebServiceErrorCode::$INTERNALERROR,"Unknown Error while processing request"));
	}
}

function getWsErrorMessage($module,$error) {
	global $adb, $current_user,$table_prefix;

	$code = $error->code;
	$message = $error->message;
	switch ($code) {
		case 'MANDATORY_FIELDS_MISSING':
			require_once('include/Webservices/WebserviceField.php');
			$fieldname = substr($message,0,strpos($message,' '));
			$moduleInstance = Vtecrm_Module::getInstance($module);
			$fieldInstance = WebserviceField::fromQueryResult($adb,$adb->pquery('SELECT * FROM '.$table_prefix.'_field WHERE tabid = ? AND fieldname = ?',array($moduleInstance->id,$fieldname)),0);
			$fieldlabel = $fieldInstance->getFieldLabelKey();
			$return = getTranslatedString($fieldlabel,$module).' '.getTranslatedString('CANNOT_BE_EMPTY','ALERT_ARR');
			break;
	}
	return $return;
}
?>
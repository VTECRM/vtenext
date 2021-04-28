<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
ini_set('include_path',ini_get('include_path').':../..');

	require_once("config.inc.php");
	require_once("include/HTTP_Session/Session.php");
	require_once 'include/Webservices/Utils.php';
	require_once("modules/Users/Users.php");
	require_once("include/Webservices/State.php");
	require_once("include/Webservices/OperationManager.php");
	require_once("include/Webservices/SessionManager.php");
	require_once 'include/Webservices/WebserviceField.php';
	require_once 'include/Webservices/EntityMeta.php';
	require_once 'include/Webservices/VtenextWebserviceObject.php';//crmv@207871
	require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
	require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
	require_once("include/Webservices/DataTransform.php");
	require_once("include/Webservices/WebServiceError.php");
	require_once 'include/Webservices/ModuleTypes.php';
	require_once 'include/Webservices/WebserviceEntityOperation.php';
	require_once "include/language/$default_language.lang.php";
require_once 'include/Webservices/Retrieve.php';
require_once('modules/Emails/mail.php');
require_once 'modules/Users/Users.php';
require_once('VTSimpleTemplate.inc');
require_once 'VTEntityCache.inc';
require_once('VTWorkflowUtils.php');

	require 'include.inc';
	
	function vtRunTaskJob($adb){
		$util = new VTWorkflowUtils();
		$adminUser = $util->adminUser();
		$tq = new VTTaskQueue($adb);
		$readyTasks = $tq->getReadyTasks();
		$tm = new VTTaskManager($adb);
		foreach($readyTasks as $pair){
			list($taskId, $entityId) = $pair;
			$task = $tm->retrieveTask($taskId);
			$entity = new VTWorkflowEntity($adminUser, $entityId);
			$task->doTask($entity);
		}
	}
	vtRunTaskJob($adb);
?>
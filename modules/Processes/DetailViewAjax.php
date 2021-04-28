<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@67410

global $currentModule, $current_user;
$modObj = CRMEntity::getInstance($currentModule);

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == "DETAILVIEW")
{
	$crmid = $_REQUEST["recordid"];
	$tablename = $_REQUEST["tableName"];
	$fieldname = $_REQUEST["fldName"];
	$fieldvalue = utf8RawUrlDecode($_REQUEST["fieldValue"]); 
	if($crmid != "")
	{
		$permEdit = isPermitted($currentModule, 'DetailViewAjax', $crmid);
		$permField = getFieldVisibilityPermission($currentModule, $current_user->id, $fieldname);
		//crmv@96450
		if ($permEdit == 'yes') {
			$modObj->retrieve_entity_info($crmid,$currentModule);
			
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$processDynaFormObj->retrieveDynaform($modObj,$_REQUEST);
			$modObj->column_fields[$fieldname] = $fieldvalue;
			$_REQUEST[$fieldname] = $fieldvalue;
			
			if ($permField == 0 || $processDynaFormObj->getFieldVisibilityPermission($modObj,$fieldname)) {
				$modObj->id = $crmid;
				$modObj->mode = "edit";
				$modObj->save($currentModule);
				if($modObj->id != "") {
					echo ":#:SUCCESS";
				} else {
					echo ":#:FAILURE";
				}
			} else {
				echo ":#:FAILURE";
			}
		//crmv@96450e
		} else {
			echo ":#:FAILURE";
		}
	} else {
		echo ":#:FAILURE";
	}
} elseif($ajaxaction == "LOADRELATEDLIST" || $ajaxaction == "DISABLEMODULE"){
	require_once 'include/ListView/RelatedListViewContents.php';
//crmv@99316
} elseif($ajaxaction == "DYNAFORMCONDITIONALS"){
	require_once('modules/SDK/src/29/29Utils.php');
	$uitypeFileUtils = UitypeFileUtils::getInstance();
	$uitypeFileUtils->uploadTempFiles();
	
	require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
	$processDynaFormObj = ProcessDynaForm::getInstance();
	$output = $processDynaFormObj->applyConditionals($_REQUEST['mode'],$_REQUEST['record'],Zend_Json::decode($_REQUEST['dynaform']));
	if (!empty($output)) echo Zend_Json::encode($output);
	exit;
//crmv@99316e
//crmv@100495
} elseif($ajaxaction == "RUNPROCESSMANUALLY"){
	$module = $_REQUEST['pmodule'];
	$record = $_REQUEST["record"];
	
	$focus = CRMEntity::getInstance($module);
	$focus->retrieve_entity_info_no_html($record,$module);
	$focus->mode = 'edit';
	
	require_once("include/events/include.inc");
	require_once("modules/Settings/ProcessMaker/ProcessMakerHandler.php");
	$em = new VTEventsManager($adb);
	// Initialize Event trigger cache
	$em->initTriggerCache();
	$entityData  = VTEntityData::fromCRMEntity($focus);
	$processMakerHandler = new ProcessMakerHandler();
	ProcessMakerHandler::$manual_mode[$module] = true;
	$processMakerHandler->handleEvent('vte.entity.aftersave.processes', $entityData); // crmv@177677 crmv@207852
	
	$running_processes = ProcessMakerHandler::$running_processes;
	$message = 'LBL_NO_RUN_PROCESSES';
	if (!empty($running_processes)) {
		foreach($running_processes as $info) {
			if ($info['evaluated'] === true) {
				$message = 'LBL_RUN_PROCESSES_OK';
				break;
			}
		}
	}
	die($message);
//crmv@100495e
//crmv@93990
} elseif($ajaxaction == 'DYNAFORMPOPUP'){
	$record = $_REQUEST["record"];
	$currentModule = 'Processes';
	include('modules/Processes/EditView.php');
} elseif($ajaxaction == 'CHECKDYNAFORMPOPUP'){
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	$related = $PMUtils->getProcessRelatedTo($_REQUEST["record"],'processesid');
	if ($related !== false) echo ":#:SUCCESS".$related;
	exit;
//crmv@93990e
//crmv@176621
} elseif($ajaxaction == 'SHOWGRAPHTAB'){
	$record = $_REQUEST["record"];
	$module = $_REQUEST["rel_module"];
	$focus = CRMEntity::getInstance($module);
	echo $focus->generateProcessTab($module,$record,'graph'); // crmv@188364
	exit;
//crmv@176621e
//crmv@101506
} elseif($ajaxaction == 'SHOWGRAPH'){
	$record = $_REQUEST["record"];
	$focus = CRMEntity::getInstance('Processes');
	$focus->retrieve_entity_info_no_html($record,'Processes');
	echo Zend_Json::encode($focus->getProcessGraphInfo());
	exit;
//crmv@101506e
} elseif($ajaxaction == "VALIDATIONDATA"){
	$focus = CRMEntity::getInstance($currentModule);
	$focus->retrieve_entity_info_no_html($record,$currentModule);
	$tabid = getTabid($currentModule);
	//crmv@112297
	$otherInfo = array();
	$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus);
	$validationArray = split_validationdataArray($validationData,$otherInfo);
	echo Zend_Json::encode(array(
		'fieldname'=>$validationArray['fieldname'],
		'fieldlabel'=>$validationArray['fieldlabel'],
		'fielddatatype'=>$validationArray['datatype'],
		'fielduitype'=>$validationArray['fielduitype'],
		'fieldwstype'=>$validationArray['fieldwstype'],
	));
	//crmv@112297e
	exit;
//crmv@112539 crmv@112297
} elseif($ajaxaction == 'DELETERECORD'){
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	$PMUtils->deleteRecord($_REQUEST['processesid'], $_REQUEST['elementid'], $_REQUEST['record_module'], $_REQUEST['record']);
} elseif($ajaxaction == 'CONTINUEEXECUTION' || $ajaxaction == 'CHANGEPOSITION'){
	$record = $_REQUEST["record"];
	$focus = CRMEntity::getInstance('Processes');
	$focus->retrieve_entity_info_no_html($record,'Processes');
	
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	$success = $PMUtils->rollback(($ajaxaction == 'CHANGEPOSITION')?'change_position':'continue_execution',$focus,vtlib_purify($_REQUEST['elementid']));
	if ($success === true)
		echo 'SUCCESS';
	elseif ($success === false)
		echo 'FAILED';
	else
		echo 'ERROR';
	exit;
//crmv@112539e crmv@112297e
// crmv@188364
} elseif($ajaxaction == 'SHOWHISTORYTAB'){
	$record = $_REQUEST["record"];
	$module = $_REQUEST["rel_module"];
	$focus = CRMEntity::getInstance($module);
	echo $focus->generateProcessTab($module,$record,'history');
	exit;
} elseif($ajaxaction == 'SHOWHISTORY'){
	$record = $_REQUEST["record"];
	$focus = CRMEntity::getInstance('Processes');
	$focus->retrieve_entity_info_no_html($record,'Processes');
	echo $focus->getHTMLHistory();
	exit;
// crmv@188364e
// crmv@200816
} elseif($ajaxaction == 'CHECKRECORD') {
	$record = intval($_REQUEST["rel_record"]); // crmv@207091
	$module = vtlib_purify($_REQUEST["rel_module"]);
	$focus = CRMEntity::getInstance($module);
	$r = $focus->checkRetrieve($record, $module, false);
	if ($r !== null && !empty(VteSession::get('PM_DELETED_RECORD'))) {
		$record = VteSession::get('PM_DELETED_RECORD');
		VteSession::remove('PM_DELETED_RECORD');
		$module = getSalesEntityType($record);
		echo Zend_Json::encode(array('record'=>$record,'module'=>$module));
	} else {
		echo Zend_Json::encode(array('record'=>$record,'module'=>$module));
	}
	exit;
}
// crmv@200816e
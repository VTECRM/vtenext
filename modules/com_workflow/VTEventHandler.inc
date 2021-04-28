<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('VTWorkflowManager.inc');
require_once('VTTaskManager.inc');
require_once('VTTaskQueue.inc');
require_once('VTEntityCache.inc');

require_once 'include/Webservices/Utils.php';
require_once("modules/Users/Users.php");
require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once 'include/Webservices/ModuleTypes.php';
require_once('include/Webservices/Retrieve.php');
require_once('include/Webservices/Update.php');
require_once 'include/Webservices/WebserviceField.php';
require_once 'include/Webservices/EntityMeta.php';
require_once 'include/Webservices/VtenextWebserviceObject.php';//crmv@207871
require_once('VTWorkflowUtils.php');

/*
 * VTEventHandler 
 */
class VTWorkflowEventHandler extends VTEventHandler{
	/**
	 * Push tasks to the task queue if the conditions are true
	 * @param $entityData A VTEntityData object representing the entity.
	 */
	function handleEvent($eventName, $entityData){
		$util = new VTWorkflowUtils();
		$user = $util->adminUser();
		global $adb,$table_prefix;
		$isNew = $entityData->isNew();
		$entityCache = new VTEntityCache($user);
		$wsModuleName = $util->toWSModuleName($entityData);
		$wsId = vtws_getWebserviceEntityId($wsModuleName, 
										   $entityData->getId());
		$entityData = $entityCache->forId($wsId);
		$data = $entityData->getData();
		//crmv@44747
		if (empty($data)) {
			$util->revertUser();
			return;
		}
		//crmv@44747e
		$wfs = new VTWorkflowManager($adb);
		$workflows = $wfs->getWorkflowsForModule($entityData->getModuleName());
		$tm = new VTTaskManager($adb);
		$taskQueue = new VTTaskQueue($adb);
		
		foreach($workflows as $workflow){
			switch($workflow->executionCondition){
				case VTWorkflowManager::$ON_FIRST_SAVE:{
					if($isNew){
						$doEvaluate = true;
					}else{
						$doEvaluate = false;
					}
					break;
				}
				case VTWorkflowManager::$ONCE:{
					$entity_id = vtws_getIdComponents($entityData->getId());
					$entity_id = $entity_id[1];
					$result = $adb->pquery("SELECT * FROM com_".$table_prefix."_wf_activatedonce 
						WHERE entity_id=? and workflow_id=?", array($entity_id, $workflow->id));
					
					//Changes
					$result2=$adb->pquery("SELECT * FROM com_".$table_prefix."_workflowtasks 
									INNER JOIN com_".$table_prefix."_workflowtask_queue
									ON com_".$table_prefix."_workflowtasks.task_id= com_".$table_prefix."_workflowtask_queue.task_id
									WHERE workflow_id=? AND entity_id=?",
									array($workflow->id,$entity_id));
					
					if($adb->num_rows($result)===0 && $adb->num_rows($result2)===0){
						$doEvaluate = true;
					}else{
						$doEvaluate = false;
					}
					break;
				}
				case VTWorkflowManager::$ON_EVERY_SAVE:{
					$doEvaluate = true;
					break;
				}
				case VTWorkflowManager::$ON_MODIFY:{
					$doEvaluate = !($isNew);
				    break;
				}
				default:{
					throw new Exception("Should never come here! Execution Condition:".$workflow->executionCondition);
				}
				
			}
			if($doEvaluate && $workflow->evaluate($entityCache, $entityData->getId())){
			
				$resetCache = false; // crmv@193294
				
				if(VTWorkflowManager::$ONCE == $workflow->executionCondition) {
					$entity_id = vtws_getIdComponents($entityData->getId());
					$entity_id = $entity_id[1];
					$adb->pquery("INSERT INTO com_".$table_prefix."_wf_activatedonce (entity_id, workflow_id)
						VALUES (?,?)", array($entity_id, $workflow->id));
				}
				
				$tasks = $tm->getTasksForWorkflow($workflow->id);
				foreach($tasks as $task){
					if($task->active) {
						$trigger = $task->trigger;
						if($trigger != null){
							//crmv@32366
							if ($trigger['field'] == '') {
								$time = date('Y-m-d H:i:s');
							} else {
								$time = $data[$trigger['field']];
							}
							$delay = strtotime($time)+$trigger['days']*86400;
							//crmv@32366e
						}else{
							$delay = 0;
							$task->executeImmediately = true;	//crmv@32366
						}
						if($task->executeImmediately==true){
							$entityData->data = $entityCache->forId($wsId)->getData(); //crmv@79058
							$task->doTask($entityData);
							if ($task->hasChangedData()) $resetCache = true; // crmv@193294
						}else{
							$taskQueue->queueTask($task->id,$entityData->getId(), $delay);
						}
					}
				}
				// crmv@193294
				if ($resetCache) {
					VTEntityCache::setResetCache($entityData->getId());
				}
				// crmv@193294e
			}
		}
		$util->revertUser();
	}

	// crmv@91571
	/**
	 * Optimized method which executes all the workflows for a list of records of the same module.
	 * The $mode can be "create" or "update", and all the records must be just created or updated.
	 * Note that workflows containing the condition "has changed to" won't work with this method
	 */
	public function massWorkflows($mode, $module, $records = array()){
		global $adb,$current_user,$table_prefix;
		
		if (empty($records)) return;
		
		$util = new VTWorkflowUtils();
		$user = $util->adminUser();
		
		$wfs = new VTWorkflowManager($adb);
		$tm = new VTTaskManager($adb);
		$taskQueue = new VTTaskQueue($adb);
		
		$workflows = $wfs->getWorkflowsForModule($module);
		$taskCache = array();
		
		// get once_workflows
		$onceWorkflows = array();
		foreach($workflows as $workflow){
			if ($workflow->executionCondition == VTWorkflowManager::$ONCE) {
				$onceWorkflows[] = $workflow->id;
			}
		}
		
		// move here the 2 queries to improve performance
		$activeOnce = array();
		$queueOnce = array();
		if (count($onceWorkflows) > 0) {
			$chunks = array_chunk($records, 250);
			foreach ($chunks as $chunk) {
				$chunk = array_map('intval', $chunk);
				$result = $adb->pquery(
					"SELECT workflow_id, entity_id FROM com_".$table_prefix."_wf_activatedonce
					WHERE workflow_id IN (".generateQuestionMarks($onceWorkflows).") AND entity_id IN (".generateQuestionMarks($chunk).")",
					array($onceWorkflows, $chunk)
				);
				while ($row = $adb->FetchByAssoc($result,-1, false)) {
					$wfid = intval($row['workflow_id']);
					$eid = intval($row['entity_id']);
					$activeOnce[$wfid][$eid] = $eid;
				}
				
				$result2=$adb->pquery(
					"SELECT com_".$table_prefix."_workflowtasks.workflow_id, com_".$table_prefix."_workflowtask_queue.entity_id FROM com_".$table_prefix."_workflowtasks
					INNER JOIN com_".$table_prefix."_workflowtask_queue
					ON com_".$table_prefix."_workflowtasks.task_id= com_".$table_prefix."_workflowtask_queue.task_id
					WHERE workflow_id IN (".generateQuestionMarks($onceWorkflows).") AND entity_id IN (".generateQuestionMarks($chunk).")",
					array($onceWorkflows, $chunk)
				);
				while ($row = $adb->FetchByAssoc($result,-1, false)) {
					$wfid = intval($row['workflow_id']);
					$wsid = vtws_getIdComponents($row['entity_id']);
					$eid = $wsid[1];
					$queueOnce[$wfid][$eid] = $eid;
				}
			}
		}
		
		$isNew = ($mode == 'create');
		
		foreach ($records as $id) {
			
			$id = intval($id);
			$wsId = vtws_getWebserviceEntityId($module, $id);
			$entityCache = new VTEntityCache($user);				
			$entityData = $entityCache->forId($wsId);
		
			foreach($workflows as $workflow){				
				
				switch($workflow->executionCondition){
					case VTWorkflowManager::$ON_FIRST_SAVE:{
						if($isNew){
							$doEvaluate = true;
						}else{
							$doEvaluate = false;
						}
						break;
					}
					case VTWorkflowManager::$ONCE:{
						if(!isset($activeOnce[$workflow->id][$id]) && !isset($queueOnce[$workflow->id][$id])){
							$doEvaluate = true;
						}else{
							$doEvaluate = false;
						}
						break;
					}
					case VTWorkflowManager::$ON_EVERY_SAVE:{
						$doEvaluate = true;
						break;
					}
					case VTWorkflowManager::$ON_MODIFY:{
						$doEvaluate = !($isNew);
						break;
					}
					default:{
						throw new Exception("Should never come here! Execution Condition:".$workflow->executionCondition);
					}
		
				}
				
				if($doEvaluate && $workflow->evaluate($entityCache, $entityData->getId())) {
				
					$resetCache = false; // crmv@193294

					if(VTWorkflowManager::$ONCE == $workflow->executionCondition) {
						$adb->pquery("INSERT INTO com_".$table_prefix."_wf_activatedonce (entity_id, workflow_id)
							VALUES (?,?)", array($id, $workflow->id));
						$activeOnce[$workflow->id][$id] = $id;
					}
		
					if (!isset($taskCache[$workflow->id])) {
						$taskCache[$workflow->id] = $tm->getTasksForWorkflow($workflow->id);
					}
					$tasks = $taskCache[$workflow->id];
					
					foreach($tasks as $task){
						if($task->active) {
							$trigger = $task->trigger;
							if($trigger != null){
								$delay = strtotime($data[$trigger['field']])+$trigger['days']*86400;
							}else{
								$delay = 0;
							}
							if($task->executeImmediately==true){
								$task->doTask($entityData);
								if ($task->hasChangedData()) $resetCache = true; // crmv@193294
							}else{
								$taskQueue->queueTask($task->id,$entityData->getId(), $delay);
								// save into the local cache
								$queueOnce[$workflow->id][$id] = $id;
							}
						}
					}
					
					// crmv@193294
					if ($resetCache) {
						VTEntityCache::setResetCache($entityData->getId());
					}
					// crmv@193294e
				}
			}
		}

		$util->revertUser();
	}
	// crmv@91571e

}
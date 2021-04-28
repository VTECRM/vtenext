<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@193294 */

require_once("VTJsonCondition.inc");

class VTWorkflowManager{
	
	static $ON_FIRST_SAVE = 1;
	static $ONCE = 2;
	static $ON_EVERY_SAVE = 3;
	static $ON_MODIFY = 4;
		
	private static $tableCache = null;
	
	function __construct($adb){
		$this->adb = $adb;
		
		if (!self::$tableCache) {
			global $table_prefix;
			self::$tableCache = new ClusteredTableCache("com_".$table_prefix."_workflows", 'workflow_id', 'module_name');
			//self::$tableCache->setOrderBy('module_name ASC, sequence ASC');
		}
	}
	
	function save($workflow){
	    global $table_prefix;
		$adb=$this->adb;
		if(isset($workflow->id)){
			$wf=$workflow;
			$adb->pquery("update com_".$table_prefix."_workflows set
							module_name=?, summary=?, test=?, execution_condition=?
							where workflow_id=?", 
				array($wf->moduleName, $wf->description, $wf->test, $wf->executionCondition, $wf->id));

		}else{
			$workflowId = $adb->getUniqueID("com_".$table_prefix."_workflows");
			$workflow->id = $workflowId;
			$wf=$workflow;
			$adb->pquery("insert into com_".$table_prefix."_workflows 
							(workflow_id, module_name, summary, test, execution_condition) 
							values (?,?,?,?,?)", 
				array($workflowId, $wf->moduleName, $wf->description, $wf->test,  $wf->executionCondition));

		}
		self::$tableCache->invalidateCache($wf->moduleName);
	}
		
	function getWorkflows(){
		$adb=$this->adb;
		global $table_prefix;
		$result=$adb->getColumnNames("com_".$table_prefix."_workflows");
		if(in_array("defaultworkflow",$result)){
			$result = $adb->query("select workflow_id, module_name, summary, test, execution_condition,defaultworkflow 
								from com_".$table_prefix."_workflows");
		}else{
			
			$result = $adb->query("select workflow_id, module_name, summary, test, execution_condition 
								from com_".$table_prefix."_workflows");
		}						
		return $this->getWorkflowsForResult($result);
		
	}
	
	function getWorkflowsForModule($moduleName){
		$rows = self::$tableCache->getRows($moduleName);
		return $this->getWorkflowsForRows($rows);
	}
	
	private function getWorkflowsForRows($rows){
		$workflows=array();
		foreach($rows as $row){
			$workflow=new Workflow();
			$workflow->id = $row['workflow_id'];
			$workflow->moduleName = $row['module_name'];
			$workflow->description = $row['summary'];
			$workflow->test = $row['test'];
			$workflow->executionCondition = $row['execution_condition'];
		
			if($row['defaultworkflow']){
				$workflow->defaultworkflow=$row['defaultworkflow'];
			}
			$workflows[]=$workflow;
		}
		return $workflows;
	}
	
	private function getWorkflowsForResult($result){
		$adb=$this->adb;
		$it = new SqlResultIterator($adb, $result);
		$workflows=array();
		foreach($it as $row){
			$workflow=new Workflow();
			$workflow->id = $row->workflow_id;
			$workflow->moduleName = $row->module_name;
			$workflow->description = $row->summary;
			$workflow->test = $row->test;
			$workflow->executionCondition = $row->execution_condition;
		
			if($row->defaultworkflow){
				$workflow->defaultworkflow=$row->defaultworkflow;
			}

			$workflows[]=$workflow;
		}
		return $workflows;
	}
	
	/**
	 * Retrieve a workflow from the database
	 *
	 * Returns null if the workflow doesn't exist.
	 *
	 * @param The id of the workflow
	 * @return A workflow object.
	 */
	function retrieve($id){
		$adb=$this->adb;
		global $table_prefix;
		$workflow=new Workflow();
		$result = $adb->pquery("select workflow_id, module_name, summary, test, execution_condition 
								from com_".$table_prefix."_workflows where workflow_id=?", 
					array($id));
		if($adb->num_rows($result)){
			$data = $adb->raw_query_result_rowdata($result, 0);
			$workflow->id = $data["workflow_id"];
			$workflow->moduleName = $data["module_name"];
			//crmv@16312
			$workflow->description = to_html($data["summary"]);
			//crmv@16312 end
			$workflow->test = $data["test"];
			$workflow->executionCondition = $data['execution_condition'];

			return $workflow;
		}else{
			return null;
		}
	}
	
	function delete($id){
		$adb=$this->adb;
		global $table_prefix;
		
		$wf = $this->retrieve($id);
		$module = $wf->moduleName;
		
		$adb->pquery("delete from com_".$table_prefix."_workflowtasks where workflow_id=?", array($id));
		$adb->pquery("delete from com_".$table_prefix."_workflows where workflow_id=?", array($id));
		
        self::$tableCache->invalidateCache($module);
	}
	
	function newWorkflow($moduleName){
		$workflow=new Workflow();
		$workflow->moduleName = $moduleName;
		$workflow->executionCondition = self::$ON_EVERY_SAVE;
		return $workflow;
	}

	/**
	 * Export a workflow as a json encoded string
	 *
	 * @param $workflow The workflow instance to export.
	 */
	public function serializeWorkflow($workflow){
		$exp = array();
		$exp['moduleName'] = $workflow->moduleName;
		$exp['description'] = $workflow->description;
		$exp['test'] = $workflow->test;
		$exp['executionCondition'] = $workflow->executionCondition;
		$exp['tasks'] = array();
		$tm = new VTTaskManager($this->adb);
		$tasks = $tm->getTasksForWorkflow($workflow->id);
		foreach($tasks as $task){
			unset($task->id);
			unset($task->workflowId);
			$exp['tasks'][] = serialize($task);
		}
		return  Zend_Json::encode($exp);
	}

	/**
	 * Import a json encoded string as a workflow object
	 * 
	 * @return The Workflow instance representing the imported workflow.
	 */
	public function deserializeWorkflow($str){
		$data =  Zend_Json::decode($str);
		$workflow = $this->newWorkflow($data['moduleName']);
		$workflow->description = $data['description'];
		$workflow->test = $data['test'];
		$workflow->executionCondition = $data['executionCondition'];
		$this->save($workflow);
		$tm = new VTTaskManager($this->adb);
		$tasks = $data['tasks'];
		foreach($tasks as $taskStr){
			$task = $tm->unserializeTask($taskStr);
			$task->workflowId = $workflow->id;
			$tm->saveTask($task);
		}
		return $workflow;
	}
	
}

class Workflow{
	function __construct(){
		$this->conditionStrategy = new VTJsonCondition();
	}
	
	function evaluate($entityCache, $id){
		if($this->test==""){
			return true;
		}else{
			$cs = $this->conditionStrategy;
			return $cs->evaluate($this->test, 
								 $entityCache, $id);
		}
	}
	
	function executionConditionAsLabel($label=null){
		if($label==null){
			$arr = array('ON_FIRST_SAVE', 'ONCE', 'ON_EVERY_SAVE','ON_MODIFY');
			return $arr[$this->executionCondition-1];
		}else{
			$arr = array('ON_FIRST_SAVE'=>1, 'ONCE'=>2, 'ON_EVERY_SAVE'=>3, 'ON_MODIFY'=>4);
			$this->executionCondition = $arr[$label];
		}
	}
}
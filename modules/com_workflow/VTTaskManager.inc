<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@193294 */

/**
 * Functionality to save and retrieve Tasks from the database.
 */
class VTTaskManager{
	
	private static $tableCache = null;
	
	function __construct($adb){
		$this->adb = $adb;
		
		if (!self::$tableCache) {
			global $table_prefix;
			self::$tableCache = new ClusteredTableCache("com_".$table_prefix."_workflowtasks", 'task_id', 'workflow_id');
		}
	}
	
	/**
	 * Save the task into the database.
	 *
	 * When a new task is saved for the first time a field is added to it called
	 * id that stores the task id used in the database.
	 *
	 * @param $summary A summary of the task instance.
	 * @param $task The task instance to save.
	 * @return The id of the task 
	 */
	public function saveTask($task){
		$adb = $this->adb;
		global $table_prefix;
		if(is_numeric($task->id)){//How do I check whether a member exists in php?
			$taskId = $task->id;
			$adb->pquery("update com_".$table_prefix."_workflowtasks set summary=?, task=? where task_id=?", 
						array($task->summary, serialize($task), $taskId));	
		}else{
			$taskId = $adb->getUniqueID("com_".$table_prefix."_workflowtasks");
			$task->id = $taskId;
			$adb->pquery("insert into com_".$table_prefix."_workflowtasks 
						(task_id, workflow_id, summary, task) 
						values (?, ?, ?, ?)", 
				array($taskId, $task->workflowId, $task->summary, serialize($task)));
		}
		self::$tableCache->invalidateCache($task->workflowId);
		return $taskId;
	}
	
	public function deleteTask($taskId){
		$adb = $this->adb;
		global $table_prefix;
		
		$result = $adb->pquery("select workflow_id from com_".$table_prefix."_workflowtasks where task_id=?", array($taskId));
		$wfid = $adb->query_result_no_html($result, 0, 'workflow_id');
		
		$adb->pquery("delete from com_".$table_prefix."_workflowtasks where task_id=?", array($taskId));
		self::$tableCache->invalidateCache($wfid);
	}
	
	
	/**
	 * Create a new class instance
	 */
	public function createTask($taskType, $workflowId){
		$taskClass = $taskType;
		$this->requireTask($taskType);
		$task = new $taskClass();
		$task->workflowId=$workflowId;
		$task->summary = "";
		$task->active=true; 
		return $task;
	}
	
	
	/**
	 * Retrieve a task from the database
	 *
	 * @param $taskId The id of the task to retrieve.
	 * @return The retrieved task.
	 */
	public function retrieveTask($taskId){
	    global $table_prefix;
		$adb = $this->adb;
		$result = $adb->pquery("select task from com_".$table_prefix."_workflowtasks where task_id=?", array($taskId));
		$data = $adb->raw_query_result_rowdata($result, 0);
		$task = $data["task"];
		return $this->unserializeTask($task);
	}
	
	
	/**
	 *
	 */
	public function getTasksForWorkflow($workflowId){
		$rows = self::$tableCache->getRows($workflowId);
		return $this->getTasksForRows($rows);
	}


	/**
	 * 
	 */
	public function unserializeTask($str){
		$this->requireTask(self::taskName($str));
		return unserialize($str);

	}
	/**
	 *
	 */
	function getTasks(){
		global $table_prefix;
		$adb = $this->adb;
		$result = $adb->query("select task from com_".$table_prefix."_workflowtasks");
		return $this->getTasksForResult($result);
	}
	
	
	function getTaskTypes(){
		$taskTypes = array("VTEmailTask", "VTEntityMethodTask", "VTCreateTodoTask","VTCreateEventTask");
		// Make SMSTask available if module is active
		// TODO Generic way of handling this could be helpful
		if(getTabid('SMSNotifier') && vtlib_isModuleActive('SMSNotifier')) {
			$taskTypes [] = 'VTSMSTask'; 	
		}
		$taskTypes [] = 'VTUpdateFieldTask';	//crmv@18199
		return $taskTypes;
	}
	
	private function getTasksForRows($rows){
		$tasks = array();
		foreach($rows as $row){
			$text = $row['task'];
			$this->requireTask(self::taskName($text));
			$tasks[] = unserialize($text);
		}
		return $tasks;
	}
	
	private function getTasksForResult($result){
		$adb = $this->adb;
		$it = new SqlResultIterator($adb, $result);
		$tasks = array();
		foreach($it as $row){
			$text = $row->task;
			$this->requireTask(self::taskName($text));
			$tasks[] = unserialize($text);
		}
		return $tasks;
	}
	
	private function taskName($serializedTask){
		$matches = array();
		preg_match ('/"([^"]+)"/', $serializedTask, $matches);
		return $matches[1];
	}
	
	private function requireTask($taskType){
		require_once("tasks/".$taskType.".inc");
	}
	
}

//crmv@25443
abstract class VTTask{
	public abstract function doTask($data);
	public abstract function getFieldNames();

	public function getTimeFieldList() {
		return array();
	}

	public function formatTimeForTimePicker($time) {
		list($h, $m, $s) = explode(':', $time);
		$mn = str_pad($m - $m % 15, 2, 0, STR_PAD_LEFT);
		$AM_PM = array('am', 'pm');
		return str_pad(($h%12), 2, 0, STR_PAD_LEFT).':'.$mn.$AM_PM[($h/12)%2];
	}
	
	//crmv@36510 crmv@162158
	public function getMetaVariables($detail_url=true, $client_info=false) {
		$meta_variables = array(
			'Current Date (yyyy-mm-dd)' => '(general : (__VteMeta__) date_Y_m_d)',
			'Current Date (dd-mm-yyyy)' => '(general : (__VteMeta__) date_d_m_Y)',
			'Current Date (mm-dd-yyyy)' => '(general : (__VteMeta__) date_m_d_Y)',
			'Current Date (in user format)' => '(general : (__VteMeta__) date)',
			'Current Time' => '(general : (__VteMeta__) time)',
			'System Timezone' => '(general : (__VteMeta__) dbtimezone)',
			'Site Url' => '(general : (__VteMeta__) siteurl)',
			'Portal Url' => '(general : (__VteMeta__) portalurl)'
		);
		if ($detail_url) {
			$meta_variables['CRM Detail View URL'] = '(general : (__VteMeta__) crmdetailviewurl)';
			$meta_variables['Portal Detail View URL'] = '(general : (__VteMeta__) portaldetailviewurl)';
		}
		if ($client_info) {
			$meta_variables['Client IP'] = '(general : (__VteMeta__) clientip)';
		}
		return $meta_variables;
	}
	//crmv@36510e crmv@162158e
	
	// crmv@193294
	protected $changedData = false;
	
	public function hasChangedData() {
		return $this->changedData;
	}
	
	protected function setChangedData($value = true) {	
		$this->changedData = $value;
	}
	// crmv@193294e
	
}
//crmv@25443e

//require 'modules/Workflow/tasks/VTEmailTask.inc';
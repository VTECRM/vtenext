<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//A collection of util functions for the workflow module

class VTWorkflowUtils{

	function __construct(){
		global $current_user;
		$this->userStack = array();
	}
	/**
	 * Check whether the given identifier is valid.
	 */
	function validIdentifier($identifier){
		if(is_string($identifier)){
			return preg_match("/^[a-zA-Z][a-zA-Z_0-9]+$/", $identifier);
		}else{
			return false;
		}
	}


	/**
	 * Push the admin user on to the user stack
	 * and make it the $current_user
	 *
	 */
	function adminUser(){
		$user = Users::getActiveAdminUser(); //crmv@180676
		global $current_user;
		array_push($this->userStack, $current_user);
		$current_user = $user;
		return $user;
	}

	/**
	 * Revert to the previous use on the user stack
	 */
	function revertUser(){
		global $current_user;
		if(count($this->userStack)!=0){
			$current_user = array_pop($this->userStack);
		}else{
			$current_user = null;
		}
		return $current_user;
	}

	/**
	 * Get the current user
	 */
	function currentUser(){
		return $current_user;
	}

	/**
	 * The the webservice entity type of an EntityData object
	 */
	function toWSModuleName($entityData){
		$moduleName = $entityData->getModuleName();
		if($moduleName == 'Activity'){
			$arr = array('Task' => 'Calendar', 'Emails' => 'Emails');
			$moduleName = $arr[getActivityType($entityData->getId())];
			if($moduleName == null){
				$moduleName = 'Events';
			}
		}
		return $moduleName;
	}

	/**
	 * Insert redirection script
	 */
	function redirectTo($to, $message){
				?>
		<script type="text/javascript" charset="utf-8">
			window.location="<?php echo "$to" ?>";
		</script>
		<a href="<?php echo "$to" ?>"><?php echo "$message" ?></a>
		<?php
	}

	/**
	 * Check if the current user is admin
	 */
	function checkAdminAccess(){
		global $current_user;
		return strtolower($current_user->is_admin)==='on';
	}
	
	/**
	 * function to check if the module has workflow
	 * @params :: $modulename - name of the module
	 */
	function checkModuleWorkflow($modulename){
		global $adb,$table_prefix;
		$tabid = getTabid($modulename);
		$modules_not_supported = array('Documents','Calendar','Emails','Faq','Events','PBXManager','Users','Processes'); 
		$query = "SELECT name FROM ".$table_prefix."_tab WHERE name not in (".generateQuestionMarks($modules_not_supported).") AND isentitytype=1 AND presence = 0 AND tabid = ?";
		$result = $adb->pquery($query,array($modules_not_supported,$tabid));
		$rows = $adb->num_rows($result);
		if($rows > 0){
			return true;
		}else{
			return false;
		}
	}

	function vtGetModules($adb){
		global $table_prefix;
		$modules_not_supported = array('Documents','Calendar','Emails','Faq','Events','PBXManager','Users','ModComments','Processes');	//crmv@37660 crmv@164120 crmv@164122
		$sql="select distinct ".$table_prefix."_field.tabid, name
				from ".$table_prefix."_field 
				inner join ".$table_prefix."_tab 
					on ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid 
				where ".$table_prefix."_tab.name not in(".generateQuestionMarks($modules_not_supported).") and ".$table_prefix."_tab.isentitytype=1 and ".$table_prefix."_tab.presence = 0 ";
		$it = new SqlResultIterator($adb, $adb->pquery($sql,array($modules_not_supported)));
		$modules = array();
		foreach($it as $row){
			$modules[$row->name] = getTranslatedString($row->name, $row->name);
		}
		asort($modules);
		return $modules;
	}
 
 
	// crmv@177071
	/**
	 * Check all update tasks and change the userid with the new one.
	 * Used when a user is deleted and ownership is transferd to another user.
	 */
	public function changeOwnerInTasks($olduser, $newuser) {
		global $adb,$table_prefix;
		
		require_once('modules/com_workflow/VTTaskManager.inc');//crmv@207901
		
		$TM = new VTTaskManager($adb);
		
		// get all update tasks
		$res = $adb->query(
			"SELECT wft.task_id, wf.module_name
			FROM com_{$table_prefix}_workflowtasks wft
			INNER JOIN com_{$table_prefix}_workflows wf ON wf.workflow_id = wft.workflow_id
			WHERE wft.task LIKE 'O:17:\"VTUpdateFieldTask\"%'"
		);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$module = $row['module_name'];
			$tabid = getTabid2($module);
			$task = $TM->retrieveTask($row['task_id']);
			if ($task->fieldValue == $olduser) { // make here the comparison, to skip the query if it doesn't match
				// get field info
				$res2 = $adb->pquery("SELECT uitype FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?", array($tabid, $task->fieldName));
				if ($res2 && $adb->num_rows($res2) > 0) {
					$uitype = intval($adb->query_result_no_html($res2, 0, 'uitype'));
					if (in_array($uitype, array(50,51,52,53,77,101))) {
						$task->fieldValue = $newuser;
						$TM->saveTask($task);
					}
				}
			}
		}
	}
	// crmv@177071e
 
}
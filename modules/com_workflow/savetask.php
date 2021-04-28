<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("VTTaskManager.inc");
require_once("VTWorkflowUtils.php");
require_once("VTWorkflowApplication.inc");

	function vtSaveTask($adb, $request){
	$util = new VTWorkflowUtils();
		$module = new VTWorkflowApplication("savetask");
		$mod = return_module_language($current_language, $module->name);

		if(!$util->checkAdminAccess()){
			$errorUrl = $module->errorPageUrl($mod['LBL_ERROR_NOT_ADMIN']);
			$util->redirectTo($errorUrl, $mod['LBL_ERROR_NOT_ADMIN']);
			return;
		}

		$tm = new VTTaskManager($adb);
		if(isset($request["task_id"])){
			$task = $tm->retrieveTask($request["task_id"]);
		}else{
			$taskType = $request["task_type"];
			$workflowId = $request["workflow_id"];
			$task = $tm->createTask($taskType, $workflowId);
		}
		$task->summary = $request["summary"];
		
		if($request["active"]=="true"){
			$task->active=true;
		}else if($request["active"]=="false"){
			$task->active=false;
		}
		
		if(isset($request['check_select_date'])){
			$trigger = array(
				'days'=>($request['select_date_direction']=='after'?1:-1)*(int)$request['select_date_days'],
				'field'=>$request['select_date_field']
				); 
			$task->trigger=$trigger;
		}else{	//crmv@22921
			unset($task->trigger);
		}//crmv@22921e
		
		$fieldNames = $task->getFieldNames();
		foreach($fieldNames as $fieldName){
			$task->$fieldName = $request[$fieldName];
			if ($fieldName == 'calendar_repeat_limit_date') {
				$task->$fieldName = getDBInsertDateValue($request[$fieldName]);
			}
		}
		$tm->saveTask($task);
		
		if(isset($request["return_url"])){
			$returnUrl=$request["return_url"];
		}else{
			$returnUrl=$module->editTaskUrl($task->id);
		}
		
		// crmv@77249
		if ($_REQUEST['included'] == true) {
			$params = array(
				'included' => 'true',
				'skip_vte_header' => 'true',
				'skip_footer' => 'true',
				'formodule' => $_REQUEST['formodule']
			);
			$returnUrl .= "&".http_build_query($params);
		}
		// crmv@77249e
		
		?>
		<script type="text/javascript" charset="utf-8">
			window.location="<?php echo "$returnUrl" ?>";
		</script>
		<a href="<?php echo "$returnUrl" ?>">Return</a>
		<?php
	}
vtSaveTask($adb, $_REQUEST);
?>
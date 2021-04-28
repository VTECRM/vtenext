<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class VTWorkflowApplication{
	function __construct($action){
		global $_REQUEST;
		$this->request;
		$this->name = "com_workflow";//crmv@207901
		$this->label = "Workflow";
		$this->action = $action;
		$this->returnUrl = $_SERVER["REQUEST_URI"];
	}
	
	function currentUrl(){
		// $req = $this->request;
		// $url = "index.php?module={$this->name}&action={$this->action}";
		// if($this->action=='editworkflow'){
		// 	if(isset($req['workflow_id'])){
		// 		$url.="&workflow_id=".$req['workflow_id'];
		// 	}
		// }else if($this->action=='edittask'){
		// 	if(isset($req['task_id'])){
		// 		$url.="&task_id=".$req['task_id'];
		// 	}
		// }
		return $_SERVER["REQUEST_URI"];			
	}
	
	function returnUrl(){
		return $this->returnUrl;
	}
	
	function listViewUrl(){
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
	
		$returnUrl = "index.php?module={$this->name}&action=workflowlist";
		return $returnUrl;
	}
	
	function editWorkflowUrl($id=null){
		if($id!=null){
			$idPart="&workflow_id=$id";
		}
		$returnUrl =  "index.php?module={$this->name}&action=editworkflow$idPart&return_url=".urlencode($this->returnUrl());
		
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
		
		return $returnUrl;
	}
	
	function deleteWorkflowUrl($id){
		$idPart="&workflow_id=$id";

		$returnUrl = "index.php?module={$this->name}&action=deleteworkflow$idPart&return_url=".urlencode($this->returnUrl());
		
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

		return $returnUrl;
	}
	
	function editTaskUrl($id=null){
		if($id!=null){
			$idPart="&task_id=$id";
		}
		
		$returnUrl = "index.php?module={$this->name}&action=edittask$idPart&return_url=".urlencode($this->returnUrl());
		
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

		return $returnUrl;
		
	}
	
	function deleteTaskUrl($id){
		$idPart="&task_id=$id";
		$returnUrl =  "index.php?module={$this->name}&action=deletetask$idPart&return_url=".urlencode($this->returnUrl());
		
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

		return $returnUrl;
	}

	function setReturnUrl($returnUrl){
		$this->returnUrl = $returnUrl;
	}

	function errorPageUrl($message){
		$returnUrl  = "index.php?module={$this->name}&action=errormessage&message=".urlencode($message);
		
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

		return $returnUrl ;
	}
}
?>
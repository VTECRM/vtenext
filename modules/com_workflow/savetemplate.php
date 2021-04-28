<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once "VTWorkflowApplication.inc";
require_once "VTWorkflowManager.inc";
require_once "VTWorkflowTemplateManager.inc";
require_once "VTTaskManager.inc";
require_once "VTWorkflowUtils.php";

function vtSaveWorkflowTemplate($adb, $request){
	$util = new VTWorkflowUtils();
	$module = new VTWorkflowApplication("savetemplate");
	$mod = return_module_language($current_language, $module->name);
	
	if(!$util->checkAdminAccess()){
		$errorUrl = $module->errorPageUrl($mod['LBL_ERROR_NOT_ADMIN']);
		$util->redirectTo($errorUrl, $mod['LBL_ERROR_NOT_ADMIN']);
		return;
	}

	$title = $request['title'];
	$workflowId = $request['workflow_id'];
	$wfs = new VTworkflowManager($adb);
	$workflow = $wfs->retrieve($workflowId);
	$tm = new VTWorkflowTemplateManager($adb);
	$tpl = $tm->newTemplate($title, $workflow);
	$tm->saveTemplate($tpl);
	$returnUrl = $request['return_url'];
	
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
vtSaveWorkflowTemplate($adb, $_REQUEST);
?>
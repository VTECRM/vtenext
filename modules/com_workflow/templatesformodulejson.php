<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'VTWorkflowTemplateManager.inc';
function vtTemplatesForModuleJson($adb, $request){
	$moduleName = $request['module_name'];
	$tm = new VTWorkflowTemplateManager($adb); 
	$templates = $tm->getTemplatesForModule($moduleName);
	$arr = array();
	foreach($templates as $template){
		$arr[] = array("title"=>$template->title, 'id'=>$template->id);
	}
	echo Zend_Json::encode($arr);
}
vtTemplatesForModuleJson($adb, $_REQUEST);
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/com_workflow/VTEntityMethodManager.inc');//crmv@207901

function vtEntityMethodJson($adb, $request){
	$moduleName = $request['module_name'];
	$emm = new VTEntityMethodManager($adb);
	$methodNames = $emm->methodsForModule($moduleName);
	echo Zend_Json::encode($methodNames);
}

vtEntityMethodJson($adb, $_REQUEST);
?>
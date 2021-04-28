<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/utils/utils.php');
require_once('include/Zend/Json.php');
require_once('include/events/include.inc');
require_once('modules/com_workflow/include.inc');//crmv@207901

/**
 * This is a utility function to load a dumped templates files
 * into vte
 * @param $filename The name of the file to load.
 */
function loadTemplates($filename){
	global $adb;
	$str = file_get_contents('fetchtemplates.out');
	$tm = new VTWorkflowTemplateManager($adb);
	$tm->loadTemplates($str);
}
?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("include/Zend/Json.php");
require 'include.inc';
function vtGetExpressionListJson($adb, $request){
	$moduleName = $request['modulename'];
	$ee = new VTModuleExpressionsManager($adb);
	$arr = $ee->expressionsForModule($moduleName);
	echo Zend_Json::encode($arr);
}
vtGetExpressionListJson($adb, $_GET);
?>
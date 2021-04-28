<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("include/utils/CommonUtils.php");
require_once 'include/Webservices/Utils.php';
require_once 'include/Webservices/DescribeObject.php';
require_once("include/Zend/Json.php");

require 'include.inc';
function vtJsonFields($adb, $request){
	$moduleName = $request['modulename'];
	$mem = new VTModuleExpressionsManager($adb);
	$expressionFields = $mem->expressionFields($moduleName);
	$fields = $mem->fields($moduleName);
	echo Zend_Json::encode(array('exprFields'=>$expressionFields, 'moduleFields'=>$fields));
}
vtJsonFields($adb, $_REQUEST);
?>
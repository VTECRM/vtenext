<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("include/Zend/Json.php");
require 'include.inc';
function vtDeleteExpressionJson($adb, $request){
	$moduleName = $request['modulename'];
	$fieldName = $request['fieldname'];
	$mem = new VTModuleExpressionsManager($adb);
	$me = $mem->retrieve($moduleName);
	$me->remove($fieldName);
	$mem->save($me);
	echo Zend_Json::encode(array('status'=>'success'));
}

vtDeleteExpressionJson($adb, $_GET);
?>
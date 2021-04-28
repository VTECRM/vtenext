<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once "include/Zend/Json.php";
require 'include.inc';

function vtSaveExpressionJson($adb, $request){
	$moduleName=$request['modulename'];
	$fieldName=$request['fieldname'];
	$expression=$request['expression'];
	$mem = new VTModuleExpressionsManager($adb);
	$me = $mem->retrieve($moduleName);

	$me->add($fieldName, $expression);
	if($me->state=='savable'){
		$mem->save($me);
		echo Zend_Json::encode(array('status'=>'success'));
	}else{
		echo Zend_Json::encode(array('status'=>'fail', 'message'=>$me->message));
	}
}
vtSaveExpressionJson($adb, $_GET);
?>
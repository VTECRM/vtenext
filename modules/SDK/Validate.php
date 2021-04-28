<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$form = $_REQUEST['form'];
//$values = Zend_Json::decode($_REQUEST['values']);
$values = array();
foreach($_REQUEST as $key => $val) {
	if (strpos($key, 'sdk_par_') !== false) {
		$values[str_replace('sdk_par_','',$key)] = $val;
	}
}
if ($values['action'] == 'MassEditSave') {
	$type = $values['action'];
} else {
	$type = $form;
}
$status = true;
$message = '';
$focus = '';
$changes = array();

if ($values['module'] != '') {
	$sdk_file = SDK::getPreSave($values['module']);
	if ($sdk_file != '' && Vtecrm_Utils::checkFileAccess($sdk_file)) {
		include($sdk_file);
	}
}
//crmv@26919
if ($confirm == ''){
	$confirm = false;
}
$return = array('status'=>$status,'message'=>utf8_encode($message),'focus'=>$focus,'changes'=>$changes,'confirm'=>$confirm);
//crmv@26919e
if(!in_array($type,array('EditView','createTodo','QcEditView','ConvertLead','createQuickTODO'))) { //crmv@29954
	unset($return['focus']);
	unset($return['changes']);
}
echo Zend_Json::encode($return);
exit;
?>
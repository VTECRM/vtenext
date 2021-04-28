<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$field = $_REQUEST['field'];
$module = $_REQUEST['module_name'];
$obj = CRMEntity::getInstance('Transitions');
$obj->Initialize($module);
$ret_res['success'] = $obj->saveField($field);
if (!$ret_res['success'])
	$ret_res['msg'] = GetTranslatedString('LBL_CANT_SET_FIELD','Transitions');
echo Zend_Json::encode($ret_res);
die();
?>
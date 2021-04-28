<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$module = $_REQUEST['module_name'];
$field = $_REQUEST['field'];
$obj = CRMEntity::getInstance('Transitions');
$obj->Initialize($module,"",$field);
$ret_res['all_fields'] = $obj->all_status_field;
$ret_res['is_managed'] = $obj->is_managed;
$ret_res['module_is_managed'] = $obj->module_is_managed;
$ret_res['picklist_fields'] = $obj->getFieldPicklist();
echo Zend_Json::encode($ret_res);
exit();
?>
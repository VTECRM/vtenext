<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Picklistmulti/Picklistmulti_class.php');
include_once('include/Zend/Json.php');
$module = $_REQUEST['module_name'];
$obj=new Picklistmulti(false,$module);
$ret_res = $obj->field_list;
echo Zend_Json::encode($ret_res);
die();
?>
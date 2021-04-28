<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$module = $_REQUEST['module_name'];
$field = $_REQUEST['field'];
$obj = CRMEntity::getInstance('Transitions');
$obj->Initialize($module,$current_user->roleid);
echo $obj->saveField($field);
exit();
?>
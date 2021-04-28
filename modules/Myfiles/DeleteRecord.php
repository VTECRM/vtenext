<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/utils/utils.php');
$record = vtlib_purify($_REQUEST['myfilesid']);
if ($record){
	$obj = CRMEntity::getInstance('Myfiles');
	$obj->id = $record;
	$obj->trash('Myfiles',$record);
}
?>
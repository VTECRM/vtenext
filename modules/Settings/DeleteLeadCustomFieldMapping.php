<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb,$table_prefix;
$id=$_REQUEST['id'];


if($id !='')
{
	$sql="update ".$table_prefix."_convertleadmapping set accountfid ='NULL',contactfid='NULL',potentialfid='NULL' where cfmid=?";
	$result = $adb->pquery($sql, array($id));
}


header("Location: index.php?module=Settings&action=ListLeadCustomFieldMapping&parenttab=Settings");
?>
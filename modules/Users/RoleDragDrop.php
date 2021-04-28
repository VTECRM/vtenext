<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $current_user;

$toid=$_REQUEST['parentId'];
$fromid=$_REQUEST['childId'];

// crmv@184240
if (!is_admin($current_user)) {
	echo getTranslatedString('LBL_UNAUTHORIZED_ACCESS', 'Users');
	die();
}
// crmv@184240e

global $adb,$mod_strings,$table_prefix;
$query = "select * from ".$table_prefix."_role where roleid=?";
$result=$adb->pquery($query, array($toid));
$parentRoleList=$adb->query_result($result,0,'parentrole');
$replace_with=$parentRoleList;
$orgDepth=$adb->query_result($result,0,'depth');

$parentRoles=explode('::',$parentRoleList);

if(in_array($fromid,$parentRoles)) {
	echo $mod_strings['ROLE_DRAG_ERR_MSG'];
	die;
}

$roleInfo=getRoleAndSubordinatesInformation($fromid);

$fromRoleInfo=$roleInfo[$fromid];
$replaceToStringArr=explode('::'.$fromid,$fromRoleInfo[1]);
$replaceToString=$replaceToStringArr[0];

$stdDepth=$fromRoleInfo['2'];

//Constructing the query
foreach($roleInfo as $mvRoleId=>$mvRoleInfo)
{
	$subPar=explode($replaceToString,$mvRoleInfo[1],2);//we have to spilit as two elements only
	$mvParString=$replace_with.$subPar[1];
	$subDepth=$mvRoleInfo[2];
	$mvDepth=$orgDepth+(($subDepth-$stdDepth)+1);
	$query="update ".$table_prefix."_role set parentrole=?,depth=? where roleid=?";
	//echo $query;
	$adb->pquery($query, array($mvParString, $mvDepth, $mvRoleId));

	// Invalidate any cached information
	VTCacheUtils::clearRoleSubordinates($mvRoleId);
}

header("Location: index.php?action=SettingsAjax&module=Settings&file=listroles&ajax=true");
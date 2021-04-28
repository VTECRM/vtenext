<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix;
$rolename = $_REQUEST['roleName'];
$mode = $_REQUEST['mode'];
if(isset($_REQUEST['dup_check']) && $_REQUEST['dup_check']!='')
{
	eval(Users::m_de_cryption());
	eval($hash_version[5]);

	if($mode != 'edit')
	{
		$query = 'select rolename from '.$table_prefix.'_role where rolename=?';
		$params = array($rolename);
	}
	else
	{
		$roleid=$_REQUEST['roleid'];
		$query = 'select rolename from '.$table_prefix.'_role where rolename=? and roleid !=?';
		$params = array($rolename, $roleid);
	}
	$result = $adb->pquery($query, $params);
	if($adb->num_rows($result) > 0)
	{
		$ret_arr['success'] = false;
		$ret_arr['message'] = getTranslatedString("LBL_ROLENAME_EXIST",'Settings');
		echo Zend_Json::encode($ret_arr);
		exit;
	}else
	{
		$ret_arr['success'] = true;
		echo Zend_Json::encode($ret_arr);
		exit;
	}

}
$parentRoleId=$_REQUEST['parent'];
//Inserting values into Role Table
// crmv@39110
if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'edit') {
	$roleId = $_REQUEST['roleid'];
	$selected_col_string = 	$_REQUEST['selectedColumnsString'];
	$profile_array = explode(';',$selected_col_string);
	updateRole($roleId,$rolename,$profile_array);

	$profileIdMobile = intval($_REQUEST['profileMobileList']);
	updateRole($roleId,$rolename,array($profileIdMobile), 1);

} elseif(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'create') {
	$selected_col_string = 	$_REQUEST['selectedColumnsString'];
	$profile_array = explode(';',$selected_col_string);
	//Inserting into vte_role Table
	$roleId = createRole($rolename,$parentRoleId,$profile_array);
	if($roleId != '') {
		insertRole2Picklist($roleId,$parentRoleId);

		$profileIdMobile = intval($_REQUEST['profileMobileList']);
		if ($profileIdMobile > 0) {
			insertRole2ProfileRelation($roleId,$profileIdMobile, 1);
		}
	}
}
// crmv@39110e

header("Location: index.php?action=listroles&module=Settings&parenttab=Settings");
?>
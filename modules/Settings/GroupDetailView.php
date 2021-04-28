<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


global $mod_strings, $app_strings, $theme;
global $adb;

$groupId = intval($_REQUEST['groupId']);
$groupInfoArr = getGroupInfo($groupId);

$groupfields = array();
$groupfields['groupname'] = $groupInfoArr[0];    
$groupfields['description'] = $groupInfoArr[1];

$groupMember = $groupInfoArr[2];
$information = array();
foreach($groupMember as $memberType=>$memberValue) {
	$memberinfo = array();
	foreach($memberValue as $memberId) {
		$groupmembers = array();
		if($memberType == 'roles')
		{
			$memberName=getRoleName($memberId);
			$memberAction="RoleDetailView";
			$memberActionParameter="roleid";
			$memberDisplayType="Role";
		}
		elseif($memberType == 'rs')
		{
			$memberName=getRoleName($memberId);
			$memberAction="RoleDetailView";
			$memberActionParameter="roleid";
			$memberDisplayType="Role and Subordinates";
		}
		elseif($memberType == 'groups')
		{
			$memberName=fetchGroupName($memberId);
			$memberAction="GroupDetailView";
			$memberActionParameter="groupId";
			$memberDisplayType="Group";
		}
		elseif($memberType == 'users')
		{
			$memberName=getUserName($memberId);
			$memberAction="DetailView";
			$memberActionParameter="record";
			$memberDisplayType="User";
		}
		$groupmembers['membername'] = $memberName;
		$groupmembers['memberid'] = $memberId;
		$groupmembers['membertype'] = $memberDisplayType;
		$groupmembers['memberaction'] = $memberAction;
		$groupmembers['actionparameter'] = $memberActionParameter;
		$memberinfo[] = $groupmembers;
	}
	if(sizeof($memberinfo) >0) {
		$information[$memberDisplayType] = $memberinfo;
	}
}
$groupInfo = array($groupfields,$information);

$smarty = new VteSmarty();

$smarty->assign("GROUPINFO", $groupInfo);
$smarty->assign("GROUPID",$groupId);
$smarty->assign("GROUP_NAME",$groupInfoArr[0]);

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

$smarty->display("GroupDetailview.tpl");

<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $app_list_strings;
global $theme;


/** gives the role info, role profile info and role user info details in an array  for the specified role id
  * @param $roleid -- role id:: Type integer
  * @returns $return_data -- array contains role info, role profile info and role user info. This array is used to construct the detail view for the specified role id :: Type varchar
  *
 */
// crmv@39110
function getRoleOutput($roleid) {
	global $current_user;

	//Retreiving the related vte_profiles
	$roleProfileArr = getRoleRelatedProfiles($roleid);
	$roleProfileArrMobile = getRoleRelatedProfiles($roleid, 1);
	//Retreving the related vte_users
	$roleUserArr = getRoleUsers($roleid);

	//Constructing the Profile list
	$profileinfo = Array();
	foreach($roleProfileArr as $profileId=>$profileName) {
		$profileinfo[]=$profileId;
		$profileinfo[]=$profileName;
		$profileList .= '<a href="index.php?module=Settings&action=profilePrivileges&profileid='.$profileId.'">'.$profileName.'</a>';
	}
	$profileinfo=array_chunk($profileinfo,2);

	$profileinfoMobile = Array();
	foreach($roleProfileArrMobile as $profileId=>$profileName) {
		$profileinfoMobile[]=$profileId;
		$profileinfoMobile[]=$profileName;
		$profileList .= '<a href="index.php?module=Settings&action=profilePrivileges&profileid='.$profileId.'">'.$profileName.'</a>';
	}
	$profileinfoMobile=array_chunk($profileinfoMobile,2);

	//Constructing the Users List
	$userinfo = Array();
	foreach($roleUserArr as $userId=>$userName) {
		$userinfo[]= $userId;
		$userinfo[]= $userName;
		$userList .= '<a href="index.php?module=Settings&action=DetailView&record='.$userId.'">'.$userName.'</a>';
	}
	$userinfo=array_chunk($userinfo,2);

	//Check for Current User
	$current_role = fetchUserRole($current_user->id);
	$return_data = Array('profileinfo'=>$profileinfo,'profileinfo_mobile'=>$profileinfoMobile,'userinfo'=>$userinfo);
	
	return $return_data;
}
// crmv@39110e

if(isset($_REQUEST['roleid']) && $_REQUEST['roleid'] != '') {
	$roleid = $_REQUEST['roleid'];
	$roleInfo=getRoleInformation($roleid);
	$thisRoleDet=$roleInfo[$roleid];
	$rolename = $thisRoleDet[0];
	$parent = $thisRoleDet[3];
	//retreiving the vte_profileid
	// crmv@39110 - removed line
}
$parentname = getRoleName($parent);
//Retreiving the Role Info
$roleInfoArr = getRoleInformation($roleid);
$rolename = $roleInfoArr[$roleid][0];

$smarty = new VteSmarty();

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty->assign("ROLE_NAME",$rolename);
$smarty->assign("ROLEID",$roleid);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("ROLEINFO",getRoleOutput($roleid));
$smarty->assign("PARENTNAME",$parentname);
$smarty->assign("THEME", $theme);

$smarty->display("RoleDetailView.tpl");

<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@39110

global $adb, $table_prefix;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty = new VteSmarty();
$profDetails=getAllProfileInfo();
$profDetailsMobile = getAllProfileInfo(true);

// crmv@49398
if (is_array($profDetails) && is_array($profDetailsMobile)) {
	$profDetails = array_diff_key($profDetails, $profDetailsMobile);
}
// crmv@49398e

if(isset($_REQUEST['roleid']) && $_REQUEST['roleid'] != '') {
	$roleid= $_REQUEST['roleid'];
	$mode = $_REQUEST['mode'];
	$roleInfo=getRoleInformation($roleid);
	$thisRoleDet=$roleInfo[$roleid];
	$rolename = $thisRoleDet[0];
	$parent = $thisRoleDet[3];
	//retreiving the vte_profileid
	$roleRelatedProfiles=getRoleRelatedProfiles($roleid);
	$roleRelatedProfilesMobile=getRoleRelatedProfiles($roleid,1);

} elseif (isset($_REQUEST['parent']) && $_REQUEST['parent'] != '') {
	$mode = 'create';
	$parent=$_REQUEST['parent'];
}

$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("THEME", $theme); //crmv@30683
$parentname=getRoleName($parent);
$smarty->assign("RETURN_ACTION",$_REQUEST['returnaction']);
$smarty->assign("ROLEID",$roleid);
$smarty->assign("MODE",$mode);
$smarty->assign("PARENT",$parent);
$smarty->assign("PARENTNAME",$parentname);
$smarty->assign("ROLENAME",$rolename);

$profile_entries=array();
foreach($profDetails as $profId=>$profName) {
	$profile_entries[]=$profId;
	$profile_entries[]=$profName;
}

$profile_entries=array_chunk($profile_entries,2);
$smarty->assign("PROFILELISTS",$profile_entries);

$profile_entries_m=array();
foreach($profDetailsMobile as $profId=>$profName) {
	$profile_entries_m[]=$profId;
	$profile_entries_m[]=$profName;
}
$profile_entries_m=array_chunk($profile_entries_m,2);
$smarty->assign("PROFILELISTS_MOBILE",$profile_entries_m);


if($mode == 'edit') {
	$selected_profiles = array();
	foreach($roleRelatedProfiles as $relProfId => $relProfName) {
		$selected_profiles[]=$relProfId;
		$selected_profiles[]=$relProfName;
	}
	$selected_profiles=array_chunk($selected_profiles,2);
	$smarty->assign("SELPROFILELISTS",$selected_profiles);

	// mobile
	$selected_profiles = array();
	foreach($roleRelatedProfilesMobile as $relProfId => $relProfName) {
		$selected_profiles[]=$relProfId;
		$selected_profiles[]=$relProfName;
	}
	$selected_profiles=array_chunk($selected_profiles,2);

	$smarty->assign("SELPROFILE_MOBILE",$selected_profiles[0][0]);
}

$smarty->display("RoleEditView.tpl");
?>
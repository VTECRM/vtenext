<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $app_list_strings;
global $adb, $table_prefix;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

if (!isset($smarty)) $smarty = new VteSmarty();	//crmv@150592
$sql = "select * from ".$table_prefix."_profile";
$profileListResult = $adb->pquery($sql, array());
$noofrows = $adb->num_rows($profileListResult);
$list_entries = array($mod_strings['LBL_LIST_NO'],$mod_strings['LBL_LIST_TOOLS'],$mod_strings['LBL_NEW_PROFILE_NAME'],$mod_strings['LBL_DESCRIPTION'], 'Mobile'); // crmv@39110

/** gives the profile list info array
  * @param $profileListResult -- profile list database result:: Type array
  * @param $noofrows -- no of rows in the $profileListResult:: Type integer
  * @param $mod_strings -- i18n mod_strings array:: Type array
  * @returns $return_date -- profile list info array:: Type array
  *
 */
function getStdOutput($profileListResult, $noofrows, $mod_strings)
{
	global $adb, $current_user;
	$return_data = array();
	for($i=0; $i<$noofrows; $i++)
	{
		$standCustFld = array();
		$profile_name = $adb->query_result($profileListResult,$i,"profilename");
		$profile_id = $adb->query_result($profileListResult,$i,"profileid");
		$description = $adb->query_result($profileListResult,$i,"description");
		$mobile = $adb->query_result($profileListResult,$i,"mobile"); // crmv@39110
        $current_profile = fetchUserProfileId($current_user->id);
        if ($profile_id != 1  && $profile_id != $current_profile) {
			$standCustFld['del_permission']='yes';
		} else {
			$standCustFld['del_permission']='no';
		}
		$standCustFld['profileid']= $profile_id;
		$standCustFld['profilename']= $profile_name;
		$standCustFld['description']= $description;
		$standCustFld['mobile']= ($mobile == 1); // crmv@39110
		$return_data[]=$standCustFld;
	}
	return $return_data;
}

$smarty->assign("LIST_HEADER",$list_entries);
$smarty->assign("LIST_ENTRIES",getStdOutput($profileListResult, $noofrows, $mod_strings));
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("PROFILES", $standCustFld);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("COUNT",$noofrows);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("THEME", $theme);

//crmv@150592
$userInfoUtils = UserInfoUtils::getInstance();
$pending_version = $userInfoUtils->getPendingVersion_profile();
$smarty->assign('PENDING_VERSION', $pending_version['version']);
$smarty->assign('CURRENT_VERSION', $userInfoUtils->getCurrentVersionNumber_profile());
$smarty->assign('PERM_VERSION_EXPORT', $userInfoUtils->isExportPermitted_profile());
$smarty->assign('PERM_VERSION_IMPORT', $userInfoUtils->isImportPermitted_profile());
$smarty->assign('CHECK_VERSION_IMPORT', $userInfoUtils->checkImportVersion_profile());
//crmv@150592e

$smarty->display("UserProfileList.tpl");
?>
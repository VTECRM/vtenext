<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Users/CreateUserPrivilegeFile.php'); // crmv@199834
global $mod_strings;
global $app_strings;
global $app_list_strings;

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();
//crmv@30061
$action_map = array(
	'Public: Read Only' => 'LBL_READ_ONLY',
	'Public: Read, Create/Edit' => 'LBL_EDIT_CREATE_ONLY',
	'Public: Read, Create/Edit, Delete' => 'LBL_READ_CREATE_EDIT_DEL',
	'Assigned' => 'LBL_ASSIGNED',	//crmv@61173
);
//crmv@30061e
$defSharingPermissionData = getDefaultSharingAction();
$access_privileges = array();
$row=1;
foreach($defSharingPermissionData as $tab_id => $def_perr)
{
	//crmv@26303
	$entity_name = getTabname($tab_id);
	if($tab_id == 6)
    {
    	$cont_name = getTabname(4);
        $entity_name .= ' & '.$cont_name;
    }
	//crmv@26303e
    $entity_perr = getDefOrgShareActionName($def_perr);
    if ($entity_name == 'Messages') $entity_name = getTranslatedString('LBL_RELATED_MESSAGES','Messages');	//crmv@61173
	$access_privileges[] = $entity_name;
	//crmv@47243	crmv@30061	crmv@56114	crmv@61173
	if ($entity_name == 'MyNotes') {
		if ($entity_perr == 'Private') {
			$access_privileges[] = 'LBL_ASSIGNED';
			$access_privileges[] = $mod_strings['LBL_PRIVATE_MESSAGES'];
		} elseif ($entity_perr == 'Public: Read Only') {
			$access_privileges[] = 'Inherited';
			$access_privileges[] = $mod_strings['LBL_INHERITED'];
		}
	} elseif ($entity_name == getTranslatedString('LBL_RELATED_MESSAGES','Messages') && $entity_perr == 'Public: Read Only') {
		$access_privileges[] = 'Inherited';
		$access_privileges[] = $mod_strings['LBL_INHERITED'];
	} else {
		if (!empty($action_map[$entity_perr])) {
			$access_privileges[] = $action_map[$entity_perr];
		} else {
			$access_privileges[] = $entity_perr;
		}
		if($entity_perr != 'Private')	
			$access_privileges[] = $mod_strings['LBL_USR_CAN_ACCESS'].' '.$mod_strings[$entity_perr];
		else
	        $access_privileges[] = $mod_strings['LBL_USR_CANNOT_ACCESS'];
	}
	//crmv@47243e	crmv@30061e	crmv@56114e	crmv@61173e
	$row++;
}
$access_privileges=array_chunk($access_privileges,3);

// crmv@204343
usort($access_privileges, function ($mod1, $mod2) { 
	return getTranslatedString($mod1[0], $mod1[0]) <=> getTranslatedString($mod2[0], $mod2[0]);
});
$smarty->assign("DEFAULT_SHARING", $access_privileges);
// crmv@204343e

// Look up for modules for which sharing access is enabled.
// NOTE: Accounts and Contacts has been couple, so we need to elimiate Contacts also
$custom_access = [];
$othermodules = getSharingModuleList(Array('Contacts'));
if(!empty($othermodules)) {
	foreach($othermodules as $moduleresname) {
		$custom_access[$moduleresname] = getSharingRuleList($moduleresname);
	}
}

// crmv@204343
uksort($custom_access, function ($mod1, $mod2) {
	return getTranslatedString($mod1, $mod1) <=> getTranslatedString($mod2, $mod2);
});
$smarty->assign("MODSHARING", $custom_access);
// crmv@204343e

// crmv@199834
$SM = new SharingPrivileges();
$sharingStatusMessage = $SM->getSharingStatusMessage();
$sharingStatusIcon = $SM->getSharingStatusIcon();
$smarty->assign("SHARING_STATUS_MESSAGE", $sharingStatusMessage);
$smarty->assign("SHARING_STATUS_ICON", $sharingStatusIcon);
// crmv@199834e

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));

$smarty->display("OrgSharingDetailView.tpl");
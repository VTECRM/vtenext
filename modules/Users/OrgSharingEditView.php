<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $app_list_strings, $current_user;
global $theme;

// crmv@184240
if (!is_admin($current_user)) {
	echo getTranslatedString('LBL_UNAUTHORIZED_ACCESS', 'Users');
	die();
}
// crmv@184240e

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty(); 
$defSharingPermissionData = getDefaultSharingEditAction();

$row=1;
$entries = Array();
foreach($defSharingPermissionData as $tab_id => $def_perr)
{
	$entity_name = getTabname($tab_id);
	if($tab_id == 6)
	{
		$cont_name = getTabname(4);
		$entity_name .= ' & '.$cont_name;
	}
	if ($entity_name == 'Messages') $entity_name = getTranslatedString('LBL_RELATED_MESSAGES','Messages');	//crmv@61173
	$defActionArr=getModuleSharingActionArray($tab_id);

	$entries[] = $entity_name;

	if($tab_id != 6)
	{
		$output = '<select class="detailedViewTextBox" id="'.$tab_id.'_perm_combo" name="'.$tab_id.'_per">';
	}
	else
	{
		$output = '<select class="detailedViewTextBox" id="'.$tab_id.'_perm_combo" name="'.$tab_id.'_per" onchange="checkAccessPermission(this.value)">';
	}
	$entries[] = $tab_id;
	
	//crmv@47243	crmv@56114	crmv@61173
	if (in_array($entity_name,array(getTranslatedString('LBL_RELATED_MESSAGES','Messages'),'MyNotes'))) {
		$defActionArr[0] = 'Inherited';
		unset($defActionArr[1]);
		unset($defActionArr[2]);
		if ($entity_name == getTranslatedString('LBL_RELATED_MESSAGES','Messages')) {
			$defActionArr[8] = 'LBL_ASSIGNED';
		} elseif ($entity_name == 'MyNotes') {
			$defActionArr[3] = 'LBL_ASSIGNED';
		}
	}
	//crmv@47243e	crmv@56114e	crmv@61173e
	foreach($defActionArr as $shareActId=>$shareActName)
	{
		$selected='';
		if($shareActId == $def_perr)
		{
			$selected='selected';
		}
		$output .= '<option value="'.$shareActId.'" '.$selected. '>'.$mod_strings[$shareActName].'</option>';
	}

	$output .= '</select>';
	$entries[] = $output;
	$row++;
}

$list_entries=array_chunk($entries,3);

// crmv@204343
usort($list_entries, function ($mod1, $mod2) { 
	return getTranslatedString($mod1[0], $mod1[0]) <=> getTranslatedString($mod2[0], $mod2[0]);
});
$smarty->assign("ORGINFO", $list_entries);
// crmv@204343e

$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

$smarty->display("OrgSharingEditView.tpl");
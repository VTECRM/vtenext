<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $app_strings;
global $mod_strings;
//crmv@16312
global $current_user, $currentModule;
global $adb,$table_prefix;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$profileId = intval($_REQUEST['profileid']);  // crmv@26907
$profileName='';
$profileDescription='';

if(!empty($profileId)) {
	if(!profileExists($profileId)) {
		die(getTranslatedString('ERR_INVALID_PROFILE_ID', $currentModule));
	}
} elseif($_REQUEST['mode'] !='create') {
	die(getTranslatedString('ERR_INVALID_PROFILE_ID', $currentModule));
}
//crmv@16312 end
$parentProfileId=vtlib_purify($_REQUEST['parentprofile']);
if($_REQUEST['mode'] =='create' && $_REQUEST['radiobutton'] != 'baseprofile')
	$parentProfileId = '';


$smarty = new VteSmarty();
if(isset($_REQUEST['selected_tab']) && $_REQUEST['selected_tab']!='')
	$parentProfileId=vtlib_purify($_REQUEST['parentprofile']);
else
	$smarty->assign("SELECTED_TAB", "global_privileges");

if(isset($_REQUEST['selected_module']) && $_REQUEST['selected_module']!='')
	$parentProfileId=vtlib_purify($_REQUEST['parentprofile']);
else
	$smarty->assign("SELECTED_MODULE", "field_Leads");

$smarty->assign("PARENTPROFILEID", $parentProfileId);
$parentProfileId=vtlib_purify($_REQUEST['parentprofile']);

$secondaryModule='';
$mode='';
$output ='';
$output1 ='';
$smarty->assign("PROFILEID", $profileId);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("CMOD", $mod_strings);
if(isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != '')
	$smarty->assign("RETURN_ACTION", vtlib_purify($_REQUEST['return_action']));

// crmv@39110

// crmv@26907 - spostato
$mode = vtlib_purify($_REQUEST['mode']);
if(!empty($mode)) {
	$smarty->assign("MODE",$mode);
}
// crmv@26907e

if ($mode == 'create' && $parentProfileId > 0) {
	$allProfileInfo = getProfileInfo($parentProfileId);
} elseif ($profileId > 0) {
	$allProfileInfo = getProfileInfo($profileId);
}
$mobilePriv = array('mobile' => ($allProfileInfo['mobile'] == 1));
$mobilePriv['image'] = getDisplayValue( ($mobilePriv['mobile'] ? 0 : 1) );
$mobilePriv['checkbox'] = getGlobalDisplayOutput( ($mobilePriv['mobile'] ? 0 : 1), 20, false);
$smarty->assign("MOBILE_PRIV",$mobilePriv);


if(isset($_REQUEST['profile_name']) && $_REQUEST['profile_name'] != '' && $_REQUEST['mode'] == 'create') {
	$profileName=$_REQUEST['profile_name'];
	$smarty->assign("PROFILE_NAME", to_html($profileName));
} else {
	$profileName = $allProfileInfo['profilename'];
	$smarty->assign("PROFILE_NAME", $profileName);
}

if(isset($_REQUEST['profile_description']) && $_REQUEST['profile_description'] != '' && $_REQUEST['mode'] == 'create') {
	$profileDescription = $_REQUEST['profile_description'];
} else {
	$profileDescription = $allProfileInfo['description'];
}
// crmv@39110e

$smarty->assign("PROFILE_DESCRIPTION", $profileDescription);


//Initially setting the secondary selected vte_tab
if($mode == 'create')
{
	$smarty->assign("ACTION",'SaveProfile');
}
elseif($mode == 'edit')
{
	$smarty->assign("ACTION",'UpdateProfileChanges');
}


//Global Privileges

if($mode == 'view')
{
	$global_per_arry = getProfileGlobalPermission($profileId);
	$view_all_per = $global_per_arry[1];
	$edit_all_per = $global_per_arry[2];
	$privileges_global[]=getGlobalDisplayValue($view_all_per,1);
	$privileges_global[]=getGlobalDisplayValue($edit_all_per,2);
}
elseif($mode == 'edit')
{
	$global_per_arry = getProfileGlobalPermission($profileId);
	$view_all_per = $global_per_arry[1];
	$edit_all_per = $global_per_arry[2];
	$privileges_global[]=getGlobalDisplayOutput($view_all_per,1);
	$privileges_global[]=getGlobalDisplayOutput($edit_all_per,2);
}
elseif($mode == 'create')
{
	if($parentProfileId != '')
	{
		$global_per_arry = getProfileGlobalPermission($parentProfileId);
		$view_all_per = $global_per_arry[1];
		$edit_all_per = $global_per_arry[2];
		$privileges_global[]=getGlobalDisplayOutput($view_all_per,1);
		$privileges_global[]=getGlobalDisplayOutput($edit_all_per,2);
	}
	else
	{
		$privileges_global[]=getGlobalDisplayOutput(0,1);
		$privileges_global[]=getGlobalDisplayOutput(0,2);
	}

}
$smarty->assign("GLOBAL_PRIV",$privileges_global);

//standard privileges
if($mode == 'view')
{
	$act_perr_arry = getTabsActionPermission($profileId);
	foreach($act_perr_arry as $tabid=>$action_array)
	{
		$stand = array();
		$entity_name = getTabname($tabid);
		//Create/Edit Permission
		$tab_create_per_id = $action_array['1'];
		$tab_create_per = getDisplayValue($tab_create_per_id,$tabid,'1');
		//Delete Permission
		$tab_delete_per_id = $action_array['2'];
		$tab_delete_per = getDisplayValue($tab_delete_per_id,$tabid,'2');
		//View Permission
		$tab_view_per_id = $action_array['4'];
		$tab_view_per = getDisplayValue($tab_view_per_id,$tabid,'4');

		$stand[]=$entity_name;
		$stand[]=$tab_create_per;
		$stand[]=$tab_delete_per;
		$stand[]=$tab_view_per;
		$privileges_stand[$tabid]=$stand;
	}
}
if($mode == 'edit')
{
	$act_perr_arry = getTabsActionPermission($profileId);
	foreach($act_perr_arry as $tabid=>$action_array)
	{
		$stand = array();
		$entity_name = getTabname($tabid);
		//Create/Edit Permission
		$tab_create_per_id = $action_array['1'];
		$tab_create_per = getDisplayOutput($tab_create_per_id,$tabid,'1');
		//Delete Permission
		$tab_delete_per_id = $action_array['2'];
		$tab_delete_per = getDisplayOutput($tab_delete_per_id,$tabid,'2');
		//View Permission
		$tab_view_per_id = $action_array['4'];
		$tab_view_per = getDisplayOutput($tab_view_per_id,$tabid,'4');

		$stand[]=$entity_name;
		$stand[]=$tab_create_per;
		$stand[]=$tab_delete_per;
		$stand[]=$tab_view_per;
		$privileges_stand[$tabid]=$stand;
	}
}
if($mode == 'create')
{
	if($parentProfileId != '')
	{
		$act_perr_arry = getTabsActionPermission($parentProfileId);
		foreach($act_perr_arry as $tabid=>$action_array)
		{
			$stand = array();
			$entity_name = getTabname($tabid);
			//Create/Edit Permission
			$tab_create_per_id = $action_array['1'];
			$tab_create_per = getDisplayOutput($tab_create_per_id,$tabid,'1');
			//Delete Permission
			$tab_delete_per_id = $action_array['2'];
			$tab_delete_per = getDisplayOutput($tab_delete_per_id,$tabid,'2');
			//View Permission
			$tab_view_per_id = $action_array['4'];
			$tab_view_per = getDisplayOutput($tab_view_per_id,$tabid,'4');

			$stand[]=$entity_name;
			$stand[]=$tab_create_per;
			$stand[]=$tab_delete_per;
			$stand[]=$tab_view_per;
			$privileges_stand[$tabid]=$stand;
		}
	}
	else
	{
		$act_perr_arry = getTabsActionPermission(1);
		foreach($act_perr_arry as $tabid=>$action_array)
		{
			$stand = array();
			$entity_name = getTabname($tabid);
			//Create/Edit Permission
			$tab_create_per_id = $action_array['1'];
			$tab_create_per = getDisplayOutput(0,$tabid,'1');
			//Delete Permission
			$tab_delete_per_id = $action_array['2'];
			$tab_delete_per = getDisplayOutput(0,$tabid,'2');
			//View Permission
			$tab_view_per_id = $action_array['4'];
			$tab_view_per = getDisplayOutput(0,$tabid,'4');

			$stand[]=$entity_name;
			$stand[]=$tab_create_per;
			$stand[]=$tab_delete_per;
			$stand[]=$tab_view_per;
			$privileges_stand[$tabid]=$stand;
		}
	}

}
$smarty->assign("STANDARD_PRIV",$privileges_stand);
//tab Privileges

if($mode == 'view')
{
	$tab_perr_array = getTabsPermission($profileId);
	$no_of_tabs =  sizeof($tab_perr_array);
	foreach($tab_perr_array as $tabid=>$tab_perr)
	{
		$tab=array();
		$entity_name = getTabname($tabid);
		$tab_allow_per_id = $tab_perr_array[$tabid];
		$tab_allow_per = getDisplayValue($tab_allow_per_id,$tabid,'');
		$tab[]=$entity_name;
		$tab[]=$tab_allow_per;
		$privileges_tab[$tabid]=$tab;
	}
}
if($mode == 'edit')
{
	$tab_perr_array = getTabsPermission($profileId);
	$no_of_tabs =  sizeof($tab_perr_array);
	foreach($tab_perr_array as $tabid=>$tab_perr)
	{
		$tab=array();
		$entity_name = getTabname($tabid);
		$tab_allow_per_id = $tab_perr_array[$tabid];
		$tab_allow_per = getDisplayOutput($tab_allow_per_id,$tabid,'');
		$tab[]=$entity_name;
		$tab[]=$tab_allow_per;
		$privileges_tab[$tabid]=$tab;
	}
}
if($mode == 'create')
{
	if($parentProfileId != '')
	{
		$tab_perr_array = getTabsPermission($parentProfileId);
		$no_of_tabs =  sizeof($tab_perr_array);
		foreach($tab_perr_array as $tabid=>$tab_perr)
		{
			$tab=array();
			$entity_name = getTabname($tabid);
			$tab_allow_per_id = $tab_perr_array[$tabid];
			$tab_allow_per = getDisplayOutput($tab_allow_per_id,$tabid,'');
			$tab[]=$entity_name;
			$tab[]=$tab_allow_per;
			$privileges_tab[$tabid]=$tab;
		}
	}
	else
	{
		$tab_perr_array = getTabsPermission(1);
		$no_of_tabs =  sizeof($tab_perr_array);
		foreach($tab_perr_array as $tabid=>$tab_perr)
		{
			$tab=array();
			$entity_name = getTabname($tabid);
			$tab_allow_per_id = $tab_perr_array[$tabid];
			$tab_allow_per = getDisplayOutput(0,$tabid,'');
			$tab[]=$entity_name;
			$tab[]=$tab_allow_per;
			$privileges_tab[$tabid]=$tab;
		}
	}

}
$smarty->assign("TAB_PRIV",$privileges_tab);
//utilities privileges

if($mode == 'view')
{
	$act_utility_arry = getTabsUtilityActionPermission($profileId);
	foreach($act_utility_arry as $tabid=>$action_array)
	{
		$util=array();
		$entity_name = getTabname($tabid);
		$no_of_actions=sizeof($action_array);
		foreach($action_array as $action_id=>$act_per)
		{
			$action_name = getActionname($action_id);
			$tab_util_act_per = $action_array[$action_id];
			$tab_util_per = getDisplayValue($tab_util_act_per,$tabid,$action_id);
			$util[]=$action_name;
			$util[]=$tab_util_per;
		}
		$util=array_chunk($util,2);
		$util=array_chunk($util,3);
		$privilege_util[$tabid] = $util;
	}
}
elseif($mode == 'edit')
{
	$act_utility_arry = getTabsUtilityActionPermission($profileId);
	foreach($act_utility_arry as $tabid=>$action_array)
	{
		$util=array();
		$entity_name = getTabname($tabid);
		$no_of_actions=sizeof($action_array);
		foreach($action_array as $action_id=>$act_per)
		{
			$action_name = getActionname($action_id);
			$tab_util_act_per = $action_array[$action_id];
			$tab_util_per = getDisplayOutput($tab_util_act_per,$tabid,$action_id);
			$util[]=$action_name;
			$util[]=$tab_util_per;
		}
		$util=array_chunk($util,2);
		$util=array_chunk($util,3);
		$privilege_util[$tabid] = $util;
	}
}
elseif($mode == 'create')
{
	if($parentProfileId != '')
	{
		$act_utility_arry = getTabsUtilityActionPermission($parentProfileId);
		foreach($act_utility_arry as $tabid=>$action_array)
		{
			$util=array();
			$entity_name = getTabname($tabid);
			$no_of_actions=sizeof($action_array);
			foreach($action_array as $action_id=>$act_per)
			{
				$action_name = getActionname($action_id);
				$tab_util_act_per = $action_array[$action_id];
				$tab_util_per = getDisplayOutput($tab_util_act_per,$tabid,$action_id);
				$util[]=$action_name;
				$util[]=$tab_util_per;
			}
			$util=array_chunk($util,2);
			$util=array_chunk($util,3);
			$privilege_util[$tabid] = $util;
		}
	}
	else
	{
		$act_utility_arry = getTabsUtilityActionPermission(1);
		foreach($act_utility_arry as $tabid=>$action_array)
		{
			$util=array();
			$entity_name = getTabname($tabid);
			$no_of_actions=sizeof($action_array);
			foreach($action_array as $action_id=>$act_per)
			{
				$action_name = getActionname($action_id);
				$tab_util_act_per = $action_array[$action_id];
				$tab_util_per = getDisplayOutput(0,$tabid,$action_id);
				$util[]=$action_name;
				$util[]=$tab_util_per;
			}
			$util=array_chunk($util,2);
			$util=array_chunk($util,3);
			$privilege_util[$tabid] = $util;
		}

	}

}
$smarty->assign("UTILITIES_PRIV",$privilege_util);

//Field privileges
$modArr=getFieldModuleAccessArray();


$no_of_mod=sizeof($modArr);
for($i=0;$i<$no_of_mod; $i++)
{
	$fldModule=key($modArr);
	$lang_str=$modArr[$fldModule];
	$privilege_fld[]=$fldModule;
	next($modArr);
}
$smarty->assign("PRI_FIELD_LIST",$privilege_fld);
$smarty->assign("MODE",$mode);

$disable_field_array = Array();
$sql_disablefield = "select * from ".$table_prefix."_def_org_field";
$result = $adb->pquery($sql_disablefield, array());
$noofrows=$adb->num_rows($result);
for($i=0; $i<$noofrows; $i++)
{
	$FieldId = $adb->query_result($result,$i,'fieldid');
	$Visible = $adb->query_result($result,$i,'visible');
	$disable_field_array[$FieldId] = $Visible;
}

//crmv@49510
if($mode=='view')
{
	$fieldListResult = getProfile2AllFieldList($modArr,$profileId);
	for($i=0; $i<count($fieldListResult);$i++)
	{
		$field_module=array();
		$module_name=key($fieldListResult);
		//crmv@24665
		$moduleinstance = Vtecrm_Module::getInstance($module_name);
		if ($moduleinstance){
			$module_id = $moduleinstance->id;
		}
		//crmv@24665
		$language_strings = return_module_language($current_language,$module_name);
		for($j=0; $j<count($fieldListResult[$module_name]); $j++)
		{
			$typeofdata = $fieldListResult[$module_name][$j][6];
			$fieldtype = explode("~",$typeofdata);
			
			$field=array();
			
			// lev. 2 (_profile2field)
			($fieldListResult[$module_name][$j][1] == 0) ? $visible = "<img src=".$image_path."/prvPrfSelectedTick.gif>" : $visible = "<img src=".$image_path."/no.gif>";
			($fieldListResult[$module_name][$j][9] == 0) ? $mandatory = '*' : $mandatory = '';
			
			// lev. 1 (_field)
			if($fieldtype[1] == "M")
			{
				$visible = "<img src=".$image_path."/prvPrfSelectedTick.gif>";
				$mandatory = '*';
			}
			
			// lev. 0 (_def_org_field)
			if($disable_field_array[$fieldListResult[$module_name][$j][4]] == 1)
			{
				$visible = "<img src=".$image_path."/no.gif>";
				$mandatory = '';
			}
			
			$field[] = $visible;
			$field[] = $mandatory;
			
			if($language_strings[$fieldListResult[$module_name][$j][0]] != '')
				$field[]=$language_strings[$fieldListResult[$module_name][$j][0]];
			else
				$field[]=$fieldListResult[$module_name][$j][0];
			
			$field_module[]=$field;
		}
		$privilege_field[$module_id] = array_chunk($field_module,3);
		next($fieldListResult);
	}
}
elseif($mode=='edit')
{
	$fieldListResult = getProfile2AllFieldList($modArr,$profileId);
	for($i=0; $i<count($fieldListResult);$i++)
	{
		$field_module=array();
		$module_name=key($fieldListResult);
		//crmv@24665
		$moduleinstance = Vtecrm_Module::getInstance($module_name);
		if ($moduleinstance){
			$module_id = $moduleinstance->id;
		}
		//crmv@24665
		$language_strings = return_module_language($current_language,$module_name);
		for($j=0; $j<count($fieldListResult[$module_name]); $j++)
		{
			$fldLabel= $fieldListResult[$module_name][$j][0];
			$uitype = $fieldListResult[$module_name][$j][2];
			$displaytype = $fieldListResult[$module_name][$j][5];
			$typeofdata = $fieldListResult[$module_name][$j][6];
			$fieldtype = explode("~",$typeofdata);

			$visible_readonly = $mandatory_readonly= '';
			$field = array();
			
			// lev. 2 (_profile2field)
			($fieldListResult[$module_name][$j][3] == 0) ? $visible = 'checked' : $visible = '';
			($fieldListResult[$module_name][$j][9] != '' && $fieldListResult[$module_name][$j][9] == 0) ? $mandatory = 'checked' : $mandatory = ''; // crmv@170777

			// lev. 1 (_field)
			if($fieldtype[1] == "M")
			{
				$visible_readonly = 'disabled';
				$visible = 'checked';
				
				$mandatory_readonly = 'disabled';
				$mandatory = 'checked';
			}

			// lev. 0 (_def_org_field)
			if($disable_field_array[$fieldListResult[$module_name][$j][4]] == 1)
			{
				$visible_readonly = 'disabled';
				$visible = '';
				
				$mandatory_readonly = 'disabled';
				$mandatory = '';
			}

			// visible
			$field[] = '<input id="'.$module_id.'_field_'.$fieldListResult[$module_name][$j][4].'" name="'.$fieldListResult[$module_name][$j][4].'" onClick="selectUnselect(\''.$module_id.'\',\''.$fieldListResult[$module_name][$j][4].'\');" type="checkbox" '.$visible.' '.$visible_readonly.'>';
			// mandatory
			$field[] = '<input id="'.$module_id.'_fieldm_'.$fieldListResult[$module_name][$j][4].'" name="m_'.$fieldListResult[$module_name][$j][4].'" onClick="selectMandatory(\''.$module_id.'\',\''.$fieldListResult[$module_name][$j][4].'\');" type="checkbox" '.$mandatory.' '.$mandatory_readonly.'>';
			
			if($language_strings[$fldLabel] != '')
				$field[] = $language_strings[$fldLabel];
			else
				$field[] = $fldLabel;
							
			$field_module[] = $field;
		}
		$privilege_field[$module_id] = array_chunk($field_module,3);
		next($fieldListResult);
	}
}
elseif($mode=='create')
{
	if($parentProfileId != '')
	{
		$fieldListResult = getProfile2AllFieldList($modArr,$parentProfileId);
		for($i=0; $i<count($fieldListResult);$i++)
		{
			$field_module=array();
			$module_name=key($fieldListResult);
			//crmv@24665
			$moduleinstance = Vtecrm_Module::getInstance($module_name);
			if ($moduleinstance){
				$module_id = $moduleinstance->id;
			}
			//crmv@24665
			$language_strings = return_module_language($current_language,$module_name);
			for($j=0; $j<count($fieldListResult[$module_name]); $j++)
			{
				$fldLabel= $fieldListResult[$module_name][$j][0];
				$uitype = $fieldListResult[$module_name][$j][2];
				$displaytype = $fieldListResult[$module_name][$j][5];
				$typeofdata = $fieldListResult[$module_name][$j][6];
				$fieldtype = explode("~",$typeofdata);
				
				$visible_readonly = $mandatory_readonly= '';
				$field = array();
				
				// lev. 2 (_profile2field)
				($fieldListResult[$module_name][$j][3] == 0) ? $visible = 'checked' : $visible = '';
				($fieldListResult[$module_name][$j][9] == 0) ? $mandatory = 'checked' : $mandatory = '';
				
				// lev. 1 (_field)
				if($fieldtype[1] == "M")
				{
					$visible_readonly = 'disabled';
					$visible = 'checked';
					
					$mandatory_readonly = 'disabled';
					$mandatory = 'checked';
				}
				
				// lev. 0 (_def_org_field)
				if($disable_field_array[$fieldListResult[$module_name][$j][4]] == 1)
				{
					$visible_readonly = 'disabled';
					$visible = '';
					
					$mandatory_readonly = 'disabled';
					$mandatory = '';
				}
				
				// visible
				$field[] = '<input id="'.$module_id.'_field_'.$fieldListResult[$module_name][$j][4].'" name="'.$fieldListResult[$module_name][$j][4].'" onClick="selectUnselect(\''.$module_id.'\',\''.$fieldListResult[$module_name][$j][4].'\');" type="checkbox" '.$visible.' '.$visible_readonly.'>';
				// mandatory
				$field[] = '<input id="'.$module_id.'_fieldm_'.$fieldListResult[$module_name][$j][4].'" name="m_'.$fieldListResult[$module_name][$j][4].'" onClick="selectMandatory(\''.$module_id.'\',\''.$fieldListResult[$module_name][$j][4].'\');" type="checkbox" '.$mandatory.' '.$mandatory_readonly.'>';
				
				if($language_strings[$fldLabel] != '')
					$field[] = $language_strings[$fldLabel];
				else
					$field[] = $fldLabel;
								
				$field_module[] = $field;
			}
			$privilege_field[$module_id] = array_chunk($field_module,3);
			next($fieldListResult);
		}
	}
	else
	{
		$fieldListResult = getProfile2AllFieldList($modArr,1);
		for($i=0; $i<count($fieldListResult);$i++)
		{
			$field_module=array();
			$module_name=key($fieldListResult);
			//crmv@24665
			$moduleinstance = Vtecrm_Module::getInstance($module_name);
			if ($moduleinstance){
				$module_id = $moduleinstance->id;
			}
			//crmv@24665
			$language_strings = return_module_language($current_language,$module_name);
			for($j=0; $j<count($fieldListResult[$module_name]); $j++)
			{
				$fldLabel= $fieldListResult[$module_name][$j][0];
				$uitype = $fieldListResult[$module_name][$j][2];
				$displaytype = $fieldListResult[$module_name][$j][5];
				$typeofdata = $fieldListResult[$module_name][$j][6];
				$fieldtype = explode("~",$typeofdata);
				
				$visible_readonly = $mandatory_readonly= '';
				$field = array();
				
				// lev. 1 (_field)
				if($fieldtype[1] == "M")
				{
					$visible_readonly = 'disabled';
					$visible = 'checked';
					
					$mandatory_readonly = 'disabled';
					$mandatory = 'checked';
				}
				
				// lev. 0 (_def_org_field)
				if($disable_field_array[$fieldListResult[$module_name][$j][4]] == 1)
				{
					$visible_readonly = 'disabled';
					$visible = '';
					
					$mandatory_readonly = 'disabled';
					$mandatory = '';
				}
		
				// visible
				$field[] = '<input id="'.$module_id.'_field_'.$fieldListResult[$module_name][$j][4].'" name="'.$fieldListResult[$module_name][$j][4].'" onClick="selectUnselect(\''.$module_id.'\',\''.$fieldListResult[$module_name][$j][4].'\');" type="checkbox" '.$visible.' '.$visible_readonly.'>';
				// mandatory
				$field[] = '<input id="'.$module_id.'_fieldm_'.$fieldListResult[$module_name][$j][4].'" name="m_'.$fieldListResult[$module_name][$j][4].'" onClick="selectMandatory(\''.$module_id.'\',\''.$fieldListResult[$module_name][$j][4].'\');" type="checkbox" '.$mandatory.' '.$mandatory_readonly.'>';
				
				if($language_strings[$fldLabel] != '')
					$field[]=$mandatory.' '.$language_strings[$fldLabel];
				else
					$field[]=$mandatory.' '.$fldLabel;
				
				$field_module[]=$field;
			}
			$privilege_field[$module_id] = array_chunk($field_module,3);
			next($fieldListResult);
		}	
	}
}
//crmv@49510e

$smarty->assign("FIELD_PRIVILEGES",$privilege_field);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);

//crmv@161021
if ($mode == 'view')
	$smarty_template = 'ProfileDetailView.tpl';
else
	$smarty_template = 'EditProfile.tpl';
	
$sdk_custom_file = 'profilePrivilegesCustomisations';
if (isModuleInstalled('SDK')) {
	$tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
	if (!empty($tmp_sdk_custom_file)) {
		$sdk_custom_file = $tmp_sdk_custom_file;
	}
}
@include("modules/$currentModule/$sdk_custom_file.php");

$smarty->display($smarty_template);
//crmv@161021e

/** returns html image code based on the input id
  * @param $id -- Role Name:: Type varchar
  * @returns $value -- html image code:: Type varcha:w
  *
 */
function getGlobalDisplayValue($id,$actionid)
{
	global $image_path;
	if($id == '')
	{
		$value = '&nbsp;';
	}
	elseif($id == 0)
	{
		$value = '<img src="'.$image_path.'prvPrfSelectedTick.gif">';
	}
	elseif($id == 1)
	{
		$value = '<img src="'.$image_path.'no.gif">';
	}
	else
	{
		$value = '&nbsp;';
	}

	return $value;

}


/** returns html check box code based on the input id
  * @param $id -- Role Name:: Type varchar
  * @returns $value -- html check box code:: Type varcha:w
  *
 */
// crmv@39110
function getGlobalDisplayOutput($id,$actionid, $useJSCallback = true) {
	if($actionid == '1') {
		$name = 'view_all';
	} elseif($actionid == '2') {
		$name = 'edit_all';
	} elseif($actionid == 20) {
		$name = 'enable_mobile';
	}

	$jsClick = ($useJSCallback ? "onClick=\"invoke{$name}();\"" : "");
	if($id == '' && $id != 0) {
		$value = '';
	} elseif($id == 0) {
		$value = '<input type="checkbox" id="'.$name.'_chk" '.$jsClick.' name="'.$name.'" checked>';
	} elseif($id == 1) {
		$value = '<input type="checkbox" id="'.$name.'_chk" '.$jsClick.' name="'.$name.'">';
	}
	return $value;
}
// crmv@39110e


/** returns html image code based on the input id
  * @param $id -- Role Name:: Type varchar
  * @returns $value -- html image code:: Type varcha:w
  *
 */
function getDisplayValue($id)
{
	global $image_path;

	if($id == 0)
	{
		$value = '<img src="'.$image_path.'prvPrfSelectedTick.gif">';
	}
	elseif($id == 1)
	{
		$value = '<img src="'.$image_path.'no.gif">';
	}
	else
	{
		$value = '&nbsp;';
	}
	return $value;

}


/** returns html check box code based on the input id
  * @param $id -- Role Name:: Type varchar
  * @returns $value -- html check box code:: Type varcha:w
  *
 */
function getDisplayOutput($id,$tabid,$actionid)
{
	if($actionid == '')
	{
		$name = $tabid.'_tab';
		$ckbox_id = 'tab_chk_com_'.$tabid;
		$jsfn = 'hideTab('.$tabid.')';
	}
	else
	{
		$temp_name = getActionname($actionid);
		$name = $tabid.'_'.$temp_name;
		$ckbox_id = 'tab_chk_'.$actionid.'_'.$tabid;
		if($actionid == 1)
			$jsfn = 'unSelectCreate('.$tabid.')';
		elseif($actionid == 4)
			$jsfn = 'unSelectView('.$tabid.')';
		elseif($actionid == 2)
			$jsfn = 'unSelectDelete('.$tabid.')';
		else
		{
			$ckbox_id = $tabid.'_field_util_'.$actionid;
			$jsfn = 'javascript:';
		}
	}



	if($id == '' && $id != 0)
	{
		$value = '';
	}
	elseif($id == 0)
	{
		$value = '<input type="checkbox" onClick="'.$jsfn.';" id="'.$ckbox_id.'" name="'.$name.'" checked>';
	}
	elseif($id == 1)
	{
		$value = '<input type="checkbox" onClick="'.$jsfn.';" id="'.$ckbox_id.'" name="'.$name.'">';
	}
	return $value;

}
//crmv@16312
function profileExists($profileId) {
	global $adb,$table_prefix;

	$result = $adb->pquery('SELECT 1 FROM '.$table_prefix.'_profile WHERE profileid = ?', array($profileId));
	if($adb->num_rows($result) > 0) return true;
	return false;
}
//crmv@16312 end
?>
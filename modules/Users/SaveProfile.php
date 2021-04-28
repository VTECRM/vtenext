<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@184240 */

global $current_user;

if (!is_admin($current_user)) {
	// redirect to settings, where an error will be shown
	header("Location: index.php?module=Settings&action=index&parenttab=Settings");
	die();
}

global $adb, $table_prefix;

$profilename = from_html(decode_html($_REQUEST['profile_name']));
$description= from_html(decode_html($_REQUEST['profile_description']));
$def_module = vtlib_purify($_REQUEST['selected_module']);
$def_tab = vtlib_purify($_REQUEST['selected_tab']);
$profileid = $adb->getUniqueID($table_prefix."_profile");

//crmv@111926
$CU = CRMVUtils::getInstance();
$r = $CU->checkMaxInputVars('form_var_count');
if (!$r) {
	VteSession::set('vtealert', getTranslatedString('LBL_TOO_MANY_INPUT_VARS'));
	$loc = "Location: index.php?action=ListProfiles&module=Settings&mode=view&parenttab=Settings&profileid=".vtlib_purify($profileid)."&selected_tab=".vtlib_purify($def_tab)."&selected_module=".vtlib_purify($def_module);
	header($loc);
	return;
}
//crmv@111926e

// crmv@49398
//Inserting values into Profile Table
$enableMobile = (vtlib_purify($_REQUEST['enable_mobile']) == 'on' ? 1 : 0);
$sql1 = "insert into ".$table_prefix."_profile(profileid, profilename, description, mobile) values(?,?,?,?)";
$adb->pquery($sql1, array($profileid,$profilename, $description, $enableMobile));
// crmv@49398e

//Retreiving the first profileid
$prof_query="select profileid from ".$table_prefix."_profile order by profileid ASC";
$prof_result = $adb->pquery($prof_query, array());
$first_prof_id = $adb->query_result($prof_result,0,'profileid');

$tab_perr_result = $adb->pquery("select * from ".$table_prefix."_profile2tab where profileid=?", array($first_prof_id));
$act_perr_result = $adb->pquery("select * from ".$table_prefix."_profile2standardperm where profileid=?", array($first_prof_id));
$act_utility_result = $adb->pquery("select * from ".$table_prefix."_profile2utility where profileid=?", array($first_prof_id));
$num_tab_per = $adb->num_rows($tab_perr_result);
$num_act_per = $adb->num_rows($act_perr_result);
$num_act_util_per = $adb->num_rows($act_utility_result);

$hideTabs = getHideTab('hide_profile');	//crmv@27711

//Updating vte_profile2global permissons vte_table
$view_all_req=$_REQUEST['view_all'];
$view_all = getPermissionValue($view_all_req);

$edit_all_req=$_REQUEST['edit_all'];
$edit_all = getPermissionValue($edit_all_req);

$sql4="insert into ".$table_prefix."_profile2globalperm values(?,?,?)";
$adb->pquery($sql4, array($profileid,1, $view_all));

$sql4="insert into ".$table_prefix."_profile2globalperm values(?,?,?)";
$adb->pquery($sql4, array($profileid,2, $edit_all));


//profile2tab permissions
for($i=0; $i<$num_tab_per; $i++)
{
	$tab_id = $adb->query_result($tab_perr_result,$i,"tabid");
	$request_var = $tab_id.'_tab';
	if($tab_id != 3 && $tab_id != 16)
	{
		$permission = $_REQUEST[$request_var];
		if($permission == 'on' || in_array($tab_id,$hideTabs))	//crmv@27711
		{
			$permission_value = 0;
		}
		else
		{
			$permission_value = 1;
		}
		$sql4="insert into ".$table_prefix."_profile2tab values(?,?,?)";
		$adb->pquery($sql4, array($profileid, $tab_id, $permission_value));

		if($tab_id ==9)
		{
			$sql4="insert into ".$table_prefix."_profile2tab values(?,?,?)";
			$adb->pquery($sql4, array($profileid,16, $permission_value));
		}
	}
}

//profile2standard permissions
for($i=0; $i<$num_act_per; $i++)
{
	$tab_id = $adb->query_result($act_perr_result,$i,"tabid");
	$action_id = $adb->query_result($act_perr_result,$i,"operation");
	if($tab_id != 16)
	{
		$action_name = getActionname($action_id);
		if($action_name == 'EditView' || $action_name == 'Delete' || $action_name == 'DetailView')
		{
			$request_var = $tab_id.'_'.$action_name;
		}
		elseif($action_name == 'Save')
		{
			$request_var = $tab_id.'_EditView';
		}
		elseif($action_name == 'index')
		{
			$request_var = $tab_id.'_DetailView';
		}

		$permission = $_REQUEST[$request_var];
		if($permission == 'on' || in_array($tab_id,$hideTabs))	//crmv@27711
		{
			$permission_value = 0;
		}
		else
		{
			$permission_value = 1;
		}

		$sql7="insert into ".$table_prefix."_profile2standardperm values(?,?,?,?)";
		$adb->pquery($sql7, array($profileid, $tab_id, $action_id, $permission_value));

		if($tab_id ==9)
		{
			$sql7="insert into ".$table_prefix."_profile2standardperm values(?,?,?,?)";
			$adb->pquery($sql7, array($profileid, 16, $action_id, $permission_value));
		}
	}
}

//Update Profile 2 utility
for($i=0; $i<$num_act_util_per; $i++)
{
	$tab_id = $adb->query_result($act_utility_result,$i,"tabid");

	$action_id = $adb->query_result($act_utility_result,$i,"activityid");
	$action_name = getActionname($action_id);
	$request_var = $tab_id.'_'.$action_name;


	$permission = $_REQUEST[$request_var];
	if($permission == 'on' || in_array($tab_id,$hideTabs))	//crmv@27711
	{
		$permission_value = 0;
	}
	else
	{
		$permission_value = 1;
	}

	$sql9="insert into ".$table_prefix."_profile2utility values(?,?,?,?)";
	$adb->pquery($sql9, array($profileid, $tab_id, $action_id, $permission_value));
}

$modArr=getFieldModuleAccessArray();

foreach($modArr as $fld_module => $fld_label)
{
	$fieldListResult = getProfile2FieldList($fld_module, $first_prof_id);
	$noofrows = $adb->num_rows($fieldListResult);
	//crmv@24665
	$moduleinstance = Vtecrm_Module::getInstance($fld_module);
	if ($moduleinstance){
		$tab_id = $moduleinstance->id;
	}
	//crmv@24665e
	//crmv@49510 crmv@160041
	for($i=0; $i<$noofrows; $i++)
	{
		//Updating the Mandatory vte_fields
		$uitype = $adb->query_result($fieldListResult,$i,"uitype");
		$displaytype =  $adb->query_result($fieldListResult,$i,"displaytype");
		$fieldname =  $adb->query_result($fieldListResult,$i,"fieldname");
		$typeofdata = $adb->query_result($fieldListResult,$i,"typeofdata");
		$fieldtype = explode("~",$typeofdata);
		$fieldid =  $adb->query_result($fieldListResult,$i,"fieldid");
		
		$propProfile2field = array();
		if ($fieldtype[1] == 'M' || $disable_field_array[$fieldid] == 1) {
			// default values
			$propProfile2field['visible'] = 0;
			$propProfile2field['mandatory'] = 1;
		} else {
			$visible = $_REQUEST[$fieldid];
			($visible == 'on' || in_array($tab_id,$hideTabs)) ? $visible_value = 0 : $visible_value = 1;	//crmv@27711
			$propProfile2field['visible'] = $visible_value;
			
			$mandatory = $_REQUEST['m_'.$fieldid];
			($mandatory == 'on' || in_array($tab_id,$hideTabs)) ? $mandatory_value = 0 : $mandatory_value = 1;
			$propProfile2field['mandatory'] = $mandatory_value;
		}
		
		//Updating the database
		// crmv@39110
		$sequence = $adb->query_result($fieldListResult,$i,'sequence');
		$columns = array('profileid','tabid','fieldid','readonly','sequence');
		$params = array($profileid, $tab_id, $fieldid, 1, (empty($sequence) ? $i : $sequence));
		if (!empty($propProfile2field)) {
			foreach($propProfile2field as $c => $v) {
				$columns[] = $c;
				$params[] = $v;
			}
		}
		$sql11 = "insert into ".$table_prefix."_profile2field(".implode(',',$columns).") values(".generateQuestionMarks($params).")";
		$adb->pquery($sql11, $params);
        // crmv@39110e
	}
	//crmv@49510e crmv@160041e
}

// crmv@49398 crmv@150592
global $metaLogs;
$userInfoUtils = UserInfoUtils::getInstance();
if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_ADDPROFILE, $profileid, array('profilename'=>$profilename));
if (!empty($metaLogId)) $userInfoUtils->versionOperation_profile($metaLogId);
// crmv@49398e crmv@150592e

$loc = "Location: index.php?action=ListProfiles&module=Settings&mode=view&parenttab=Settings&profileid=".vtlib_purify($profileid)."&selected_tab=".vtlib_purify($def_tab)."&selected_module=".vtlib_purify($def_module);
header($loc);


/** returns value 0 if request permission is on else returns value 1
  * @param $req_per -- Request Permission:: Type varchar
  * @returns $permission - can have value 0 or 1:: Type integer
  *
 */
function getPermissionValue($req_per)
{
	if($req_per == 'on')
	{
		$permission_value = 0;
	}
	else
	{
		$permission_value = 1;
	}
	return $permission_value;
}

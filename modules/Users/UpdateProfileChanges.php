<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $current_user;

/* crmv@184240 */

if (!is_admin($current_user)) {
	// redirect to settings, where an error will be shown
	header("Location: index.php?module=Settings&action=index&parenttab=Settings");
	die();
}

global $adb,$log,$table_prefix;
$profileid = intval($_REQUEST['profileid']); // crmv@39110
$def_module = vtlib_purify($_REQUEST['selected_module']);
$def_tab = vtlib_purify($_REQUEST['selected_tab']);

if(isset($_REQUEST['return_action']) && $_REQUEST['return_action']!= '')
	$return_action =vtlib_purify($_REQUEST['return_action']);
else
	$return_action = 'ListProfiles';

//crmv@111926
$CU = CRMVUtils::getInstance();
$r = $CU->checkMaxInputVars('form_var_count');
if (!$r) {
	VteSession::set('vtealert', getTranslatedString('LBL_TOO_MANY_INPUT_VARS'));
	// crmv@162674
	if($return_action == 'profilePrivileges' || $return_action == 'ListProfiles') {
		$loc = "index.php?action=".$return_action."&module=Settings&mode=view&parenttab=Settings&profileid=".$profileid."&selected_tab=".$def_tab."&selected_module=".$def_module;
	} else {
		$loc = "index.php?action=".$return_action."&module=Users&mode=view&parenttab=Settings&profileid=".$profileid."&selected_tab=".$def_tab."&selected_module=".$def_module;
	}
	RequestHandler::outputRedirect($loc);
	// crmv@162674e
	return;
}
//crmv@111926e

//Retreiving the _def_org_field permission array
//crmv@160041
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
//crmv@160041e

//Retreiving the _tabs permission array
$tab_perr_result = $adb->pquery("select * from ".$table_prefix."_profile2tab where profileid=?", array($profileid));
$act_perr_result = $adb->pquery("select * from ".$table_prefix."_profile2standardperm where profileid=?", array($profileid));
$act_utility_result = $adb->pquery("select * from ".$table_prefix."_profile2utility where profileid=?", array($profileid));
$num_tab_per = $adb->num_rows($tab_perr_result);
$num_act_per = $adb->num_rows($act_perr_result);
$num_act_util_per = $adb->num_rows($act_utility_result);

$hideTabs = getHideTab('hide_profile');	//crmv@27711

//Updating _profile2global permissons _table
$view_all_req=$_REQUEST['view_all'];
$view_all = getPermissionValue($view_all_req);

$edit_all_req=$_REQUEST['edit_all'];
$edit_all = getPermissionValue($edit_all_req);

$update_query = "update  ".$table_prefix."_profile2globalperm set globalactionpermission=? where globalactionid=1 and profileid=?";
$adb->pquery($update_query, array($view_all, $profileid));
$update_query = "update  ".$table_prefix."_profile2globalperm set globalactionpermission=? where globalactionid=2 and profileid=?";
$adb->pquery($update_query, array($edit_all, $profileid));

// crmv@39110
// mobile permissions
$mobile_perm = (vtlib_purify($_REQUEST['enable_mobile']) == 'on');
$update_query = "update  {$table_prefix}_profile set mobile = ? where profileid = ?";
$adb->pquery($update_query, array(($mobile_perm ? 1 : 0), $profileid));
// crmv@39110e

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
		$update_query = "update ".$table_prefix."_profile2tab set permissions=? where tabid=? and profileid=?";
		$adb->pquery($update_query, array($permission_value, $tab_id, $profileid));
		if($tab_id ==9)
		{
			$update_query = "update ".$table_prefix."_profile2tab set permissions=? where tabid=16 and profileid=?";
			$adb->pquery($update_query, array($permission_value, $profileid));
		}
	}
}

//profile2standard permissions
for($i=0; $i<$num_act_per; $i++)
{
	$tab_id = $adb->query_result($act_perr_result,$i,"tabid");
	if($tab_id != 16)
	{
		$action_id = $adb->query_result($act_perr_result,$i,"operation");
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
		$update_query = "update ".$table_prefix."_profile2standardperm set permissions=? where tabid=? and Operation=? and profileid=?";
		$adb->pquery($update_query, array($permission_value, $tab_id, $action_id, $profileid));
		if($tab_id ==9)
		{
			$update_query = "update ".$table_prefix."_profile2standardperm set permissions=? where tabid=16 and Operation=? and profileid=?";
			$adb->pquery($update_query, array($permission_value, $action_id, $profileid));
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

	$update_query = "update ".$table_prefix."_profile2utility set permission=? where tabid=? and activityid=? and profileid=?";
	$adb->pquery($update_query, array($permission_value, $tab_id, $action_id, $profileid));
}

$modArr=getFieldModuleAccessArray();
foreach($modArr as $fld_module => $fld_label)
{
	$fieldListResult = getProfile2FieldList($fld_module, $profileid);
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
		//Updating the Mandatory _fields
		$uitype = $adb->query_result($fieldListResult,$i,"uitype");
		$displaytype =  $adb->query_result($fieldListResult,$i,"displaytype");
		$fieldname =  $adb->query_result($fieldListResult,$i,"fieldname");
		$typeofdata = $adb->query_result($fieldListResult,$i,"typeofdata");
		$fieldtype = explode("~",$typeofdata);
		$fieldid =  $adb->query_result($fieldListResult,$i,"fieldid");
		
		$propProfile2field = array();
		if ($fieldtype[1] == 'M' || $disable_field_array[$fieldid] == 1) {
			// skip update visibility and mandatory flags because are disabled
		} else {
			$visible = $_REQUEST[$fieldid];
			($visible == 'on' || in_array($tab_id,$hideTabs)) ? $visible_value = 0 : $visible_value = 1;	//crmv@27711
			$propProfile2field['visible'] = $visible_value;
			
			$mandatory = $_REQUEST['m_'.$fieldid];
			($mandatory == 'on' || in_array($tab_id,$hideTabs)) ? $mandatory_value = 0 : $mandatory_value = 1;
			$propProfile2field['mandatory'] = $mandatory_value;
		}
		
		//Updating the database
		//crmv@24665
		$check_query = "select * from ".$table_prefix."_profile2field where fieldid=? and profileid=? and tabid=?";
		$check_res = $adb->pquery($check_query,Array($fieldid,$profileid,$tab_id));
		if ($check_res && $adb->num_rows($check_res) > 0){
			if (!empty($propProfile2field)) {
				$set = array(); foreach($propProfile2field as $c => $v) $set[] = $c.'=?';
				$update_query = "update ".$table_prefix."_profile2field set ".implode(',',$set)." where fieldid=? and profileid=? and tabid=?";
				$adb->pquery($update_query, array($propProfile2field, $fieldid, $profileid, $tab_id));
			}
		}
		else{
			$check_query2 = "select * from ".$table_prefix."_profile2field where fieldid=? and profileid=?";
			$check_res2 = $adb->pquery($check_query2,Array($fieldid,$profileid));
			if ($check_res2 && $adb->num_rows($check_res2) > 0){
				$sql11="delete from ".$table_prefix."_profile2field where fieldid=? and profileid=?";
		        $adb->pquery($sql11, array($fieldid, $profileid));
			}			
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
		}
		//crmv@24665e
	}
	//crmv@49510e crmv@160041e
}

//crmv@150592
$result = $adb->pquery("select profilename from {$table_prefix}_profile where profileid=?", array($profileid));
if ($result && $adb->num_rows($result) > 0) {
	$profilename = $adb->query_result($result,0,'profilename');
}
//crmv@150592e

// crmv@49398 crmv@150592
global $metaLogs;
$userInfoUtils = UserInfoUtils::getInstance();
if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_EDITPROFILE, $profileid, array('profilename'=>$profilename));
if (!empty($metaLogId)) $userInfoUtils->versionOperation_profile($metaLogId);
// crmv@49398e crmv@150592e

// crmv@162674
if($return_action == 'profilePrivileges' || $return_action == 'ListProfiles') {
	$loc = "index.php?action=".$return_action."&module=Settings&mode=view&parenttab=Settings&profileid=".$profileid."&selected_tab=".$def_tab."&selected_module=".$def_module;
} else {
	$loc = "index.php?action=".$return_action."&module=Users&mode=view&parenttab=Settings&profileid=".$profileid."&selected_tab=".$def_tab."&selected_module=".$def_module;
}
RequestHandler::outputRedirect($loc);
// crmv@162674e

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
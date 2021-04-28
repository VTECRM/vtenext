<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$smarty = new VteSmarty();

global $theme,$mod_strings;
global $table_prefix;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$log->info("Conditionals edit view");

$smarty->assign("UMOD", $mod_strings);
// global $current_language;
$smod_strings = return_module_language($current_language,'Settings');
$smarty->assign("MOD", $smod_strings);
$smarty->assign("CURRENT_USERID", $current_user->id);
$smarty->assign("APP", $app_strings);

if (isset($_REQUEST['error_string'])) $smarty->assign("ERROR_STRING", "<font class='error'>Error: ".$_REQUEST['error_string']."</font>");
if (isset($_REQUEST['return_module']))
{
        $smarty->assign("RETURN_MODULE", $_REQUEST['return_module']);
        $RETURN_MODULE=$_REQUEST['return_module'];
}
if (isset($_REQUEST['return_action']))
{
        $smarty->assign("RETURN_ACTION", $_REQUEST['return_action']);
        $RETURN_ACTION = $_REQUEST['return_action'];
}
if ($_REQUEST['isDuplicate'] != 'true' && isset($_REQUEST['return_id']))
{
        $smarty->assign("RETURN_ID", $_REQUEST['return_id']);
        $RETURN_ID = $_REQUEST['return_id'];
}
$conditionals_obj = CRMEntity::getInstance('Conditionals'); //crmv@36505
$modules_list = $conditionals_obj->getTransitionConditionalWorkflowModulesList(); //crmv@36505

$fields_list = array();
$fields_uitypes = array();
$fields_columnnames = array();

for ($i = 0; $i < sizeof($modules_list); $i++)
{

	$sql = "SELECT f.fieldlabel, f.uitype, f.columnname, f.fieldname, t.tabid
		FROM ".$table_prefix."_field f, ".$table_prefix."_tab t
		WHERE f.tabid = t.tabid
		AND t.name = '".$modules_list[$i][0]."'";
	$result = $adb->query($sql);

	$fields_list[$modules_list[$i][0]] = array();
	$fields_uitypes[$modules_list[$i][0]] = array();
	$fields_columnnames[$modules_list[$i][0]] = array();

	$num_rows = $adb->num_rows($result);
    for ($k = 0; $k < $num_rows; $k++)
	{
		$field_name_key = $adb->query_result($result, $k, 'fieldlabel');
		$field_name = "";
						
//		print_r($modules_list[$i][0]);echo ' - '.$field_name_key.' \n';
						
		if ($app_strings[$field_name_key])
			$field_name = $app_strings[$field_name_key];
		elseif ($mod_strings[$field_name_key])
			$field_name = $mod_strings[$field_name_key];
		else
		{
			$field_name = $field_name_key; // localization file miss " []";
		}

		$fields_list[$modules_list[$i][0]][] = $field_name_key;
		$fields_columnnames[$modules_list[$i][0]][] = $adb->query_result($result, $k, 'fieldname');
		$fields_uitypes[$modules_list[$i][0]][] = $adb->query_result($result, $k, 'uitype');
	}
}

$smarty->assign("modules_list", $modules_list);
$smarty->assign("modules_fields", $fields_list);
$smarty->assign("modules_fields_uitypes", $fields_uitypes);
$smarty->assign("fields_columnnames", $fields_columnnames);


$group_rs = get_group_options();
$group_rs_row = $adb->fetch_array($group_rs);

if($_REQUEST["ruleid"] != "") {
	
	$ruleinfo = $conditionals_obj->getRulesInfo($_REQUEST["ruleid"]); //crmv@36505
//	echo '<pre>';
//	print_r($ruleinfo);
//	echo '</pre>';

	$mode='edit';
	$FpofvData = $conditionals_obj->wui_getFpofvData($_REQUEST["ruleid"],$ruleinfo['tablabel']); //crmv@36505
	$FpofvDataRemapped = Array();
		
	for($i=0;$i<count($FpofvData);$i++) {
		// crmv@172864
		$FpofvDataRemapped[$i]['TaskField'] 			= $FpofvData[$i]["FpofvChkFieldName"];
		$FpofvDataRemapped[$i]['TaskFieldLabel'] 		= $FpofvData[$i]["FpofvChkFieldLabel"];
		$FpofvDataRemapped[$i]['TaskType']			= "FieldChange";
		$FpofvDataRemapped[$i]['FpovManaged'] 	= $FpofvData[$i]["FpovManaged"];
		$FpofvDataRemapped[$i]['FpovReadPermission'] 	= $FpofvData[$i]["FpovReadPermission"];
		$FpofvDataRemapped[$i]['FpovWritePermission']	= $FpofvData[$i]["FpovWritePermission"];
		$FpofvDataRemapped[$i]['FpovMandatoryPermission']	= $FpofvData[$i]["FpovMandatoryPermission"];
		$FpofvDataRemapped[$i]['FpofvSequence'] = $FpofvData[$i]["FpofvSequence"];
		$FpofvDataRemapped[$i]['FpofvBlockLabel'] = $FpofvData[$i]["FpofvBlockLabel"];
		// crmv@172864e
	}
	
	$smarty->assign("WorkflowName", $ruleinfo['description']);
	$smarty->assign("ModuleName", $ruleinfo['tablabel']);
	$smarty->assign("FpofvRoleGrpCheck", $ruleinfo['role_grp_check']);
	$smarty->assign("Rules", $ruleinfo['rules']);
	$to_mod_strings = return_module_language($current_language,$ruleinfo['tablabel']);
	$smarty->assign("TOMOD", $to_mod_strings);
	
} else {
	$mode='create';
}

$roleDetails=getAllRoleDetails();
unset($roleDetails['H1']);
$grpDetails=getAllGroupName();
$role_grp_check_picklist ='<select id="role_grp_check" name="role_grp_check" class="detailedViewTextBox">';

$role_grp_check_picklist .='<option value="ALL'.$roleid.'"';
if ($ruleinfo['role_grp_check'] == 'ALL') $role_grp_check_picklist .= 'selected';
$role_grp_check_picklist .= '>'.$mod_strings['NO_CONDITIONS'].'</option>';
foreach($roleDetails as $roleid=>$rolename)
{
	if('roles::'.$roleid == $ruleinfo['role_grp_check'])
		$selected = "selected";
	else $selected = "";
	$role_grp_check_picklist .='<option value="roles::'.$roleid.'" '.$selected.'>'.$mod_strings['LBL_ROLES'].'::'.$rolename[0].'</option>';
	 
}
foreach($roleDetails as $roleid=>$rolename)
{
	if('rs::'.$roleid == $ruleinfo['role_grp_check'])
		$selected = "selected";
	else $selected = "";
	$role_grp_check_picklist .='<option value="rs::'.$roleid.'" '.$selected.'>'.$mod_strings['LBL_ROLES_SUBORDINATES'].'::'.$rolename[0].'</option>';
}
foreach($grpDetails as $groupid=>$groupname)
{
	if('groups::'.$groupid == $ruleinfo['role_grp_check'])
		$selected = "selected";
	else $selected = "";		
	$role_grp_check_picklist .='<option value="groups::'.$groupid.'" '.$selected.'>'.$mod_strings['LBL_GROUP'].'::'.$groupname.'</option>';
}
$role_grp_check_picklist .= '</select>';

$smarty->assign("FPOFV_PIECE_DATA", $FpofvDataRemapped);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path); 
if (empty($focus)) $focus = new stdClass(); // crmv@92855
$focus->mode = $mode;
$smarty->assign("MODULE", 'Settings');
$smarty->assign("MODE",$mode);
$smarty->assign("DUPLICATE",$_REQUEST['isDuplicate']);
$smarty->assign("USER_MODE",$mode);
$smarty->assign('PARENTTAB',$_REQUEST['parenttab']);
if($mode == "edit")
	$smarty->assign('FIELD_PERMISSIONS_DISPLAY',"inline");
else $smarty->assign('FIELD_PERMISSIONS_DISPLAY',"none");
$smarty->assign("ROLE_GRP_CHECK_PICKLIST",$role_grp_check_picklist);

// crmv@77249
if ($_REQUEST['included'] == true) {
	$smarty->assign("INCLUDED",true);
	$smarty->assign("FORMODULE",$_REQUEST['formodule']);
	$smarty->assign("STATUSFIELD",$_REQUEST['statusfield']);	
}
// crmv@77249e

$smarty->display('modules/Conditionals/EditView.tpl');
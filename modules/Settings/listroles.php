<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@94525 crmv@150266 */

if (!isset($smarty)) $smarty = new VteSmarty();
$userInfoUtils = UserInfoUtils::getInstance();

global $mod_strings, $app_strings, $app_list_strings, $theme;
global $adb, $table_prefix;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

//Retrieving the hierarchy
$hquery = "select * from ".$table_prefix."_role order by parentrole asc";
$hr_res = $adb->pquery($hquery, array());
$num_rows = $adb->num_rows($hr_res);
$hrarray= Array();

for($l=0; $l<$num_rows; $l++)
{
	$roleid = $adb->query_result_no_html($hr_res,$l,'roleid');
	$parent = $adb->query_result_no_html($hr_res,$l,'parentrole');
	$temp_list = explode('::',$parent);
	$size = sizeof($temp_list);
	$i=0;
	$k= Array();
	$y=$hrarray;
	if(sizeof($hrarray) == 0)
	{
		$hrarray[$temp_list[0]]= Array();
	}
	else
	{
		while($i<$size-1)
		{
			$y=$y[$temp_list[$i]];
			$k[$temp_list[$i]] = $y;
			$i++;
			
		}
		$y[$roleid] = Array();
		$k[$roleid] = Array();
		
		//Reversing the Array
		$rev_temp_list=array_reverse($temp_list);
		$j=0;
		//Now adding this into the main array
		foreach($rev_temp_list as $value)
		{
			if($j == $size-1)
			{
				$hrarray[$value]=$k[$value];
			}
			else
			{
				$k[$rev_temp_list[$j+1]][$value]=$k[$value];
			}
			$j++;
		}
	}
	
}
//Constructing the Roledetails array
$role_det = getAllRoleDetails();

$roleout ='';
$roleout .= indent($hrarray,$roleout,$role_det);

/** recursive function to construct the role tree ui
 * @param $hrarray -- Hierarchial role tree array with only the roleid:: Type array
 * @param $roleout -- html string ouput of the constucted role tree ui:: Type varchar
 * @param $role_det -- Roledetails array got from calling getAllRoleDetails():: Type array
 * @returns $role_out -- html string ouput of the constucted role tree ui:: Type string
 *
 */

function indent($hrarray,$roleout,$role_det) {
	global $mod_strings,$app_strings;
	
	foreach($hrarray as $roleid => $value) {
		
		//retreiving the vte_role details
		$role_det_arr=$role_det[$roleid];
		$roleid_arr=$role_det_arr[2];
		$rolename = $role_det_arr[0];
		$roledepth = $role_det_arr[1];
		
		$roleout .= '<ul class="uil" id="'.$roleid.'" style="display:block;list-style-type:none;">';
		$roleout .=  '<li ><table border="0" cellpadding="0" cellspacing="0" onMouseOver="ListRoles.fnVisible(\'layer_'.$roleid.'\')" onMouseOut="ListRoles.fnInVisible(\'layer_'.$roleid.'\')">';
		$roleout.= '<tr><td nowrap>';
		
		if (sizeof($value) >0 && $roledepth != 0) {
			$roleout.='<b style="font-weight:bold;margin:0;padding:0;cursor:pointer;">';
			$roleout .= '<i class="vteicon md-sm md-link valign-bottom" id="img_'.$roleid.'" title="'.$app_strings['LBL_EXPAND_COLLAPSE'].'" onClick="ListRoles.showhide(\''.$roleid_arr.'\',\'img_'.$roleid.'\')" >indeterminate_check_box</i>';
		} elseif ($roledepth != 0) {
			$roleout .= '<i class="vteicon md-sm valign-bottom" id="img_'.$roleid.'" title="'.$app_strings['LBL_EXPAND_COLLAPSE'].'">label_outline</i>';
		} else {
			$roleout .= '<i class="vteicon valign-bottom" id="img_'.$roleid.'" title="'.$app_strings['LBL_ROOT'].'">apps</i>';
		}
		
		if ($roledepth == 0 ) {
			$roleout .= '&nbsp;<b class="genHeaderGray">'.$rolename.'</b></td>';
			$roleout .= '<td nowrap><div id="layer_'.$roleid.'" class="drag_Element"><a href="index.php?module=Settings&action=createrole&parenttab=Settings&parent='.$roleid.'"><i class="vteicon" title="'.$mod_strings['LBL_ADD_ROLE'].'">add</i></a></div></td></tr></table>';
		} else {
			$roleout .= '&nbsp;<a href="javascript:ListRoles.put_child_ID(\'user_'.$roleid.'\');" class="x" id="user_'.$roleid.'">'.$rolename.'</a></td>';
			
			$roleout.='<td nowrap><div id="layer_'.$roleid.'" class="drag_Element">
				<a href="index.php?module=Settings&action=createrole&parenttab=Settings&parent='.$roleid.'"><i class="vteicon md-sm" title="'.$mod_strings['LBL_ADD_ROLE'].'">add</i></a>
				<a href="index.php?module=Settings&action=createrole&roleid='.$roleid.'&parenttab=Settings&mode=edit"><i class="vteicon md-sm" title="'.$mod_strings['LBL_EDIT_ROLE'].'">create</i></a>';
			
			if($roleid != 'H1'  && $roleid != 'H2') {
				$roleout .=	'<a href="index.php?module=Settings&action=RoleDeleteStep1&roleid='.$roleid.'&parenttab=Settings"><i class="vteicon md-sm" title="'.$mod_strings['LBL_DELETE_ROLE'].'">delete</i></a>';
			}
			$roleout .='<a href="javascript:;" class="small" onClick="ListRoles.get_parent_ID(this,\'user_'.$roleid.'\')"><i class="vteicon md-sm" title="'.$mod_strings['LBL_MOVE_ROLE'].'">open_with</i></a></div></td></tr></table>';
		}
		$roleout .=  '</li>';
		
		if(sizeof($value) > 0 ) {
			$roleout = indent($value,$roleout,$role_det);
		}
		
		$roleout .=  '</ul>';
	}
	
	return $roleout;
}

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("ROLETREE", $roleout);

$pending_version = $userInfoUtils->getPendingVersion_role();
$smarty->assign('PENDING_VERSION', $pending_version['version']);
$smarty->assign('CURRENT_VERSION', $userInfoUtils->getCurrentVersionNumber_role());
$smarty->assign('PERM_VERSION_EXPORT', $userInfoUtils->isExportPermitted_role());
$smarty->assign('PERM_VERSION_IMPORT', $userInfoUtils->isImportPermitted_role());
$smarty->assign('CHECK_VERSION_IMPORT', $userInfoUtils->checkImportVersion_role());

if($_REQUEST['ajax'] == 'true') {
	$smarty->display("RoleTree.tpl");
} else {
	$smarty->display("ListRoles.tpl");
}
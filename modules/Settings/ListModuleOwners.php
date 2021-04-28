<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings,$table_prefix;
global $theme;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$module_array = getModuleNameList();

if ($_REQUEST['list_module_mode'] != '' && $_REQUEST['list_module_mode'] == 'save') {
	foreach($module_array as $val) {
		$req_name = 'user_'.strtolower($val);
		$userid = $_REQUEST[$req_name];
		$tabid = getTabid($val);
		if($tabid != '' && $userid != '') {
			$sql = 'update '.$table_prefix.'_moduleowners set user_id = ? where tabid = ?';
			$adb->pquery($sql, array($userid, $tabid));
		}
	}
	
} elseif($_REQUEST['list_module_mode'] != '' && $_REQUEST['list_module_mode'] == 'edit') {
	$user_array = get_user_array(false);
	foreach($module_array as $val) {
		$user_combo = getUserCombo($user_array,$val);

		$user_list []= $val;
		$user_list []= $user_combo;
	}
	$user_list = array_chunk($user_list,2);
}


if($_REQUEST['list_module_mode'] != 'edit') {
	foreach($module_array as $val) {
		$user_list []= $val;

		//get the user array as a combo list
		$user_id = getModuleOwner(getTabid($val));
		$user_name = getUserName($user_id);

		$user_list []= $user_id;
		$user_list []= $user_name;
	}

	$user_list = array_chunk($user_list,3);
}

$smarty = new VteSmarty();

$smarty->assign("THEME", $theme);
$smarty->assign("MODULE_MODE",$_REQUEST['list_module_mode']);
$smarty->assign("USER_LIST", $user_list);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

if($_REQUEST['file_mode'] != 'ajax')
	$smarty->display("Settings/ModuleOwners.tpl");
else
	$smarty->display("Settings/ModuleOwnersContents.tpl");
	
/** Function to get the module owner for the tabid
 *  @ param $tabid
 *  It gets the module owner for the given tabid from vte_moduleowners table
 *  returns the userid of the module owner for the given tabid
 */
function getModuleOwner($tabid) {
	global $adb,$table_prefix;
	$sql = "select * from ".$table_prefix."_moduleowners where tabid=?";
	$res = $adb->pquery($sql, array($tabid));
	$userid = $adb->query_result_no_html($res,0,'user_id');

	return $userid;
}
	/** Function to get the module List to which the owners can be assigned 
	 *  It gets the module list and returns in an array 
	 */
function getModuleNameList() {
	global $adb,$table_prefix;

	// vtlib customization: Ignore disabled modules
	//$sql = "select vte_moduleowners.*, vte_tab.name from vte_moduleowners inner join vte_tab on vte_moduleowners.tabid = vte_tab.tabid order by vte_tab.tabsequence";
	$sql = "SELECT ".$table_prefix."_moduleowners.*, ".$table_prefix."_tab.name
		from ".$table_prefix."_moduleowners
		inner join ".$table_prefix."_tab on ".$table_prefix."_moduleowners.tabid = ".$table_prefix."_tab.tabid 
		WHERE ".$table_prefix."_tab.presence != 1
		order by ".$table_prefix."_tab.tabsequence";
	// END
	$res = $adb->pquery($sql, array());
	$mod_array = Array();
	while($row = $adb->fetchByAssoc($res)) {
		array_push($mod_array,$row['name']);
	}
	return $mod_array;
}

/** Function to get combostrings of users 
 *  @ $user_array : type Array
 *  @ $modulename : Type String 
 *  returns the html string for module owners for the given module owners
 */
function getUserCombo($user_array,$name) {
	global $adb,$table_prefix;

	$tabid = getTabid($name);
	$sql = "select * from ".$table_prefix."_moduleowners where tabid=?";
	$res = $adb->pquery($sql, array($tabid));
	$db_userid = $adb->query_result($res,0,'user_id');
	
	//Form the user combo list for each module
	$combo_name = "user_".strtolower($name);
	$user_combo = '<select name="'.$combo_name.'">';
	foreach($user_array as $user_id => $user_name)
	{
		$selected = '';
		if($user_id == $db_userid)
		{	
			$selected = 'selected';
		}
		$user_combo .= '<OPTION value="'.$user_id.'" '.$selected.'>'.$user_name.'</OPTION>';
	}
	$user_combo .= '</select>';

	return $user_combo;
}

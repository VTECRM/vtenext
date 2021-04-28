<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@158543 */

require_once('include/CustomFieldUtil.php');
require_once('modules/Settings/LayoutBlockListUtils.php');

global $mod_strings,$app_strings,$theme;

$module_array = array('Calendar'=>'Calendar');

$smarty = new VteSmarty();

$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("MODULES",$module_array);

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty->assign("IMAGE_PATH", $image_path);


if($_REQUEST['fld_module'] !='')
	$fld_module = $_REQUEST['fld_module'];
//crmv@62929
elseif($_REQUEST['formodule'] !='')
	$fld_module = $_REQUEST['formodule'];
//crmv@62929e
else {
	reset($module_array); // make sure array pointer is at first element
	$fld_module = key($module_array);
}

if ($fld_module != 'Calendar') die('Module not supported');

$smarty->assign("MODULE",$fld_module);
$smarty->assign("CFENTRIES",getCFListEntries($fld_module));

if(isset($_REQUEST["duplicate"]) && $_REQUEST["duplicate"] == "yes") {
	$error = $mod_strings['ERR_CUSTOM_FIELD_WITH_NAME']. $_REQUEST["fldlabel"] .$mod_strings['ERR_ALREADY_EXISTS'] . ' ' .$mod_strings['ERR_SPECIFY_DIFFERENT_LABEL'];
	$smarty->assign("DUPLICATE_ERROR", $error);
}

if($_REQUEST['mode'] !='') {
	$smarty->assign("MODE", $_REQUEST['mode']);
}

$newfielddata = array(
	'Events' => array(
		'blockid' => getBlockId(16, 'LBL_CUSTOM_INFORMATION'),
	),
	'Calendar' => array(
		'blockid' => getBlockId(9, 'LBL_CUSTOM_INFORMATION'),
	),
);
$smarty->assign('NEWFIELDDATA', $newfielddata);
$smarty->assign('NEWFIELDS', getNewFields());
$smarty->assign('USERSLIST', get_user_array(true, "Active"));

if($_REQUEST['ajax'] != 'true')
	$smarty->display('CustomFieldList.tpl');	
else
	$smarty->display('CustomFieldEntries.tpl');

/**
 * Function to get customfield entries
 * @param string $module - Module name
 * return array  $cflist - customfield entries
 */
function getCFListEntries($module) {
	global $adb,$table_prefix;
	
	$tabid = getTabid($module);
	
	if ($module == 'Calendar') {
		$tabid = array(9, 16);
	}

	$dbQuery = "select fieldid,columnname,fieldlabel,uitype,displaytype,block,tabid 
		from ".$table_prefix."_field 
		where tabid in (". generateQuestionMarks($tabid) .") and ".$table_prefix."_field.presence in (0,2) and generatedtype = 2
		order by sequence";
	$result = $adb->pquery($dbQuery, array($tabid));
	
	$count=1;
	$cflist=Array();
	while ($row = $adb->fetch_array($result)) {
		$cf_element=Array();
		$cf_element['no']=$count;
		$cf_element['label']=getTranslatedString($row["fieldlabel"],$module);
		$fld_type_name = getCustomFieldTypeName($row["uitype"]);
		$cf_element['type']=$fld_type_name;
		$cf_tab_id = $row["tabid"];
		
		if($module == 'Calendar')
		{
			if ($cf_tab_id == '9')
				$cf_element['activitytype'] = getTranslatedString('Task',$module);
			else
				$cf_element['activitytype'] = getTranslatedString('Event',$module);
		}
		$cf_element['tool']='&nbsp;<i class="vteicon md-link" onClick="LayoutEditor.deleteCalendarField('.$row["fieldid"].',\''.$module.'\', \''.$row["columnname"].'\', \''.$row["uitype"].'\')" src="'. resourcever('delete.gif') .'" title="'.getTranslatedString('LBL_DELETE_BUTTON_LABEL').'">delete</i>';
		$cflist[] = $cf_element;
		$count++;
	}
	return $cflist;
}

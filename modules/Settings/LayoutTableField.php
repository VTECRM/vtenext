<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@106857 */
require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');

global $mod_strings, $app_strings, $theme, $adb, $table_prefix;

$blockid = vtlib_purify($_REQUEST['blockid']);
$fieldid = vtlib_purify($_REQUEST['fieldid']);

global $small_page_title, $small_page_title;
$small_page_title = $app_strings['LBL_ADD_FIELD_TABLE'];
$small_page_buttons = '
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="100%" style="padding:5px"></td>
 	<td align="right" style="padding: 5px;" nowrap>
 		<input type="button" class="crmbutton small save" value="'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'" onclick="MlTableFieldConfig.saveConfig()">
 		<input type="button" class="crmbutton small cancel" value="'.getTranslatedString('LBL_CANCEL_BUTTON_LABEL').'" onclick="MlTableFieldConfig.cancelConfig()">';
if (!empty($fieldid)) {
	$small_page_buttons .= ' <input type="button" class="crmbutton small cancel" value="'.getTranslatedString('LBL_DELETE_BUTTON_LABEL').'" onclick="MlTableFieldConfig.deleteConfig()">';
}
$small_page_buttons .= '
 	</td>
</tr>
</table>';
include('themes/SmallHeader.php');

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
$smarty->assign("LAYOUT_MANAGER", true);
$smarty->assign("BLOCKID", $blockid);
if (!empty($fieldid)) {
	$result = $adb->pquery("select fieldname, fieldlabel, readonly, typeofdata, name from {$table_prefix}_field inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid where fieldid = ?", array($fieldid)); // crmv@190916
	$module = $adb->query_result($result,0,'name');
	$fieldname = $adb->query_result($result,0,'fieldname');
	$fieldlabel = $adb->query_result($result,0,'fieldlabel');
	// crmv@190916
	$readonly = $adb->query_result($result,0,'readonly');
	$typeofdata = $adb->query_result($result,0,'typeofdata');
	$typeofdata = explode("~",$typeofdata);
	$mandatory = ($typeofdata[1] == 'M') ? true : false;
	// crmv@190916e
	
	require_once('include/utils/ModLightUtils.php');
	$MLUtils = ModLightUtils::getInstance();
	$columns = $MLUtils->getColumns($module,$fieldname);

	$fieldinfo = array(
		'label'=>$fieldlabel,
		'columns'=>$columns,
		// crmv@190916
		'readonly'=>$readonly,
		'mandatory'=>$mandatory,
		// crmv@190916e
	);
	$smarty->assign("FIELDINFO", Zend_Json::encode($fieldinfo));
	$smarty->assign("FIELDID", $fieldid);
}

$MMUtils = new ModuleMakerUtils();
$MMSteps = new ProcessModuleMakerSteps($MMUtils);
$smarty->assign("NEWFIELDS", $MMSteps->getNewFields());
$smarty->assign("NEWTABLEFIELDCOLUMNS", $MMSteps->getNewTableFieldColumns()); // crmv@102879

$smarty->display("Settings/ModuleMaker/LayoutTableField.tpl");
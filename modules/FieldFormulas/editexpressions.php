<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("include/utils/utils.php");
require_once("include/Zend/Json.php");

function vtGetModules($adb) {
	$modules = com_vtGetModules($adb);
	return $modules;
}

function vtEditExpressions($adb, $appStrings, $current_language, $theme, $formodule='') {
	$theme_path="themes/".$theme."/";
	$image_path=$theme_path."images/";

	$smarty = new VteSmarty();
	$smarty->assign('APP', $appStrings);

	$mod =  array_merge(
	return_module_language($current_language,'FieldFormulas'),
	return_module_language($current_language,'Settings'));

	$jsStrings = array(
		'NEED_TO_ADD_A'=>$mod['NEED_TO_ADD_A'],
		'CUSTOM_FIELD' =>$mod['LBL_CUSTOM_FIELD'],
		'LBL_USE_FUNCTION_DASHDASH'=>$mod['LBL_USE_FUNCTION_DASHDASH'],
		'LBL_USE_FIELD_VALUE_DASHDASH'=>$mod['LBL_USE_FIELD_VALUE_DASHDASH'],
		'LBL_DELETE_EXPRESSION_CONFIRM'=>$mod['LBL_DELETE_EXPRESSION_CONFIRM']
	);
	$smarty->assign("JS_STRINGS", Zend_Json::encode($jsStrings));

	$smarty->assign("MOD", $mod);
	$smarty->assign("THEME",$theme);
	$smarty->assign("IMAGE_PATH",$image_path);
	$smarty->assign("MODULE_NAME", 'FieldFormulas');
	$smarty->assign("PAGE_NAME", 'LBL_FIELDFORMULAS');
	$smarty->assign("PAGE_TITLE", 'LBL_FIELDFORMULAS');
	$smarty->assign("PAGE_DESC", 'LBL_FIELDFORMULAS_DESCRIPTION');
	$smarty->assign("FORMODULE", $formodule);

	if(file_exists("modules/$formodule/$formodule.php")) {
		$focus = CRMEntity::getInstance($formodule);
		// crmv@83877 crmv@112297
		// Field Validation Information
		$tabid = getTabid($formodule);
		$otherInfo = array();
		$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo);
		$validationArray = split_validationdataArray($validationData, $otherInfo);
		$smarty->assign("VALIDATION_DATA_FIELDNAME",$validationArray['fieldname']);
		$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",$validationArray['datatype']);
		$smarty->assign("VALIDATION_DATA_FIELDLABEL",$validationArray['fieldlabel']);
		$smarty->assign("VALIDATION_DATA_FIELDUITYPE",$validationArray['fielduitype']);
		$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",$validationArray['fieldwstype']);
		// crmv@83877e crmv@112297e
	}

	$smarty->display(vtlib_getModuleTemplate('FieldFormulas', 'EditExpressions.tpl'));
}

$modules = vtGetModules($adb);
if(vtlib_isModuleActive('FieldFormulas') && array_key_exists(vtlib_purify($_REQUEST['formodule']),$modules)) { //crmv@174787
	vtEditExpressions($adb, $app_strings, $current_language, $theme, $_REQUEST['formodule']);
} else {
	echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
	echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 80%; position: relative; z-index: 10000000;'>

	<table border='0' cellpadding='5' cellspacing='0' width='98%'>
	<tbody><tr>
	<td rowspan='2' width='11%'><img src='". resourcever('denied.gif') ."' ></td>
	<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>".$app_strings['LBL_PERMISSION']." </span></td>
	</tr>
	<tr>
	<td class='small' align='right' nowrap='nowrap'>
	<a href='javascript:window.history.back();'>$app_strings[LBL_BACK]</a><br></td>
	</tr>
	</tbody></table>
	</div>";
	echo "</td></tr></table>";die;
}

?>
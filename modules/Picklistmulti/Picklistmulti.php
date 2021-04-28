<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Picklistmulti/Picklistmulti_class.php');
require_once('modules/Picklistmulti/Picklistmulti_utils.php');
global $app_strings, $app_list_strings, $current_language, $currentModule, $theme,$list_max_entries_per_page;

if ($currentModule == 'Picklistmulti' && $_REQUEST['action'] == 'PicklistmultiAjax') return; // crmv@39106 - fix edit

$smarty = new VteSmarty();
$fld_module = $_REQUEST['moduleName'];
if($fld_module == 'Events'){
	$temp_module_strings = return_module_language($current_language, 'Calendar');
}else{
	$temp_module_strings = return_module_language($current_language, $fld_module);
}
$pick_obj = new Picklistmulti(false,$_REQUEST['moduleName'],$_REQUEST['fieldName'],Array(0,$list_max_entries_per_page));
$picklists_entries = $pick_obj->field;
$picklists_columns=array();
$smarty->assign("MODULE_LIST",$pick_obj->module_list);
$smarty->assign("FIELD_LIST",$pick_obj->field_list);
$smarty->assign("APP", $app_strings);		//the include language files
$smarty->assign("MOD", return_module_language($current_language,'Settings'));	//the settings module language file
$smarty->assign("MOD_PICKLIST", return_module_language($current_language,'Picklistmulti'));	//the picklist module language files
$smarty->assign("TEMP_MOD", $temp_module_strings);	//the selected module language file
$smarty->assign("MODULE",$pick_obj->module_name);
$smarty->assign("FLD_NAME",$pick_obj->field_name);
$smarty->assign("FLD_LABEL",$pick_obj->field_label);
$smarty->assign("MAX_ROWS",$list_max_entries_per_page);
$smarty->assign("PICKLIST_VALUES",$pick_obj->field_label);
$smarty->assign("EMPTY",$pick_obj->is_empty());
$smarty->assign("PICKLIST_COLUMNS",$pick_obj->getColumnsjson());
$smarty->assign("PICKLIST_COLUMN_NAMES",$pick_obj->getColumnNames());
$smarty->assign("EDIT",$_REQUEST['edit']);
$smarty->assign("THEME",$theme);
$smarty->assign("UITYPE", $uitype);
$lang = explode("_",$current_language);
$lang = $lang[0];
$smarty->assign("LANGUAGE_SUFFIX", $lang);
$smarty->display("modules/Picklistmulti/PickList.tpl");
?>
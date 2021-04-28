<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user,$theme; //crmv@36505
$record = $_REQUEST['record'];
$module = getSalesEntityType($record);
$focus = CRMEntity::getInstance($module);
$focus->id = $record;
$focus->retrieve_entity_info($record,$module);
$trans_obj = CRMEntity::getInstance('Transitions');
$trans_obj->Initialize($module,$current_user->roleid);

$log = LoggerManager::getLogger($currentModule); //@todo - decidere il nome
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty = new VteSmarty();
$settings_strings = return_module_language($current_language,'Settings');
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("SMOD", $settings_strings);
$smarty->assign("TMOD", return_module_language($current_language,'Transitions'));
$smarty->assign("MOD", return_module_language($current_language,$module));
$smarty->assign("APP", $app_strings);
$smarty->assign("CURRENT_USERID", $current_user->id);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("FIELDNAME",$trans_obj->status_field);
$smarty->assign("MODULENAME",$trans_obj->modulename);
$smarty->assign("MODULE",'Transitions');
$smarty->assign("UITYPE",15);
$smarty->assign("ACTUAL_STATUS",$focus->column_fields[$trans_obj->status_field]);
$smarty->assign("ACTUAL_ID",$record);
$history = $trans_obj->get_last_change_state($record);
if (!$history)
	$smarty->assign("HISTORY_VOID",'true');
$smarty->assign("HISTORY",$history);
//crmv@sdk-27926
$permitted_states = $trans_obj->get_permitted_states($focus->column_fields[$trans_obj->status_field],$record,true);
$smarty->assign("EXTRA_MESSAGE",$permitted_states['message']);
$permitted_states = $permitted_states['values'];
//crmv@sdk-27926e
if (vtlib_isModuleActive('Conditionals')){
    //crmv@36505
    $conditionals_obj = CRMEntity::getInstance('Conditionals');
	$rules_to_check = $conditionals_obj->getStatusBlockRules($module,$focus->column_fields); //crmv@101719
	$role_grp_check = $conditionals_obj->wui_sql_restric_status_on_mandatory_fields($focus,$module,$trans_obj->status_field,$focus->column_fields[$trans_obj->status_field],$rules_to_check); //crmv@101719
	//crmv@36505 e	
}
$perm_state_count = count($permitted_states);
$smarty->assign("STATES_COUNT",$perm_state_count);
if ($perm_state_count == 0 || ($perm_state_count == 1 && $focus->column_fields[$trans_obj->status_field] == $permitted_states[0])){
	$title = getTranslatedString('LBL_STATUS_NO_AVAILABLE_STATES','Transitions');
}	
elseif ($role_grp_check[0]){
	$title = getTranslatedString('LBL_STATUS_CAN_CHANGE_IF_FILL','Transitions');
	$smarty->assign("STATES_DISABLED",'true');
	unset($role_grp_check[0]);
	$smarty->assign("MANDATORY_FIELDS",$role_grp_check);
}		
else {
	$title = getTranslatedString('LBL_STATUS_CAN_CHANGE_TO','Transitions');
}	
$smarty->assign("TITLE",$title);	
$smarty->assign("COUNT_STATES",count($permitted_states));
$smarty->assign("PERMITTED_STATUS",$permitted_states);
$smarty->display("modules/Transitions/StatusBlock.tpl");
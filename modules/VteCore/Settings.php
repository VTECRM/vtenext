<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("modules/com_workflow/VTWorkflowUtils.php");//crmv@207901

global $mod_strings, $app_strings, $theme, $adb, $table_prefix;
$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", "$theme");
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

// Operation to be restricted for non-admin users.
global $current_user;
if(!is_admin($current_user)) {
	$smarty->display(vtlib_getModuleTemplate('VteCore','OperationNotPermitted.tpl'));
} else {
	$module = vtlib_purify($_REQUEST['formodule']);

	$menu_array = Array();

	//if(layout editor is permitted)
	$menu_array['LayoutEditor']['location'] = 'index.php?module=Settings&action=LayoutBlockList&parenttab=Settings&formodule='.$module;
	$menu_array['LayoutEditor']['image_src'] = resourcever('orgshar.gif');
	$menu_array['LayoutEditor']['desc'] = getTranslatedString('LBL_LAYOUT_EDITOR_DESCRIPTION');
	$menu_array['LayoutEditor']['label'] = getTranslatedString('LBL_LAYOUT_EDITOR');

	// crmv@39110
	if(vtlib_isModuleActive('Touch')) {
		$menu_array['LayoutEditorMobile']['location'] = 'index.php?module=Settings&action=LayoutBlockList&parenttab=Settings&mobile=1&formodule='.$module;
		$menu_array['LayoutEditorMobile']['image_src'] = resourcever('orgshar.gif');
		$menu_array['LayoutEditorMobile']['desc'] = getTranslatedString('LBL_LAYOUT_EDITOR_DESCRIPTION')." (Mobile)";
		$menu_array['LayoutEditorMobile']['label'] = getTranslatedString('LBL_LAYOUT_EDITOR')." Mobile";
	}
	// crmv@39110e
	
	//crmv@29463	crmv@20193
	if ($module == 'Leads') {
		$menu_array['LeadsMapping']['location'] = 'index.php?module=Settings&action=LeadCustomFieldMapping';
		$menu_array['LeadsMapping']['image_src'] = resourcever('custom.gif');
		$menu_array['LeadsMapping']['desc'] = getTranslatedString('LBL_LEADS_CUSTOM_FIELD_MAPPING_DESCRIPTION');
		$menu_array['LeadsMapping']['label'] = getTranslatedString('LBL_LEADS_CUSTOM_FIELD_MAPPING');
	}
	//crmv@29463e	crmv@20193e

	if(vtlib_isModuleActive('FieldFormulas')) {
		$modules = com_vtGetModules($adb);
		if(array_key_exists($module,$modules)) { // crmv@109907
			$sql_result = $adb->pquery("select * from {$table_prefix}_settings_field where name = ? and active=0",array('LBL_FIELDFORMULAS'));
			if($adb->num_rows($sql_result) > 0) {
				$menu_array['FieldFormulas']['location'] = $adb->query_result($sql_result, 0, 'linkto').'&formodule='.$module;
				$menu_array['FieldFormulas']['image_src'] = $adb->query_result($sql_result, 0, 'iconpath');
				$menu_array['FieldFormulas']['desc'] = getTranslatedString($adb->query_result($sql_result, 0, 'description'),'FieldFormulas');
				$menu_array['FieldFormulas']['label'] = getTranslatedString($adb->query_result($sql_result, 0, 'name'),'FieldFormulas');
			}
		}
	}

	if(vtlib_isModuleActive('Tooltip')){
		$sql_result = $adb->pquery("select * from {$table_prefix}_settings_field where name = ? and active=0",array('LBL_TOOLTIP_MANAGEMENT'));
		if($adb->num_rows($sql_result) > 0) {
			$menu_array['Tooltip']['location'] = $adb->query_result($sql_result, 0, 'linkto').'&formodule='.$module;
			$menu_array['Tooltip']['image_src'] = resourcever($adb->query_result($sql_result, 0, 'iconpath'));
			$menu_array['Tooltip']['desc'] = getTranslatedString($adb->query_result($sql_result, 0, 'description'),'Tooltip');
			$menu_array['Tooltip']['label'] = getTranslatedString($adb->query_result($sql_result, 0, 'name'),'Tooltip');
		}
	}
	
	$picklistModules = getPickListModules();
	if (isset($picklistModules[$module])) {
		$sql_result = $adb->pquery("select * from {$table_prefix}_settings_field where name = ? and active=0",array('LBL_PICKLIST_EDITOR'));
		if($adb->num_rows($sql_result) > 0) {
			$menu_array['PickList']['location'] = $adb->query_result($sql_result, 0, 'linkto').'&module_manager=yes&moduleName='.$module;
			$menu_array['PickList']['image_src'] = resourcever($adb->query_result($sql_result, 0, 'iconpath'));
			$menu_array['PickList']['desc'] = getTranslatedString($adb->query_result($sql_result, 0, 'description'),'Settings');
			$menu_array['PickList']['label'] = getTranslatedString($adb->query_result($sql_result, 0, 'name'),'Settings');
		}
	}
	require_once('modules/Picklistmulti/Picklistmulti_class.php');
	$pick_obj = new Picklistmulti();
	if (isset($pick_obj->module_list[$module])) {
		$sql_result = $adb->pquery("select * from {$table_prefix}_settings_field where name = ? and active=0",array('LBL_PICKLIST_EDITOR_MULTI'));
		if($adb->num_rows($sql_result) > 0) {
			$menu_array['Picklistmulti']['location'] = $adb->query_result($sql_result, 0, 'linkto').'&module_manager=yes&moduleName='.$module;
			$menu_array['Picklistmulti']['image_src'] = resourcever($adb->query_result($sql_result, 0, 'iconpath'));
			$menu_array['Picklistmulti']['desc'] = getTranslatedString($adb->query_result($sql_result, 0, 'description'),'Settings');
			$menu_array['Picklistmulti']['label'] = getTranslatedString($adb->query_result($sql_result, 0, 'name'),'Settings');
		}
	}
	require_once('modules/SDK/examples/uitypePicklist/300Utils.php');
	$plist = getAllPicklists();
	$picklistModules = array_keys($plist);
	if (in_array($module,$picklistModules)) {
		$sql_result = $adb->pquery("select * from {$table_prefix}_settings_field where name = ? and active=0",array('LBL_EDIT_LINKED_PICKLIST'));
		if($adb->num_rows($sql_result) > 0) {
			$menu_array['LinkedPicklist']['location'] = $adb->query_result($sql_result, 0, 'linkto').'&module_manager=yes&moduleName='.$module;
			$menu_array['LinkedPicklist']['image_src'] = resourcever($adb->query_result($sql_result, 0, 'iconpath'));
			$menu_array['LinkedPicklist']['desc'] = getTranslatedString($adb->query_result($sql_result, 0, 'description'),'Settings');
			$menu_array['LinkedPicklist']['label'] = getTranslatedString($adb->query_result($sql_result, 0, 'name'),'Settings');
		}
	}

	if(VTWorkflowUtils::checkModuleWorkflow($module)){
		$sql_result = $adb->pquery("SELECT * FROM {$table_prefix}_settings_field WHERE name = ? AND active=0",array('LBL_WORKFLOW_LIST'));
			if($adb->num_rows($sql_result) > 0) {
				$menu_array['Workflow']['location'] = $adb->query_result($sql_result, 0, 'linkto').'&list_module='.$module;
				$menu_array['Workflow']['image_src'] = resourcever($adb->query_result($sql_result, 0, 'iconpath'));
				$menu_array['Workflow']['desc'] = getTranslatedString($adb->query_result($sql_result, 0, 'description'),'com_workflow');//crmv@207901
				$menu_array['Workflow']['label'] = getTranslatedString($adb->query_result($sql_result, 0, 'name'),'com_workflow');//crmv@207901
			}
	}
	//add blanks for 3-column layout
	$count = count($menu_array)%3;
	if($count>0) {
		for($i=0;$i<3-$count;$i++) {
			$menu_array[] = array();
		}
	}

	$smarty->assign('MODULE',$module);
	$smarty->assign('MODULE_LBL',getTranslatedString($module,$module));
	$smarty->assign('MENU_ARRAY', $menu_array);

	$smarty->display(vtlib_getModuleTemplate('VteCore','Settings.tpl'));
}
?>
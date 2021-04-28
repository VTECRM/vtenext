<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@3085m crmv@3086m */


global $currentModule, $mod_strings, $app_strings, $theme, $table_prefix;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$view = $_REQUEST['view'];
if ($view == 'summary') {
	$summary = true;
} else {
	$summary = false;
}

// Calendar fix
if ($currentModule == 'Calendar') {
	$actvity_type = getSingleFieldValue($table_prefix.'_activity', 'activitytype', 'activityid', $_REQUEST['record']);
	if ($actvity_type == 'Task') {
		$tab_type = 'Calendar';
	} else {
		$tab_type = 'Events';
	}
	$focus = CRMEntity::getInstance('Activity');
	$focus->retrieve_entity_info($_REQUEST['record'],$tab_type);
	$focus->id = $_REQUEST['record'];
	$blocks = getBlocks($tab_type,'detail_view',$view,$focus->column_fields);
} else {
	$focus = CRMEntity::getInstance($currentModule);
	$focus->retrieve_entity_info($_REQUEST['record'],$currentModule);
	$focus->id = $_REQUEST['record'];
	$blocks = getBlocks($currentModule,'detail_view',$view,$focus->column_fields);
}

$smarty = new VteSmarty();
$smarty->assign("BLOCKS", $blocks);
$smarty->assign("SUMMARY", $summary);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("MODULE", $currentModule);
$smarty->assign("ID", $focus->id);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("EDIT_PERMISSION", isPermitted($currentModule,'EditView',$_REQUEST['record']));
$smarty->assign('DETAIL_PERMISSION', isPermitted($currentModule,'DetailView',$_REQUEST['record']));	//crmv@77702

// crmv@140129
$panelid = intval($_REQUEST['panelid']) ?: getCurrentPanelId($currentModule);
$smarty->assign("PANELID", $panelid);
// crmv@140129e

// extraParams

//crmv@57221
$CU = CRMVUtils::getInstance();
$show_details_button = $CU->getConfigurationLayout('enable_switch_detail_view');
//crmv@57221e
if (isset($_REQUEST['show_details_button'])) {
	$show_details_button = true;
	($_REQUEST['show_details_button'] == 'true' || $_REQUEST['show_details_button'] === true || $_REQUEST['show_details_button'] == '1') ? $show_details_button = true : $show_details_button = false;
}

$show_related_buttons = false;
if (isset($_REQUEST['show_related_buttons'])) {
	($_REQUEST['show_related_buttons'] == 'true' || $_REQUEST['show_related_buttons'] === true) ? $show_related_buttons = true : $show_related_buttons = false;
}
//crmv@OPER6288
$show_kanban_buttons = false;
if (isset($_REQUEST['show_kanban_buttons'])) {
	($_REQUEST['show_kanban_buttons'] == 'true' || $_REQUEST['show_kanban_buttons'] === true) ? $show_kanban_buttons = true : $show_kanban_buttons = false;
}
//crmv@OPER6288e
if (isset($_REQUEST['DETAILVIEW_AJAX_EDIT'])) {
	($_REQUEST['DETAILVIEW_AJAX_EDIT'] == 'true' || $_REQUEST['DETAILVIEW_AJAX_EDIT'] === true) ? $dtlv_ajax_edit = true : $dtlv_ajax_edit = false;
} else {
	$dtlv_ajax_edit = PerformancePrefs::getBoolean('DETAILVIEW_AJAX_EDIT', true);
}
$destination = $real_destination = 'DetailViewBlocks';
if (!empty($_REQUEST['relation_id'])) {
	$destination = $_REQUEST['destination'];
	$real_destination = $_REQUEST['destination'].'_Summary';
	
	if ($show_related_buttons) {
		$recordNavigationInfo = RelatedListViewSession::getListViewNavigation($_REQUEST['relation_id'],$focus->id);
		VT_detailViewNavigation($smarty,$recordNavigationInfo,$focus->id);
		$smarty->assign("RELATION_ID", $_REQUEST['relation_id']);
	}
}
$extraParams = array(
	'show_details_button'=>$show_details_button,
	'show_related_buttons'=>$show_related_buttons,
	'show_kanban_buttons'=>$show_kanban_buttons,	//crmv@OPER6288
	'DETAILVIEW_AJAX_EDIT'=>$dtlv_ajax_edit,
	'destination'=>$destination,
	'real_destination'=>$real_destination,
	'relation_id'=>$_REQUEST['relation_id'],
);
$extraParamsJs = '{';
foreach ($extraParams as $k => $v) {
	if ($v === true) $v = 'true';
	elseif ($v === false) $v = 'false';
	$extraParamsJs .= "'$k':'$v',";
}
$extraParamsJs .= '}';

$smarty->assign("SHOW_DETAILS_BUTTON", $show_details_button);
$smarty->assign("SHOW_RELATED_BUTTONS", $show_related_buttons);
$smarty->assign("SHOW_KANBAN_BUTTONS", $show_kanban_buttons);	//crmv@OPER6288
$smarty->assign("DESTINATION", $destination);
$smarty->assign("REAL_DESTINATION", $real_destination);
$smarty->assign("EXTRAPARAMS", $extraParams);
$smarty->assign("EXTRAPARAMSJS", $extraParamsJs);
$smarty->assign('DETAILVIEW_AJAX_EDIT', $dtlv_ajax_edit);

$sdk_custom_file = 'DetailViewCustomisations';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
@include("modules/$currentModule/$sdk_custom_file.php");

$smarty->display("DetailViewBlocks.tpl");
?>
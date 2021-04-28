<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("include/utils/CommonUtils.php");
require_once("include/FormValidationUtil.php");

global $app_strings, $mod_strings, $current_user, $currentModule, $theme;
$module = $currentModule;

//crmv@16265 : QuickCreatePopup
if ($_REQUEST['mode'] == 'Save') {

	if ($module == 'Calendar') {
		$focus->column_fields["activitytype"] = 'Task';
		$tab_type = 'Calendar';
	}
	if ($module == 'Events')
		$module = 'Calendar';

	$focus = CRMEntity::getInstance($module);
	$focus->mode = '';
	foreach($focus->column_fields as $fieldname => $val)
	{
		if(isset($_REQUEST[$fieldname]))
		{
			if(is_array($_REQUEST[$fieldname]))
				$value = $_REQUEST[$fieldname];
			else
				$value = trim($_REQUEST[$fieldname]);
			$focus->column_fields[$fieldname] = $value;
		}
	}
	if ($tab_type == 'Calendar') {
		$focus->column_fields["activitytype"] = 'Task';
	}
	if($_REQUEST['assigntype'] == 'U')  {
		$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
	} elseif($_REQUEST['assigntype'] == 'T') {
		$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
	}
	// crmv@100924
	if (empty($focus->column_fields['assigned_user_id'])) {
		$focus->column_fields['assigned_user_id'] = $current_user->id;
	}
	// crmv@100924e
	$focus->save($module);
	if ($_REQUEST['quickcreatepopop'] == 'true') {
		echo Zend_Json::encode(array('record'=>$focus->id,'module'=>$module));
		die;
	} else {
		// crmv@30014
		$return_action = isset($_REQUEST['return_action']) ? $_REQUEST['return_action'] : 'DetailView';
		$return_module = isset($_REQUEST['return_module']) ? $_REQUEST['return_module'] : $module;
		$return_id = isset($_REQUEST['return_id']) ? $_REQUEST['return_id'] : $focus->id;
		header("Location: index.php?action=$return_action&module=$return_module&record=$return_id");
		// crmv@30014e
	}
}
//crmv@16265e

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();

//crmv@26631	//crmv@29506	//crmv@45770
$qcreate_array = QuickCreate("$module");
$focus = CRMEntity::getInstance($module);
$col_fields = $focus->getQuickCreateDefault($module, $qcreate_array, $_REQUEST['search_field'], $_REQUEST['search_text']);
if(isset($col_fields['description']) && $col_fields['description'] != ''){
	$col_fields['description'] = preg_replace('/\s{2,}/',' ',strip_tags($col_fields['description'],'<br /><br/><br>'));
	$col_fields['description'] = preg_replace('/<br\\s*?\/??>/i', "\n", $col_fields['description']);
}
setObjectValuesFromRequest($focus);
foreach($focus->column_fields as $col => $val) {
	if (!empty($val) && empty($col_fields[$col])) {
		$col_fields[$col] = $val;
	}
}
if (!empty($col_fields)) {
	$qcreate_array = QuickCreate("$module", $col_fields);
}
//crmv@26631e	//crmv@29506e	//crmv@45770e
$validationData = $qcreate_array['data'];
$data = split_validationdataArray($validationData);
$smarty->assign("QUICKCREATE", $qcreate_array['form']);
$smarty->assign("THEME",$theme);
$smarty->assign("APP",$app_strings);
$smarty->assign("MOD",$mod_strings);
$smarty->assign("THEME",$theme);
$smarty->assign("IMAGE_PATH",$image_path);
//crmv@fix Calendar
$activity_mode = vtlib_purify($_REQUEST['activity_mode']);
$smarty->assign("ACTIVITY_MODE", $activity_mode);

if($module == 'Calendar'){
	$smarty->assign("QCMODULE", getTranslatedString(vtlib_purify($_REQUEST['activity_mode']), $module));
}
elseif($module == 'Events'){
	$smarty->assign("QCMODULE", getTranslatedString("SINGLE_".$module, 'Calendar'));
}
//crmv@fix Calendar end
elseif($module == "HelpDesk")
	$smarty->assign("QCMODULE", getTranslatedString('Ticket', 'HelpDesk'));
else
	$smarty->assign("QCMODULE",getTranslatedString("SINGLE_".$currentModule, $currentModule));
$smarty->assign("USERID",$current_user->id);
$smarty->assign("VALIDATION_DATA_FIELDNAME",$data['fieldname']);
$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",$data['datatype']);
$smarty->assign("VALIDATION_DATA_FIELDLABEL",$data['fieldlabel']);
//crmv@100903 crmv@112297
$otherInfo = array();
$validationData = getDBValidationData($focus->tab_name,getTabid($module),$otherInfo);
$validationArray = split_validationdataArray($validationData, $otherInfo);
$smarty->assign("VALIDATION_DATA_FIELDUITYPE",$validationArray['fielduitype']);
$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",$validationArray['fieldwstype']);
//crmv@100903e crmv@112297e
$smarty->assign("MODULE", $currentModule);
$smarty->assign("CATEGORY",$category);
//crmv@merge check
$smarty->assign("MERGE_USER_FIELDS",implode(',',get_merge_user_fields($currentModule))); //crmv_utils
//crmv@merge check end
//crmv@16265 : QuickCreatePopup
if ($_REQUEST['quickcreatepopup'] == 'true')
	$smarty->assign("QUICKCREATEPOPUP",'true');
//crmv@16265e

// crmv@30014
if ($module == 'Charts' && vtlib_isModuleActive('Charts')) {
	$reportid = intval($_REQUEST['reportid']);
	$chartInst = CRMEntity::getInstance('Charts');

	$report_owner = $current_user->id;
	if ($reportid > 0) {
		require_once('modules/Reports/Reports.php');
		$reportInst = new Reports($reportid);
		$report_owner = $reportInst->owner;
	}

	$smarty->assign("REPORTID", $reportid);
	$smarty->assign("REPORT_OWNER", $report_owner);
	$smarty->assign("CHART_TYPES", $chartInst->getChartTypes());
	$smarty->display("modules/Charts/QuickCreate.tpl");
} else {
	$smarty->display("QuickCreate.tpl");
}
// crmv@30014e
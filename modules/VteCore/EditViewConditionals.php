<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@112297 crmv@113771 crmv@115268 crmv@203886 */

global $currentModule, $app_strings, $mod_strings, $theme;

$crmvUtils = CRMVUtils::getInstance();

$_REQUEST = Zend_Json::decode($_REQUEST['form']);
$_REQUEST = array_map('to_html', $_REQUEST);
$_REQUEST['action'] = 'EditView'; // crmv@190504
$mode = $_REQUEST['mode'];
$record = $_REQUEST['record'];

$tabid = getTabid($currentModule);
$focus = CRMEntity::getInstance($currentModule);

if ($mode) $focus->mode = $mode;
if ($record) {
	$focus->id = $record;
	$focus->retrieve_entity_info($record, $currentModule);
	$focus->column_fields['record_id'] = $record;
	$focus->column_fields['record_module'] = $currentModule;
}

setObjectValuesFromRequest($focus);

require_once('modules/SDK/src/29/29Utils.php');
$uitypeFileUtils = UitypeFileUtils::getInstance();
$uitypeFileUtils->uploadTempFiles();

// crmv@64542
if (isInventoryModule($currentModule)) {
	$focus->column_fields['currency_id'] = $_REQUEST['inventory_currency'];
	$cur_sym_rate = getCurrencySymbolandCRate($_REQUEST['inventory_currency']);
	$focus->column_fields['conversion_rate'] = $cur_sym_rate['rate'];
}
// crmv@64542e

if($_REQUEST['assigntype'] == 'U') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

// get fields informations
$fieldRows = array();
//$fieldWS = array();
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ?", array($tabid));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result)) {
		$fieldRows[$row['fieldname']] = $row;
		//$fieldWS[$row['fieldname']] = WebserviceField::fromArray($adb,$row);
	}
}
// format values
foreach($focus->column_fields as $fieldname => &$value) {
	$value = $crmvUtils->formatValue($value, $fieldRows[$fieldname], $_REQUEST);
}

$smarty = new VteSmarty();
$smarty->assign('MODE',$focus->mode);
$smarty->assign('MODULE',$currentModule);
$smarty->assign('APP',$app_strings);
$smarty->assign('MOD',$mod_strings);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('ID', $focus->id);
$smarty->assign("CONDITIONAL_LOAD", true);
// Gather the help information associated with fields
$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));
if (!empty($record) && $currentModule == 'HelpDesk') {
	//Added to display the ticket comments information
	$smarty->assign("COMMENT_BLOCK",$focus->getCommentInformation($record));
}

// crmv@133606
// Module Sequence Numbering
$mod_seq_field = getModuleSequenceField($currentModule);
if($focus->mode != 'edit' && $mod_seq_field != null) {
	$autostr = getTranslatedString('MSG_AUTO_GEN_ON_SAVE');
	//crmv@154715
	if ($focus->isBUMCInstalled($currentModule)) {
		$smarty->assign("MOD_SEQ_ID",$autostr);
	} else {
	//crmv@154715e
		$mod_seq_string = $adb->pquery("SELECT prefix, cur_id from {$table_prefix}_modentity_num where semodule = ? and active=1",array($currentModule));
		$mod_seq_prefix = $adb->query_result($mod_seq_string,0,'prefix');
		$mod_seq_no = $adb->query_result($mod_seq_string,0,'cur_id');
		if ($adb->num_rows($mod_seq_string) == 0 || $focus->checkModuleSeqNumber($focus->table_name, $mod_seq_field['column'], $mod_seq_prefix.$mod_seq_no)) {
			
		} else {
			$smarty->assign("MOD_SEQ_ID",$autostr);
		}
	}	//crmv@154715
} else {
	$smarty->assign("MOD_SEQ_ID", $focus->column_fields[$mod_seq_field['name']]);
}
// crmv@133606e

//crmv@57221
$CU = CRMVUtils::getInstance();
$smarty->assign("OLD_STYLE", $CU->getConfigurationLayout('old_style'));
//crmv@57221e

$disp_view = getView($focus->mode);
$blocks_info = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields, '', $blockVisibility);
$blocks = array();
if (!empty($blocks_info)) {
	foreach($blocks_info as $header => $data) {
		$smarty->assign('data',$data['fields']);
		$smarty->assign('header',$data['label']);
		$blocks[$data['blockid']] = $smarty->fetch('DisplayFields.tpl');
	}
}

// Field Validation Information
$validationUitypes = array();
$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus);
$validationArray = getValidationdataArray($validationData, $otherInfo);

//crmv@134058
$conditionalsFocus = CRMEntity::getInstance('Conditionals');
$conditionalsFocus->existsConditionalPermissions($currentModule, $focus, $condFields);
//crmv@134058e

$return = array(
	'BLOCKS' => $blocks,
	'BLOCKVISIBILITY' => $blockVisibility,
	'CONDITIONAL_FIELDS' => $condFields,	//crmv@134058
	'VALIDATION_DATA_FIELDNAME' => $validationArray['fieldname'],
	'VALIDATION_DATA_FIELDDATATYPE' => $validationArray['datatype'],
	'VALIDATION_DATA_FIELDLABEL' => $validationArray['fieldlabel'],
	'VALIDATION_DATA_FIELDUITYPE' => $validationArray['fielduitype'],
	'VALIDATION_DATA_FIELDWSTYPE' => $validationArray['fieldwstype'],
);

echo Zend_Json::encode($return);
exit;
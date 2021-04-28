<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/VteCore/EditView.php';	//crmv@30447

// crmv@64542

global $currentModule;

$templates = array(
	'inventory' => array(
		'create' => 'Inventory/InventoryEditView.tpl',
		'edit' => 'Inventory/InventoryEditView.tpl',
	),
	'standard' => array(
		'create' => 'salesEditView.tpl',
		'edit' => 'salesEditView.tpl',
	)
);

$templateMode = isInventoryModule($currentModule) ? 'inventory' : 'standard';

//crmv@99316
if ($focus->mode == 'edit')
	$template = $templates[$templateMode]['edit'];
else
	$template = $templates[$templateMode]['create'];
	
$smarty->assign('TEMPLATE', $template);

// crmv@105933 crmv@181170
// remove some tools for the module
if ($smarty && is_array($smarty->getTemplateVars('CHECK'))) {
	$tool_buttons = $smarty->getTemplateVars('CHECK');
	unset($tool_buttons['EditView']);
	unset($tool_buttons['Import']);
	unset($tool_buttons['Merge']);
	unset($tool_buttons['DuplicatesHandling']);
	$smarty->assign('CHECK', $tool_buttons);
}
// crmv@105933e crmv@181170e

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
$processDynaFormObj = ProcessDynaForm::getInstance();
$enable = $processDynaFormObj->existsConditionalPermissions($focus);
$smarty->assign('ENABLE_DFCONDITIONALS', $enable);
if ($enable) {
	$dynaFormFields = $processDynaFormObj->getFields($focus);
	$smarty->assign('DFFIELDS', Zend_Json::encode($dynaFormFields));
}

//crmv@93990
if($_REQUEST['ajxaction'] == 'DYNAFORMPOPUP') {
	
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	$processesid = $PMUtils->getProcessRelatedTo($focus->column_fields['related_to'],'processesid');
	if ($focus->id == $processesid) {
		$related_to_popup_opt = $PMUtils->getProcessRelatedTo($focus->column_fields['related_to'],'related_to_popup_opt');
		if ($related_to_popup_opt == 'once') {
			$dynaformmetaid = $PMUtils->getProcessRelatedTo($focus->column_fields['related_to'],'dynaformmetaid');
			$adb->pquery("UPDATE {$table_prefix}_process_dynaform SET done = ? WHERE running_process = ? AND metaid = ?", array(2,$focus->column_fields['running_process'],$dynaformmetaid));
		}
	}
	
	$smarty->assign('PROCESS_NAME', $focus->column_fields['process_name']);
	$smarty->assign('REQUESTED_ACTION', $focus->column_fields['requested_action']);
	
	//crmv@99316 crmv@110419 crmv@135502 crmv@141827
	$dyna_blocks_empty = true;
	$blockstatus = array();
	$blockids = array();
	$blocks = $smarty->getTemplateVars('BLOCKS');
	foreach($blocks as $header => $block) {
		$blockstatus[$block['blockid']] = 0;
		$blockids[$header] = $block['blockid'];
	}
	$dyna_blocks = $processDynaFormObj->getCurrentDynaForm($focus);
	foreach($dyna_blocks as $dyna_block) {
		$label = getTranslatedString($dyna_block['label']);
		if (isset($blockVisibility[$dyna_block['label']])) {
			$blockstatus[$blockids[$label]] = $blockVisibility[$label];
		} else {
			$blockstatus[$blockids[$label]] = 1;
		}
		unset($blockstatus[$label]);
		if (!empty($dyna_block['fields'])) $dyna_blocks_empty = false;
	}
	$smarty->assign('DYNA_BLOCKS_EMPTY', $dyna_blocks_empty);
	//crmv@99316e crmv@110419e crmv@135502e crmv@141827e
	$smarty->assign('BLOCKVISIBILITY', $blockstatus);
}
//crmv@93990e

$smarty->display('modules/Processes/EditView.tpl');
//crmv@99316e
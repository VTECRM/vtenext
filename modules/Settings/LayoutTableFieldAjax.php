<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@106857 crmv@146434 */

global $adb, $table_prefix;
$MLUtils = ModLightUtils::getInstance();

$action = $_REQUEST['subaction'];

// check
if ($action == 'addfield' || $action == 'editfield') {
	$properties = Zend_Json::decode($_REQUEST['properties']);
	if (strlen($properties['label']) > 50) die('LENGTH_ERROR');
	
	$result = $adb->pquery("select {$table_prefix}_tab.tabid, name from {$table_prefix}_blocks
	inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_blocks.tabid
	where blockid = ?", array($_REQUEST['blockno']));
	if ($result && $adb->num_rows($result) > 0) {
		$reltabid = $adb->query_result($result,0,'tabid');
		$relmodule = $adb->query_result($result,0,'name');
	}
	
	$query = "select * from {$table_prefix}_field where tabid in (".generateQuestionMarks($dup_check_tab_id).") and fieldlabel = ?";
	($relmodule == 'Calendar') ? $params = array('9', '16') : $params = array($reltabid);
	$params[] = $properties['label']; // crmv@190916
	if ($action == 'editfield') {
		$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where fieldid = ?", array($_REQUEST['editfieldno']));
		$curr_fieldlabel = $adb->query_result($result,0,'fieldlabel');
		$query .= " and fieldlabel <> ?";
		$params[] = $curr_fieldlabel;
	}
	// crmv@190916 removed
	$checkresult = $adb->pquery($query, $params);
	if ($adb->num_rows($checkresult) > 0) die('DUPLICATE');
}

if ($action == 'addfield') {
	$MLUtils->addTableField($_REQUEST['blockno'], $_REQUEST['addfieldno'], $properties);
} elseif ($action == 'editfield') {
	$MLUtils->editTableField($_REQUEST['blockno'], $_REQUEST['editfieldno'], $properties);
} elseif ($action == 'deletefield') {
	$MLUtils->deleteTableField($_REQUEST['editfieldno']);
}

$blockInstance = Vtecrm_Block::getInstance($_REQUEST['blockno']);
$_REQUEST['sub_mode'] = '';
$_REQUEST['formodule'] = $blockInstance->module->name;
$_REQUEST['ajax'] = 'true';
include('modules/Settings/LayoutBlockList.php');
exit;
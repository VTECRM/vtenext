<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//Redirecting Header for single page layout

global $mod_strings, $app_strings, $adb, $theme;

//crmv@203484
$VTEP = VTEProperties::getInstance();
$singlePaneVew = $VTEP->getProperty('layout.singlePaneVew');
//crmv@203484e

$currentModule = vtlib_purify($_REQUEST['module']);
$record = vtlib_purify($_REQUEST['record']);
$category = getParentTab();

if ($singlePaneVew == true && $_REQUEST['action'] == 'CallRelatedList') { //crmv@203484
	header("Location:index.php?action=DetailView&module=$currentModule&record=$record&parenttab=$category");
} else {
	$focus = CRMEntity::getInstance($currentModule);
	if (isset($_REQUEST['record']) && $_REQUEST['record'] != '') {
		$focus->retrieve_entity_info($record, $currentModule);
		$focus->id = $record;
		$focus->name = $focus->column_fields['campaignname'];
		$log->debug("id is " . $focus->id);
		$log->debug("name is " . $focus->name);
	}

	$theme_path = "themes/" . $theme . "/";
	$image_path = $theme_path . "images/";

	$smarty = new VteSmarty();

	if (isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
		$focus->id = "";
	}
	if (isset($_REQUEST['mode']) && $_REQUEST['mode'] != ' ') {
		$smarty->assign("OP_MODE", vtlib_purify($_REQUEST['mode']));
	}
	if (isset($focus->name)) {
		$smarty->assign("NAME", $focus->name);
	}
	$relatedArray = getRelatedLists($currentModule, $focus);
	$focus->filterStatisticRelatedLists('remove', $relatedArray); //crmv@22700

	// vtlib customization: Related module could be disabled, check it
	checkIfRelatedModuleDisabled($relatedArray);

	$smarty->assign("RELATEDLISTS", $relatedArray);

	require_once('include/ListView/RelatedListViewSession.php');

	if (!empty($_REQUEST['selected_header']) && !empty($_REQUEST['relation_id'])) {
		$relationId = vtlib_purify($_REQUEST['relation_id']);
		RelatedListViewSession::addRelatedModuleToSession($relationId, vtlib_purify($_REQUEST['selected_header']));
	}
	$openRelatedModules = RelatedListViewSession::getRelatedModulesFromSession();
	$smarty->assign("SELECTEDHEADERS", $openRelatedModules);

	require_once('modules/CustomView/CustomView.php');

	// Module Sequence Numbering
	$modSeqField = getModuleSequenceField($currentModule);
	if ($modSeqField != null) {
		$modSeqId = $focus->column_fields[$modSeqField['name']];
	} else {
		$modSeqId = $focus->id;
	}
	$smarty->assign('MOD_SEQ_ID', $modSeqId);
	// END

	$smarty->assign("ID", $focus->id);
	$smarty->assign("MODULE", $currentModule);
	$smarty->assign("MOD", $mod_strings);
	$smarty->assign("SINGLE_MOD", $app_strings['Campaign']);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("CATEGORY", $category);
	$smarty->assign("TODO_PERMISSION", CheckFieldPermission('parent_id', 'Calendar'));
	$smarty->assign("EVENT_PERMISSION", CheckFieldPermission('parent_id', 'Events'));
	$smarty->assign("UPDATEINFO", updateInfo($focus->id));
	$smarty->assign("THEME", $theme);
	$smarty->assign("IMAGE_PATH", $image_path);

	$check_button = Button_Check($module);
	$smarty->assign("CHECK", $check_button);

	if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '') {
		$smarty->display("RelatedListContents.tpl");
	} else {
		$smarty->display("RelatedLists.tpl");
	}
}

function checkIfRelatedModuleDisabled(&$relatedArray)
{
	if (isset($relatedArray)) {
		foreach ($relatedArray as $modKey => $modVal) {
			if ($modKey == "Contacts" || $modKey == "Leads" || $modKey == "Accounts") {
				$relChecked = $_REQUEST[$modKey . '_all'];
				$relCheckSplit = explode(";", $relChecked);
				if (is_array($modVal)) {
					$modVal["checked"] = array();
					if (isset($modVal['entries'])) {
						foreach ($modVal['entries'] as $key => $val) {
							if (in_array($key, $relCheckSplit)) {
								$relatedArray[$modKey]["checked"][$key] = 'checked';
							}
							else {
								$relatedArray[$modKey]["checked"][$key] = '';
							}
						}
					}
				}
			}
		}
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@163191

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $current_user, $currentModule, $current_language, $default_language;

$smarty = new VteSmarty();
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
$smarty->assign("MODULE", $currentModule);

$returnModule = $_REQUEST["return_module"];
$returnId = intval($_REQUEST["return_id"]);

$entityName = getEntityName($returnModule, $returnId, true);

$tabid = getTabid($returnModule);

$fieldinfo = $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE tabid = ? AND uitype = 4", array($tabid));

if ($fieldinfo && $adb->num_rows($fieldinfo)) {
	$row = $adb->fetchByAssoc($fieldinfo);
	$table = $row['tablename'];
	$field = $row['fieldname'];
	if (!empty($table) && !empty($field)) {
		$returnFocus = CRMEntity::getInstance($returnModule);
		$returnFocus->id = $returnId;
		$s = $returnFocus->retrieve_entity_info($returnId, $returnModule, false);
		if (empty($s)) {
			$entityNo = $returnFocus->column_fields[$field];
			$entityName = $entityName . ' - ' . $entityNo;
		}
	}
}

$smarty->assign("DEFAULT_TITLE", $entityName);

$folders = array();

$foldersQuery = "SELECT foldername, folderid FROM {$table_prefix}_crmentityfolder WHERE tabid = ? ORDER BY foldername";
$foldersRes = $adb->pquery($foldersQuery, array(getTabId('Documents')));

if ($foldersRes && $adb->num_rows($foldersRes)) {
	while ($row = $adb->fetchByAssoc($foldersRes, -1, false)) {
		$folders[] = array('id' => $row['folderid'], 'name' => $row['foldername']);
	}
}

$smarty->assign('FOLDERS', $folders);

$smarty->assign('RETURN_MODULE', $returnModule);
$smarty->assign('RETURN_ID', $returnId);

$html = $smarty->fetch('modules/PDFMaker/DocSelect.tpl');

$json = array('success' => true, 'title' => getTranslatedString('LBL_SAVEASDOC', 'PDFMaker'), 'html' => $html);

echo Zend_Json::encode($json);
exit();
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@95157 */

global $adb, $table_prefix;
global $app_strings, $mod_strings, $current_language, $currentModule, $theme;

require_once('modules/Documents/storage/StorageBackendUtils.php');

$crmid = intval($_REQUEST['record']);

$smarty = new VteSmarty();

$action = $_REQUEST['ajxaction'];


$SBU = StorageBackendUtils::getInstance();
$check = $SBU->checkMetadata($currentModule, $crmid);

if (!$check) {
	$smarty->assign('ERROR', 'Metadata not supported for this backend');
} else {

	if ($action == 'save') {
		$props = Zend_Json::decode($_REQUEST['properties']);
		if ($props) {
			$ok = $SBU->updateMetadata($currentModule, $crmid, $props);
			if (!$ok) {
				$smarty->assign('ERROR', 'Unable to save metadata');
			}
		}
	}

	$metadata = $SBU->readMetadata($currentModule, $crmid);
	$smarty->assign('METADATA', $metadata);
	$smarty->assign('META_EDITABLE', true);
}

$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('ID', $crmid);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");

// display
$smarty->display('modules/Documents/ShowMetadata.tpl');
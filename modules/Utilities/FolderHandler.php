<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@30967 */

global $adb, $table_prefix;

$action = $_REQUEST['subaction'];
$formodule = vtlib_purify($_REQUEST['formodule']);


if (!vtlib_isModuleActive($formodule) || !isPermitted($formodule, 'EditView')) {
	die('ERROR::'.getTranslatedString('LBL_PERMISSION'));
}

if ($action == 'add') {
	$foldername = trim(vtlib_purify($_REQUEST['foldername']));
	$folderdesc = trim(vtlib_purify($_REQUEST['folderdesc']));

	if (empty($foldername)) {
		die('ERROR::'.getTranslatedString('FOLDERNAME_CANNOT_BE_EMPTY'));
	}

	// check if it exists
	$folderinfo = getEntityFoldersByName($foldername, $formodule);

	if (!empty($folderinfo)) {
		die('ERROR::'.getTranslatedString('FOLDER_NAME_ALREADY_EXISTS'));
	}

	$state = null;
	if ($formodule == 'Reports') $state = 'CUSTOMIZED';
	addEntityFolder($formodule, $foldername, $folderdesc, $current_user->id, $state);
} elseif ($action == 'del') {

	if (!vtlib_isModuleActive($formodule) || !isPermitted($formodule, 'Delete')) {
		die('ERROR::'.getTranslatedString('LBL_PERMISSION'));
	}

	$folderids = array_map(intval, explode(',', $_REQUEST['folderids']));

	// crmv@38798 - check if empty
	$cls = CRMEntity::getInstance($formodule);
	$hasRecords = false;
	foreach ($folderids as $folderid) {
		$count = $cls->countAllRecordsInFolder($formodule, $folderid);
		if ($count > 0) {
			$hasRecords = true;
			break;
		}
	}
	if ($hasRecords) {
		die('ERROR::'.getTranslatedString('LBL_FOLDER_HAS_RECORDS', 'Reports'));
	}
	// crmv@38798e

	foreach ($folderids as $folderid) {
		deleteEntityFolder($folderid);
	}

}

echo "SUCCESS";
?>
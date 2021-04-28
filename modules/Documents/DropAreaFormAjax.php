<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@176893

require_once('modules/Documents/DropArea.php');

global $current_user;

$mode = 'ajax';
$action = $_REQUEST['subaction'];
$json = null;

$DA = DropArea::getInstance();

try {
	if ($action == 'get_folders') {
		$folders = $DA->getFolders();
		$json = array('success' => true, 'data' => $folders);
	} elseif ($action == 'add_folder') {
		$folderName = vtlib_purify($_REQUEST['new_folder_name']);
		$folderDesc = vtlib_purify($_REQUEST['new_folder_desc']);
		
		$folderId = $DA->addNewFolder($folderName, $folderDesc);
		$json = array('success' => ($folderId !== 0), 'folderid' => $folderId, 'foldername' => ($folderId !== 0 ? $folderName : null));
	} else {
		$json = array('success' => false, 'error' => 'Unknown action');
	}
} catch (Exception $e) {
	$json = array('success' => false, 'error' => $e->getMessage());
}

echo Zend_Json::encode($json);
exit();
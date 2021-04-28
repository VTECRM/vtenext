<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;
global $login, $userId;

if (!$login || !$userId) {
	echo 'Login Failed';
} else {

	function getRealFolder($folder, $focus) {
		global $serverFolders;

		$specialFolders = array(
			"[:INBOX:]" => 'INBOX', 
			"[:DRAFTS:]" => 'Drafts', 
			"[:SENT:]" => 'Sent', 
			"[:SPAM:]" => 'Spam', 
			"[:TRASH:]" => 'Trash',
		);

		if (preg_match('/^\[:[A-Z]+:\]$/', $folder)) {
			// special folder
			if (!array_key_exists($folder, $specialFolders)) return false;
			$foldType = $specialFolders[$folder];
			$account = $focus->column_fields['account'];
			$folder = $serverFolders[$account][$foldType];
			if (empty($folder)) return false;
		}
		return $folder;
	}
	
	global $currentModule;

	$serverFolders = null;
	
	$records = array_filter(array_map('intval', Zend_Json::decode($_REQUEST['records']) ?: array()));
	$destfolder = $_REQUEST['dest_folder'];
		
	// destfolder can be the exact name of the destination folder or have a special value:
	// "[:INBOX:]", "[:DRAFTS:]", "[:SENT:]", "[:SPAM:]", "[:TRASH:]"
		
	if (count($records) == 0) die(Zend_Json::encode(array('success'=> false, 'error' => "No records specified")));
	if (empty($destfolder)) die(Zend_Json::encode(array('success'=> false, 'error' => "No destination folder specified")));
		
		
	$currentModule = 'Messages';
	$focus = CRMEntity::getInstance($currentModule);
		
	$serverFolders = $focus->getAllSpecialFolders();
		
	foreach ($records as $messageid) {
		$focus->id = $messageid;
		$r = $focus->retrieve_entity_info($messageid, $currentModule, false);
			
		if ($r == 'LBL_RECORD_DELETE') {
			die(Zend_Json::encode(array('success'=> false, 'error' => "The record #$messageid has been deleted")));
		} elseif ($r == 'LBL_RECORD_NOT_FOUND') {
			die(Zend_Json::encode(array('success'=> false, 'error' => "The record #$messageid was not found")));
		}
		
		if (isPermitted($currentModule, 'DetailView', $messageid) != 'yes') {
			die(Zend_Json::encode(array('success'=> false, 'error' => "Permission denied to move record #$messageid")));
		}
			
		// get the real folder name in case of special one
		$folder = getRealFolder($destfolder, $focus);
		if (!$folder) die(Zend_Json::encode(array('success'=> false, 'error' => "The specified folder is not valid")));
		
		try {
			$focus->setAccount($focus->column_fields['account']);
			$focus->moveMessage($folder);
		} catch (Exception $e) {
			die(Zend_Json::encode(array('success'=> false, 'error' => "Error moving record #$messageid: ".$e->getMessage())));
		}
	}

	// end
	die(Zend_Json::encode(array('success'=> true, 'error' => "")));

}

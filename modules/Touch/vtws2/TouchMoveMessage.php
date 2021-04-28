<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchMoveMessage extends TouchWSClass {

	protected $specialFolders = array(
		"[:INBOX:]" => 'INBOX', 
		"[:DRAFTS:]" => 'Drafts', 
		"[:SENT:]" => 'Sent', 
		"[:SPAM:]" => 'Spam', 
		"[:TRASH:]" => 'Trash',
	);
	
	protected $serverFolders = null;
	
	function process(&$request) {
		global $touchInst, $touchUtils, $currentModule;
	
		$records = array_filter(array_map('intval', Zend_Json::decode($request['records']) ?: array()));
		$destfolder = $request['dest_folder'];
		
		// destfolder can be the exact name of the destination folder or have a special value:
		// "[:INBOX:]", "[:DRAFTS:]", "[:SENT:]", "[:SPAM:]", "[:TRASH:]"
		
		if (count($records) == 0) return $this->error("No records specified");
		if (empty($destfolder)) return $this->error("No destination folder specified");
		
		
		$currentModule = 'Messages';
		$focus = $touchUtils->getModuleInstance($currentModule);
		
		$this->serverFolders = $focus->getAllSpecialFolders();
		
		foreach ($records as $messageid) {
			$focus->id = $messageid;
			$r = $focus->retrieve_entity_info($messageid, $currentModule, false);
			
			if ($r == 'LBL_RECORD_DELETE') {
				return $this->error("The record #$messageid has been deleted");
			} elseif ($r == 'LBL_RECORD_NOT_FOUND') {
				return $this->error("The record #$messageid was not found");
			}
			
			if (isPermitted($currentModule, 'DetailView', $messageid) != 'yes') {
				return $this->error("Permission denied to move record #$messageid");
			}
			
			// get the real folder name in case of special one
			$folder = $this->getRealFolder($destfolder, $focus);
			if (!$folder) return $this->error("The specified folder is not valid");
			
			try {
				$focus->setAccount($focus->column_fields['account']);
				$focus->moveMessage($folder);
			} catch (Exception $e) {
				return $this->error("Error moving record #$messageid: ".$e->getMessage());
			}
		}

		return $this->success();
	}
	
	protected function getRealFolder($folder, $focus) {
		if (preg_match('/^\[:[A-Z]+:\]$/', $folder)) {
			// special folder
			if (!array_key_exists($folder, $this->specialFolders)) return false;
			$foldType = $this->specialFolders[$folder];
			$account = $focus->column_fields['account'];
			$folder = $this->serverFolders[$account][$foldType];
			if (empty($folder)) return false;
		}
		return $folder;
	}
	
}
 
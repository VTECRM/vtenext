<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@176893

class DropArea extends SDKExtendableClass {
	
	public function getFolders() {
		global $adb, $table_prefix;
		
		$folders = array();
		
		$foldersQuery = "SELECT foldername, folderid FROM {$table_prefix}_crmentityfolder WHERE tabid = ? ORDER BY foldername";
		$foldersRes = $adb->pquery($foldersQuery, array(getTabId('Documents')));
		
		if ($foldersRes && $adb->num_rows($foldersRes)) {
			while ($row = $adb->fetchByAssoc($foldersRes, -1, false)) {
				$folders[] = array('id' => $row['folderid'], 'value' => $row['foldername']);
			}
		}
		
		return $folders;
	}
	
	public function addNewFolder($folderName, $folderDesc) {
		global $current_user;		
		
		$folderId = 0;
		
		if (!vtlib_isModuleActive('Documents') || !isPermitted('Documents', 'EditView')) {
			throw new Exception(getTranslatedString('LBL_PERMISSION'));
		}
		
		if (empty($folderName)) {
			throw new Exception(getTranslatedString('FOLDERNAME_CANNOT_BE_EMPTY'));
		}
		
		// check if folder exists
		$folderinfo = getEntityFoldersByName($folderName, 'Documents');
		
		if (!empty($folderinfo)) {
			throw new Exception(getTranslatedString('FOLDER_NAME_ALREADY_EXISTS'));
		}

		$folderId = addEntityFolder('Documents', $folderName, $folderDesc, $current_user->id, null);
	
		return $folderId;
	}
	
	public function getUserList() {
		global $current_user;
		
		if (!is_admin($current_user)) {
			$users = get_user_array(false, 'Active', null, 'private');
		} else {
			$users = get_user_array(false, 'Active', null, null);
		}
		
		$users = array_map(function ($k, $v) {
			return array('id' => $k, 'value' => $v);
		}, array_keys($users), $users);
		
		return $users;
	}
	
	public function getGroupList() {
		global $current_user;
		
		if (!is_admin($current_user)) {
			$groups = get_group_array(false, 'Active', null, 'private');
		} else {
			$groups = get_group_array(false, 'Active', null, null);
		}
		
		$groups = array_map(function ($k, $v) {
			return array('id' => $k, 'value' => $v);
		}, array_keys($groups), $groups);
		
		return $groups;
	}
	
	public function fetchDropAreaForm($parentModule, $parentRecord) {
		global $current_user;
		global $mod_strings, $app_strings, $theme;
		global $currentModule;
		
		$smarty = new VteSmarty();
		$smarty->assign("MOD", $mod_strings);
		$smarty->assign("APP", $app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
		$smarty->assign("MODULE", $currentModule);
		
		$smarty->assign('PARENT_MODULE', $parentModule);
		$smarty->assign('PARENT_RECORD', $parentRecord);
		$smarty->assign('PARENT_ENTITYNAME', getEntityName($parentModule, $parentRecord, true));
		
		$smarty->assign('CURRENT_USER_ID', $current_user->id);
		$smarty->assign('BUMC', $current_user->column_fields['bu_mc']);
		
		$smarty->assign('FOLDERS', Zend_Json::encode($this->getFolders()));
		$smarty->assign('USER_LIST', Zend_Json::encode($this->getUserList()));
		$smarty->assign('GROUP_LIST', Zend_Json::encode($this->getGroupList()));
		
		$html = $smarty->fetch('modules/Documents/DropAreaForm.tpl');
		
		return $html;
	}
	
}
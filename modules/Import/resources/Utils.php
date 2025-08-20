<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once 'include/utils/ConfigReader.php';
require_once 'modules/Import/ui/Viewer.php';

class Import_Utils {

	static $AUTO_MERGE_NONE = 0;
	static $AUTO_MERGE_IGNORE = 1;
	static $AUTO_MERGE_OVERWRITE = 2;
	static $AUTO_MERGE_MERGEFIELDS = 3;

	static $supportedFileEncoding = array('UTF-8'=>'UTF-8', 'ISO-8859-1'=>'ISO-8859-1', 'Windows-1250'=>'Windows-1250'); // crmv@92218
	static $supportedDelimiters = array(','=>'comma', ';'=>'semicolon');
	static $supportedFileExtensions = array('csv','vcf');

	public function getSupportedFileExtensions() {
		return self::$supportedFileExtensions;
	}

	public function getSupportedFileEncoding() {
		return self::$supportedFileEncoding;
	}

	public function getSupportedDelimiters() {
		return self::$supportedDelimiters;
	}

	public static function getAutoMergeTypes() {
		return array(
			self::$AUTO_MERGE_IGNORE => 'Skip',
			self::$AUTO_MERGE_OVERWRITE => 'Overwrite',
			self::$AUTO_MERGE_MERGEFIELDS => 'Merge');
	}

	public static function getMaxUploadSize() {
		global $upload_maxsize;
		return $upload_maxsize;
	}

	public static function getImportDirectory() {
		global $import_dir;
		return $import_dir;
	}

	public static function getImportFilePath($user) {
		$importDirectory = self::getImportDirectory();
		return $importDirectory. "IMPORT_".intval($user->id);  // crmv@341226
	}


	public static function getFileReaderInfo($type) {
		$configReader = new ConfigReader('modules/Import/config.inc', 'ImportConfig');
		$importTypeConfig = $configReader->getConfig('importTypes');
		if(isset($importTypeConfig[$type])) {
			return $importTypeConfig[$type];
		}
		return null;
	}

	public static function getFileReader($userInputObject, $user) {
		$fileReaderInfo = self::getFileReaderInfo($userInputObject->get('type'));
		if(!empty($fileReaderInfo)) {
			require_once $fileReaderInfo['classpath'];
			$fileReader = new $fileReaderInfo['reader'] ($userInputObject, $user);
			// crmv@92218
			$enc = $userInputObject->get('use_file_encoding');
			if (!empty($enc)) {
				$fileReader->detectedEncoding = $enc;
			}
			// crmv@92218e
		} else {
			$fileReader = null;
		}
		return $fileReader;
	}

	public static function getDbTableName($user) {
		$configReader = new ConfigReader('modules/Import/config.inc', 'ImportConfig');
		$userImportTablePrefix = $configReader->getConfig('userImportTablePrefix');
		return $userImportTablePrefix . intval($user->id);  // crmv@341226
	}

	public static function showErrorPage($errorMessage, $errorDetails=false, $customActions=false) {
		$viewer = new Import_UI_Viewer();
		$viewer->assign('ERROR_MESSAGE', $errorMessage);
		$viewer->assign('ERROR_DETAILS', $errorDetails);
		$viewer->assign('CUSTOM_ACTIONS', $customActions);
		$viewer->display('ImportError.tpl');
	}

	public static function showImportLockedError($lockInfo) {

		$errorMessage = getTranslatedString('ERR_MODULE_IMPORT_LOCKED', 'Import');
		$errorDetails = array(getTranslatedString('LBL_MODULE_NAME', 'Import') => getTabModuleName($lockInfo['tabid']),
							getTranslatedString('LBL_USER_NAME', 'Import') => getUserFullName($lockInfo['userid']),
							getTranslatedString('LBL_LOCKED_TIME', 'Import') => $lockInfo['locked_since']);

		self::showErrorPage($errorMessage, $errorDetails);
	}

	public static function showImportTableBlockedError($moduleName, $user) {

		$errorMessage = getTranslatedString('ERR_UNIMPORTED_RECORDS_EXIST', 'Import');
		$customActions = array('LBL_CLEAR_DATA' => "location.href='index.php?module={$moduleName}&action=Import&mode=clear_corrupted_data'");

		self::showErrorPage($errorMessage, '', $customActions);
	}

	public static function isUserImportBlocked($user) {
		$adb = PearDatabase::getInstance();
		$tableName = self::getDbTableName($user);

		if(Vtecrm_Utils::CheckTable($tableName)) {
			$result = $adb->query('SELECT 1 FROM '.$tableName.' WHERE status = '.Import_Data_Controller::$IMPORT_RECORD_NONE);
			if($adb->num_rows($result) > 0) {
				return true;
			}
		}
		return false;
	}

	public static function clearUserImportInfo($user) {
		$adb = PearDatabase::getInstance();
		$tableName = self::getDbTableName($user);
		//crmv@57238
		if($adb->table_exist($tableName)){
			$adb->query('DROP TABLE '.$tableName);
		}
		//crmv@57238e
		Import_Lock_Controller::unLock($user);
		Import_Queue_Controller::removeForUser($user);
	}

	public static function getAssignedToUserList($module) {
		global $current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		$tabId = getTabid($module);

		if(!is_admin($current_user) && $profileGlobalPermission[2] == 1
				&& ($defaultOrgSharingPermission[$tabId] == 3 or $defaultOrgSharingPermission[$tabId] == 0)) {

			return get_user_array(FALSE, "Active", $current_user->id,'private');
		} else {
			return get_user_array(FALSE, "Active", $current_user->id);
		}
	}

	public static function getAssignedToGroupList($module) {
		global $current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		$tabId = getTabid($module);

		if(!is_admin($current_user) && $profileGlobalPermission[2] == 1
				&& ($defaultOrgSharingPermission[$tabId] == 3 or $defaultOrgSharingPermission[$tabId] == 0)) {

			return get_group_array(FALSE, "Active", $current_user->id,'private');
		} else {
			return get_group_array(FALSE, "Active", $current_user->id);
		}
	}

	public static function hasAssignPrivilege($moduleName, $assignToUserId) {
		$assignableUsersList = self::getAssignedToUserList($moduleName);
		if(array_key_exists($assignToUserId, $assignableUsersList)) {
			return true;
		}
		$assignableGroupsList = self::getAssignedToGroupList($moduleName);
		if(array_key_exists($assignToUserId, $assignableGroupsList)) {
			return true;
		}
		return false;

	}

}
?>
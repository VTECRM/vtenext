<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\VTE;


/**
 * Simulate the vtwsclib but with a local VTE environment loaded
 */
class LocalWSClient {

	public $version = '1.0';
	
	// TODO: load dynamically
	/*protected $ws_prefixes = array(
		'Events' => '18',
		'Calendar' => '1',
		'Contacts' => '4',
		'Users' => '19',
	);*/
	
	protected $_lasterror;
	
	public function __construct($path) {
		global $adb, $root_directory;
		
		\VteSyncLib\VTEUtils::includeVTE($path);
		
		if (rtrim($root_directory, '/') != rtrim($path, '/')) {
			$this->_lasterror = 'Included VTE runtime mismatch';
			return false;
		}
		
		$this->_lasterror = null;
		
		return true;
	}
	
	public function version() {
		return $this->version;
	}
	
	public function doLogin($nameOrId, $key = null) {
		global $current_user;
		
		if (!is_numeric($nameOrId)) {
			$nameOrId = getUserId_Ol($nameOrId);
		}
		
		if (empty($nameOrId)) {
			$this->_lasterror = 'Empty userid or user not found';
			return false;
		}
		
		if (!$current_user || $current_user->id != $nameOrId) {
			try {
				$current_user = \CRMEntity::getInstance('Users');
				$current_user->retrieveCurrentUserInfoFromFile($nameOrId);
			} catch (Exception $e) {
				$this->_lasterror = "Unable to retrieve user #$nameOrId: ".$e->getMessage();
				return false;
			}
		}
		
		return true;
	}
	
	public function lastError() {
		return $this->_lasterror;
	}
	
	public function doDescribe($module) {
		global $current_user;
		require_once('include/Webservices/DescribeObject.php');
		try {
			$r = vtws_describe($module, $current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doRetrieve($id) {
		global $current_user;
		require_once('include/Webservices/Retrieve.php');
		try {
			$r = vtws_retrieve($id, $current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doCreate($module, $valuemap, $postfiles = null) {
		global $current_user;
		require_once('include/Webservices/Create.php');
		try {
			$r = vtws_create($module, $valuemap, $current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doUpdate($valuemap) {
		global $current_user;
		require_once('include/Webservices/Update.php');
		try {
			$r = vtws_update($valuemap, $current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doRevise($valuemap) {
		global $current_user;
		require_once('include/Webservices/Revise.php');
		try {
			$r = vtws_revise($valuemap, $current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doQuery($query) {
		global $current_user;
		require_once('include/Webservices/Query.php');
		try {
			$r = vtws_query($query, $current_user, false);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doDelete($id) {
		global $current_user;
		require_once('include/Webservices/Delete.php');
		try {
			$r = vtws_delete($id, $current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doDeleteUser($id, $transferTo) {
		global $current_user;
		require_once('include/Webservices/DeleteUser.php');
		try {
			$r = vtws_deleteUser($id, $transferTo, $current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function doInvoke($method, $params = null, $type = 'POST') {
		global $adb, $current_user;
		require_once('include/Webservices/SessionManager.php');
		require_once('include/Webservices/OperationManager.php');
		
		try {
			$sessionManager = new \SessionManager();
			$operationManager = new \OperationManager($adb,$method,'json',$sessionManager);
			$operationInput = $operationManager->sanitizeOperation($params);
		
			$includes = $operationManager->getOperationIncludes();
			foreach($includes as $ind=>$path){
				require_once($path);
			}
			$r = $operationManager->runOperation($operationInput,$current_user);
		} catch(Exception $e) {
			$this->_lasterror = $e->getMessage();
			return false;
		}
		$this->_lasterror = false;
		return $r;
	}
	
	public function addPicklistValues($module, $fieldname, $values) {
		global $adb, $table_prefix;
		
		// first filter out values already present
		$existing = getAllPickListValues($fieldname, $module);
		
		$missing = array_diff_key($values, $existing);
		if (count($missing) > 0) {
			require_once('vtlib/Vtecrm/Field.php');
			require_once('vtlib/Vtecrm/Module.php');
			
			$mod = \Vtecrm_Module::getInstance($module);
			$fld = \Vtecrm_Field::getInstance($fieldname, $mod);
			if ($fld) {
				$fld->setPicklistValues(array_keys($values));
				// TODO: translation -> language ?
				//foreach ($values as $val => $label)
			}
		}
		
		return true;
	}
	
	
}
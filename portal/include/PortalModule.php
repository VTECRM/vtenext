<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@173271 */

/**
 * Base class for a module entity in portal
 */
class PortalModule {

	public $hasComments = false;
	public $hasAttachments = false;
	public $hasListOwnerFilter = true;
	public $hasListStatusFilter = false;
	public $hasListSearch = false;
	
	public $display_columns = 2;
	
	public $list_function = 'get_list_values';
	public $detail_function = 'get_details';
	public $edit_function = 'get_details';
	
	public $list_template = 'List.tpl';
	public $detail_template = 'Detail.tpl';
	public $create_template = 'Create.tpl';
	public $edit_template = 'Edit.tpl';

	protected $moduleName = '';
	
	public static function getInstance($module) {
		$module = str_replace('.', '', $module);
		$path = "$module/$module.php";
		if (file_exists($path)) {
			require_once($path);
			$classname = $module."Module";
			return new $classname($module);
		}
		return new self($module);
	}
	
	public function __construct($module) {
		$this->moduleName = $module;
	}
	
	public function getModule() {
		return $this->moduleName;
	}
	
	public function hasComments() {
		return $this->hasComments;
	}
	
	public function hasAttachments() {
		return $this->hasAttachments;
	}
	
	public function hasListOwnerFilter() {
		global $client, $Server_Path;
		if ($this->hasListOwnerFilter) {
			// check if enabled
			$allow_all = $client->call('show_all',array('module'=>$this->moduleName),$Server_Path, $Server_Path);
			return ($allow_all == 'true');
		}
		return false;
	}
	
	public function hasListStatusFilter() {
		return $this->hasListStatusFilter;
	}
	
	public function hasListSearch() {
		return $this->hasListSearch;
	}
	
	public function canCreateRecord() {
		// TODO: use permissions
		return false;
	}
	
	/**
	 * Get the list of fields visibile in detail view
	 */
	public function getVisibleFields() {
		// read old file, to mantain compatibiltiy
		$list = array();
		
		$path = $this->moduleName.'/config.php';
		if (file_exists($path)) {
			include($path);
			if (is_array($permittedFields)) {
				$list = $permittedFields;
			}
		}
		return $list;
	}
	
	/**
	 * Get the list of fields visible in create view. By default, take all editable fields
	 */
	public function getCreationFields() {
		global $client;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$struct = $client->call('get_fields_structure', array('customerid' => $customerid, 'module' => $this->moduleName, 'id' => 0, 'language' => getPortalCurrentLanguage()));

		if (is_array($struct)) {
			// filter for editable fields
			$struct = array_values(array_filter($struct, function($field) {
				return $field['editable'];
			}));
			
		}
		
		return $struct;
	}
	
	public function getEditFields($id) {
		global $client;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$struct = $client->call('get_fields_structure', array('customerid' => $customerid, 'module' => $this->moduleName, 'id' => $id, 'language' => getPortalCurrentLanguage()));
		
		return $struct;
	}
	
	public function getStatusFilterValues() {
		return array();
	}
	
	protected function getExtraJs() {
		$extraJs = array();
		
		$jsModule = $this->moduleName.'/'.$this->moduleName.'.js';
		if (file_exists($jsModule)) {
			$extraJs[] = $jsModule;
		}
		
		return $extraJs;
	}
	
	protected function initSmarty() {
		require_once('Smarty_setup.php');
		
		$customerid = $_SESSION['customer_id'];
		
		$smarty = new VTECRM_Smarty();
		
		$smarty->assign('CUSTOMERID',$customerid);
		$smarty->assign('MODULE',$this->moduleName);
		$smarty->assign('MODULE_JS',$this->getExtraJs());
		
		return $smarty;
	}
	
	protected function callListFunction() {
		global $client, $Server_Path;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$onlymine = $_REQUEST['onlymine'];
		$folderid = intval($_REQUEST['folderid']);
		
		$params = Array('id'=>$customerid,'block'=>$this->moduleName,'sessionid'=>$sessionid,'onlymine'=>$onlymine, 'folderid'=>$folderid);
		$result = $client->call($this->list_function, $params, $Server_Path, $Server_Path);
		
		return $result;
	}
	
	protected function processListResult($result) {
		return getblock_fieldlistview($result,$this->moduleName);
	}
	
	protected function prepareList() {
		$smarty = $this->initSmarty();
		
		$onlymine = $_REQUEST['onlymine'];
		$showstatus = $_REQUEST['showstatus'];
		
		if ($this->hasListOwnerFilter()) {
			$smarty->assign('SHOW_LIST_OWNER_FILTER', true);
			if($onlymine == 'true') {
				$mine_selected = 'selected';
				$all_selected = '';
			} else {
				$mine_selected = '';
				$all_selected = 'selected';
			}
			$smarty->assign('MINE_SELECTED',$mine_selected);
			$smarty->assign('ALL_SELECTED',$all_selected);
		}
		
		if ($this->hasListStatusFilter()) {
			$smarty->assign('SHOW_LIST_STATUS_FILTER', true);
			$statusValues = $this->getStatusFilterValues();
			$smarty->assign('STATUS_FILTER_VALUES', $statusValues);
			$smarty->assign('STATUS_FILTER', $showstatus);
		}
		
		if ($this->hasListSearch()) {
			$smarty->assign('SHOW_LIST_SEARCH', true);
		}
		
		$smarty->assign('CAN_CREATE_RECORD', $this->canCreateRecord());
		$smarty->assign('LIST_TITLE', getTranslatedString($this->moduleName));

		// now get the list
		$result = $this->callListFunction();
		$listData = $this->processListResult($result);
		$smarty->assign('FIELDLISTVIEW',$listData);
		
		return $smarty;
	}
	
	public function displayList() {
		$smarty = $this->prepareList();
		if ($smarty) {
			$smarty->display($this->list_template);
		}
	}
	
	protected function preprocessDetailFields($id, $fields) {
		return $fields;
	}
	
	protected function postprocessDetailFields($id, $fields) {
		return $fields;
	}
	
	protected function filterDetailFields($id, $fields) {
		$permittedFields = $this->getVisibleFields();
		if (is_array($fields) && is_array($permittedFields) && count($permittedFields) > 0) {
			$fields = array_intersect_key($fields, array_flip($permittedFields));
		}
		return $fields;
	}
	
	protected function callDetailFunction($id) {
		global $client, $Server_Path;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$params = array('id' => "$id", 'block'=>$this->moduleName,'contactid'=>$customerid,'sessionid'=>"$sessionid",'language'=>getPortalCurrentLanguage());
		$result = $client->call($this->detail_function, $params, $Server_Path, $Server_Path);
		
		return $result;
	}
	
	protected function prepareDetail($id) {
		global $client;
		
		$customerid = $_SESSION['customer_id'];
		
		$permission = getModulePermissions($customerid, $this->moduleName);
		if (!$permission['perm_read']) {
			return $this->displayNotAuthorized();
		}
		
		$result = $this->callDetailFunction($id);

		// Check for Authorization
		if (is_array($result) && count($result) == 1 && $result[0] == "#NOT AUTHORIZED#") {
			return $this->displayNotAuthorized();
		}
		
		$smarty = $this->initSmarty();
		$smarty->assign('ID',$id);
		
		if (!$permission['perm_write']) {
			$edit = $client->call('is_edit_permitted', array('customerid' => $customerid, 'module' => $this->moduleName, 'id' => $id));
			$err = $client->getError();
			if (!$err) {
				$permission['perm_write'] = $edit;
			}
		}
		$smarty->assign('PERMISSION', $permission);
		
		$info = $result[0][$this->moduleName];
		
		// do some transformations or read hidden fields
		$info = $this->preprocessDetailFields($id, $info);
		
		// remove unwanted fields
		$info = $this->filterDetailFields($id, $info);
		
		// do something else
		$info = $this->postprocessDetailFields($id, $info);

		// filter fields
		$smarty->assign('FIELDLIST', $info);
		$smarty->assign('DISPLAY_COLUMNS', $this->display_columns);
		
		return $smarty;
	}
	
	/**
	 * Display the detailview of a record
	 */
	public function displayDetail($id) {
		$smarty = $this->prepareDetail($id);
		if ($smarty) {
			$smarty->display($this->detail_template);
		}
	}
	
	protected function prepareCreate() {
		
		$smarty = $this->initSmarty();
		
		$fields = $this->getCreationFields();
		$smarty->assign('FIELDSTRUCT',$fields);
		
		// by default don't show the indicator for mandatory fields
		$smarty->assign('SHOW_MANDATORY_SYMBOL', false);
		$smarty->assign('DISPLAY_COLUMNS', $this->display_columns);
		
		if ($this->hasAttachments()) {
			$smarty->assign('UPLOAD_ATTACHMENTS',true);
		}
		
		return $smarty;
	}
	
	/**
	 * Display the create view
	 */
	public function displayCreate() {
		$smarty = $this->prepareCreate();
		if ($smarty) {
			$smarty->display($this->create_template);
		}
	}
	
	protected function callEditFunction($id) {
		global $client, $Server_Path;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$params = array('id' => "$id", 'block'=>$this->moduleName, 'contactid'=>"$customerid",'sessionid'=>"$sessionid",'language'=>getPortalCurrentLanguage());
		$result = $client->call($this->edit_function, $params, $Server_Path, $Server_Path);
		
		return $result;
	}
	
	protected function prepareEdit($id) {
		global $client;
		
		$smarty = $this->initSmarty();
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$permission = getModulePermissions($customerid, $this->moduleName);
		$smarty->assign('PERMISSION', $permission);

		// specific record permission
		$edit = $client->call('is_edit_permitted', array('customerid' => $customerid, 'module' => $this->moduleName, 'id' => $id));
		if (!$edit) {
			return $this->displayNotAuthorized();
		}
		
		$smarty->assign('ID',$id);
		
		$result = $this->callEditFunction($id);

		// Check for Authorization
		if (count($result) == 1 && $result[0] == "#NOT AUTHORIZED#") {
			return $this->displayNotAuthorized();
		}
		
		$info = $result[0][$this->moduleName];

		// now get field structure
		$struct = $this->getEditFields($id);
		
		// crmv@171334 - fix filename field
		foreach ($struct as &$field) {
			if ($this->moduleName == 'Documents' && $field['fieldname'] == 'filename') {
				$field['fieldvalue'] = strip_tags($field['fieldvalue']);
			}
		}
		// crmv@171334e

		if ($_SESSION['validation'] && !$_SESSION['validation']['success']) {
			// save failed
			$validation = $_SESSION['validation'];
			$smarty->assign('ERROR_MSG',$validation['message']);
			unset($_SESSION['validation']);
		}

		$smarty->assign('FIELDSTRUCT',$struct);
		$smarty->assign('FIELDLIST',$info);
		
		return $smarty;
	}
	
	/**
	 * Display the edit view
	 */
	public function displayEdit($id) {
		$smarty = $this->prepareEdit($id);
		if ($smarty) {
			$smarty->display($this->edit_template);
		}
	}
	
	/**
	 * Create a new record. Return the crmid in case of successful creation
	 * Not implemented for generic modules yet
	 */
	public function createRecord($request) {
		throw new Exception("Record creation not implemented for module {$this->moduleName}");
		return false;
	}
	
	/**
	 * Generic function to update record. File upload are not supported yet.
	 * Return true in case of success
	 */
	public function updateRecord($id, $request) {
		global $client;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		// check permission
		$edit = $client->call('is_edit_permitted', array('customerid' => $customerid, 'module' => $this->moduleName, 'id' => $id));
		if (!$edit) {
			$_SESSION['validation'] = array(
				'success' => false,
				'message' => 'Not authorized',
			);
			return false;
		}
		
		$fields = $request;
		
		// remove extra fields
		unset($fields['module'], $fields['action'], $fields['id']);
	
		// TODO: handle file upload...
	
		$params = array(
			'customerid' => "$customerid",
			'module' => $this->moduleName,
			'id' => $id,
			'fields' => $fields,
			'files' => array(), // TODO
		);
		// crmv@171334e
		
		$result = $client->call('update_record', $params);
		$err = $client->getError();

		if (!$result || !$result['success']) {
			// error while saving
			$_SESSION['validation'] = array(
				'success' => false,
				'message' => 'Update failed: '.$result['message'],
			);
			return false;
		}
		
		// reset validation status
		$_SESSION['validation'] = array(
			'success' => true,
		);

		return true;
	}
	
	public function getComments($id) {
		throw new Exception("Comments not implemented for module {$this->moduleName}");
	}
	
	public function displayNotAuthorized() {
		
		$smarty = $this->initSmarty();
		$smarty->display('NotAuthorized.tpl');
		
		include("footer.html");
		die();
		
		return false;
	}
	
	public function displayNotAvailable() {
		
		$smarty = $this->initSmarty();
		
		$smarty->assign('ERR_MESSAGE','LBL_NOT_AVAILABLE');
		$smarty->assign('TITLE',getTranslatedString($this->moduleName));
		
		$smarty->display('List.tpl');
		
		die();
		
		return false;
	}
	
	
}
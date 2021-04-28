<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

namespace GDPR;

defined('BASEPATH') OR exit('No direct script access allowed');

class GDPRManager {
	
	private static $instance = null;
	
	public $webservices = array(
		'gdpr_authtoken', 
		'gdpr_sendverify', 
		'gdpr_accesstoken',
		'gdpr_checkaccesstoken', 
		'gdpr_update', 
		'gdpr_fields',
		'gdpr_confirmupdate', 
		'gdpr_delete', 
		'gdpr_supportrequest',
		'gdpr_privacypolicy', 
		'gdpr_mergecontact', 
		'gdpr_sendprivacypolicy',
	);
	
	public $actionsFolder = 'actions';
	
	protected $config = null;
	protected $sessionManager = null;
	
	protected $vtwsclient = null;
	
	protected $accessToken = null;
	
	protected $contactId = null;
	protected $contactEmail = null;
	protected $contactData = null;
	protected $contactDuplicates = null;
	
	protected $currentAction = null;
	
	private function __construct($CFG, $SM, $request) {
		$this->config = $CFG;
		$this->sessionManager = $SM;
		
		if (!empty($request['cid']) || (!empty($request['cid']) && !$this->sessionManager->hasKey('cid'))) {
			$this->contactId = $request['cid'];
			$this->sessionManager->set('cid', $request['cid']);
		} else {
			$this->contactId = $this->sessionManager->get('cid');
		}
		
		if (!empty($request['accesstoken']) || (!empty($request['accesstoken']) && !$this->sessionManager->hasKey('accesstoken'))) {
			$this->accessToken = $request['accesstoken'];
			$this->sessionManager->set('accesstoken', $request['accesstoken']);
		} else {
			$this->accessToken = $this->sessionManager->get('accesstoken');
		}
	}
	
	public function processAction($action) {
		$actionsFolder = $this->getActionsFolder();
		
		if (empty($action)) {
			$action = 'detailview';
		}
		
		if (!empty($actionsFolder)) {
			$actionPath = "$actionsFolder/$action.php";
			
			if (file_exists($actionPath) && is_readable($actionPath)) {
				$this->currentAction = $action;
				include($actionPath);
			} else {
				Redirect::to(404);
			}
		} else {
			Redirect::to(404);
		}
	}
	
	public function initializeVTWSClient() {
		if ($this->vtwsclient) return true;
		
		$endpoint = $this->config->webservice_endpoint;
		$username = $this->config->webservice_username;
		$accesskey = $this->config->webservice_accesskey;
		
		$this->vtwsclient = new \VTE_WSClient($endpoint);//crmv@207871
		$login = $this->vtwsclient->doLogin($username, $accesskey);
		
		if (!$login) {
			$this->showError(_T('webservice_connection_error'), '', true);
			return false;
		}
		
		return true;
	}
	
	public function doRequest($wsname, $params, $method) {
		$this->initializeVTWSClient();
		
		if (!$this->isValidWebservice($wsname)) return false;
		
		$result = $this->vtwsclient->doInvoke($wsname, $params, $method);
		$this->processResult($result);
		
		return $result;
	}
	
	public function getAuthToken() {
		$params = array('contactid' => $this->contactId);
		return $this->doRequest('gdpr_authtoken', $params, 'POST');
	}
	
	public function generateAccessToken($authtoken) {
		$params = array('contactid' => $this->contactId, 'authtoken' => $authtoken);
		return $this->doRequest('gdpr_accesstoken', $params, 'POST');
	}
	
	public function sendVerify($authtoken) {
		$params = array('contactid' => $this->contactId, 'authtoken' => $authtoken);
		return $this->doRequest('gdpr_sendverify', $params, 'POST');
	}
	
	public function updateContact($data) {
		$params = array('accesstoken' => $this->accessToken, 'data' => \Zend_Json::encode($data));
		return $this->doRequest('gdpr_update', $params, 'POST');
	}
	
	public function deleteContact() {
		$params = array('accesstoken' => $this->accessToken);
		return $this->doRequest('gdpr_delete', $params, 'POST');
	}
	
	public function mergeContact($mainContact, $otherIds) {
		$params = array('accesstoken' => $this->accessToken, 'maincontact' => $mainContact, 'otherids' => \Zend_Json::encode($otherIds));
		return $this->doRequest('gdpr_mergecontact', $params, 'POST');
	}
	
	public function getFields() {
		$params = array('accesstoken' => $this->accessToken);
		return $this->doRequest('gdpr_fields', $params, 'POST');
	}
	
	public function confirmUpdate($token) {
		$params = array('accesstoken' => $this->accessToken, 'token' => $token);
		return $this->doRequest('gdpr_confirmupdate', $params, 'POST');
	}
	
	public function sendSupportRequest($subject, $description) {
		$params = array('contactid' => $this->contactId, 'subject' => $subject, 'description' => $description);
		return $this->doRequest('gdpr_supportrequest', $params, 'POST');
	}
	
	public function getPrivacyPolicy() {
		$params = array('contactid' => $this->contactId);
		return $this->doRequest('gdpr_privacypolicy', $params, 'POST');
	}
	
	public function sendPrivacyPolicy() {
		$params = array('contactid' => $this->contactId);
		return $this->doRequest('gdpr_sendprivacypolicy', $params, 'POST');
	}
	
	public function isValidSession() {
		$params = array('accesstoken' => $this->accessToken);
		$result = $this->doRequest('gdpr_checkaccesstoken', $params, 'POST');

		$valid = $result['success'];
		if (!$valid) $this->clear();
		
		return $valid;
	}
	
	public function downloadContactData() {
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=gdpr_contactdata.csv');
		
		$output = fopen('php://output', 'w');
		
		$data = array();
		
		foreach ($this->contactData as $fieldname => $value) {
			if (!preg_match('/^gdpr_/', $fieldname, $matches)) {
				$data[_T($fieldname)] = $value;
			}
		}
		
		fputcsv($output, array_keys($data));
		fputcsv($output, $data);
		
		fclose($output);
		exit();
	}
	
	protected function processResult($result) {
		if (is_array($result) && isset($result['cid'])) {
			$this->contactId = $result['cid'];
			$this->sessionManager->set('cid', $result['cid']);
		}
		
		if (is_array($result) && isset($result['contact'])) {
			$this->contactData = $result['contact'];
		}
		
		if (is_array($result) && isset($result['email'])) {
			$this->contactEmail = $result['email'];
		}
		
		if (is_array($result) && isset($result['duplicates'])) {
			$this->contactDuplicates = $result['duplicates'];
		}
		
		if (is_array($result) && isset($result['business_id'])) {
			$this->sessionManager->set('bid', $result['business_id']);
		}
	}
	
	public function clear() {
		$this->accessToken = null;
		$this->sessionManager->remove('accesstoken');
	}
	
	public function getActionsFolder() {
		return $this->actionsFolder;
	}
	
	public function getContactId() {
		return $this->contactId;
	}
	
	public function getContactEmail() {
		return $this->contactEmail;
	}
	
	public function getContactDuplicates() {
		return $this->contactDuplicates;
	}
	
	public function hasDuplicates() {
		return count($this->contactDuplicates) > 0 ? true : false;
	}
	
	public function getAccessToken() {
		return $this->accessToken;
	}
	
	public function getCurrentAction() {
		return $this->currentAction;
	}
	
	public function getContactData() {
		return $this->contactData;
	}
	
	public function getData($field) {
		return isset($this->contactData[$field]) ? $this->contactData[$field] : null;
	}
	
	public function isPrivacyPolicyConfirmed() {
		return $this->getData('gdpr_privacypolicy') ? true : false;
	}
	
	public function showError($title, $message, $exit = true) {
		$smarty = new SmartyConfig();
		
		$smarty->assign('BROWSER_TITLE', _T('browser_title_error'));
		$smarty->assign('TITLE', $title);
		$smarty->assign('MESSAGE', $message);
		
		$smarty->assign('CONTACT_ID', $this->getContactId());
		
		$smarty->display('Error.tpl');
		if ($exit) exit();
	}
	
	public function showOperationDenied($params, $exit = true) {
		$smarty = new SmartyConfig();
		
		$cidData = $params['cid_data'];
		
		$smarty->assign('BROWSER_TITLE', _T('browser_title_op_denied'));
		$smarty->assign('EMAIL', $cidData['email']);
		
		$smarty->assign('CONTACT_ID', $this->getContactId());
		
		$smarty->display('OperationDenied.tpl');
		if ($exit) exit();
	}
	
	protected function isValidWebservice($wsname) {
		return in_array($wsname, $this->webservices);
	}
	
	public static function getInstance($CFG, $SM, $request) {
		if (!isset(self::$instance)) {
			self::$instance = new GDPRManager($CFG, $SM, $request);
		}
		return self::$instance;
	}
	
}
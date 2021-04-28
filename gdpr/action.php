<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

require_once('config.php');

$action = filter_var($_REQUEST['ajax_action'], FILTER_SANITIZE_STRING);

$SM = GDPR\SessionManager::getInstance();
$GPDRManager = GDPR\GDPRManager::getInstance($CFG, $SM, $_REQUEST);

$VTGDPRAction = new VTGDPRAction();
$VTGDPRAction->process($action, $_REQUEST);

exit();

class VTGDPRAction {

	public function __construct() {
	}

	public function process($action, $params) {
		$fnName = lcfirst($action);

		if (method_exists($this, $fnName)) {
			return $this->$fnName($params);
		} else {
			return $this->error(_T('unknown_service'));
		}
	}
	
	public function sendVerify($params) {
		global $GPDRManager;
		
		$authtoken = $params['authtoken'];
		
		if (empty($authtoken)) {
			return $this->error(_T('invalid_parameters'));
		}
		
		$result = $GPDRManager->sendVerify($authtoken);
		
		$error = null;
		if (!$result || $result['error']) {
			$error = !empty($result['error']) ? _T($result['error']) : _T('unknown_error');
		}
		if (!empty($error)) return $this->error($error);
		
		return $this->success();
	}
	
	public function saveSettings($params) {
		global $GPDRManager;
		
		if (empty($params)) {
			return $this->error(_T('invalid_parameters'));
		}
		
		$result = $GPDRManager->updateContact($params);
		
		$error = null;
		if (!$result || $result['error']) {
			$error = !empty($result['error']) ? _T($result['error']) : _T('unknown_error');
		}
		if (!empty($error)) return $this->error($error);
		
		return $this->success();
	}
	
	public function editContact($params) {
		global $GPDRManager;
		
		if (empty($params)) {
			return $this->error(_T('invalid_parameters'));
		}
		
		$result = $GPDRManager->updateContact($params);
		
		$error = null;
		if (!$result || $result['error']) {
			$error = !empty($result['error']) ? _T($result['error']) : _T('unknown_error');
		}
		if (!empty($error)) return $this->error($error);
		
		return $this->success();
	}
	
	public function deleteContact($params) {
		global $GPDRManager;
		
		if (empty($params)) {
			return $this->error(_T('invalid_parameters'));
		}
		
		$result = $GPDRManager->deleteContact();

		$error = null;
		if (!$result || $result['error']) {
			$error = !empty($result['error']) ? _T($result['error']) : _T('unknown_error');
		}
		if (!empty($error)) return $this->error($error);
		
		return $this->success();
	}
	
	public function mergeContact($params) {
		global $GPDRManager;
		
		if (empty($params)) {
			return $this->error(_T('invalid_parameters'));
		}
		
		$mainContact = intval($params['maincontact']);
		$otherIds = $params['otherids'];
		
		$result = $GPDRManager->mergeContact($mainContact, $otherIds);
		
		$error = null;
		if (!$result || $result['error']) {
			$error = !empty($result['error']) ? _T($result['error']) : _T('unknown_error');
		}
		if (!empty($error)) return $this->error($error);
		
		return $this->success();
	}
	
	public function sendSupportRequest($params) {
		global $GPDRManager;
		
		if (empty($params)) {
			return $this->error(_T('invalid_parameters'));
		}
		
		$subject = $params['request_subject'];
		$description = $params['request_description'];
		
		$result = $GPDRManager->sendSupportRequest($subject, $description);
		
		$error = null;
		if (!$result || $result['error']) {
			$error = !empty($result['error']) ? _T($result['error']) : _T('unknown_error');
		}
		if (!empty($error)) return $this->error($error);
		
		return $this->success();
	}
	
	public function sendPrivacyPolicy($params) {
		global $GPDRManager;
		
		if (empty($params)) {
			return $this->error(_T('invalid_parameters'));
		}
		
		$result = $GPDRManager->sendPrivacyPolicy();

		$error = null;
		if (!$result || $result['error']) {
			$error = !empty($result['error']) ? _T($result['error']) : _T('unknown_error');
		}
		if (!empty($error)) return $this->error($error);
		
		return $this->success();
	}
	
	public function loadDetailBlock($params) {
		global $GPDRManager;
		
		$smarty = new GDPR\SmartyConfig();
		
		$contactId = $GPDRManager->getContactId();
		$accessToken = $GPDRManager->getAccessToken();
		
		$structureResult = $GPDRManager->getFields();
		$structure = $structureResult['structure'];
		
		if (!$structure) {
			return $this->error(_T('structure_not_available'));
		}
		
		$contactData = $GPDRManager->getContactData();
		
		$smarty->assign('CONTACT_ID', $contactId);
		$smarty->assign('ACCESS_TOKEN', $accessToken);
		$smarty->assign('STRUCTURE', $structure);
		$smarty->assign('CONTACT_DATA', Zend_Json::encode($contactData));
		
		$html = $smarty->fetch('DetailBlock.tpl');
		
		return $this->success(array('html' => $html));
	}
	
	public function loadEditBlock($params) {
		global $GPDRManager;
		
		$smarty = new GDPR\SmartyConfig();
		
		$contactId = $GPDRManager->getContactId();
		$accessToken = $GPDRManager->getAccessToken();
		
		$structureResult = $GPDRManager->getFields();
		$structure = $structureResult['structure'];
		
		if (!$structure) {
			return $this->error(_T('structure_not_available'));
		}
		
		$contactData = $GPDRManager->getContactData();
		
		$smarty->assign('CONTACT_ID', $contactId);
		$smarty->assign('ACCESS_TOKEN', $accessToken);
		$smarty->assign('STRUCTURE', $structure);
		$smarty->assign('CONTACT_DATA', Zend_Json::encode($contactData));
		
		$html = $smarty->fetch('EditBlock.tpl');
		
		return $this->success(array('html' => $html));
	}
	
	public function error($message) {
		$out = array('success' => false, 'error' => $message);
		$this->rawOutput($out);
	}

	public function success($data = array()) {
		$out = array_merge(array('success' => true), $data);
		$this->rawOutput($out);
	}

	public function rawOutput($data) {
		echo json_encode($data);
	}

}
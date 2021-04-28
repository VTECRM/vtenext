<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

class GDPRUtils {
	
	public function __construct() {}
	
	public function getGDPRInfo($id) {
		global $adb, $table_prefix;
		
		$ret = array();
		
		$BU = BusinessUnit::getInstance();
		$business = $BU->getBusinessInfo($id);
		if (!$business) return false;
		
		$vteProp = VTEProperties::getInstance();
		
		$generalSettings = $vteProp->get('services.gdpr.general_settings');
		
		$ret['default_business'] = $generalSettings['default_business'];
		
		$config = $vteProp->get("services.gdpr.config.business.{$id}");
		
		$ret['webservice_endpoint'] = $config['webservice_endpoint'];
		$ret['webservice_username'] = $config['webservice_username'];
		$ret['webservice_accesskey'] = $config['webservice_accesskey'];
		$ret['default_language'] = $config['default_language'];
		$ret['website_logo'] = $config['website_logo'];
		$ret['sender_name'] = $config['sender_name'];
		$ret['sender_email'] = $config['sender_email'];
		$ret['noconfirm_deletion_months'] = $config['noconfirm_deletion_months'];
		
		$templates = $vteProp->get("services.gdpr.templates.business.{$id}");
		
		$ret['templates'] = array();
		$ret['templates']['support_request_template'] = array('id' => $templates['support_request_template'], 'name' => $this->getTemplateNameFromId($templates['support_request_template']), 'label' => getTranslatedString('LBL_SUPPORT_REQUEST_TEMPLATE', 'Settings'), 'description' => getTranslatedString('LBL_SUPPORT_REQUEST_TEMPLATE_DESC', 'Settings'));
		$ret['templates']['access_template'] = array('id' => $templates['access_template'], 'name' => $this->getTemplateNameFromId($templates['access_template']), 'label' => getTranslatedString('LBL_ACCESS_TEMPLATE', 'Settings'), 'description' => getTranslatedString('LBL_ACCESS_TEMPLATE_DESC', 'Settings'));
		$ret['templates']['confirm_update_template'] = array('id' => $templates['confirm_update_template'], 'name' => $this->getTemplateNameFromId($templates['confirm_update_template']), 'label' => getTranslatedString('LBL_CONFIRM_UPDATE_TEMPLATE', 'Settings'), 'description' => getTranslatedString('LBL_CONFIRM_UPDATE_TEMPLATE_DESC', 'Settings'));
		$ret['templates']['contact_updated_template'] = array('id' => $templates['contact_updated_template'], 'name' => $this->getTemplateNameFromId($templates['contact_updated_template']), 'label' => getTranslatedString('LBL_CONTACT_UPDATED_TEMPLATE', 'Settings'), 'description' => getTranslatedString('LBL_CONTACT_UPDATED_TEMPLATE_DESC', 'Settings'));
		
		$PPU = PrivacyPolicyUtils::getInstance();
		$companyPrivacyPolicy = $PPU->get($id, 'Company');
		
		$ret['privacy_policy'] = $companyPrivacyPolicy;
		
		return $ret;
	}
	
	public function getEmailTemplates() {
		global $adb, $table_prefix;
		
		$templates = array();
		
		$emailTemplatesResult = $adb->pquery("SELECT * FROM {$table_prefix}_emailtemplates WHERE templatetype = ? AND deleted = 0", array('Email'));
		
		if ($emailTemplatesResult && $adb->num_rows($emailTemplatesResult)) {
			while ($row = $adb->fetchByAssoc($emailTemplatesResult, -1, false)) {
				$templateid = $row['templateid'];
				$templatename = $row['templatename'];
				
				$templates[] = array('id' => $templateid, 'name' => $templatename);
			}
		}
		
		return $templates;
	}
	
	public function getTemplateNameFromId($templateid) {
		global $adb, $table_prefix;
		
		$templateName = '';
		
		if (empty($templateid)) return $templateName;
		
		$templateNameResult = $adb->pquery("SELECT * FROM {$table_prefix}_emailtemplates WHERE templateid = ? AND deleted = 0", array($templateid));
		
		if ($templateNameResult && $adb->num_rows($templateNameResult)) {
			$row = $adb->fetchByAssoc($templateNameResult, -1, false);
			$templateName = $row['templatename'];
		}
		
		return $templateName;
	}
	
	public function updateGDPR($id, $data) {
		global $adb, $table_prefix;
		
		$BU = BusinessUnit::getInstance();
		$business = $BU->getBusinessInfo($id);
		if (!$business) return false;
		
		$vteProp = VTEProperties::getInstance();
		
		$config = array();
		
		$config['webservice_endpoint'] = $data['webservice_endpoint'];
		$config['webservice_username'] = $data['webservice_username'];
		$config['webservice_accesskey'] = $data['webservice_accesskey'];
		$config['default_language'] = $data['default_language'];
		$config['website_logo'] = $data['website_logo'];
		$config['sender_name'] = $data['sender_name'];
		$config['sender_email'] = $data['sender_email'];
		$config['noconfirm_deletion_months'] = $data['noconfirm_deletion_months'];
		
		$vteProp->set("services.gdpr.config.business.{$id}", $config);
		
		$templates = array();
		$templates['support_request_template'] = $data['templates']['support_request_template'];
		$templates['access_template'] = $data['templates']['access_template'];
		$templates['confirm_update_template'] = $data['templates']['confirm_update_template'];
		$templates['contact_updated_template'] = $data['templates']['contact_updated_template'];
		
		$vteProp->set("services.gdpr.templates.business.{$id}", $templates);
		
		$type = 'Company';
		
		$PPU = PrivacyPolicyUtils::getInstance();
		$ok = $PPU->save($id, $type, $data['privacy_policy']);
		
		return true;
	}
	
	public function prepareDataFromRequest() {
		$data = array(
			'webservice_endpoint' => vtlib_purify($_REQUEST['webservice_endpoint']),
			'webservice_username' => vtlib_purify($_REQUEST['webservice_username']),
			'webservice_accesskey' => vtlib_purify($_REQUEST['webservice_accesskey']),
			'default_language' => vtlib_purify($_REQUEST['default_language']),
			'website_logo' => vtlib_purify($_REQUEST['website_logo']),
			'sender_name' => vtlib_purify($_REQUEST['sender_name']),
			'sender_email' => vtlib_purify($_REQUEST['sender_email']),
			'noconfirm_deletion_months' => vtlib_purify($_REQUEST['noconfirm_deletion_months']),
			'privacy_policy' => vtlib_purify($_REQUEST['privacy_policy']),
		);
		
		$templates = array();
		
		foreach ($_REQUEST as $k => $v) {
			if (substr($k, -9) === '_template') {
				$templates[$k] = intval($v);
			}
		}
		
		$data['templates'] = $templates;
		
		return $data;
	}
	
	public function saveGeneralSettings($request) {
		global $adb, $table_prefix;
		
		$vteProp = VTEProperties::getInstance();
		
		$config = array();
		
		$defaultBusiness = $request['default_business'];
		
		$BU = BusinessUnit::getInstance();
		$business = $BU->getBusinessInfo($defaultBusiness);
		if (!$business) return false;
		
		$config['default_business'] = $request['default_business'];
		
		$vteProp->set('services.gdpr.general_settings', $config);
		
		return true;
	}
	
}
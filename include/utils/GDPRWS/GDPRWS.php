<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 cmrv@163697

require_once('include/BaseClasses.php');
require_once('modules/Morphsuit/utils/RSA/Crypt/Random.php');
require_once('vtlib/Vtecrm/SettingsBlock.php');
require_once('vtlib/Vtecrm/SettingsField.php');
require_once('modules/Update/Update.php');

class GDPRWS extends SDKExtendableUniqueClass {
	
	public static $supportedModules = array('Contacts', 'Leads');
	
	public $availableFields = array(
		'Contacts' => array(
			'salutation', 'firstname', 'phone', 'lastname', 'mobile', 'homephone',
			'otherphone', 'title', 'fax', 'department', 'birthday', 'assistant',
			'assistantphone', 'mailingstreet', 'mailingcity', 'mailingzip', 'mailingstate',
			'mailingcountry', 'mailingpobox', 'otherstreet', 'othercity', 'otherzip',
			'otherstate', 'othercountry', 'otherpobox',
		),
		'Leads' => array(
			'firstname', 'lastname', 'mobile', 'company', 'designation', 'website', 'annualrevenue',
			'noofemployees', 'lane', 'city', 'code', 'state', 'country', 'pobox'
		),
	);
	
	public $gdprFields = array(
		'gdpr_privacypolicy', 'gdpr_personal_data', 'gdpr_marketing', 'gdpr_thirdparties',
		'gdpr_profiling', 'gdpr_restricted', 'gdpr_notifychange',
	);
	
	public $emailFields = array();
	
	protected $cid = null;
	protected $cidData = null;
	
	protected $authTable = null;
	protected $logTable = null;
	protected $confirmTable = null;
	
	protected $senderName = null;
	protected $senderEmail = null;
	protected $templates = null;
	
	protected $noconfirm_deletion_months = null;
	public $default_noconfirm_deletion_months = 12;
	
	public function __construct() {
		global $table_prefix;
		
		$this->emailFields = array(
			'Accounts' => array('fieldname' => 'email1', 'tablename' => $table_prefix.'_account', 'columnname' => 'email1'),
			'Contacts' => array('fieldname' => 'email', 'tablename' => $table_prefix.'_contactdetails', 'columnname' => 'email'),
			'Leads' => array('fieldname' => 'email', 'tablename' => $table_prefix.'_leaddetails', 'columnname' => 'email'),
		);
		
		$this->authTable = $table_prefix.'_gdpr_auth';
		$this->logTable = $table_prefix.'_gdpr_log';
		$this->confirmTable = $table_prefix.'_gdpr_confirm_queue';
	}
	
	public function install() {
		global $adb, $table_prefix, $site_URL, $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;
		
		$vteProp = VTEProperties::getInstance();
		
		$BU = BusinessUnit::getInstance();
		$businessList = $BU->getBusinessList();
		
		$prop = $vteProp->get('services.gdpr.general_settings');
		
		if ($prop === null) {
			$generalSettings = array();
			$generalSettings['default_business'] = $businessList[0]['organizationid'];
			$vteProp->set('services.gdpr.general_settings', $generalSettings);
		}
		
		foreach ($businessList as $business) {
			$bid = $business['organizationid'];
			
			$prop = $vteProp->get("services.gdpr.config.business.{$bid}");
			
			if ($prop === null) {
				$config = array();
				
				$config['webservice_endpoint'] = $site_URL;
				
				$user = Users::getActiveAdminUser();
				$username = $user->column_fields['user_name'];
				$accesskey = $user->column_fields['accesskey'];
				
				$config['webservice_username'] = $username;
				$config['webservice_accesskey'] = $accesskey;
				$config['default_language'] = 'en';
				
				$config['sender_name'] = $HELPDESK_SUPPORT_NAME;
				$config['sender_email'] = $HELPDESK_SUPPORT_EMAIL_ID;
				$config['noconfirm_deletion_months'] = $this->default_noconfirm_deletion_months;
				
				$logo = '';
				
				$companyDetailsResult = $adb->pquery("SELECT logoname FROM {$table_prefix}_organizationdetails WHERE organizationid = ?", array($bid));
				if ($companyDetailsResult && $adb->num_rows($companyDetailsResult)) {
					$companyDetails = $adb->fetchByAssoc($companyDetailsResult, -1, false);
					$logo = $companyDetails['logoname'];
					$logo = $site_URL.'/storage/logo/'.$logo;
				}
				
				$config['website_logo'] = $logo;
				
				$vteProp->set("services.gdpr.config.business.{$bid}", $config);
			}
			
			$prop = $vteProp->get("services.gdpr.templates.business.{$bid}");
			
			if ($prop === null) {
				$templates = array();
				
				$templates['support_request_template'] = $this->createTemplateEmail('support_request_template', 'GDPR - Support request template - EN', 'New support request', 'Template used for support request', 'en');
				$this->createTemplateEmail('support_request_template', 'GDPR - Template richiesta supporto - IT', 'Nuova richiesta di supporto', 'Template utilizzato per le richieste di supporto', 'it');
				
				$templates['access_template'] = $this->createTemplateEmail('access_template', 'GDPR - Access template - EN', 'Access to manage your contact', 'Template used for sending the access details to the contact', 'en');
				$this->createTemplateEmail('access_template', 'GDPR - Template accesso - IT', 'Accesso per la gestione del tuo contatto', 'Template utilizzato per l\'invio dell\'accesso al contatto', 'it');
				
				$templates['confirm_update_template'] = $this->createTemplateEmail('confirm_update_template', 'GDPR - Confirm update template - EN', 'Confirm your contact\'s changes', 'Template used for confirming the contact update', 'en');
				$this->createTemplateEmail('confirm_update_template', 'GDPR - Template conferma modifiche - IT', 'Conferma le modifiche del tuo contatto', 'Template utilizzato per la conferma dell\'aggiornamento del contatto', 'it');
				
				$templates['contact_updated_template'] = $this->createTemplateEmail('contact_updated_template', 'GDPR - Contact updated template - EN', 'Contact update', 'Template used for sending update notifications to the contact', 'en');
				$this->createTemplateEmail('contact_updated_template', 'GDPR - Template dati contatto aggiornati - IT', 'Aggiornamento contatto', 'Template utilizzato per inviare le notifiche di cambio dati al contatto', 'it');
				
				$vteProp->set("services.gdpr.templates.business.{$bid}", $templates);
			}
			
			$PPU = PrivacyPolicyUtils::getInstance();
			$ok = $PPU->save($bid, 'Company', file_get_contents('include/utils/GDPRWS/templates/privacy_policy.html'));
		}
		
		$this->checkTables();
		
		$this->createTemplateEmail('gdpr_verify_newsletter', 'GDPR - Default newsletter - EN', 'Verify your contact', 'The default template used for sending GDPR newsletter', 'en', 'Newsletter');
		$this->createTemplateEmail('gdpr_verify_newsletter', 'GDPR - Default newsletter - IT', 'Verifica il tuo contatto', 'Template di default utilizzato per l\'invio della newsletter GDPR', 'it', 'Newsletter');
		
		$translations = array();
		
		$gdprModules = self::$supportedModules;
		$GDPRFields = array('gdpr_privacypolicy', 'gdpr_personal_data', 'gdpr_marketing', 'gdpr_thirdparties', 'gdpr_profiling', 'gdpr_restricted', 'gdpr_notifychange', 'gdpr_deleted');
		
		foreach ($gdprModules as $module) {
			$moduleInstance = Vtecrm_Module::getInstance($module);
			
			$blockInstance = Vtecrm_Block::getInstance('LBL_GDPR_INFORMATION', $moduleInstance);
			if (!$blockInstance) {
				$blockInstance = new Vtecrm_Block();
				$blockInstance->label = 'LBL_GDPR_INFORMATION';
				$moduleInstance->addBlock($blockInstance);
			}
			
			$fields = array();
			
			foreach ($GDPRFields as $field) {
				$label = 'LBL_'.strtoupper($field);
				$fields[] = array('module' => $module, 'block' => 'LBL_GDPR_INFORMATION', 'name' => $field, 'label' => $label, 'uitype' => '56', 'readonly' => '99', 'columntype' => 'CHAR(1)', 'typeofdata' => 'C~O', 'quickcreate' => 1); // crmv@187404
				$fields[] = array('module' => $module, 'block' => 'LBL_GDPR_INFORMATION', 'name' => $field.'_checkedtime', 'label' => $label.'_CHECKEDTIME', 'uitype' => '1', 'readonly' => '99', 'columntype' => 'DATETIME', 'typeofdata' => 'V~O', 'quickcreate' => 1); // crmv@187404
				$fields[] = array('module' => $module, 'block' => 'LBL_GDPR_INFORMATION', 'name' => $field.'_remote_addr', 'label' => $label.'_REMOTE_ADDRESS', 'uitype' => '1', 'readonly' => '99', 'columntype' => 'C(45)', 'typeofdata' => 'V~O', 'quickcreate' => 1); // crmv@187404
			}
			
			$fields[] = array('module' => $module, 'block' => 'LBL_GDPR_INFORMATION', 'name' => 'gdpr_sentdate', 'label' => 'LBL_GDPR_SENTTIME', 'uitype' => '1', 'readonly' => '99', 'columntype' => 'DATETIME', 'typeofdata' => 'V~O', 'quickcreate' => 1); // crmv@187404
			
			Update::create_fields($fields);
			
			$translations[$module]['it_it'] = array(
				'LBL_GDPR_INFORMATION' => 'Informazioni GDPR',
				'LBL_GDPR_PRIVACYPOLICY' => 'Visione Informativa',
				'LBL_GDPR_PRIVACYPOLICY_CHECKEDTIME' => 'Data Visione Informativa',
				'LBL_GDPR_PRIVACYPOLICY_REMOTE_ADDRESS' => 'Indirizzo IP Visione Informativa',
				'LBL_GDPR_PERSONAL_DATA' => 'Consenso Dati Personali',
				'LBL_GDPR_PERSONAL_DATA_CHECKEDTIME' => 'Data Consenso Dati Personali',
				'LBL_GDPR_PERSONAL_DATA_REMOTE_ADDRESS' => 'Indirizzo IP Dati Personali',
				'LBL_GDPR_MARKETING' => 'Consenso Marketing',
				'LBL_GDPR_MARKETING_CHECKEDTIME' => 'Data Consenso Marketing',
				'LBL_GDPR_MARKETING_REMOTE_ADDRESS' => 'Indirizzo IP Consenso Marketing',
				'LBL_GDPR_THIRDPARTIES' => 'Consenso Terze Parti',
				'LBL_GDPR_THIRDPARTIES_CHECKEDTIME' => 'Data Consenso Terze Parti',
				'LBL_GDPR_THIRDPARTIES_REMOTE_ADDRESS' => 'Indirizzo IP Consenso Terze Parti',
				'LBL_GDPR_PROFILING' => 'Consenso Profilazione',
				'LBL_GDPR_PROFILING_CHECKEDTIME' => 'Data Consenso Profilazione',
				'LBL_GDPR_PROFILING_REMOTE_ADDRESS' => 'Indirizzo IP Consenso Profilazione',
				'LBL_GDPR_RESTRICTED' => 'Consenso Comunicazione Dati Ambiti Informativa',
				'LBL_GDPR_RESTRICTED_CHECKEDTIME' => 'Data Consenso Comunicazione Dati Ambiti Informativa',
				'LBL_GDPR_RESTRICTED_REMOTE_ADDRESS' => 'Indirizzo IP Consenso Comunicazione Dati Ambiti Informativa',
				'LBL_GDPR_NOTIFYCHANGE' => 'Avvisa Cambio Dati',
				'LBL_GDPR_NOTIFYCHANGE_CHECKEDTIME' => 'Data Consenso Avvisa Cambio Dati',
				'LBL_GDPR_NOTIFYCHANGE_REMOTE_ADDRESS' => 'Indirizzo IP Avvisa Cambio Dati',
				'LBL_GDPR_DELETED' => 'Eliminato',
				'LBL_GDPR_DELETED_CHECKEDTIME' => 'Data Eliminazione',
				'LBL_GDPR_DELETED_REMOTE_ADDRESS' => 'Indirizzo IP Eliminazione',
				'LBL_GDPR_SENTTIME' => 'Data Invio GDPR',
			);
			
			$translations[$module]['en_us'] = array(
				'LBL_GDPR_INFORMATION' => 'GDPR Information',
				'LBL_GDPR_PRIVACYPOLICY' => 'Privacy Policy',
				'LBL_GDPR_PRIVACYPOLICY_CHECKEDTIME' => 'Privacy Policy - Date',
				'LBL_GDPR_PRIVACYPOLICY_REMOTE_ADDRESS' => 'Privacy Policy - IP Address',
				'LBL_GDPR_PERSONAL_DATA' => 'Consent to Personal Data',
				'LBL_GDPR_PERSONAL_DATA_CHECKEDTIME' => 'Consent to Personal Data - Date',
				'LBL_GDPR_PERSONAL_DATA_REMOTE_ADDRESS' => 'Consent to Personal Data - IP Address',
				'LBL_GDPR_MARKETING' => 'Consent to Marketing',
				'LBL_GDPR_MARKETING_CHECKEDTIME' => 'Consent to Marketing - Date',
				'LBL_GDPR_MARKETING_REMOTE_ADDRESS' => 'Consent to Marketing - IP Address',
				'LBL_GDPR_THIRDPARTIES' => 'Consent to Third Parties',
				'LBL_GDPR_THIRDPARTIES_CHECKEDTIME' => 'Consent to Third Parties - Date',
				'LBL_GDPR_THIRDPARTIES_REMOTE_ADDRESS' => 'Consent to Third Parties - IP Address',
				'LBL_GDPR_PROFILING' => 'Consent to Profiling',
				'LBL_GDPR_PROFILING_CHECKEDTIME' => 'Consent to Profiling - Date',
				'LBL_GDPR_PROFILING_REMOTE_ADDRESS' => 'Consent to Profiling - IP Address',
				'LBL_GDPR_RESTRICTED' => 'Consent only to specified institutions',
				'LBL_GDPR_RESTRICTED_CHECKEDTIME' => 'Consent only to specified institutions - Date',
				'LBL_GDPR_RESTRICTED_REMOTE_ADDRESS' => 'Consent only to specified institutions - IP Address',
				'LBL_GDPR_NOTIFYCHANGE' => 'Notify contact changes',
				'LBL_GDPR_NOTIFYCHANGE_CHECKEDTIME' => 'Notify contact changes - Date',
				'LBL_GDPR_NOTIFYCHANGE_REMOTE_ADDRESS' => 'Notify contact changes - IP Address',
				'LBL_GDPR_DELETED' => 'Deleted',
				'LBL_GDPR_DELETED_CHECKEDTIME' => 'Deleted - Date',
				'LBL_GDPR_DELETED_REMOTE_ADDRESS' => 'Deleted - IP Address',
				'LBL_GDPR_SENTTIME' => 'GDPR Sent Date',
			);
		}
		
		$block = Vtecrm_SettingsBlock::getInstance('LBL_COMMUNICATION_TEMPLATES');
		
		$res = $adb->pquery("SELECT fieldid FROM {$table_prefix}_settings_field WHERE name = ?", array('LBL_GDPR'));
		if ($block && $res && $adb->num_rows($res) == 0) {
			$field = new Vtecrm_SettingsField();
			$field->name = 'LBL_GDPR';
			$field->iconpath = 'themes/images/PrivacySettings.png';
			$field->description = 'LBL_GDPR_DESCRIPTION';
			$field->linkto = 'index.php?module=Settings&action=GDPRConfig&parenttab=Settings';
			$block->addField($field);
		}
		
		$result = $adb->pquery("SELECT campaigntype FROM {$table_prefix}_campaigntype WHERE campaigntype = ?", array('GDPR'));
		if ($result && $adb->num_rows($result) < 1) {
			$field = Vtecrm_Field::getInstance('campaigntype', Vtecrm_Module::getInstance('Campaigns'));
			if ($field) {
				$field->setPicklistValues(array('GDPR'));
			}
		}
		
		$translations['Newsletter'] = array(
			'it_it' => array(
				'LBL_GDPR_AND_PRICAY_POLICY' => 'GDPR e informativa privacy',
				'LBL_GDPR_VERIFY_LINK' => 'GDPR Accesso - Link di verifica',
				'LBL_GDPR_ACCESS_LINK' => 'GDPR Accesso - Link di accesso',
				'LBL_GDPR_CONFIRM_LINK' => 'GDPR Aggiornamento - Link di conferma',
				'LBL_GDPR_SUPPORT_REQUEST_SENDER' => 'GDPR Richiesta supporto - Mittente',
				'LBL_GDPR_SUPPORT_REQUEST_SUBJECT' => 'GDPR Richiesta supporto - Oggetto',
				'LBL_GDPR_SUPPORT_REQUEST_DESC' => 'GDPR Richiesta supporto - Descrizione',
			),
			'en_us' => array(
				'LBL_GDPR_AND_PRICAY_POLICY' => 'GDPR and privacy policy',
				'LBL_GDPR_VERIFY_LINK' => 'GDPR Access - Verify link',
				'LBL_GDPR_ACCESS_LINK' => 'GDPR Access - Access link',
				'LBL_GDPR_CONFIRM_LINK' => 'GDPR Update - Confirm link',
				'LBL_GDPR_SUPPORT_REQUEST_SENDER' => 'GDPR Support Request - Sender',
				'LBL_GDPR_SUPPORT_REQUEST_SUBJECT' => 'GDPR Support Request - Subject',
				'LBL_GDPR_SUPPORT_REQUEST_DESC' => 'GDPR Support Request - Description',
			),
		);
		
		$translations['APP_STRINGS'] = array(
			'it_it' => array(
				'LBL_GDPR_ANONYMIZE' => 'Anonimizza',
			),
			'en_us' => array(
				'LBL_GDPR_ANONYMIZE' => 'Anonymize',
			),
		);
		
		$translations['Settings'] = array(
			'it_it' => array(
				'GDPR' => 'GDPR',
				'LBL_GDPR' => 'GDPR',
				'LBL_GDPR_DESCRIPTION' => 'Configura le impostazioni del GDPR',
				'LBL_WEBSERVICE' => 'Webservice',
				'LBL_WEBSERVICE_ENDPOINT' => 'Webservice endpoint',
				'LBL_WEBSERVICE_USERNAME' => 'Webservice username',
				'LBL_WEBSERVICE_ACCESSKEY' => 'Webservice accesskey',
				'LBL_DEFAULT_LANGUAGE' => 'Lingua di default',
				'LBL_WEBSITE_LOGO' => 'Logo di default',
				'LBL_SENDER_NAME' => 'Nome mittente',
				'LBL_SENDER_EMAIL' => 'Email mittente',
				'LBL_TEMPLATES' => 'Template',
				'LBL_PRIVACY_POLICY' => 'Informativa Privacy',
				'LBL_WEBSERVICE_ENDPOINT_DESC' => 'L\'URL dove &egrave; installato il CRM',
				'LBL_WEBSERVICE_USERNAME_DESC' => 'Utente utilizzato per le chiamate Webservice',
				'LBL_WEBSERVICE_ACCESSKEY_DESC' => 'Accesskey dell\'utente utilizzato per le chiamate Webservice',
				'LBL_WEBSITE_LOGO_DESC' => 'Il logo di default utilizzato nell\'app',
				'LBL_SENDER_NAME_DESC' => 'Il nome del mittente utilizzato per le comunicazioni GDPR',
				'LBL_SENDER_EMAIL_DESC' => 'L\'email del mittente utilizzata per le comunicazioni GDPR',
				'LBL_ENGLISH_LANG' => 'EN English',
				'LBL_ITALIAN_LANG' => 'IT Italiano',
				'LBL_DEFAULT_LANGUAGE_DESC' => 'La lingua di default utilizzata nell\'app',
				'LBL_SUPPORT_REQUEST_TEMPLATE' => 'Template richiesta supporto',
				'LBL_SUPPORT_REQUEST_TEMPLATE_DESC' => 'Template utilizzato per le richieste di supporto',
				'LBL_ACCESS_TEMPLATE' => 'Template accesso',
				'LBL_ACCESS_TEMPLATE_DESC' => 'Template utilizzato per l\'invio dell\'accesso al contatto',
				'LBL_CONFIRM_UPDATE_TEMPLATE' => 'Template di richiesta conferma',
				'LBL_CONFIRM_UPDATE_TEMPLATE_DESC' => 'Template utilizzato per la conferma dell\'aggiornamento del contatto',
				'LBL_CONTACT_UPDATED_TEMPLATE' => 'Template modifiche contatto',
				'LBL_CONTACT_UPDATED_TEMPLATE_DESC' => 'Template utilizzato per inviare le notifiche di cambio dati al contatto',
				'LBL_GDPR_VERIFY_LINK' => 'GDPR Accesso - Link di verifica',
				'LBL_GDPR_ACCESS_LINK' => 'GDPR Accesso - Link di accesso',
				'LBL_GDPR_CONFIRM_LINK' => 'GDPR Aggiornamento - Link di conferma',
				'LBL_GDPR_SUPPORT_REQUEST_SENDER' => 'GDPR Richiesta supporto - Mittente',
				'LBL_GDPR_SUPPORT_REQUEST_SUBJECT' => 'GDPR Richiesta supporto - Oggetto',
				'LBL_GDPR_SUPPORT_REQUEST_DESC' => 'GDPR Richiesta supporto - Descrizione',
				'CompanyDetails' => 'Dettagli societa`',
				'LBL_ANONYMOUS' => 'Anonymous',
				'LBL_GDPR_NOTIFY_ANONYMIZE_SUBJECT'=>'Anonimizzazione contatto',
				'LBL_GDPR_NOTIFY_ANONYMIZE_BODY'=>'E\' stata effettuata l\'anonimizzazione di %s, %s, %s.<br>Entro il %s devi assicurarti che vengano eliminati i suoi dati anche da eventuali supporti cartacei o esterni.<br>Ricordati di cancellare anche questa email!',
				'LBL_NOCONFIRM_DELETION_MOTHS' => 'Mesi attesa conferma',
				'LBL_NOCONFIRM_DELETION_MOTHS_DESC' => 'Il numero di mesi dopo il quale il contatto verra` anonimizzato',
				'LBL_GENERAL_SETTINGS' => 'Impostazioni generali',
				'LBL_DEFAULT_BUSINESS_UNIT' => 'Default Business Unit',
				'LBL_DEFAULT_BUSINESS_UNIT_DESC' => 'La business unit di default utilizzata come fallback',
			),
			'en_us' => array(
				'GDPR' => 'GDPR',
				'LBL_GDPR' => 'GDPR',
				'LBL_GDPR_DESCRIPTION' => 'Configure the GDPR settings',
				'LBL_WEBSERVICE' => 'Webservice',
				'LBL_WEBSERVICE_ENDPOINT' => 'Webservice endpoint',
				'LBL_WEBSERVICE_USERNAME' => 'Webservice username',
				'LBL_WEBSERVICE_ACCESSKEY' => 'Webservice access key',
				'LBL_DEFAULT_LANGUAGE' => 'Default language',
				'LBL_WEBSITE_LOGO' => 'Default logo',
				'LBL_SENDER_NAME' => 'Sender name',
				'LBL_SENDER_EMAIL' => 'Sender email',
				'LBL_TEMPLATES' => 'Template',
				'LBL_PRIVACY_POLICY' => 'Privacy Policy',
				'LBL_WEBSERVICE_ENDPOINT_DESC' => 'The URL where CRM is installed',
				'LBL_WEBSERVICE_USERNAME_DESC' => 'User employed for the Webservice calls',
				'LBL_WEBSERVICE_ACCESSKEY_DESC' => 'User Access key used for the Webservice calls',
				'LBL_WEBSITE_LOGO_DESC' => 'The default logo used in the app',
				'LBL_SENDER_NAME_DESC' => 'The sender name used for GDPR communication',
				'LBL_SENDER_EMAIL_DESC' => 'The sender email used for GDPR communication',
				'LBL_ENGLISH_LANG' => 'EN English',
				'LBL_ITALIAN_LANG' => 'IT Italiano',
				'LBL_DEFAULT_LANGUAGE_DESC' => 'The default language used in the app',
				'LBL_SUPPORT_REQUEST_TEMPLATE' => 'Support request Template',
				'LBL_SUPPORT_REQUEST_TEMPLATE_DESC' => 'Template used for support request',
				'LBL_ACCESS_TEMPLATE' => 'Access Template',
				'LBL_ACCESS_TEMPLATE_DESC' => 'Template used for sending the access details to the contact',
				'LBL_CONFIRM_UPDATE_TEMPLATE' => 'Confirm update template',
				'LBL_CONFIRM_UPDATE_TEMPLATE_DESC' => 'Template used for confirming the contact update',
				'LBL_CONTACT_UPDATED_TEMPLATE' => 'Contact updated template',
				'LBL_CONTACT_UPDATED_TEMPLATE_DESC' => 'Template used for sending update notifications to the contact',
				'LBL_GDPR_VERIFY_LINK' => 'GDPR Access - Verify link',
				'LBL_GDPR_ACCESS_LINK' => 'GDPR Access - Access link',
				'LBL_GDPR_CONFIRM_LINK' => 'GDPR Update - Confirm link',
				'LBL_GDPR_SUPPORT_REQUEST_SENDER' => 'GDPR Support Request - Sender',
				'LBL_GDPR_SUPPORT_REQUEST_SUBJECT' => 'GDPR Support Request - Subject',
				'LBL_GDPR_SUPPORT_REQUEST_DESC' => 'GDPR Support Request - Description',
				'CompanyDetails' => 'Company details',
				'LBL_ANONYMOUS' => 'Anonymous',
				'LBL_GDPR_NOTIFY_ANONYMIZE_SUBJECT'=>'Contact anonymization',
				'LBL_GDPR_NOTIFY_ANONYMIZE_BODY'=>'Has been made the anonymization of %s, %s and %s was made.<br>Within the %s you must ensure that your data is also deleted from any paper or external media.<br>Remember to also delete this email!',
				'LBL_NOCONFIRM_DELETION_MOTHS' => 'Number of waiting months for confirm',
				'LBL_NOCONFIRM_DELETION_MOTHS_DESC' => 'The number of months after which the contact will be anonymised',
				'LBL_GENERAL_SETTINGS' => 'General settings',
				'LBL_DEFAULT_BUSINESS_UNIT' => 'Default Business Unit',
				'LBL_DEFAULT_BUSINESS_UNIT_DESC' => 'The default business unit used as fallback',
			),
		);
		
		$languages = vtlib_getToggleLanguageInfo();
		foreach ($translations as $module => $modlang) {
			foreach ($modlang as $lang => $translist) {
				if (array_key_exists($lang, $languages)) {
					foreach ($translist as $label => $translabel) {
						SDK::setLanguageEntry($module, $lang, $label, $translabel);
					}
				}
			}
		}
		
		$this->initCustomWebserviceOperations();
		
		if (Vtecrm_Event::hasSupport()) {
			Vtecrm_Event::register('', 'vte.entity.beforesave', 'GDPRHandler', 'include/utils/GDPRWS/handlers/GDPRHandler.php');//crmv@207852
		}
		
		$focus = ModNotifications::getInstance(); // crmv@164122
		$focus->addNotificationType('GDPR_INSTALLED', 'GDPR_INSTALLED', 0);
		
		$notifications = array(
			'it_it' => "E' stato installato un importante aggiornamento relativo alla gestione del GDPR. Scopri come permettere ai tuoi contatti la tutela delle loro informazioni personali in tuo possesso consultando la <a href=\"http://www.vtenext.com/utilizzo-del-modulo-gdpr-vtenext/\">guida online</a>. Per maggiori informazioni sul GDPR in generale <a href=\"https://en.wikipedia.org/wiki/General_Data_Protection_Regulation\">clicca qui</a>.",
			'en_us' => "An important update concerning the management of the GDPR has been installed. Find out how to allow your contacts to protect their personal information in your possession by consulting the <a href=\"http://www.vtenext.com/en/utilizzo-del-modulo-gdpr-vtenext/\">online guide</a>. For more information on GDPR in general <a href=\"https://en.wikipedia.org/wiki/General_Data_Protection_Regulation\">click here</a>.",
		);
		
		$subjects = array(
			'it_it' => "E' stato installato un importante aggiornamento relativo alla gestione del GDPR.",
			'en_us' => "An important update concerning the management of the GDPR has been installed.",
		);
		
		SDK::setLanguageEntry('ModNotifications', 'it_it', 'GDPR_INSTALLED', $notifications['it_it']);
		SDK::setLanguageEntry('ModNotifications', 'en_us', 'GDPR_INSTALLED', $notifications['en_us']);
		
		$users = array();
		$usersLang = array();
		
		$userListResult = $adb->pquery("SELECT id, default_language FROM {$table_prefix}_users WHERE status = ? AND is_admin = ?", array('Active', 'on'));
		if ($userListResult && $adb->num_rows($userListResult)) {
			while ($row = $adb->fetchByAssoc($userListResult, -1, false)) {
				$users[] = $row['id'];
				$usersLang[$row['id']] = $row['default_language'];
			}
		}
		
		$focus = ModNotifications::getInstance(); // crmv@164122
		
		if (!empty($users)) {
			$alreadyNotifiedUsers = array();
			foreach ($users as $user) {
				if (in_array($user, $alreadyNotifiedUsers)) {
					continue;
				}
				
				$LU = LanguageUtils::getInstance();
				$LU->changeCurrentLanguage($usersLang[$user]);
				
				$notifiedUsers = $focus->saveFastNotification(array(
					'assigned_user_id' => $user,
					'related_to' => '',
					'mod_not_type' => 'GDPR_INSTALLED',
					'subject' => $subjects[$usersLang[$user]],
					'description' => getTranslatedString('GDPR_INSTALLED', 'ModNotifications'),
					'createdtime' => date('Y-m-d H:i:s'),
					'modifiedtime' => date('Y-m-d H:i:s'),
				));
				
				$LU->restoreCurrentLanguage($usersLang[$user]);
				
				if (!empty($notifiedUsers)) {
					foreach ($notifiedUsers as $notifiedUser) {
						$alreadyNotifiedUsers[] = $notifiedUser;
					}
				}
			}
		}
		
		$contactsInstance = Vtecrm_Module::getInstance('Contacts');
		Vtecrm_Link::addLink($contactsInstance->id, 'DETAILVIEWBASIC', 'LBL_GDPR_ANONYMIZE', 'javascript:gdprAnonymize(\'$MODULE$\',$RECORD$);');
		
		$leadsInstance = Vtecrm_Module::getInstance('Leads');
		Vtecrm_Link::addLink($leadsInstance->id, 'DETAILVIEWBASIC', 'LBL_GDPR_ANONYMIZE', 'javascript:gdprAnonymize(\'$MODULE$\',$RECORD$);');
		
		$result = $adb->pquery("SELECT * FROM {$table_prefix}_cronjobs WHERE cronname = ?", array('GDPR'));
		if ($adb->num_rows($result) == 0) {
			require_once('include/utils/CronUtils.php');
			$CU = CronUtils::getInstance();
			$cj = new CronJob();
			$cj->name = 'GDPR';
			$cj->active = 1;
			$cj->singleRun = false;
			$cj->fileName = 'cron/modules/Newsletter/GDPR.service.php';
			$cj->timeout = 5400;
			$cj->repeat = 14400; // repeat every 4 hours
			$CU->insertCronJob($cj);
		}

		$this->updateConvertLead(); // crmv@194712
	}
	
	public function createTemplateEmail($name, $label, $subject, $description, $language, $type = 'Email') {
		global $adb, $table_prefix;
		
		$emailTemplatesResult = $adb->pquery("SELECT * FROM {$table_prefix}_emailtemplates WHERE templatename = ? AND deleted = 0", array($label));
		
		if ($emailTemplatesResult && $adb->num_rows($emailTemplatesResult)) {
			$row = $adb->fetchByAssoc($emailTemplatesResult, -1, false);
			return $row['templateid'];
		}
		
		$templateFile = 'include/utils/GDPRWS/templates/'.$name.'_'.$language.'.html';
		
		if (!(file_exists($templateFile) && is_readable($templateFile))) {
			return false;
		}
		
		$id = $adb->getUniqueID($table_prefix . '_emailtemplates');
		$body = file_get_contents($templateFile);
		
		$params = array(
			'foldername' => 'Public',
			'templatename' => $label,
			'subject' => $subject,
			'description' => $description,
			'body' => $adb->getEmptyClob(false),
			'deleted' => 0,
			'templateid' => $id,
			'templatetype' => $type,
			'overwrite_message' => 1,
		);
		
		$columns = array_keys($params);
		$adb->format_columns($columns);
		$adb->pquery("INSERT INTO {$table_prefix}_emailtemplates (" . implode(',', $columns) . ") VALUES (" . generateQuestionMarks($params) . ")", $params);
		
		$adb->updateClob($table_prefix . '_emailtemplates', 'body', "templateid=$id", $body);
		
		return $id;
	}
	
	public function checkTables() {
		global $adb, $table_prefix;
		
		$schema_table = '<schema version="0.3">
			<table name="' . $this->authTable . '">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="contactid" type="R" size="19">
					<KEY/>
				</field>
				<field name="authtoken" type="C" size="63">
					<KEY/>
				</field>
				<field name="authtoken_expire" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="authtoken_createdtime" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="authtoken_remote_addr" type="C" size="45" />
				<field name="authtoken_confirmed" type="I" size="1" />
				<field name="authtoken_confirmedtime" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="accesstoken" type="C" size="63" />
				<field name="accesstoken_expire" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="accesstoken_createdtime" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="accesstoken_remote_addr" type="C" size="45" />
				<field name="accesstoken_confirmed" type="I" size="1" />
				<field name="accesstoken_confirmedtime" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<index name="authtoken_expire_idx">
					<col>authtoken_expire</col>
				</index>
				<index name="accesstoken_expire_idx">
					<col>accesstoken_expire</col>
				</index>
			</table>
		</schema>';
		
		if (!Vtecrm_Utils::CheckTable($this->authTable)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		
		$schema_table = '<schema version="0.3">
			<table name="' . $this->logTable . '">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="logid" type="R" size="19">
					<KEY/>
				</field>
				<field name="contactid" type="I" size="19" />
				<field name="timestamp" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="operation" type="C" size="63" />
				<field name="operation_remote_addr" type="C" size="45" />
				<field name="data" type="XL" />
				<index name="timestamp_idx">
					<col>timestamp</col>
				</index>
				<index name="operation_idx">
					<col>operation</col>
				</index>
			</table>
		</schema>';
		
		if (!Vtecrm_Utils::CheckTable($this->logTable)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		
		$schema_table = '<schema version="0.3">
			<table name="' . $this->confirmTable . '">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="queueid" type="R" size="19">
					<KEY/>
				</field>
				<field name="contactid" type="I" size="19" />
				<field name="accesstoken" type="C" size="63" />
				<field name="timestamp" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="operation" type="C" size="63" />
				<field name="operation_remote_addr" type="C" size="45" />
				<field name="data" type="XL" />
				<field name="token" type="C" size="63" />
				<field name="token_expire" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="token_createdtime" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
				<field name="token_remote_addr" type="C" size="45" />
				<field name="token_confirmed" type="I" size="1" />
				<field name="token_confirmedtime" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
				</field>
			</table>
		</schema>';
		
		if (!Vtecrm_Utils::CheckTable($this->confirmTable)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
	}
	
	public static function isEnabledForModule($module) {
		return in_array($module, self::$supportedModules);
	}
	
	public function validateContactId($cid, &$error) {
		global $adb, $table_prefix, $current_user;
		
		$contactid = $module = $email = null;
		
		if (!is_numeric($cid)) {
			list($msgtype, $email, $module) = explode('|', base64_decode(urldecode($cid)));
		} else {
			$contactid = $cid;
			$module = getSalesEntityType($contactid);
		}
		
		if (!self::isEnabledForModule($module)) {
			return $this->validateUnsupportedModule($cid, $module, $email, $error);
		}
		
		if (empty($contactid)) {
			$column = $this->emailFields[$module]['tablename'].'.'.$this->emailFields[$module]['columnname'];
			
			$qg = QueryGenerator::getInstance($module, $current_user);
			$idcol = $qg->getSQLColumn('id', false);
			$qg->initForAllCustomView();
			$qg->addField('id');
			$qg->addFieldAlias('id', 'crmid');
			
			$query = $qg->getQuery();
			$query .= " AND $column = ? AND COALESCE(gdpr_deleted, 0) <> 1 ORDER BY crmid";
			
			$contactResult = $adb->limitpquery($query, 0, 1, array($email));
			if ($contactResult && $adb->num_rows($contactResult)) {
				$contactid = intval($adb->query_result($contactResult, 0, 'crmid'));
			} else {
				$error = 'RECORD_NOT_FOUND';
				return false;
			}
		}
		
		$focus = CRMEntity::getInstance($module);
		$ret = $focus->retrieve_entity_info_no_html($contactid, $module, false);
		$focus->id = $contactid;
		
		if ($ret == 'LBL_RECORD_DELETE') {
			$error = 'RECORD_DELETED';
			return false;
		} elseif ($ret == 'LBL_RECORD_NOT_FOUND') {
			$error = 'RECORD_NOT_FOUND';
			return false;
		}
		
		if ($focus->column_fields['gdpr_deleted'] === '1') {
			$error = 'RECORD_DELETED';
			return false;
		}
		
		$emailField = $this->emailFields[$module]['fieldname'];
		
		$availableData = array();
		
		$fields = array_flip($this->getAvailableFields($module));
		$columnFields = $focus->column_fields;
		
		foreach ($columnFields as $fieldname => $value) {
			if (isset($fields[$fieldname])) {
				$availableData[$fieldname] = $value;
			}
		}
		
		$email = $columnFields[$emailField];
		
		$gdprws = GDPRWS::getInstance();
		$bid = $gdprws->getBusinessId($contactid, $module, $email);
		$this->initBusinessData($bid);
		
		$newsletterFocus = CRMEntity::getInstance('Newsletter');
		//$availableData['gdpr_marketing'] = $newsletterFocus->receivingNewsletter($email); // crmv@181193
		
		$cid = urlencode(base64_encode("C|".$email."|".$module));
		
		$this->cid = $cid;
		$this->cidData = array();
		$this->cidData['email'] = $email;
		$this->cidData['module'] = $module;
		
		$contactData = array(
			'module' => $module,
			'contactid' => $contactid,
			'email' => $email,
			'focus' => $focus,
			'email_field' => $this->emailFields[$module],
			'available_data' => $availableData,
			'business_id' => $bid,
		);
		
		// crmv@164120
		global $current_auth_record;
		if (empty($current_auth_record)) $current_auth_record = array('module' => $module, 'id' => $contactid);
		// crmv@164120e
		
		return $contactData;
	}
	
	public function validateAccess($accesstoken, &$error) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactid = $this->validateAccessToken($accesstoken, $error);
		if (!empty($error)) return false;
		
		$contactid = intval($contactid);
		
		$error = null;
		$contactData = $this->validateContactId($contactid, $error);
		if (!empty($error)) return false;
		
		return $contactData;
	}
	
	public function validateUnsupportedModule($cid, $module, $email, &$error) {
		global $adb, $table_prefix, $current_user;
		
		$this->cid = $cid;
		$this->cidData = array();
		$this->cidData['email'] = $email;
		$this->cidData['module'] = $module;
		
		$contactData = array();
		$contactData['module'] = $module;
		$contactData['email'] = $email;
		$contactData['business_id'] = false;
		$contactData['contactid'] = false;
		
		if (isset($this->emailFields[$module])) {
			$crmid = null;
			$column = $this->emailFields[$module]['tablename'].'.'.$this->emailFields[$module]['columnname'];
			
			$qg = QueryGenerator::getInstance($module, $current_user);
			$idcol = $qg->getSQLColumn('id', false);
			$qg->initForAllCustomView();
			$qg->addField('id');
			$qg->addFieldAlias('id', 'crmid');
			
			$query = $qg->getQuery();
			$query .= " AND $column = ? ORDER BY crmid";
			
			$result = $adb->limitpquery($query, 0, 1, array($email));
			if ($result && $adb->num_rows($result)) {
				$crmid = intval($adb->query_result($result, 0, 'crmid'));
			}
			
			$contactData['contactid'] = $crmid;
			
			$bid = $this->getBusinessId($crmid, $module, $email);
			$contactData['business_id'] = $bid;
			
			$this->initBusinessData($bid);
		}
		
		$error = 'OPERATION_DENIED';
		
		return $contactData;
	}
	
	protected function initBusinessData($bid) {
		$vteProp = VTEProperties::getInstance();
		
		$config = $vteProp->get("services.gdpr.config.business.{$bid}");
		
		$this->senderName = $config['sender_name'];
		$this->senderEmail = $config['sender_email'];
		$this->noconfirm_deletion_months = $config['noconfirm_deletion_months'];
		
		$templates = $vteProp->get("services.gdpr.templates.business.{$bid}");
		$this->templates = $templates;
	}
	
	public function generateAuthToken($cid) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateContactId($cid, $error);
		if (!empty($error)) return $this->error($error);
		
		$contactid = $contactData['contactid'];
		$contractEmail = $contactData['email'];
		
		$authToken = $this->generateToken(32);
		
		$servertime = time();
		$expireTime = time() + (60*5);
		
		$params = array(
			'contactid' => $contactid,
			'authtoken' => $authToken,
			'authtoken_expire' => date('Y-m-d H:i:s', $expireTime),
			'authtoken_createdtime' => date('Y-m-d H:i:s'),
			'authtoken_remote_addr' => getIp(),
			'authtoken_confirmed' => 0,
		);
		
		$columns = array_keys($params);
		$adb->format_columns($columns);
		$adb->pquery("INSERT INTO {$this->authTable} (" . implode(',', $columns) . ") VALUES (" . generateQuestionMarks($params) . ")", $params);
		
		$this->log($contactid, 'AUTHTOKEN_GENERATED', array());
		
		return $this->success(array('email' => $contractEmail, 'token' => $authToken, 'servertime' => $servertime, 'expiretime' => $expireTime));
	}
	
	public function generateAccessToken($cid, $authtoken) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateContactId($cid, $error);
		if (!empty($error)) return $this->error($error);
		
		$contactid = $contactData['contactid'];
		$contractEmail = $contactData['email'];
		
		if ($this->validateAuthToken($contactid, $authtoken)) {
			if ($this->countActiveAccessToken($contactid) >= 4) {
				$this->log($contactid, 'ACCESSTOKEN_LIMIT_REACHED', array());
				return $this->error('ACCESSTOKEN_LIMIT_REACHED');
			}
		} else {
			$this->log($contactid, 'AUTHTOKEN_EXPIRED', array());
			return $this->error('AUTHTOKEN_EXPIRED');
		}
		
		$params = array(
			'authtoken_confirmed' => 1,
			'authtoken_confirmedtime' => date('Y-m-d H:i:s'),
		);
		
		$upd = array();
		foreach ($params as $col => $value) {
			$upd[] = "$col = ?";
		}
		
		$sql = "UPDATE {$this->authTable} SET " . implode(',', $upd) . " WHERE contactid = ? AND authtoken = ?";
		$adb->pquery($sql, array($params, $contactid, $authtoken));
		
		$this->log($contactid, 'AUTHTOKEN_CONFIRMED', array());
		
		// Let's create accesstoken
		$accessToken = $this->generateToken(32);
		
		$servertime = time();
		$expireTime = time() + (60*60*2);
		
		$params = array(
			'accesstoken' => $accessToken,
			'accesstoken_expire' => date('Y-m-d H:i:s', $expireTime),
			'accesstoken_createdtime' => date('Y-m-d H:i:s'),
			'accesstoken_remote_addr' => getIp(),
			'accesstoken_confirmed' => 0,
		);
		
		$upd = array();
		foreach ($params as $col => $value) {
			$upd[] = "$col = ?";
		}
		
		$sql = "UPDATE {$this->authTable} SET " . implode(',', $upd) . " WHERE contactid = ? AND authtoken = ?";
		$adb->pquery($sql, array($params, $contactid, $authtoken));
		
		$this->deleteOrphanToken($contactid);
		
		$this->log($contactid, 'ACCESSTOKEN_GENERATED', array());
		
		return $this->success(array('email' => $contractEmail, 'token' => $accessToken, 'servertime' => $servertime, 'expiretime' => $expireTime));
	}
	
	public function sendVerify($cid, $authtoken) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateContactId($cid, $error);
		if (!empty($error)) return $this->error($error);
		
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$contactEmail = $contactData['email'];
		
		$requestAccesstoken = $this->generateAccessToken($cid, $authtoken);
		
		if ($requestAccesstoken['success']) {
			$accesstoken = $requestAccesstoken['token'];
			$emailStatus = $this->sendAccessEmail($contactData, $accesstoken);
			if (!$emailStatus) {
				return $this->error('SEND_EMAIL_FAILED');
			}
		} else {
			return $this->error($requestAccesstoken['error']);
		}
		
		return $this->success(array('email' => $contactEmail));
	}
	
	public function checkAccessToken($accesstoken, $options = array()) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateAccess($accesstoken, $error);
		if (!empty($error)) return $this->error($error);
		
		$output = $this->formatOutputData(array(), $contactData, $options);
		
		return $this->success($output);
	}
	
	public function updateContact($accesstoken, $data, $options = array()) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateAccess($accesstoken, $error);
		if (!empty($error)) return $this->error($error);
		
		$module = $contactData['module'];
		
		$fields = array_flip($this->getAvailableFields($module));
		
		foreach ($data as $fieldname => $value) {
			if (!isset($fields[$fieldname])) {
				unset($data[$fieldname]);
			}
		}
		
		$error = null;
		$success = $this->enqueueUpdate($contactData, $accesstoken, 'UPDATE_CONTACT', $data, $error);
		if (!empty($error)) return $this->error($error);
		
		$output = $this->formatOutputData(array(), $contactData, $options);
		
		return $this->success($output);
	}
	
	public function deleteContact($accesstoken, $options = array()) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateAccess($accesstoken, $error);
		if (!empty($error)) return $this->error($error);
		
		$error = null;
		$success = $this->enqueueUpdate($contactData, $accesstoken, 'DELETE_CONTACT', array(), $error);
		if (!empty($error)) return $this->error($error);
		
		$output = $this->formatOutputData(array(), $contactData, $options);
		
		return $this->success($output);
	}
	
	public function confirmUpdate($accesstoken, $token, $options = array()) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateAccess($accesstoken, $error);
		if (!empty($error)) return $this->error($error);
		
		$contactid = $contactData['contactid'];
		
		if (!$this->validateConfirmToken($contactid, $accesstoken, $token)) {
			$this->log($contactid, 'CONFIRMTOKEN_EXPIRED', array());
			return $this->error('CONFIRMTOKEN_EXPIRED');
		}
		
		$params = array(
			'token_confirmed' => 1,
			'token_confirmedtime' => date('Y-m-d H:i:s'),
		);
		
		$upd = array();
		foreach ($params as $col => $value) {
			$upd[] = "$col = ?";
		}
		
		$sql = "UPDATE {$this->confirmTable} SET " . implode(',', $upd) . " WHERE contactid = ? AND accesstoken = ? AND token = ?";
		$adb->pquery($sql, array($params, $contactid, $accesstoken, $token));
		
		$this->log($contactid, 'CONFIRMTOKEN_CONFIRMED', array());
		
		$this->deleteOrphanConfirmToken($contactid);
		
		$error = null;
		$changes = $this->applyContactChanges($contactData, $accesstoken, $token, $error);
		if (!empty($error)) return $this->error($error);
		
		$output = $this->formatOutputData(array(), $contactData, $options);
		
		return $this->success($output);
	}
	
	public function mergeContactData($accesstoken, $maincontact, $otherids, $options = array()) {
		global $adb, $table_prefix, $current_user;
		
		$error = null;
		$contactData = $this->validateAccess($accesstoken, $error);
		if (!empty($error)) return $this->error($error);
		
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$contactEmail = $contactData['email'];
		
		$column = $contactData['email_field']['tablename'].'.'.$contactData['email_field']['columnname'];
		
		// 1. Validate ids
		
		$otherids = array_filter(array_map('intval', $otherids));
		
		$otherids = array_filter($otherids, function($v1) use($module) {
			return in_array(getSalesEntityType($v1), array($module));
		});
		
		// 2. Get all duplicate contacts data
		
		$qg = QueryGenerator::getInstance($module, $current_user);
		$idcol = $qg->getSQLColumn('id', false);
		$qg->initForAllCustomView();
		$qg->addField('id');
		$qg->addFieldAlias('id', 'crmid');
		
		$fields = $this->getAvailableFields($module, false);
		
		foreach ($fields as $field) {
			$qg->addField($field);
			$qg->addFieldAlias($field, $field);
		}
		
		$query = $qg->getQuery();
		$query .= " AND $column = ? AND COALESCE(gdpr_deleted, 0) <> 1 AND $idcol IN (".generateQuestionMarks($otherids).")";
		
		$duplicates = array();
		
		$duplicateResult = $adb->pquery($query, array($contactEmail, $otherids));
		if ($duplicateResult && $adb->num_rows($duplicateResult)) {
			while ($row = $adb->fetchByAssoc($duplicateResult, -1, false)) {
				$duplicates[] = $row;
			}
		}
		
		// 3. Merge contacts data and send confirm
		
		$obj1 = CRMEntity::getInstance($module);
		$obj1->id = $maincontact;
		$obj1->mode = 'edit';
		$obj1->retrieve_entity_info_no_html($maincontact, $module);
		
		// crmv@164120
		$changeLogQuery = "SELECT parent_id, changelogid, description, modified_date
			FROM {$table_prefix}_changelog
			WHERE parent_id IN (".generateQuestionMarks($otherids).") AND description NOT LIKE '%_Relation%'
		ORDER BY modified_date ASC";
		// crmv@164120e
		
		$changeLogResult = $adb->pquery($changeLogQuery, array($otherids));
		$changeLogInfo = array();
		if ($changeLogResult && $adb->num_rows($changeLogResult) > 0) {
			while ($row = $adb->fetchByAssoc($changeLogResult, -1, false)) {
				$descriptionInfo = Zend_Json::decode($row['description']);
				for ($i = 0; $i < count($descriptionInfo); $i++) {
					if (!empty($descriptionInfo[$i][2])) {
						if ($descriptionInfo[$i][3] == 'assigned_user_id') {
							$changeLogInfo[$descriptionInfo[$i][3]] = getIdfromUsername($descriptionInfo[$i][2]);
						} else {
							$changeLogInfo[$descriptionInfo[$i][3]] = $descriptionInfo[$i][2];
						}
					}
				}
			}
		}
		
		$mergedRecord = array();
		$emptyValues = array('', null, '0', '0000-00-00 00:00:00', '--None--', '--Nessuno--');
		
		foreach ($duplicates as $duplicate) {
			$duplicateId = $duplicate['crmid'];
			
			$obj2 = CRMEntity::getInstance($module);
			$obj2->id = $duplicateId;
			$obj2->mode = 'edit';
			$obj2->retrieve_entity_info_no_html($duplicateId, $module);
			
			$colFields = array_keys($obj1->column_fields);
			
			foreach ($colFields as $key) {
				if (!in_array($key, array('firstname', 'lastname', 'email', 'bu_mc'))) { // crmv@163697
					if (!isset($changeLogInfo[$key])) {
						if (!in_array($obj1->column_fields[$key], $emptyValues)) {
							$mergedRecord[$key] = $obj1->column_fields[$key];
						} elseif (!in_array($obj2->column_fields[$key], $emptyValues)) {
							$mergedRecord[$key] = $obj2->column_fields[$key];
						} else {
							$mergedRecord[$key] = $obj1->column_fields[$key];
						}
					} elseif (!in_array($changeLogInfo[$key], $emptyValues)) {
						$mergedRecord[$key] = $changeLogInfo[$key];
					} else {
						$mergedRecord[$key] = $obj1->column_fields[$key];
					}
				} else {
					if ($key === 'bu_mc') {
						if (empty($mergedRecord['bu_mc'])) {
							$bumc1 = $obj1->column_fields['bu_mc'];
						} else {
							$bumc1 = $mergedRecord[$key];
						}
						$bumc2 = $obj2->column_fields['bu_mc'];
						$bumc = implode(' |##| ', array_unique(array_merge(explode(' |##| ', $bumc1), explode(' |##| ', $bumc2))));
						$mergedRecord[$key] = $bumc;
					} else {
						$mergedRecord[$key] = $obj1->column_fields[$key];
					}
				}
			}
			
			unset($obj2);
		}
		
		unset($obj1);
		
		// Unset useless fields
		
		unset($mergedRecord['record_id']);
		unset($mergedRecord['record_module']);
		unset($mergedRecord['createdtime']);
		unset($mergedRecord['modifiedtime']);
		unset($mergedRecord['creator']);
		unset($mergedRecord['assigned_user_id']);
		
		$mergedRecord['transfer_relations'] = $otherids;
		$mergedRecord['main_contact'] = $maincontact;
		
		$error = null;
		$success = $this->enqueueUpdate($contactData, $accesstoken, 'MERGE_CONTACT', $mergedRecord, $error);
		if (!empty($error)) return $this->error($error);
		
		$output = $this->formatOutputData(array(), $contactData, $options);
		
		return $this->success($output);
	}
	
	public function getFields($accesstoken, $options = array()) {
		global $adb, $table_prefix, $current_user;
		
		$error = null;
		$contactData = $this->validateAccess($accesstoken, $error);
		if (!empty($error)) return $this->error($error);
		
		$module = $contactData['module'];
		
		$structure = $this->calculateFieldStructure($module);
		
		$output = $this->formatOutputData(array('structure' => $structure), $contactData, $options);
		
		return $this->success($output);
	}
	
	public function sendSupportRequest($cid, $subject, $description, $options = array()) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateContactId($cid, $error);
		if (!empty($error)) {
			if ($error === 'OPERATION_DENIED') {
				if (empty($contactData['business_id'])) {
					return $this->error($error);
				}
			} else {
				return $this->error($error);
			}
		}
		
		if (empty($subject)) {
			return $this->error('INVALID_SUBJECT');
		}
		
		if (empty($description)) {
			return $this->error('INVALID_DESCRIPTION');
		}
		
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$contactEmail = $contactData['email'];
		
		$templateid = $this->templates['support_request_template'];
		
		$r = array(
			'$custom||gdpr_support_request_sender$' => $contactEmail,
			'$custom||gdpr_support_request_subject$' => $subject,
			'$custom||gdpr_support_request_description$' => $description
		);
		
		$status = $this->sendEmailTemplate($module, $contactid, $this->senderEmail, $templateid, $r);
		
		if ($status) {
			$this->log($contactid, 'SUPPORT_REQUEST_SENT', array());
		} else {
			$this->log($contactid, 'SUPPORT_REQUEST_NOT_SENT', array());
		}
		
		return $status ? $this->success(array()) : $this->error('SEND_EMAIL_FAILED');
	}
	
	public function getPrivacyPolicy($cid, $options = array()) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateContactId($cid, $error);
		if (!empty($error)) {
			if ($error === 'OPERATION_DENIED') {
				if (empty($contactData['business_id'])) {
					return $this->error($error);
				}
			} else {
				return $this->error($error);
			}
		}
		
		$bid = $contactData['business_id'];
		
		$PPU = PrivacyPolicyUtils::getInstance();
		$companyPrivacyPolicy = $PPU->get($bid, 'Company');
		
		$output = array('privacy_policy' => $companyPrivacyPolicy);
		
		return $this->success($output);
	}
	
	public function sendPrivacyPolicy($cid, $options = array()) {
		global $adb, $table_prefix;
		
		$error = null;
		$contactData = $this->validateContactId($cid, $error);
		if (!empty($error)) {
			if ($error === 'OPERATION_DENIED') {
				if (empty($contactData['business_id'])) {
					return $this->error($error);
				}
			} else {
				return $this->error($error);
			}
		}
		
		$bid = $contactData['business_id'];
		
		$PPU = PrivacyPolicyUtils::getInstance();
		$companyPrivacyPolicy = $PPU->get($bid, 'Company');
		
		$contactEmail = $contactData['email'];
		
		$success = send_mail('', $contactEmail, $this->senderName, $this->senderEmail, 'Privacy Policy', $companyPrivacyPolicy);
		
		return $success ? $this->success(array()) : $this->error('SEND_EMAIL_FAILED');
	}
	
	protected function sendAccessEmail($contactData, $accesstoken) {
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$email = $contactData['email'];
		
		$templateid = $this->templates['access_template'];
		
		$newsletterFocus = CRMEntity::getInstance('Newsletter');
		$gdprLink = $newsletterFocus->gdpr_link;
		
		$r = array(
			'$custom||gdpr_access_login_link$' => $gdprLink.'?action=detailview&accesstoken='.urlencode($accesstoken),
		);
		
		$status = $this->sendEmailTemplate($module, $contactid, $email, $templateid, $r);
		
		if ($status) {
			$this->log($contactid, 'ACCESSTOKEN_SENT', array());
		} else {
			$this->log($contactid, 'ACCESSTOKEN_NOT_SENT', array());
		}
		
		return $status;
	}
	
	protected function deleteOrphanToken($contactid) {
		global $adb, $table_prefix;
		
		// Clean old expired authtoken
		$sql = "DELETE FROM {$this->authTable} WHERE contactid = ? AND authtoken_confirmed = 0 AND authtoken_expire <= ?";
		$adb->pquery($sql, array($contactid, date('Y-m-d H:i:s')));
		
		// Clean old expired accesstoken
		$sql = "DELETE FROM {$this->authTable} WHERE contactid = ? AND accesstoken_expire <= ?";
		$adb->pquery($sql, array($contactid, date('Y-m-d H:i:s')));
	}
	
	protected function deleteOrphanConfirmToken($contactid) {
		global $adb, $table_prefix;
		
		// Clean old expired token
		$sql = "DELETE FROM {$this->confirmTable} WHERE contactid = ? AND token_expire <= ?";
		$adb->pquery($sql, array($contactid, date('Y-m-d H:i:s')));
	}
	
	protected function validateAuthToken($contactid, $authtoken) {
		global $adb, $table_prefix;
		
		$checkQuery = "SELECT 1 FROM {$this->authTable} WHERE contactid = ? AND authtoken = ? AND authtoken_confirmed = 0 AND authtoken_expire > ?";
		$checkResult = $adb->pquery($checkQuery, array($contactid, $authtoken, date('Y-m-d H:i:s')));
		
		return $checkResult && $adb->num_rows($checkResult) > 0;
	}
	
	protected function countActiveAccessToken($contactid) {
		global $adb, $table_prefix;
		
		$checkQuery = "SELECT 1 FROM {$this->authTable} WHERE contactid = ? AND accesstoken_confirmed = 1 AND accesstoken_expire > ?";
		$checkResult = $adb->pquery($checkQuery, array($contactid, date('Y-m-d H:i:s')));
		
		return $adb->num_rows($checkResult);
	}
	
	protected function validateAccessToken($accesstoken, &$error) {
		global $adb, $table_prefix;
		
		$contactid = null;
		
		if ($this->checkFirstAccess($accesstoken, $contactid)) return $contactid;
		
		if (!$this->checkAccessTokenExpire($accesstoken, $contactid)) {
			$error = 'SESSION_EXPIRED';
		}
		
		return $contactid;
	}
	
	protected function checkAccessTokenExpire($accesstoken, &$contactid = null) {
		global $adb, $table_prefix;
		
		$checkQuery = "SELECT contactid FROM {$this->authTable} WHERE accesstoken = ? AND accesstoken_confirmed = 1 AND accesstoken_expire > ?";
		$checkResult = $adb->pquery($checkQuery, array($accesstoken, date('Y-m-d H:i:s')));
		
		if ($checkResult && $adb->num_rows($checkResult) > 0) {
			$contactid = intval($adb->query_result($checkResult, 0, 'contactid'));
			return true;
		}
		
		return false;
	}
	
	protected function checkFirstAccess($accesstoken, &$contactid = null) {
		global $adb, $table_prefix;
		
		$checkQuery = "SELECT contactid FROM {$this->authTable} WHERE accesstoken = ? AND accesstoken_confirmed = 0 AND accesstoken_expire > ?";
		$checkResult = $adb->pquery($checkQuery, array($accesstoken, date('Y-m-d H:i:s')));
		
		if ($checkResult && $adb->num_rows($checkResult) > 0) {
			$contactid = intval($adb->query_result($checkResult, 0, 'contactid'));
			
			$adb->pquery("UPDATE {$this->authTable} SET accesstoken_confirmed = 1, accesstoken_confirmedtime = ? WHERE accesstoken = ?", array(date('Y-m-d H:i:s'), $accesstoken));
			return true;
		}
		
		return false;
	}
	
	protected function updateAccessTokenContact($contactid, $accesstoken, $maincontact) {
		global $adb, $table_prefix;
		
		if (!$this->checkAccessTokenExpire($accesstoken)) {
			return false;
		}
		
		$adb->pquery("UPDATE {$this->authTable} SET contactid = ? WHERE contactid = ? AND accesstoken = ?", array($maincontact, $contactid, $accesstoken));
		
		return true;
	}
	
	protected function invalidateContactTokens($contactid) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM {$this->authTable} WHERE contactid = ?", array($contactid));
		
		return true;
	}
	
	protected function validateConfirmToken($contactid, $accesstoken, $token) {
		global $adb, $table_prefix;
		
		$checkQuery = "SELECT 1 FROM {$this->confirmTable} WHERE contactid = ? AND accesstoken = ? AND token = ? AND token_confirmed = 0 AND token_expire > ?";
		$checkResult = $adb->pquery($checkQuery, array($contactid, $accesstoken, $token, date('Y-m-d H:i:s')));
		
		return $checkResult && $adb->num_rows($checkResult) > 0;
	}
	
	protected function getContactDuplicates($module, $contactid, $column, $value) {
		global $adb, $table_prefix, $current_user;
		
		$RM = RelationManager::getInstance();
		
		$qg = QueryGenerator::getInstance($module, $current_user);
		$idcol = $qg->getSQLColumn('id', false);
		$qg->initForAllCustomView();
		$qg->addField('id');
		$qg->addFieldAlias('id', 'crmid');
		
		$fields = $this->getAvailableFields($module, false);
		
		foreach ($fields as $field) {
			$qg->addField($field);
			$qg->addFieldAlias($field, $field);
		}
		
		$query = $qg->getQuery();
		$query .= " AND $column = ? AND COALESCE(gdpr_deleted, 0) <> 1";
		
		$duplicates = array();
		
		$duplicateResult = $adb->pquery($query, array($value));
		
		if ($duplicateResult && $adb->num_rows($duplicateResult)) {
			while ($row = $adb->fetchByAssoc($duplicateResult, -1, false)) {
				$contact = array();
				$contact['crmid'] = $row['crmid'];
				$contact['entityname'] = getEntityName($module, array($row['crmid']), true);
				
				$contact['details'] = array();
				
				foreach ($fields as $field) {
					$fieldWS = WebserviceField::fromCachedWS($module, $field);
					$contact['details'][] = array(
						'fieldname' => $field,
						'fieldlabel' => getTranslatedString($fieldWS->getFieldLabelKey(), $module),
						'value' => $row[$field],
					);
				}
				
				$relids = $RM->getRelatedIds($module, $row['crmid']);
				
				$contact['relids'] = count($relids);
				$contact['suggested'] = false;
				
				$duplicates[] = $contact;
			}
		}
		
		if (count($duplicates) < 2) $duplicates = array();
		
		if (!empty($duplicates)) {
			$suggestedDuplicate = $duplicates[0];
			
			foreach ($duplicates as $duplicate) {
				if ($duplicate['relids'] > $maxRelations) {
					$suggestedDuplicate = $duplicate;
				}
			}
			
			foreach ($duplicates as &$duplicate) {
				if ($duplicate['crmid'] !== $suggestedDuplicate['crmid']) continue;
				$duplicate['suggested'] = true;
			}
		}
		
		return $duplicates;
	}
	
	protected function getAvailableFields($module, $gdprFields = true) {
		$fields = array();
		
		if (is_array($this->availableFields[$module])) {
			$fields = array_merge($fields, $this->availableFields[$module]);
		}
		
		if ($gdprFields && is_array($this->gdprFields)) {
			$fields = array_merge($fields, $this->gdprFields);
		}
		
		return $fields;
	}
	
	protected function calculateFieldStructure($module) {
		global $adb, $table_prefix;
		
		$structure = array();
		
		$blocks = array();
		$fields = $this->getAvailableFields($module, false);
		
		foreach ($fields as $fieldname) {
			$fieldWS = WebserviceField::fromCachedWS($module, $fieldname);
			
			$fld = array();
			$fld['name'] = $fieldWS->getFieldName();
			$fld['blockid'] = $fieldWS->getBlockId();
			$fld['label'] = strtolower(preg_replace('/\s+/', '_', $fieldWS->getFieldLabelKey()));
			$fld['type'] = array('name' => $fieldWS->getFieldDataType());
			$fld['uitype'] = $fieldWS->getUIType();
			
			$blockid = $fieldWS->getBlockId();
			
			if ($blockid > 0) {
				if (!is_array($blocks[$blockid])) {
					$res = $adb->pquery("SELECT * FROM {$table_prefix}_blocks WHERE blockid = ?", array($blockid));
					if ($res && $adb->num_rows($res) > 0) {
						$row = $adb->fetchByAssoc($res, -1, false);
						$block = array(
							'blockid' => $blockid,
							'module' => $module,
							'tabid' => getTabid($module),
							'label' => strtolower(str_replace('LBL_', '', $row['blocklabel'])),
							'sequence' => $row['sequence'],
						);
						$blocks[$blockid] = $block;
					}
				}
				
				$blocks[$blockid]['fields'][] = $fld;
			}
		}
		
		foreach ($blocks as $blockid => $binfo) {
			usort($blocks[$blockid]['fields'], function($v1,$v2) {
				return ($v1["sequence"] > $v2["sequence"] ? +1 : ($v1["sequence"] < $v2["sequence"] ? -1 : 0));
			});
			$blocks[$blockid]['fields'] = $blocks[$blockid]['fields'];
		}
		
		$structure = array_values($blocks);
		
		usort($structure, function($v1,$v2) {
			return ($v1["sequence"] > $v2["sequence"] ? +1 : ($v1["sequence"] < $v2["sequence"] ? -1 : 0));
		});
		
		return $structure;
	}
	
	protected function enqueueUpdate($contactData, $accesstoken, $operation, $data, &$error) {
		global $adb, $table_prefix;
		
		$contactid = $contactData['contactid'];
		
		$token = $this->generateToken(32);
		
		$servertime = time();
		$expireTime = time() + (60*60*2);
		
		$params = array(
			'queueid' => $adb->getUniqueID($this->confirmTable),
			'contactid' => $contactid,
			'accesstoken' => $accesstoken,
			'timestamp' => date('Y-m-d H:i:s'),
			'operation' => $operation,
			'operation_remote_addr' => getIp(),
			'data' => Zend_Json::encode($data),
			'token' => $token,
			'token_expire' => date('Y-m-d H:i:s', $expireTime),
			'token_createdtime' => date('Y-m-d H:i:s'),
			'token_remote_addr' => getIp(),
			'token_confirmed' => 0,
		);
		
		$columns = array_keys($params);
		$adb->format_columns($columns);
		$adb->pquery("INSERT INTO {$this->confirmTable} (" . implode(',', $columns) . ") VALUES (" . generateQuestionMarks($params) . ")", $params);
		
		$this->log($contactid, 'REQUEST_CONTACT_UPDATE', array());
		
		$error = null;
		$success = $this->sendConfirmEmail($contactData, $accesstoken, $token, $error);
		if (!$success) $error = "SEND_EMAIL_FAILED";
		
		return $success;
	}
	
	protected function applyContactChanges($contactData, $accesstoken, $token, &$error) {
		global $adb, $table_prefix;
		
		$output = array();
		
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$email = $contactData['email'];
		
		$changesQuery = "SELECT * FROM {$this->confirmTable} WHERE contactid = ? AND accesstoken = ? AND token = ? AND token_confirmed = 1";
		$changesResult = $adb->pquery($changesQuery, array($contactid, $accesstoken, $token));
		
		if ($changesResult && $adb->num_rows($changesResult)) {
			$row = $adb->fetchByAssoc($changesResult, -1, false);
			
			$operation = $row['operation'];
			$data = Zend_Json::decode($row['data']);
			
			if ($operation === 'UPDATE_CONTACT') {
				$this->applyContactUpdate($contactData, $accesstoken, $data);
			} elseif ($operation === 'DELETE_CONTACT') {
				$this->applyContactDelete($contactData, $accesstoken, $data);
			} elseif ($operation === 'MERGE_CONTACT') {
				$this->applyContactMerge($contactData, $accesstoken, $data);
			}
			
			$this->log($contactid, $operation, array());
		}
		
		return $output;
	}
	
	public function applyContactUpdate($contactData, $accesstoken, $data) {
		global $adb, $table_prefix;
		
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$email = $contactData['email'];
		
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info_no_html($contactid, $module, false);
		$focus->mode = 'edit';
		$focus->id = $contactid;
		
		$gdprFields = $this->gdprFields;
		
		foreach ($data as $fieldname => $value) {
			if (in_array($fieldname, $gdprFields)) {
				$value = $value === 'true' ? '1' : '0';
				$focus->column_fields[$fieldname] = $value;
				$focus->column_fields[$fieldname.'_checkedtime'] = date('Y-m-d H:i:s');
				$focus->column_fields[$fieldname.'_remote_addr'] = getIp();
			} else {
				if (isset($focus->column_fields[$fieldname])) {
					$focus->column_fields[$fieldname] = $value;
				}
			}
		}
		
		$focus->save($module);
		
		$newsletterFocus = CRMEntity::getInstance('Newsletter');
		
		if ($focus->column_fields['gdpr_marketing'] === '0') {
			$newsletterFocus->lockReceivingNewsletter($email, 'lock');
		} else {
			$newsletterFocus->lockReceivingNewsletter($email, 'unlock');
		}
		
		$this->log($contactid, 'CONTACT_UPDATED', array($data));
	}
	
	public function applyContactDelete($contactData, $accesstoken, $data) {
		global $adb, $table_prefix;
		
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$email = $contactData['email'];
		
		$focus = CRMEntity::getInstance($module);
		$err = $focus->retrieve_entity_info_no_html($contactid, $module, false); // crmv@182782
		$focus->mode = 'edit';
		$focus->id = $contactid;
		
		$RM = RelationManager::getInstance();
		
		$fields = $this->getAvailableFields($module);
		$gdprFields = $this->gdprFields;
		
		// 0. Notify
		
		$this->notifyAnonymize($focus);
		
		// 1. Empty fields
		
		foreach ($fields as $fieldname) {
			if (in_array($fieldname, $gdprFields)) {
				$focus->column_fields[$fieldname] = '0';
				$focus->column_fields[$fieldname.'_checkedtime'] = date('Y-m-d H:i:s');
				$focus->column_fields[$fieldname.'_remote_addr'] = getIp();
			} else {
				if ($fieldname === 'gdpr_sentdate') continue;
				if (isset($focus->column_fields[$fieldname])) {
					$focus->column_fields[$fieldname] = getTranslatedString('LBL_ANONYMOUS', 'Settings');
				}
			}
		}
		
		// 2. Set contact as GDPR deleted
		
		$focus->column_fields['gdpr_deleted'] = '1';
		$focus->column_fields['gdpr_deleted_checkedtime'] = date('Y-m-d H:i:s');
		$focus->column_fields['gdpr_deleted_remote_addr'] = getIp();
		
		$emailField = $this->emailFields[$module]['fieldname'];
		$focus->column_fields[$emailField] = getTranslatedString('LBL_ANONYMOUS', 'Settings');
		
		// crmv@182782
		if ($err == 'LBL_RECORD_DELETE') {
			$focus->save($module,false,false,false);
		} else {
			$focus->save($module);
		}
		// crmv@182782e
		
		// 3. Turn off the automatic emails
		
		$newsletterFocus = CRMEntity::getInstance('Newsletter');
		$newsletterFocus->lockReceivingNewsletter($email, 'lock');
		
		// 4. Empty changelog history
		
		// crmv@164120
		$changeLogFocus = ChangeLog::getInstance();
		$changeLogFocus->deleteforRecord($module, $contactid);
		// crmv@164120e
		
		// 5. Invalidate accesstoken
		
		$this->invalidateContactTokens($contactid);
		
		$this->log($contactid, 'CONTACT_DELETED', array());
	}
	
	public function applyContactMerge($contactData, $accesstoken, $data) {
		global $adb, $table_prefix;
		
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$email = $contactData['email'];
		
		$RM = RelationManager::getInstance();
		
		$maincontact = $data['main_contact'];
		
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info_no_html($maincontact, $module, false);
		$focus->mode = 'edit';
		$focus->id = $maincontact;
		
		foreach ($data as $fieldname => $value) {
			if (isset($focus->column_fields[$fieldname])) {
				$focus->column_fields[$fieldname] = $value;
			}
		}
		
		global $global_skip_notifications;
		$tmp_global_skip_notifications = $global_skip_notifications;
		$global_skip_notifications = true;
		
		$focus->save($module);
		
		$global_skip_notifications = $tmp_global_skip_notifications;
		
		$transferRelations = $data['transfer_relations'];
		foreach ($transferRelations as $transferid) {
			$relatedIds = $RM->getRelatedIds($module, $transferid); // crmv@164120
			
			// Transfer all relations
			foreach ($relatedIds as $id) {
				$RM->relate($module, $maincontact, getSalesEntityType($id), $id);
			}
			
			$transferFocus = CRMEntity::getInstance($module);
			$transferFocus->retrieve_entity_info_no_html($transferid, $module, false);
			$transferFocus->mode = 'edit';
			$transferFocus->id = $transferid;
			$transferFocus->mark_deleted($transferid);
		}
		
		// Update access token with selected contact
		$this->updateAccessTokenContact($contactid, $accesstoken, $maincontact);
		
		$this->log($contactid, 'CONTACT_MERGED', array());
	}
	
	public function lockContact($email) {
		global $adb;
		
		foreach ($this->emailFields as $module => $field) {
			if (self::isEnabledForModule($module)) {
				$focus = CRMEntity::getInstance($module);
				$update = array('gdpr_sentdate = ?' => date('Y-m-d H:i:s'));
				foreach ($this->gdprFields as $gdprField) {
					$update["$gdprField = ?"] = 0;
				}
				$adb->pquery("UPDATE {$field['tablename']} SET " . implode(',', array_keys($update)) . " WHERE {$field['columnname']} = ?", array(array_values($update), $email));
			}
		}
		$newsletterFocus = CRMEntity::getInstance('Newsletter');
		$newsletterFocus->lockReceivingNewsletter($email, 'lock');
	}
	
	protected function sendConfirmEmail($contactData, $accesstoken, $token, &$error) {
		$module = $contactData['module'];
		$contactid = $contactData['contactid'];
		$email = $contactData['email'];
		
		$templateid = $this->templates['confirm_update_template'];
		
		$newsletterFocus = CRMEntity::getInstance('Newsletter');
		$gdprLink = $newsletterFocus->gdpr_link;
		
		$r = array(
			'$custom||gdpr_update_confirm_link$' => $gdprLink.'?action=confirm-update&accesstoken='.urlencode($accesstoken).'&token='.urlencode($token),
		);
		
		$status = $this->sendEmailTemplate($module, $contactid, $email, $templateid, $r);
		
		if ($status) {
			$this->log($contactid, 'CONFIRM_EMAIL_SENT', array());
		} else {
			$this->log($contactid, 'CONFIRM_EMAIL_NOT_SENT', array());
		}
		
		return $status;
	}
	
	public function sendContactNotifyChangeEmail($module, $contactid) {
		$templateid = $this->templates['contact_updated_template'];
		
		$focus = CRMEntity::getInstance($module);
		$ret = $focus->retrieve_entity_info_no_html($contactid, $module, false);
		$focus->id = $contactid;
		
		$emailField = $this->emailFields[$module]['fieldname'];
		$email = $focus->column_fields[$emailField];
		
		$newsletterFocus = CRMEntity::getInstance('Newsletter');
		$gdprLink = $newsletterFocus->gdpr_link;
		
		$cid = urlencode(base64_encode("C|".$email."|".$module));
		
		$r = array(
			'$custom||gdpr_verify_link$' => $gdprLink.'?action=verify&cid='.$cid,
		);
		
		$status = $this->sendEmailTemplate($module, $contactid, $email, $templateid, $r);
		
		if ($status) {
			$this->log($contactid, 'NOTIFY_CHANGE_EMAIL_SENT', array());
		} else {
			$this->log($contactid, 'NOTIFY_CHANGE_EMAIL_NOT_SENT', array());
		}
		
		return $status;
	}
	
	protected function sendEmailTemplate($module, $contactid, $email, $templateid, $replacements = array()) {
		global $adb, $table_prefix;
		
		$emailTemplateId = $templateid;
		$result = $adb->pquery("SELECT subject, body FROM {$table_prefix}_emailtemplates WHERE templateid = ?", array($emailTemplateId));
		$tsubject = $adb->query_result_no_html($result, 0, 'subject');
		$tdescription = $adb->query_result_no_html($result, 0, 'body');
		
		if (empty($tsubject) || empty($tdescription) || empty($emailTemplateId)) return false;
		
		$r = $replacements;
		$tdescription = str_replace(array_keys($r), array_values($r), $tdescription);
		$tdescription = getMergedDescription($tdescription, $contactid, $module);
		
		$success = send_mail('', $email, $this->senderName, $this->senderEmail, $tsubject, $tdescription);
		
		return $success;
	}
	
	protected function formatOutputData($data, $contactData, $options = array()) {
		$out = array_merge(array(), $data);
		
		$options = array_merge(array('include_contacts_data' => true), $options);
		
		if ($options && $options['include_contacts_data']) {
			$module = $contactData['module'];
			$contactid = $contactData['contactid'];
			$contactEmail = $contactData['email'];
			
			$column = $contactData['email_field']['tablename'].'.'.$contactData['email_field']['columnname'];
			$value = $contactEmail;
			
			$duplicates = $this->getContactDuplicates($module, $contactid, $column, $value);
			
			$out = array_merge(array('email' => $contactEmail, 'contact' => $contactData['available_data'], 'duplicates' => $duplicates, 'business_id' => $contactData['business_id']), $out);
		}
		
		return $out;
	}
	
	protected function log($contactid, $operation, $data = array()) {
		global $adb, $table_prefix;
		
		if (empty($operation)) return;
		
		$params = array(
			'logid' => $adb->getUniqueID($this->logTable),
			'contactid' => $contactid,
			'timestamp' => date('Y-m-d H:i:s'),
			'operation' => $operation,
			'operation_remote_addr' => getIp(),
			'data' => Zend_Json::encode($data),
		);
		
		$columns = array_keys($params);
		$adb->format_columns($columns);
		$adb->pquery("INSERT INTO {$this->logTable} (" . implode(',', $columns) . ") VALUES (" . generateQuestionMarks($params) . ")", $params);
	}
	
	public function generateToken($length = 32) {
		return base64_encode(crypt_random_string($length));
	}
	
	public function error($message, $data = array()) {
		$out = array_merge(array('success' => false, 'error' => $message, 'cid' => $this->cid, 'cid_data' => $this->cidData), $data);
		return $out;
	}
	
	public function success($data = array()) {
		$out = array_merge(array('success' => true, 'cid' => $this->cid, 'cid_data' => $this->cidData), $data);
		return $out;
	}
	
	protected function initCustomWebserviceOperations() {
		$operations = array();
		
		$parameters = array('contactid' => 'string');
		$operations['gdpr_authtoken'] = array('file' => 'include/utils/GDPRWS/webservices/AuthToken.php', 'handler' => 'gdpr_authtoken', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('contactid' => 'string', 'authtoken' => 'string');
		$operations['gdpr_sendverify'] = array('file' => 'include/utils/GDPRWS/webservices/SendVerify.php', 'handler' => 'gdpr_sendverify', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('contactid' => 'string', 'authtoken' => 'string');
		$operations['gdpr_accesstoken'] = array('file' => 'include/utils/GDPRWS/webservices/AccessToken.php', 'handler' => 'gdpr_accesstoken', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('accesstoken' => 'string');
		$operations['gdpr_checkaccesstoken'] = array('file' => 'include/utils/GDPRWS/webservices/CheckAccessToken.php', 'handler' => 'gdpr_checkaccesstoken', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('accesstoken' => 'string', 'data' => 'encoded');
		$operations['gdpr_update'] = array('file' => 'include/utils/GDPRWS/webservices/Update.php', 'handler' => 'gdpr_update', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('accesstoken' => 'string', 'maincontact' => 'int', 'otherids' => 'encoded');
		$operations['gdpr_mergecontact'] = array('file' => 'include/utils/GDPRWS/webservices/Merge.php', 'handler' => 'gdpr_mergecontact', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('accesstoken' => 'string');
		$operations['gdpr_fields'] = array('file' => 'include/utils/GDPRWS/webservices/Fields.php', 'handler' => 'gdpr_fields', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('accesstoken' => 'string', 'token' => 'string');
		$operations['gdpr_confirmupdate'] = array('file' => 'include/utils/GDPRWS/webservices/ConfirmUpdate.php', 'handler' => 'gdpr_confirmupdate', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('accesstoken' => 'string');
		$operations['gdpr_delete'] = array('file' => 'include/utils/GDPRWS/webservices/Delete.php', 'handler' => 'gdpr_delete', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('contactid' => 'string');
		$operations['gdpr_privacypolicy'] = array('file' => 'include/utils/GDPRWS/webservices/PrivacyPolicy.php', 'handler' => 'gdpr_privacypolicy', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('contactid' => 'string', 'subject' => 'string', 'description' => 'string');
		$operations['gdpr_supportrequest'] = array('file' => 'include/utils/GDPRWS/webservices/SupportRequest.php', 'handler' => 'gdpr_supportrequest', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$parameters = array('contactid' => 'string');
		$operations['gdpr_sendprivacypolicy'] = array('file' => 'include/utils/GDPRWS/webservices/SendPrivacyPolicy.php', 'handler' => 'gdpr_sendprivacypolicy', 'reqtype' => 'POST', 'prelogin' => '0', 'parameters' => $parameters);
		
		$this->registerCustomWebservices($operations);
	}
	
	protected function registerCustomWebservices($operations) {
		global $adb, $table_prefix;
		
		foreach ($operations as $operationName => $operationInfo) {
			$exists = $adb->pquery("SELECT operationid FROM {$table_prefix}_ws_operation WHERE name = ?", array($operationName));
			if ($exists && $adb->num_rows($exists) < 1) {
				$operationId = $adb->getUniqueId($table_prefix . '_ws_operation');
				
				$params = array(
					'operationid' => $operationId,
					'name' => $operationName,
					'handler_path' => $operationInfo['file'],
					'handler_method' => $operationInfo['handler'],
					'type' => $operationInfo['reqtype'],
					'prelogin' => $operationInfo['prelogin']
				);
				
				$columns = array_keys($params);
				$adb->format_columns($columns);
				$adb->pquery("INSERT INTO {$table_prefix}_ws_operation (" . implode(',', $columns) . ") VALUES (" . generateQuestionMarks($params) . ")", $params);
				
				$parameters = $operationInfo['parameters'];
				$parameterIndex = 0;
				
				foreach ($parameters as $parameterName => $parameterType) {
					$params = array(
						'operationid' => $operationId,
						'name' => $parameterName,
						'type' => $parameterType,
						'sequence' => ($parameterIndex + 1)
					);
					
					$columns = array_keys($params);
					$adb->format_columns($columns);
					$adb->pquery("INSERT INTO {$table_prefix}_ws_operation_parameters (" . implode(',', $columns) . ") VALUES (" . generateQuestionMarks($params) . ")", $params);
					
					++$parameterIndex;
				}
			}
		}
	}

	// crmv@194712
	public function updateConvertLead() {
		global $adb, $table_prefix;

		$gdprFieldMappingList = $this->getGdprFieldMappingList();

		$fieldMap = array();
		$fieldIds = array('Leads' => array(), 'Accounts' => array(), 'Contacts' => array(), 'Potentials' => array());

		foreach ($gdprFieldMappingList as $gdprField) {
			$fieldMap[] = $gdprField;
			
			if (!empty($gdprField[0])) {
				$fieldIds['Leads'][$gdprField[0]] = null;
			}
			if (!empty($gdprField[1])) {
				$fieldIds['Accounts'][$gdprField[1]] = null;
			}
			if (!empty($gdprField[2])) {
				$fieldIds['Contacts'][$gdprField[2]] = null;
			}
			if (!empty($gdprField[3])) {
				$fieldIds['Potentials'][$gdprField[3]] = null;
			}
		}

		foreach ($fieldIds as $module => $fields) {
			if (!empty($fields)) {
				$fieldQuery = "SELECT fieldname, fieldid FROM {$table_prefix}_field WHERE tabid = ? AND fieldname IN (" . generateQuestionMarks($fields) . ")";
				$fieldRes = $adb->pquery($fieldQuery, array(getTabid($module), array_keys($fields)));
				if (!!$fieldRes && $adb->num_rows($fieldRes) > 0) {
					while ($row = $adb->fetchByAssoc($fieldRes, -1, false)) {
						$fieldIds[$module][$row['fieldname']] = $row['fieldid'];
					}
				}
			}
		}

		foreach ($fieldMap as $values) {
			$leadfid = $fieldIds['Leads'][$values[0]];
			if (empty($leadfid)) continue;
			
			$checkMappingQuery = "SELECT 1 FROM {$table_prefix}_convertleadmapping WHERE leadfid = ?";
			$checkMappingRes = $adb->pquery($checkMappingQuery, array($leadfid));
			
			if (!!$checkMappingRes && $adb->num_rows($checkMappingRes) > 0) {
				continue;
			}
			
			$accountfid = $fieldIds['Accounts'][$values[1]];
			if (empty($accountfid)) $accountfid = null;
			$contactfid = $fieldIds['Contacts'][$values[2]];
			if (empty($contactfid)) $contactfid = null;
			$potentialfid = $fieldIds['Potentials'][$values[3]];
			if (empty($potentialfid)) $potentialfid = null;
			
			$insertQuery = "INSERT INTO {$table_prefix}_convertleadmapping (cfmid, leadfid, accountfid, contactfid, potentialfid) VALUES (?, ?, ?, ?, ?)";
			$adb->pquery($insertQuery, array($adb->getUniqueID($table_prefix . "_convertleadmapping"), $leadfid, $accountfid, $contactfid, $potentialfid));
		}
	}
	// crmv@194712e

	public function checkNoConfirmDeletion() {
		global $adb, $table_prefix;
		
		if (empty($this->noconfirm_deletion_months)) $this->noconfirm_deletion_months = $this->default_noconfirm_deletion_months;
		$deletion_time = date('Y-m-d H:i:s', strtotime("-{$this->noconfirm_deletion_months} months"));
		
		foreach ($this->emailFields as $module => $field) {
			if (self::isEnabledForModule($module)) {
				$focus = CRMEntity::getInstance($module);
				$result = $adb->pquery("SELECT {$focus->tab_name_index[$field['tablename']]} as \"crmid\", {$field['columnname']} as \"email\" FROM {$field['tablename']} WHERE gdpr_privacypolicy = ? and gdpr_deleted = ? and gdpr_sentdate is not null and gdpr_sentdate <> ? and gdpr_sentdate < ?", array(0,0, '0000-00-00 00:00:00', $adb->formatDate($deletion_time, true))); // crmv@182782
				if ($result && $adb->num_rows($result) > 0) {
					while ($row = $adb->fetchByAssoc($result)) {
						$this->applyContactDelete(array('module' => $module, 'contactid' => $row['crmid'], 'email' => $row['email']), null, null);
					}
				}
			}
		}
	}
	
	protected function notifyAnonymize($focus) {
		$subject = getTranslatedString('LBL_GDPR_NOTIFY_ANONYMIZE_SUBJECT', 'Settings');
		$description = getTranslatedString('LBL_GDPR_NOTIFY_ANONYMIZE_BODY', 'Settings');
		$description = sprintf($description, $focus->column_fields['firstname'], $focus->column_fields['lastname'], $focus->column_fields['email'], date('Y-m-d', strtotime("+{$this->noconfirm_deletion_months} months")));
		$success = send_mail('', $this->senderEmail, $this->senderName, $this->senderEmail, $subject, $description);
		return $success;
	}
	
	public function getBusinessId($crmid, $module, $email = '') {
		global $adb, $table_prefix, $current_user;
		
		$BU = BusinessUnit::getInstance();
		
		if ($module === 'Contacts' && !BusinessUnit::isEnabledForModule($module)) {
			$contactFocus = CRMEntity::getInstance('Contacts');
			$contactFocus->id = $crmid;
			$s = $contactFocus->retrieve_entity_info($crmid, 'Contacts', false);
			if (empty($s)) {
				$accountId = intval($contactFocus->column_fields['account_id']);
				if ($accountId > 0) {
					$module = 'Accounts';
					$crmid = $accountId;
				}
			}
		}
		
		$business = BusinessUnit::getBusinessForId($crmid);
		
		$useDefaultBusiness = false;
		
		if (!empty($email)) {
			if (BusinessUnit::isEnabledForModule($module) && isset($this->emailFields[$module])) {
				$focus = CRMEntity::getInstance($module);
				$tableIndex = $focus->table_index;
				$column = $this->emailFields[$module]['tablename'].'.'.$this->emailFields[$module]['columnname'];
				
				$businessByEmail = $adb->pquery("SELECT DISTINCT(bu_mc) FROM {$this->emailFields[$module]['tablename']} INNER JOIN {$table_prefix}_crmentity ON {$this->emailFields[$module]['tablename']}.{$tableIndex} = {$table_prefix}_crmentity.crmid WHERE {$table_prefix}_crmentity.deleted = 0 AND {$column} = ?", array($email));
				if ($businessByEmail && $adb->num_rows($businessByEmail) > 1) {
					$useDefaultBusiness = true;
				}
			}
		}
		
		$businessId = 0;
		
		if ($business) {
			$vteProp = VTEProperties::getInstance();
			$generalSettings = $vteProp->get('services.gdpr.general_settings');
			
			$businessId = $generalSettings['default_business'];
			
			if ((count($business) === 1 && !empty($business[0])) && !$useDefaultBusiness) {
				$businessInfo = $BU->getBusinessInfoByName($business[0]);
				$businessId = $businessInfo['organizationid'];
			} else {
				$businessId = $generalSettings['default_business'];
			}
		} else {
			$businessList = $BU->getBusinessList();
			if (!empty($businessList)) {
				$businessId = $businessList[0]['organizationid'];
			}
		}
		
		return $businessId;
	}

	// crmv@194712
	public function getGdprFieldMappingList() {
		$fields = array();

		$gdprFields = $this->gdprFields;

		foreach ($gdprFields as $gdprField) {
			$fields[] = array($gdprField, null, $gdprField, null);
			$fields[] = array($gdprField.'_checkedtime', null, $gdprField.'_checkedtime', null);
			$fields[] = array($gdprField.'_remote_addr', null, $gdprField.'_remote_addr', null);
		}
		
		$fields[] = array('gdpr_deleted', null, 'gdpr_deleted', null);
		$fields[] = array('gdpr_deleted_checkedtime', null, 'gdpr_deleted_checkedtime', null);
		$fields[] = array('gdpr_deleted_remote_addr', null, 'gdpr_deleted_remote_addr', null);

		$fields[] = array('gdpr_sentdate', null, 'gdpr_sentdate', null);

		return $fields;
	}
	// crmv@194712e
	
}
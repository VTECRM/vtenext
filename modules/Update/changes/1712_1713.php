<?php 

// crmv@163797

require_once('modules/Update/Update.php');
require_once('include/utils/BusinessUnit.php');
require_once('include/utils/VTEProperties.php');
require_once('include/utils/GDPRWS/GDPRWS.php');

global $adb, $table_prefix, $site_URL, $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;

if (!function_exists('addColumnToTable')) {
	function addColumnToTable($tablename, $columnname, $type, $extra = '') {
		global $adb;
		
		// check if already present
		$cols = $adb->getColumnNames($tablename);
		if (in_array($columnname, $cols)) {
			return;
		}
		
		$col = $columnname . ' ' . $type . ' ' . $extra;
		$adb->alterTable($tablename, $col, 'Add_Column');
	}
}

if (!function_exists('getPrimaryKeyName')) {
	function getPrimaryKeyName($tablename) {
		global $adb, $dbconfig;
		$ret = '';
		if ($adb->isMysql()) {
			// for mysql just check if it exists
			$res = $adb->query("SHOW KEYS FROM {$tablename} WHERE Key_name = 'PRIMARY'");
			if ($res && $adb->num_rows($res) > 0) $ret = 'PRIMARY';
		} elseif ($adb->isMssql()) {
			$res = $adb->pquery("SELECT CONSTRAINT_NAME as cn from INFORMATION_SCHEMA.TABLE_CONSTRAINTS where CONSTRAINT_CATALOG = ? and TABLE_NAME = ? and CONSTRAINT_TYPE = 'PRIMARY KEY'", array($dbconfig['db_name'], $tablename));
			if ($res) $ret = $adb->query_result_no_html($res, 0, 'cn');
		} elseif ($adb->isOracle()) {
			$res = $adb->pquery("SELECT CONSTRAINT_NAME as cn FROM all_constraints cons	WHERE cons.table_name = ? AND cons.constraint_type = 'P'", array(strtoupper($tablename)));
			if ($res) $ret = $adb->query_result_no_html($res, 0, 'cn');
		}
		return $ret;
	}
}

if (!function_exists('dropPrimaryKey')) {
	function dropPrimaryKey($tablename) {
		global $adb;
		if ($adb->isMysql()) {
			$keyname = getPrimaryKeyName($tablename);
			if ($keyname == 'PRIMARY') $adb->query("ALTER TABLE {$tablename} DROP PRIMARY KEY");
		} elseif ($adb->isMssql() || $adb->isOracle()) {
			$keyname = getPrimaryKeyName($tablename);
			$adb->query("ALTER TABLE {$tablename} DROP CONSTRAINT {$keyname}");
		} else {
			echo "Drop Primary key not supported for this database";
		}
	}
}

Update::change_field($table_prefix.'_organizationdetails', 'organizationname', 'C', '100');
addColumnToTable($table_prefix.'_organizationdetails', 'organizationid', 'I(5)');

$BU = BusinessUnit::getInstance();
$businessList = $BU->getBusinessList();

foreach ($businessList as $business) {
	$businessId = intval($business['organizationid']);
	if (empty($businessId)) {
		$businessName = $business['organizationname'];
		$organizationid = $adb->getUniqueId($table_prefix.'_organizationdetails');
		$adb->pquery("UPDATE {$table_prefix}_organizationdetails SET organizationid = ? WHERE organizationname = ?", array($organizationid, $businessName));
	}
}

dropPrimaryKey($table_prefix.'_organizationdetails');
$adb->query("ALTER TABLE {$table_prefix}_organizationdetails ADD PRIMARY KEY (organizationid)");

dropPrimaryKey($table_prefix.'_privacy_policy');
$adb->query("ALTER TABLE {$table_prefix}_privacy_policy ADD PRIMARY KEY (id, type)");

$BU = BusinessUnit::getInstance();
$businessList = $BU->getBusinessList();

$gdprws = GDPRWS::getInstance();

$vteProp = VTEProperties::getInstance();

$restoreConfig = false;

$prevConfig = $vteProp->get('services.gdpr.config');
if ($prevConfig !== null) {
	$config = array();
	$config['webservice_endpoint'] = $prevConfig['webservice_endpoint'];
	$config['webservice_username'] = $prevConfig['webservice_username'];
	$config['webservice_accesskey'] = $prevConfig['webservice_accesskey'];
	$config['default_language'] = $prevConfig['default_language'];
	$config['sender_name'] = $prevConfig['sender_name'];
	$config['sender_email'] = $prevConfig['sender_email'];
	$config['noconfirm_deletion_months'] = $prevConfig['noconfirm_deletion_months'];
	$config['website_logo'] = $prevConfig['website_logo'];
	
	$bid = $businessList[0]['organizationid'];
	$vteProp->set("services.gdpr.config.business.{$bid}", $config);
	$vteProp->deleteProperty('services.gdpr.config');
	
	$restoreConfig = true;
}

$prevTemplates = $vteProp->get('services.gdpr.templates');
if ($prevTemplates !== null) {
	$templates = array();
	$templates['support_request_template'] = $prevTemplates['support_request_template'];
	$templates['access_template'] = $prevTemplates['access_template'];
	$templates['confirm_update_template'] = $prevTemplates['confirm_update_template'];
	$templates['contact_updated_template'] = $prevTemplates['contact_updated_template'];
	
	$bid = $businessList[0]['organizationid'];
	$vteProp->set("services.gdpr.templates.business.{$bid}", $templates);
	$vteProp->deleteProperty('services.gdpr.templates');
	
	$restoreConfig = true;
}

$adb->pquery("UPDATE {$table_prefix}_privacy_policy SET id = ? WHERE id = ?", array($businessList[0]['organizationid'], 1));

$prop = $vteProp->get('services.gdpr.general_settings');

if ($prop === null) {
	$generalSettings = array();
	$generalSettings['default_business'] = $businessList[0]['organizationid'];
	$vteProp->set('services.gdpr.general_settings', $generalSettings);
}

foreach ($businessList as $k => $business) {
	$bid = $business['organizationid'];
	if ($k === 0 && $restoreConfig) continue;
	
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
		$config['noconfirm_deletion_months'] = $gdprws->default_noconfirm_deletion_months;
		
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
		
		$templates['support_request_template'] = $gdprws->createTemplateEmail('support_request_template', 'GDPR - Support request template - EN', 'New support request', 'Template used for support request', 'en');
		$gdprws->createTemplateEmail('support_request_template', 'GDPR - Template richiesta supporto - IT', 'Nuova richiesta di supporto', 'Template utilizzato per le richieste di supporto', 'it');
		
		$templates['access_template'] = $gdprws->createTemplateEmail('access_template', 'GDPR - Access template - EN', 'Access to manage your contact', 'Template used for sending the access details to the contact', 'en');
		$gdprws->createTemplateEmail('access_template', 'GDPR - Template accesso - IT', 'Accesso per la gestione del tuo contatto', 'Template utilizzato per l\'invio dell\'accesso al contatto', 'it');
		
		$templates['confirm_update_template'] = $gdprws->createTemplateEmail('confirm_update_template', 'GDPR - Confirm update template - EN', 'Confirm your contact\'s changes', 'Template used for confirming the contact update', 'en');
		$gdprws->createTemplateEmail('confirm_update_template', 'GDPR - Template conferma modifiche - IT', 'Conferma le modifiche del tuo contatto', 'Template utilizzato per la conferma dell\'aggiornamento del contatto', 'it');
		
		$templates['contact_updated_template'] = $gdprws->createTemplateEmail('contact_updated_template', 'GDPR - Contact updated template - EN', 'Contact update', 'Template used for sending update notifications to the contact', 'en');
		$gdprws->createTemplateEmail('contact_updated_template', 'GDPR - Template dati contatto aggiornati - IT', 'Aggiornamento contatto', 'Template utilizzato per inviare le notifiche di cambio dati al contatto', 'it');
		
		$vteProp->set("services.gdpr.templates.business.{$bid}", $templates);
	}
	
	$PPU = PrivacyPolicyUtils::getInstance();
	$ok = $PPU->save($bid, 'Company', file_get_contents('include/utils/GDPRWS/templates/privacy_policy.html'));
}

$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_GENERAL_SETTINGS' => 'Impostazioni generali',
			'LBL_DEFAULT_BUSINESS_UNIT' => 'Default Business Unit',
			'LBL_DEFAULT_BUSINESS_UNIT_DESC' => 'La business unit di default utilizzata come fallback',
		),
		'en_us' => array(
			'LBL_GENERAL_SETTINGS' => 'General settings',
			'LBL_DEFAULT_BUSINESS_UNIT' => 'Default Business Unit',
			'LBL_DEFAULT_BUSINESS_UNIT_DESC' => 'The default business unit used as fallback',
		),
	),
);

$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		if (array_key_exists($lang, $languages)) {
			foreach ($translist as $label => $translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}

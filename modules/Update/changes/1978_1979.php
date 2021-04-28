<?php

// crmv@196666

if (isModuleInstalled('VteSync')) {
	$type = $adb->datadict->ActualType('C');
	Vtiger_Utils::AlterTable("{$table_prefix}_vtesync_tokens","token $type(2000)");
	Vtiger_Utils::AlterTable("{$table_prefix}_vtesync_tokens","refresh_token $type(2000)");
}

$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_OAUTH2_FLOW' => 'Flusso OAuth2',
			'oauth2_flow_authorization' => 'Flusso Authorization Code',
			'oauth2_flow_client_cred' => 'Flusso Client Credential',
		),
		'en_us' => array(
			'LBL_OAUTH2_FLOW' => 'OAuth2 flow',
			'oauth2_flow_authorization' => 'Authorization Code Flow',
			'oauth2_flow_client_cred' => 'Client Credential Flow',
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

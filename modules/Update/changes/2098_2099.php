<?php
/* crmv@206145 */

global $adb, $table_prefix;
$adb->addColumnToTable($table_prefix.'_messages_account', 'authentication', 'C(10)');
$adb->addColumnToTable($table_prefix.'_messages_account', 'token', 'C(2000)');
$adb->addColumnToTable($table_prefix.'_messages_account', 'refresh_token', 'C(2000)');
$adb->addColumnToTable($table_prefix.'_messages_account', 'expires', 'I(10)');
$adb->pquery("update {$table_prefix}_messages_account set authentication = ?", ['password']);

SDK::setLanguageEntries('ALERT_ARR', 'LBL_AUTHENTICATION_REQUIRED', array('it_it'=>'E\' necessario eseguire l\'autenticazione','en_us'=>'Authentication is required'));
SDK::setLanguageEntries('Settings', 'LBL_AUTHENTICATION_METHOD', array('it_it'=>'Metodo di autenticazione','en_us'=>'Authentication method'));
SDK::setLanguageEntries('Settings', 'LBL_AUTHENTICATE_LINK', array('it_it'=>'Autentica...','en_us'=>'Authenticate...'));

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('modules.messages.oauth2.credentials', [
	'Microsoft' => [
		'clientId' => '',
		'clientSecret' => '',
		'redirectUri' => '',
	]
]);
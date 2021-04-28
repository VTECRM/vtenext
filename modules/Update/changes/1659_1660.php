<?php
global $adb, $table_prefix;

$cols = $adb->database->MetaColumns($table_prefix.'_systems');
if ($cols[strtoupper('server_password')]->max_length != 255) {
	Update::change_field($table_prefix.'_systems','server_password','C','255');
	
	$serverConfigUtils = ServerConfigUtils::getInstance();
	$serverConfigUtils->encryptAll();
}

$cols = $adb->database->MetaColumns($table_prefix.'_portalinfo');
if ($cols[strtoupper('user_password')]->max_length != 255) {
	Update::change_field($table_prefix.'_portalinfo','user_password','C','255');
	
	$focus = CRMEntity::getInstance('Contacts');
	$focus->encryptAllPortalPasswords();
}

SDK::setLanguageEntries('ALERT_ARR', 'LBL_FIND_PORTAL_DUPLICATES', array('it_it'=>'Esiste già un utente portale con questa email','en_us'=>'A portal user already exists with this email'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_ERROR_PORTAL_DUPLICATES', array('it_it'=>'Errore nella ricerca di utenti portale duplicati','en_us'=>'Some error in searching for duplicate portal users'));
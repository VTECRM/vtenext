<?php
global $adb, $table_prefix;

SDK::setLanguageEntry('Calendar', 'it_it', 'Event', 'Attività');
SDK::setLanguageEntry('Calendar', 'it_it', 'Will begin', 'Comincerà');
SDK::setLanguageEntry('Calendar', 'it_it', 'LBL_HOLIDAYS', 'Festività');

SDK::setLanguageEntries('ALERT_ARR', 'ARE_YOU_SURE_INCREMENT_VERSION', array('it_it'=>'Sei sicuro di voler generare una nuova versione?','en_us'=>'Are you sure you want to generate a new version?'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_OLD_VERSION', array('it_it'=>'Vecchia','en_us'=>'Old'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_NEW_VERSION', array('it_it'=>'Nuova','en_us'=>'New'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_INCREMENT_VERSION_ERR_1', array(
	'it_it'=>'Sono stati rilevati dei processi in esecuzione. Vuoi che terminino con la vecchia configurazione o con la nuova?',
	'en_us'=>'Some running processes were detected. Do you want it to end with the old configuration or the new one?'
));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_INCREMENT_VERSION_ERR_2', array(
	'it_it'=>'Sono stati rilevati dei processi in esecuzione. Vuoi che terminino con la vecchia configurazione o con la nuova? Inoltre sono state rilevate modifiche pendenti nelle seguenti configurazioni:%Scegliendo VECCHIA verranno salvate automaticamente tutte le modifiche pendenti.',
	'en_us'=>'Some running processes were detected. Do you want it to end with the old configuration or the new one? Furthermore, pending changes were detected in the following configurations:%Choosing OLD will automatically save all pending changes.'
));

$adb->addColumnToTable($table_prefix.'_running_processes', 'xml_version', 'I(11)');
$adb->addColumnToTable($table_prefix.'_running_processes', 'version_chosen', 'I(1) DEFAULT 0');
$adb->addColumnToTable($table_prefix.'_running_processes', 'xml_version_forced', 'I(11)');
$adb->addColumnToTable($table_prefix.'_processmaker_versions', 'system_versions', 'X');

$indexes = $adb->database->MetaIndexes($table_prefix.'_messages_inline_cache');
if (!array_key_exists('cachedate_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('cachedate_idx', $table_prefix.'_messages_inline_cache', 'cachedate');
	$adb->datadict->ExecuteSQLArray((Array)$index);
}
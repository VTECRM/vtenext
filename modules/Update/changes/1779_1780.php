<?php
global $adb, $table_prefix;
$adb->pquery("update {$table_prefix}_relatedlists set presence = ? where tabid = ? and related_tabid = ?", array(2,26,0));

$indexes = $adb->database->MetaIndexes($table_prefix.'_running_processes_timer');
if (!array_key_exists('running_process_timer_check_boundary', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('running_process_timer_check_boundary', $table_prefix.'_running_processes_timer', array('mode','running_process','elementid'));
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

$res = $adb->pquery("SELECT fieldid FROM {$table_prefix}_settings_field WHERE name = ?", array('LBL_LOG_CONFIG'));
if ($res && $adb->num_rows($res) == 0) {
	require_once('vtlib/Vtecrm/SettingsField.php');
	require_once('vtlib/Vtecrm/SettingsBlock.php');
	$field = new Vtecrm_SettingsField();
	$field->name = 'LBL_LOG_CONFIG';
	$field->iconpath = 'set-IcoLoginHistory.gif';
	$field->description = 'LBL_LOG_CONFIG_DESCRIPTION';
	$field->linkto = 'index.php?module=Settings&action=LogConfig&parenttab=Settings';
	$block = Vtecrm_SettingsBlock::getInstance('LBL_OTHER_SETTINGS');
	$block->addField($field);
}

SDK::setLanguageEntries('Settings', 'LBL_LOG_CONFIG', array('it_it'=>'Log di sistema','en_us'=>'System logs'));
SDK::setLanguageEntries('Settings', 'LBL_LOG_CONFIG_DESCRIPTION', array('it_it'=>'Gestisci e visualizza i log di sistema','en_us'=>'Manage and view system logs'));
SDK::setLanguageEntries('Settings', 'LBL_LOG_GENERAL_CONFIG', array('it_it'=>'Configurazioni globali','en_us'=>'General configurations'));
SDK::setLanguageEntries('Settings', 'LBL_LOG_LIST', array('it_it'=>'Log disponibili','en_us'=>'Logs'));
SDK::setLanguageEntries('Settings', 'LBL_VIEW_LOG', array('it_it'=>'Visualizza log','en_us'=>'View log'));
SDK::setLanguageEntries('Settings', 'LBL_WEBSERVICE_LOG', array('it_it'=>'Webservice','en_us'=>'Webservices'));
SDK::setLanguageEntries('Settings', 'LBL_RESTAPI_LOG', array('it_it'=>'Rest api','en_us'=>'Rest api'));
SDK::setLanguageEntries('Settings', 'LBL_PROCESSES_LOG', array('it_it'=>'Processi','en_us'=>'Processes'));
SDK::setLanguageEntries('Settings', 'LBL_LOG4PHP_LOG', array('it_it'=>'log4php','en_us'=>'log4php'));

require_once('include/utils/VTEProperties.php');
$vteProp = VTEProperties::getInstance();
$vteProp->deleteProperty('performance.restapi_log');
$vteProp->deleteProperty('performance.webservice_log');
$vteProp->initDefaultProperties();
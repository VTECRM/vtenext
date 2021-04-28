<?php
SDK::setProcessMakerTaskCondition('get_running_process_current_user', 'modules/SDK/src/ProcessMaker/Utils.php', 'LBL_RUNNING_PROCESS_CURRENT_USER');
SDK::setLanguageEntries('Settings', 'LBL_RUNNING_PROCESS_CURRENT_USER', array('it_it'=>'Utente corrente: Nome Utente','en_us'=>'Current user: User Name'));

global $adb, $table_prefix;
$indexes = $adb->database->MetaIndexes($table_prefix.'_running_processes_logsi');
if (!array_key_exists('running_processes_logsi_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('running_processes_logsi_idx', $table_prefix.'_running_processes_logsi', 'running_process,elementid');
	$adb->datadict->ExecuteSQLArray((Array)$index);
}
$indexes = $adb->database->MetaIndexes($table_prefix.'_processmaker_rec');
if (!array_key_exists('processmaker_rec_running_process_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('processmaker_rec_running_process_idx', $table_prefix.'_processmaker_rec', 'running_process');
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

$em = new VTEventsManager($adb);
$em->setHandlerInActive('GeolocalizationHandler');
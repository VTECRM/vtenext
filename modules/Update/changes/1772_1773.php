<?php
global $adb, $table_prefix;
$indexes = $adb->database->MetaIndexes($table_prefix.'_running_processes_timer');
if (!array_key_exists('running_process_timer_cron', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('running_process_timer_cron', $table_prefix.'_running_processes_timer', 'executed');
	$adb->datadict->ExecuteSQLArray((Array)$index);
}
if (!array_key_exists('running_process_timer_check', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('running_process_timer_check', $table_prefix.'_running_processes_timer', array('mode','running_process','prev_elementid'));
	$adb->datadict->ExecuteSQLArray((Array)$index);
}
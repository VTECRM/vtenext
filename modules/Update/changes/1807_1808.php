<?php
global $adb, $table_prefix;

$indexes = $adb->database->MetaIndexes($table_prefix.'_running_processes_timer');
if (!array_key_exists('running_process_timer_check_boundary', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('running_process_timer_check_boundary', $table_prefix.'_running_processes_timer', array('mode','running_process','elementid'));
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

$indexes = $adb->database->MetaIndexes($table_prefix.'_processes');
if (!array_key_exists('getprocessesrelatedto_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('getprocessesrelatedto_idx', $table_prefix.'_processes', array('related_to','running_process'));
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

$indexes = $adb->database->MetaIndexes($table_prefix.'_processmaker_conditionals');
if (!array_key_exists('processmaker_conditionals_running_process_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('processmaker_conditionals_running_process_idx', $table_prefix.'_processmaker_conditionals', array('running_process'));
	$adb->datadict->ExecuteSQLArray((Array)$index);
}
if (!array_key_exists('getallconditionals_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('getallconditionals_idx', $table_prefix.'_processmaker_conditionals', array('crmid','running_process'));
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

$indexes = $adb->database->MetaIndexes($table_prefix.'_troubletickets');
if (array_key_exists('troubletickets_ticketid_idx', $indexes)) {
	$sql = $adb->datadict->DropIndexSQL('troubletickets_ticketid_idx', $table_prefix.'_troubletickets');
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}
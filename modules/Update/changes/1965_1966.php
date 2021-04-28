<?php

// crmv@195073
global $adb, $table_prefix;

if (isModuleInstalled('VteSync')) {
	if (Vtiger_Utils::CheckTable($table_prefix.'_vtesync_vsl_lastsync')) {
		$adb->addColumnToTable($table_prefix.'_vtesync_vsl_lastsync', 'last_page', 'C(64)');
	}
	
	require_once('modules/VteSync/VteSync.php');
	$vsync = VteSync::getInstance();
	$vsync->vtlib_handler('VteSync', 'module.postupdate');
}

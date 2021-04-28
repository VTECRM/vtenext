<?php
global $adb, $table_prefix;

// crmv@190020 - remove chat tables

if (Vtiger_Utils::CheckTable("{$table_prefix}_chat_msg")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_chat_msg");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}
if (Vtiger_Utils::CheckTable("{$table_prefix}_chat_pchat")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_chat_pchat");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}
if (Vtiger_Utils::CheckTable("{$table_prefix}_chat_pvchat")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_chat_pvchat");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}
if (Vtiger_Utils::CheckTable("{$table_prefix}_chat_users")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_chat_users");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

<?php
global $adb, $table_prefix;
$cols = $adb->getColumnNames($table_prefix.'_messages_layout');
if (!in_array('merge_account_folders', $cols)) {
	$adb->addColumnToTable($table_prefix.'_messages_layout', 'merge_account_folders', 'INT(1) NOTNULL DEFAULT 0');
	$adb->query("update {$table_prefix}_messages_layout set merge_account_folders = 1");
}
SDK::setLanguageEntries('Messages', 'LBL_MERGE_ACCOUNT_FOLDERS', array('it_it'=>'Unisci cartelle degli account','en_us'=>'Merge account folders'));
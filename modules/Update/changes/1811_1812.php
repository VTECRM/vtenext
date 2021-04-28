<?php

// crmv@177095

// add an index
$check = false;
$indexes = $adb->database->MetaIndexes("{$table_prefix}_messages");
foreach($indexes as $name => $index) {
	if (count($index['columns']) == 1 && $index['columns'][0] == 'modifiedtime') {
		$check = true;
		break;
	}
}
if (!$check) {
	$sql = $adb->datadict->CreateIndexSQL('messages_modifiedtime_idx', "{$table_prefix}_messages", 'modifiedtime');
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}


// crmv@179144

$adb->pquery("DELETE FROM {$table_prefix}_settings_field WHERE name = ?",array('LBL_ASSIGN_MODULE_OWNERS'));

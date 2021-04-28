<?php
global $adb, $table_prefix;
$cols = $adb->getColumnNames($table_prefix.'_targets');
if (!in_array('listhash', $cols)) {
	$adb->addColumnToTable($table_prefix.'_targets', 'listhash', "C(63)");
}
$adb->pquery("update {$table_prefix}_field set displaytype = 3 where tablename = ? and fieldname = ?", array($table_prefix.'_targets','listhash'));
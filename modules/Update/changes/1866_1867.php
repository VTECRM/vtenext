<?php
global $adb, $table_prefix;
$adb->pquery(
	"UPDATE {$table_prefix}_cronjobs SET timeout = ?, repeat_sec = ? WHERE cronname = ?",
	array(300,120,'ScheduledImport')
); 
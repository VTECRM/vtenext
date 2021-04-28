<?php

// crmv@186732
global $adb, $table_prefix;
$adb->pquery(
	"UPDATE {$table_prefix}_cronjobs SET timeout = ?, max_attempts = ? WHERE cronname = ?",
	array(600,0,'CleanMessageStorage')
); 

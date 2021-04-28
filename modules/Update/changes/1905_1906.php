<?php

// crmv@189690
global $adb, $table_prefix;
$adb->pquery(
	"UPDATE {$table_prefix}_cronjobs SET repeat_sec = ? WHERE cronname = ? AND repeat_sec = ?",
	array(3600,'ModNotifications', 7200)
); 

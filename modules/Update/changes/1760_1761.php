<?php

// crmv@103120

global $adb, $table_prefix;

// remove another useless index
$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->DropIndexSQL('userid_idx', "{$table_prefix}_messages_cron_uid"));

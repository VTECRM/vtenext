<?php

global $adb, $table_prefix;

$adb->pquery("UPDATE {$table_prefix}_activitytype SET presence = 0 WHERE activitytype= ?", array('Tracked'));

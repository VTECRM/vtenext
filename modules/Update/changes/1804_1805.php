<?php
global $adb, $table_prefix;
$adb->pquery("update {$table_prefix}_ws_fieldtype set fieldtype = ? where uitype = ?", array('string',221));
<?php
global $adb, $table_prefix;

// crmv@198388
$adb->pquery("update {$table_prefix}_field set presence = ? where tabid = ? and fieldname = ?", array(1,getTabid('Processes'),'description'));
<?php

/* crmv@207901 */

global $adb, $table_prefix;

$adb->query("UPDATE {$table_prefix}_tab_info SET prefname='vtenext_max_version' WHERE prefname='vtiger_max_version'");
$adb->query("UPDATE {$table_prefix}_tab_info SET prefname='vtenext_min_version' WHERE prefname='vtiger_min_version'");



$delete = $adb->query("DELETE FROM sdk_language WHERE module='APP_STRINGS' AND label='VTIGER';");
$update = $adb->query("UPDATE sdk_language SET label = REPLACE(label, 'VTIGER', 'VTE') WHERE label LIKE ('%VTIGER%');");

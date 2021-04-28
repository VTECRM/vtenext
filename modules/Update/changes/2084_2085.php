<?php 

/* crmv@205568 */

global $adb, $table_prefix;

$tabids = [];

$tabs = $adb->query("SELECT DISTINCT(tabid) FROM {$table_prefix}_field WHERE uitype = 1099");

if (!!$tabs && $adb->num_rows($tabs) > 0) {
	while ($row = $adb->fetchByAssoc($tabs, -1, false)) {
		$tabids[] = $row['tabid'];
	}
}

$adb->query("UPDATE {$table_prefix}_field SET uitype = 1015, readonly = 99 WHERE uitype = 1099");

foreach ($tabids as $tabid) {
	FieldUtils::invalidateCache($tabid);
}

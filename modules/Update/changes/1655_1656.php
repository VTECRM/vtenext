<?php
global $adb, $table_prefix;

// crmv@155560

// fix wrongly html-encoded names
$res = $adb->query(
	"SELECT setype FROM {$table_prefix}_entity_displayname
	WHERE displayname LIKE '%&%'
	GROUP BY setype"
);
if ($res) {
	$ENU = EntityNameUtils::getInstance();
	while ($row = $adb->fetchByAssoc($res, -1, false)) {
		$ENU->rebuildForModule($row['setype']);
	}
}


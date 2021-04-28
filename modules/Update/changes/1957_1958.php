<?php
global $adb, $table_prefix;

// crmv@194694
// remove wrong seq fields from fieldmodulerel
$res = $adb->pquery(
	"SELECT fmr.fieldid 
	FROM {$table_prefix}_fieldmodulerel fmr
	INNER JOIN {$table_prefix}_field f ON f.fieldid = fmr.fieldid
	WHERE fmr.module LIKE 'ModLight%' AND f.fieldname = ? AND uitype = 1",
	array('seq')
);
while ($row = $adb->FetchByAssoc($res, -1, false)) {
	$fieldid = intval($row['fieldid']);
	$adb->pquery("DELETE FROM {$table_prefix}_fieldmodulerel WHERE fieldid = ?", array($fieldid));
}

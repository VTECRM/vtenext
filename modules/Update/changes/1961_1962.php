<?php
global $adb, $table_prefix;

// move customview columns from crmentity to processes
$result = $adb->pquery("select {$table_prefix}_cvcolumnlist.*
	from {$table_prefix}_customview
	inner join {$table_prefix}_cvcolumnlist on {$table_prefix}_cvcolumnlist.cvid = {$table_prefix}_customview.cvid
	where entitytype = ? and columnname like ?", array('Processes',"{$table_prefix}_crmentity%"));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$adb->pquery("update {$table_prefix}_cvcolumnlist set columnname = ? where cvid = ? and columnindex = ?", array(
			str_replace("{$table_prefix}_crmentity","{$table_prefix}_processes",$row['columnname']), $row['cvid'], $row['columnindex']
		));
	}
}
$result = $adb->pquery("select {$table_prefix}_cvadvfilter.*
	from {$table_prefix}_customview
	inner join {$table_prefix}_cvadvfilter on {$table_prefix}_cvadvfilter.cvid = {$table_prefix}_customview.cvid
	where entitytype = ? and columnname like ?", array('Processes',"{$table_prefix}_crmentity%"));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$adb->pquery("update {$table_prefix}_cvadvfilter set columnname = ? where cvid = ? and columnindex = ?", array(
			str_replace("{$table_prefix}_crmentity","{$table_prefix}_processes",$row['columnname']), $row['cvid'], $row['columnindex']
		));
	}
}
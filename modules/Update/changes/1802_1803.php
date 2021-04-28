<?php
global $adb, $table_prefix;

$uitype = 54;
$result = $adb->pquery("select fieldtypeid from {$table_prefix}_ws_fieldtype where uitype=?", array($uitype));
if ($result && $adb->num_rows($result) == 0) {
	$fieldtypeid = $adb->getUniqueID($table_prefix."_ws_fieldtype");
	$adb->pquery("insert into {$table_prefix}_ws_fieldtype(fieldtypeid,uitype,fieldtype) values(?,?,?)",array($fieldtypeid,$uitype,'reference'));
	$adb->pquery("insert into {$table_prefix}_ws_referencetype(fieldtypeid,type) values(?,?)",array($fieldtypeid,'Groups'));
}
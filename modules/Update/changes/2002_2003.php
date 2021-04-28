<?php
global $adb, $table_prefix;

// crmv@198549

$type = $adb->datadict->ActualType('C');
Vtiger_Utils::AlterTable("{$table_prefix}_systems","server_password $type(500)");


// crmv@198652

SDK::setLanguageEntries('ALERT_ARR', 'LBL_BAD_CHARACTER_PICKLIST_VALUE', array('it_it'=>'Non sono permessi i seguenti caratteri','en_us'=>'The following characters are not allowed'));


// crmv@197682

require_once('modules/Update/Update.php');
$cols = $adb->database->MetaColumns($table_prefix.'_customview');
if ($cols[strtoupper('status')]->default_value != 1) {
	Update::change_field($table_prefix.'_customview','status','I','1','DEFAULT 1');
	$adb->pquery("UPDATE {$table_prefix}_customview SET status = 1 WHERE viewname = ? AND entitytype = ?", array('Pending', 'Processes'));
}


// crmv@196952

if (file_exists('hash_version.txt')) {
	$hash_version = file_get_contents('hash_version.txt');
	$adb->updateClob($table_prefix.'_version','hash_version','id=1',$hash_version);
	@unlink('hash_version.txt');
	$cache = Cache::getInstance('vteCacheHV');
	$cache->clear();
}

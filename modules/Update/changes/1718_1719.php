<?php

require_once('include/utils/GDPRWS/GDPRWS.php');

global $adb, $table_prefix;

$gdprModules = GDPRWS::$supportedModules;
$GDPRFields = array('gdpr_privacypolicy', 'gdpr_personal_data', 'gdpr_marketing', 'gdpr_thirdparties', 'gdpr_profiling', 'gdpr_restricted', 'gdpr_notifychange', 'gdpr_deleted');

foreach ($gdprModules as $module) {
	$tabid = getTabid($module);	
	
	foreach ($GDPRFields as $field) {
		$adb->pquery("UPDATE {$table_prefix}_field SET typeofdata = ? WHERE fieldname = ? AND tabid = ?", array('V~O', $field.'_checkedtime', $tabid));
		$adb->pquery("UPDATE {$table_prefix}_field SET typeofdata = ? WHERE fieldname = ? AND tabid = ?", array('V~O', $field.'_remote_addr', $tabid));
	}
	
	$adb->pquery("UPDATE {$table_prefix}_field SET typeofdata = ? WHERE fieldname = ? AND tabid = ?", array('V~O', 'gdpr_sentdate', $tabid));
}
<?php 
global $adb, $table_prefix;

// crmv@188277

// update processes filters
$modInst = Vtecrm_Module::getInstance('Processes');
if ($modInst) {
	$adb->pquery("UPDATE {$table_prefix}_customview SET setmobile = 1 WHERE viewname = ? AND entitytype = ?", array('Pending', 'Processes'));
}
 

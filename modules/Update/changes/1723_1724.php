<?php
$cols = $adb->getColumnNames($table_prefix.'_processmaker');
if (!in_array('pending_changes', $cols)) {
	$adb->addColumnToTable($table_prefix.'_processmaker', 'pending_changes', 'I(1) DEFAULT 0');
}
SDK::setLanguageEntries('ALERT_ARR', 'ARE_YOU_SURE_INCREMENT_VERSION_FOR_DOWNLOAD', array(
	'it_it'=>'Sono state individuate delle modifiche pendenti. L\'esportazione forzerà il salvataggio di versione. Vuoi procedere comunque?',
	'en_us'=>'Pending changes have been identified. The export will force the saving of version. Do you want to proceed anyway?'
));
SDK::setLanguageEntries('Settings', 'LBL_NO_PROCESS_UPDATED_WRONG_VERSION', array(
	'it_it'=>'Non è stato possibile aggiornare il processo in quando la versione da importare risulta essere minore o uguale a quella corrente.',
	'en_us'=>'The process could not be updated because the version is less than or equal to the current version.'
));

SDK::setUitype(209,'modules/SDK/src/209/209.php','modules/SDK/src/209/209.tpl','');
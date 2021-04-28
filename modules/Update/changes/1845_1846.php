<?php
global $adb, $table_prefix;

$cols = $adb->getColumnNames($table_prefix.'_version');
if (in_array('license_id', $cols)) {
	$sqlarray = $adb->datadict->DropColumnSQL($table_prefix.'_version','license_id');
	$adb->datadict->ExecuteSQLArray($sqlarray);
	$adb->addColumnToTable($table_prefix.'_version', 'license_info', 'C(500)');
}
SDK::setLanguageEntries('Morphsuit', 'LBL_ACTIVATED_USERS', array('it_it'=>'Utenti attivi','en_us'=>'Activated users'));
SDK::setLanguageEntries('Morphsuit', 'LBL_EXPIRATION_DATE', array('it_it'=>'Data di scadenza','en_us'=>'Expiration date'));
SDK::setLanguageEntries('Morphsuit', 'LICENSE_ID', array('it_it'=>'Licenza numero','en_us'=>'License number'));
SDK::setLanguageEntries('Morphsuit', 'LBL_UPDATE_YOUR_LICENSE', array('it_it'=>'Aggiorna la tua licenza','en_us'=>'Update your license'));

$cols = $adb->getColumnNames('sdk_processmaker_factions');
if (!in_array('block', $cols)) {
	$adb->addColumnToTable('sdk_processmaker_factions', 'block', 'C(50)');
}
SDK::setLanguageEntries('Settings', 'LBL_PM_SDK_DATE_FUNCTIONS', array('it_it'=>'Funzioni data','en_us'=>'Date functions'));
SDK::setProcessMakerFieldAction("date_now", "modules/SDK/src/ProcessMaker/Utils.php", "Now Date (format)", '', 'LBL_PM_SDK_DATE_FUNCTIONS');
SDK::unsetProcessMakerFieldAction("formatDate");
SDK::setProcessMakerFieldAction("formatDate", "modules/SDK/src/ProcessMaker/Utils.php", "Format Date/Time (date, format)", '', 'LBL_PM_SDK_DATE_FUNCTIONS');
SDK::setProcessMakerFieldAction("diffDate", "modules/SDK/src/ProcessMaker/Utils.php", "DiffDays (date1, date2)", '', 'LBL_PM_SDK_DATE_FUNCTIONS');

SDK::setLanguageEntries('Settings', 'LBL_PM_TIMER_ERROR_NOT_SUPPORTED', array('it_it'=>'Timer applicato a task non supportata','en_us'=>'Timer applied to unsupported task'));
SDK::setLanguageEntries('Settings', 'LBL_PM_ERROR_OUTGOING_MISSING', array('it_it'=>'Nessun ramo d\'uscita configurato','en_us'=>'No configured exit branch'));

SDK::setLanguageEntries('Settings', 'LBL_PM_ACTION_TransferRelations', array('it_it'=>'Trasferisci relazioni','en_us'=>'Transfer relations'));
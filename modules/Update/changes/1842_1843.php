<?php
global $adb, $table_prefix;

SDK::setProcessMakerFieldAction("formatDate", "modules/SDK/src/ProcessMaker/Utils.php", "Format Date/Time (date, format)"); //crmv@180645

// TODO merge LayoutBlockListUtils::getNewFields(), ModuleMakerSteps::getNewFields() and ProcessModuleMakerSteps::getNewFields()

//crmv@178164
$cols = $adb->getColumnNames($table_prefix.'_messages_account');
if (!in_array('folder_separator', $cols)) {
	$adb->addColumnToTable($table_prefix.'_messages_account', 'folder_separator', "C(1)");
}
//crmv@178164e

$adb->pquery("update {$table_prefix}_modnotifications set smcreatorid = ? where mod_not_type in (?,?)", array(0,'ListView changed','Reminder calendar')); //crmv@183346
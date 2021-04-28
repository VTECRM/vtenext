<?php
global $adb, $table_prefix;

$cols = $adb->getColumnNames($table_prefix.'_emails_send_queue');
if (!in_array('scheduled', $cols)) {
	$adb->addColumnToTable($table_prefix.'_emails_send_queue', 'scheduled', 'I(1) DEFAULT 0');
}
if (!in_array('messagesid', $cols)) {
	$adb->addColumnToTable($table_prefix.'_emails_send_queue', 'messagesid', 'I(19) DEFAULT 0');
}
$indexes = $adb->database->MetaIndexes($table_prefix.'_emails_send_queue');
if (!array_key_exists($table_prefix.'_emails_send_queue_scheduled_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL($table_prefix.'_emails_send_queue_scheduled_idx', $table_prefix.'_emails_send_queue', 'method,userid,scheduled');
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

SDK::setLanguageEntries('Messages', 'LBL_Folder_vteScheduled', array('it_it'=>'Programmati','en_us'=>'Scheduled'));
SDK::setLanguageEntries('Messages', 'LBL_SEND_NOW_BUTTON', array('it_it'=>'Invia ora','en_us'=>'Send now'));
SDK::setLanguageEntries('Emails', 'LBL_SCHEDULE_SENDING', array('it_it'=>'Programma invio','en_us'=>'Schedule sending'));
SDK::setLanguageEntries('Emails', 'MESSAGE_MAIL_SCHEDULED_SUCCESSFULLY', array('it_it'=>'Messaggio programmato','en_us'=>'Message scheduled'));
SDK::setLanguageEntries('Emails', 'LBL_CHOOSE_DATE_TIME', array('it_it'=>'Scegli data e ora','en_us'=>'Choose date and time'));
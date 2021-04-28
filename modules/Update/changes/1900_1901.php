<?php

// crmv@189222

$indexes = $adb->database->MetaIndexes($table_prefix.'_messages_account');
if (!array_key_exists('mess_acc_email_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('mess_acc_email_idx', $table_prefix.'_messages_account', 'email');
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

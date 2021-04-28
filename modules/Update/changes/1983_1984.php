<?php
global $adb, $table_prefix;


// crmv@197120

// add an index
$check = false;
$table = $table_prefix.'_messagesrel';
$indexes = $adb->database->MetaIndexes($table);
foreach($indexes as $name => $index) {
	if (count($index['columns']) == 1 && $index['columns'][0] == 'crmid') {
		$check = true;
		break;
	}
}
if (!$check) {
	$sql = $adb->datadict->CreateIndexSQL($table_prefix."_messagesrel_crmid_idx", $table, 'crmid');
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}


// crmv@197191
$check = false;
$table = $table_prefix.'_tmp_users_mod';
$indexes = $adb->database->MetaIndexes($table);
foreach($indexes as $name => $index) {
	if (count($index['columns']) == 1 && $index['columns'][0] == 'tabid') {
		$check = true;
		break;
	}
}
if (!$check) {
	$sql = $adb->datadict->CreateIndexSQL("tmp_usr_mod_tabid_idx", $table, 'tabid');
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}


// crmv@197192
$check = false;
$table = 'tbl_s_transitions_history';
$indexes = $adb->database->MetaIndexes($table);
foreach($indexes as $name => $index) {
	if (count($index['columns']) == 3 && $index['columns'][0] == 'tabid') {
		$check = true;
		break;
	}
}
if (!$check) {
	$sql = $adb->datadict->CreateIndexSQL("tbl_trans_hist_idx", $table, array('tabid', 'field', 'entity_id'));
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}

// crmv@180739


$VP = VTEProperties::getInstance();
$VP->set("security.smtp.validate_certs", 0); // to be sure not to break existing configurations

Update::info('The PHPMailer library has been updated and extended with the class VTEMailer.');
Update::info('If you have customizations using the old class, please review them.');
Update::info('');

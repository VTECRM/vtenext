<?php

// crmv@166077

$table = 'tbl_s_picklist_language';
// change column type to be more compatible
Vtiger_Utils::AlterTable($table,'value C(1000)');

// remove useless index
$idxs = array_keys($adb->database->MetaIndexes($table));
if (in_array('picklist_codes_lang_idx', $idxs)) {
	$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->DropIndexSQL('picklist_codes_lang_idx', $table));
}

Vtiger_Utils::AlterTable('sdk_language','label C(200)');
Vtiger_Utils::AlterTable('sdk_language','trans_label C(2000)');

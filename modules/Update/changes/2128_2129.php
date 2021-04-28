<?php
global $adb, $table_prefix;
// script to rename column
// change column name of vte_import_locks
$sql = $adb->datadict->RenameColumnSQL("vte_import_locks", 'vtiger_import_lock_id','vte_import_lock_id', 'vte_import_lock_id I(1)');
if ($sql && in_array(OLDNAME, $adb->getColumnNames(TABLENAME))) {
    $adb->datadict->ExecuteSQLArray($sql);
}
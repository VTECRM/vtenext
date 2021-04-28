<?php
global $adb, $table_prefix;
$adb->addColumnToTable($table_prefix.'_messages_account', 'attempts', 'I(5)', 'DEFAULT 0');
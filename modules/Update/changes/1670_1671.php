<?php

global $adb, $table_prefix;

// crmv@151308

$params = array('include/utils/InventoryFunctions.php', 'include/InventoryHandler.php', 'handleInventoryProductRel');
$adb->pquery("UPDATE com_{$table_prefix}_wft_entitymeth SET function_path = ? WHERE function_path = ? AND function_name = ?", $params);

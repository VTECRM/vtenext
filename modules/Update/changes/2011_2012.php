<?php
global $adb, $table_prefix;

// crmv@199115
$adb->pquery("update {$table_prefix}_field set helpinfo = ? where tabid = ? and fieldname = ?", array('LBL_ATTR_VALUES_HELPINFO',getTabid('ModLightProdAttr'),'attr_values'));
SDK::setLanguageEntries('ModLightProdAttr', 'LBL_ATTR_VALUES_HELPINFO', array('it_it'=>'Inserire i valori andando a capo','en_us'=>'Enter the values by going in a new line'));
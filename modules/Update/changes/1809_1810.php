<?php

// crmv@178322
global $adb, $table_prefix;

SDK::setLanguageEntry('Settings', 'it_it', 'LBL_IMPORT_ERROR_NOTIF_DESC', 'Controllare i log dell\'importazione da <a href="%s">questa</a> pagina');
SDK::setLanguageEntry('Settings', 'en_us', 'LBL_IMPORT_ERROR_NOTIF_DESC', 'Check the import log from <a href="%s">this</a> page');

$languages = vtlib_getToggleLanguageInfo();
if (array_key_exists('de_de', $languages)) {
	SDK::setLanguageEntry('Settings', 'de_de', 'LBL_IMPORT_ERROR_NOTIF_DESC', 'Überprüfen Sie die Protokolle importieren <a href="%s">dieser</a> Seite');
}

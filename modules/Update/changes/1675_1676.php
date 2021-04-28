<?php

// fix email_date column
if ($adb->isMysql()) {
	// this seems the only way to change the column
	$adb->query("ALTER TABLE {$table_prefix}_troubletickets CHANGE email_date email_date TIMESTAMP NULL");
}

SDK::setLanguageEntry('ALERT_ARR', 'en_us', 'LBL_REQ_FAILED_NO_CONNECTION', 'No network connection available at the moment. Please retry');
SDK::setLanguageEntry('ALERT_ARR', 'it_it', 'LBL_REQ_FAILED_NO_CONNECTION', 'Nessuna connessione di rete disponibile al momento. Riprova');

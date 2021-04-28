<?php 

// crmv@203590

$trans = array(
    'Settings' => array(
		'it_it' => array(
			'LBL_CHOOSE_AUDIT_LOG_TITLE' => 'Download i log archiviati',
			'LBL_CHOOSE_AUDIT_LOG_DESC' => 'I log richiesti sono troppo vecchi e disponibili solamente in forma di archivio. Selezione il file da scaricare:',
        ),
        'en_us' => array(
			'LBL_CHOOSE_AUDIT_LOG_TITLE' => 'Download archived audit trail logs',
			'LBL_CHOOSE_AUDIT_LOG_DESC' => 'Requested logs are too old and are only available in the archived form. Please select the files to download:',
        ),
    ),
);

foreach ($trans as $module => $modlang) {
    foreach ($modlang as $lang => $translist) {
        foreach ($translist as $label => $translabel) {
            SDK::setLanguageEntry($module, $lang, $label, $translabel);
        }
    }
}

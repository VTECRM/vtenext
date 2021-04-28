<?php 

// crmv@167019

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'LBL_SAVE' => 'Salva',
			'LBL_CANCEL_ALL' => 'Cancella tutto',
			'LBL_REVISION_DROP_LIMIT' => 'Non puoi inserire piu\' di un file per la revisione di un documento.',
			'LBL_REVISION_CONFIRM' => 'Sei sicuro di voler revisionare il documento?',
		),
		'en_us' => array(
			'LBL_SAVE' => 'Save',
			'LBL_CANCEL_ALL' => 'Cancel all',
			'LBL_REVISION_DROP_LIMIT' => 'You can\'t insert more than one file for reviewing a document.',
			'LBL_REVISION_CONFIRM' => 'Are you sure you want to revise the document?',
		),
	),
	'APP_STRINGS' => array(
		'it_it' => array(
			'LBL_CHOOSE_UPLOAD_MODE' => 'Scegli modalita` di upload',
			'LBL_DOCUMENT_FOREACH_FILE' => 'Documento per ogni file',
			'LBL_SINGLE_ZIP_FILE' => 'File ZIP singolo',
			'LBL_SELECT_OR_DROP_FILES' => 'Seleziona o trascina i file qui',
		),
		'en_us' => array(
			'LBL_CHOOSE_UPLOAD_MODE' => 'Choose upload mode',
			'LBL_DOCUMENT_FOREACH_FILE' => 'Document for each file',
			'LBL_SINGLE_ZIP_FILE' => 'Single ZIP File',
			'LBL_SELECT_OR_DROP_FILES' => 'Select or drop files here',
		),
	),
);

$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		if (array_key_exists($lang, $languages)) {
			foreach ($translist as $label => $translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}

<?php 

// crmv@158392

global $adb, $table_prefix;

$onclick = 'return VTE.PDFMaker.ExportTemplates();';
$adb->pquery("UPDATE sdk_menu_contestual SET onclick = ? WHERE module = ? AND title = ?", array($onclick, 'PDFMaker', 'LBL_EXPORT'));

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'PDFMAKER_DELETE_CONFIRMATION' => 'Sicuro di voler eliminare i template selezionati?',
			'SELECT_ATLEAST_ONE' => 'Prego selezionare almeno un`entita`'
		),
		'en_us' => array(
			'PDFMAKER_DELETE_CONFIRMATION' => 'Are you sure you want to delete the selected templates?',
			'SELECT_ATLEAST_ONE' => 'Please select at least one entity'
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

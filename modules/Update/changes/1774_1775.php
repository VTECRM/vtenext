<?php 

// crmv@172422

global $adb, $table_prefix;

$cols = $adb->getColumnNames($table_prefix.'_pdfmaker_settings');
if (!in_array('compliance', $cols)) {
	$adb->addColumnToTable($table_prefix.'_pdfmaker_settings', 'compliance', "VARCHAR(4) DEFAULT ''");
}

$trans = array(
	'PDFMaker' => array(
		'it_it' => array(
			'LBL_COMPLIANCE' => 'Conformità',
			'LBL_PDFA' => 'PDF/A1-b',
			'LBL_PDFX' => 'PDF/X-1a',
		),
		'en_us' => array(
			'LBL_COMPLIANCE' => 'Compliance',
			'LBL_PDFA' => 'PDF/A1-b',
			'LBL_PDFX' => 'PDF/X-1a',
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
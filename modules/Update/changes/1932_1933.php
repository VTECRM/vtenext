<?php 

@unlink('Smarty/templates/themes/next/Home/HomeBlock.tpl');

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'COLUMNS_CANNOT_BE_DUPLICATED' => 'Le colonne non possono essere duplicate',
		),
		'en_us' => array(
			'COLUMNS_CANNOT_BE_DUPLICATED' => 'Columns cannot be duplicated',
		),
		'de_de' => array(
			'COLUMNS_CANNOT_BE_DUPLICATED' => 'Spalten dürfen nicht doppelt ausgewählt werden.',
		),
		'nl_nl' => array(
			'COLUMNS_CANNOT_BE_DUPLICATED' => 'Kollommen kunnen niet gekopieerd worden',
		),
		'pt_br' => array(
			'COLUMNS_CANNOT_BE_DUPLICATED' => 'As Colunas não podem ser duplicadas',
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

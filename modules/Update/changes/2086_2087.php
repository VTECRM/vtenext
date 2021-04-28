<?php

/* crmv@205899 */

$trans = [
	'ALERT_ARR' => [
		'en_us' => [
			'LBL_GRAPES_MODULE' => 'Module',
			'LBL_GRAPES_FIELD' => 'Field',
			'LBL_GRAPES_INSERT' => 'Insert',
			'LBL_GRAPES_EMPTY_PLACEHOLDER' => '-- Select --',
		],
		'it_it' => [
			'LBL_GRAPES_MODULE' => 'Modulo',
			'LBL_GRAPES_FIELD' => 'Campo',
			'LBL_GRAPES_INSERT' => 'Inserisci',
			'LBL_GRAPES_EMPTY_PLACEHOLDER' => '-- Seleziona --',
		],
	]
];

foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		foreach ($translist as $label => $translabel) {
			SDK::setLanguageEntry($module, $lang, $label, $translabel);
		}
	}
}

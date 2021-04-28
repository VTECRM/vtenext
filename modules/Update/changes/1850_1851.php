<?php

/* crmv@185501 */

$trans = array(
	'Calendar' => array(
		'it_it' => array(
			'Second' => 'Secondo',
			'Third' => 'Terzo',
			'Fourth' => 'Quarto',
		),
		'en_us' => array(
			'Second' => 'Second',
			'Third' => 'Third',
			'Fourth' => 'Fourth',
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

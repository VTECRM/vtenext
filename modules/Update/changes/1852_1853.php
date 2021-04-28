<?php

/* crmv@185576 */

$trans = array(
	'Calendar' => array(
		'it_it' => array(
			'LBL_REPEATEVENT' => 'Ripeti ogni',
			'LBL_FORXTIMES' => 'Per %s volte',
		),
		'en_us' => array(
			'LBL_REPEATEVENT' => 'Repeat every',
			'LBL_FORXTIMES' => 'For %s times',
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

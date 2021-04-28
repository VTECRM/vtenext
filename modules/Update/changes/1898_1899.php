<?php
$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_PM_ACTION_EMAIL_SELECT_PARENTID' => 'Relaziona email a',
			'LBL_PM_ACTION_EMAIL_AUTOMATIC_MODE' => 'Automatico (default)',
		),
		'en_us' => array(
			'LBL_PM_ACTION_EMAIL_SELECT_PARENTID' => 'Relate email to',
			'LBL_PM_ACTION_EMAIL_AUTOMATIC_MODE' => 'Automatic (default)',
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
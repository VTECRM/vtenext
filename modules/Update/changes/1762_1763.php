<?php 

// crmv@171115

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'confirm_exit_from_panel' => 'Vuoi uscire dal pannello? I dati inseriti non verranno salvati.',
		),
		'en_us' => array(
			'confirm_exit_from_panel' => 'Do you want to exit? The data entered will not be saved.',
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

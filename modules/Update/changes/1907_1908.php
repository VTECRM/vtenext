<?php

// crmv@190014
global $adb, $table_prefix;

$adb->addColumnToTable($table_prefix.'_extws', 'rawbody', 'XL');


$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_EXTWS_RAWBODY' => 'Corpo grezzo',
		),
		'en_us' => array(
			'LBL_EXTWS_RAWBODY' => 'Raw body',
		),
	),
);
$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module=>$modlang) {
	foreach ($modlang as $lang=>$translist) {
		if (array_key_exists($lang,$languages)) {
			foreach ($translist as $label=>$translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}

<?php

$trans = array(
	'Home' => array(
		'it_it' => array(
			'Help VTE' => 'Guida VTENEXT',
			'News CRMVILLAGE.BIZ' => 'News da VTENEXT',
			'CRMVNEWS'=>'News da VTENEXT',
		),
		'en_us' => array(
			'Help VTE'=>'Help VTENEXT',
			'News CRMVILLAGE.BIZ'=>'News from VTENEXT',
			'CRMVNEWS'=>'News from VTENEXT',
		),
	),
	'APP_STRINGS' => array(
		'it_it' => array(
			'LBL_AGO'=>'%s fa',
			'LBL_IN_TIME' => 'Fra %s',
			'LBL_SECOND'=>'Secondo',
			'LBL_SECONDS'=>'Secondi',
			'LBL_MINUTE'=>'Minuto',
			'LBL_MINUTES'=>'Minuti',
			'LBL_HOUR'=>'Ora',
			'LBL_HOURS'=>'Ore',
			'LBL_DAY'=>'Giorno',
			'LBL_DAYS'=>'Giorni',
			'LBL_WEEK'=>'Settimana',
			'LBL_WEEKS'=>'Settimane',
			'LBL_MONTH'=>'Mese',
			'LBL_MONTHS'=>'Mesi',
			'LBL_YEAR'=>'Anno',
			'LBL_YEARS'=>'Anni',
			'LBL_DECADE'=>'Decade',
			'LBL_DECADES'=>'Decadi',
		),
		'en_us' => array(
			'LBL_AGO'=>'%s ago',
			'LBL_IN_TIME' => 'in %s',
			'LBL_SECOND'=>'Second',
			'LBL_SECONDS'=>'Seconds',
			'LBL_MINUTE'=>'Minute',
			'LBL_MINUTES'=>'Minutes',
			'LBL_HOUR'=>'Hour',
			'LBL_HOURS'=>'Hours',
			'LBL_DAY'=>'Day',
			'LBL_DAYS'=>'Days',
			'LBL_WEEK'=>'Week',
			'LBL_WEEKS'=>'Weeks',
			'LBL_MONTH'=>'Month',
			'LBL_MONTHS'=>'Months',
			'LBL_YEAR'=>'Year',
			'LBL_YEARS'=>'Years',
			'LBL_DECADE'=>'Decade',
			'LBL_DECADES'=>'Decades',
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

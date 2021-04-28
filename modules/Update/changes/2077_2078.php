<?php 

/* crmv@199834 */

$trans = [
	'APP_STRINGS' => [
		'en_us' => [
			'LBL_SHARING_RECALC_SCHEDULED' => 'The share recalculation has been scheduled and will run in the background.',
			'LBL_SHARING_RECALC_RUNNING' => 'The share recalculation is in progress.',
			'LBL_SHARING_RECALC_ABORTED' => 'The share recalculation was canceled.',
			'LBL_SHARING_RECALC_LASTDATE' => 'The last share recalculation was performed on {start_date}.',
		],
		'it_it' => [
			'LBL_SHARING_RECALC_SCHEDULED' => 'Il ricalcolo dei privilegi è stato schedulato e verrà eseguito in background.',
			'LBL_SHARING_RECALC_RUNNING' => 'Il ricalcolo dei privilegi è in corso.',
			'LBL_SHARING_RECALC_ABORTED' => 'Il ricalcolo dei privilegi è stato annullato.',
			'LBL_SHARING_RECALC_LASTDATE' => 'L\'ultimo ricalcolo dei privilegi è stato eseguito il {start_date}.',
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

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('performance.recalc_privileges_limit', 50);

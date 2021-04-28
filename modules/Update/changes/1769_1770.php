<?php

// crmv@172355

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'LBL_CHART_NO_DATA' => 'Nessun dato disponibile.',
			'LBL_CHART_NO_SUMMARY' => 'Report sprovvisto di riassuntivo.',
			'LBL_REPORT_REMOVE_CHARTS_1' => 'Il report non è più riassuntivo, ma hai 1 grafico associato. Eliminarlo?',
			'LBL_REPORT_REMOVE_CHARTS_N' => 'Il report non è più riassuntivo, ma hai {n} grafici associati. Eliminarli?',
		),
		'en_us' => array(
			'LBL_CHART_NO_DATA' => 'No data available.',
			'LBL_CHART_NO_SUMMARY' => 'Report doesn\'t have summary.',
			'LBL_REPORT_REMOVE_CHARTS_1' => 'Report doesn\'t have summary anymore, but you have 1 chart still associated. Delete it?',
			'LBL_REPORT_REMOVE_CHARTS_N' => 'Report doesn\'t have summary anymore, but you have {n} charts still associated. Delete them?',
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

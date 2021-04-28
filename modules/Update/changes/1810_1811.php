<?php

// crmv@177677
$adb->pquery("UPDATE {$table_prefix}_eventhandlers SET event_name = ? WHERE handler_class = ?", array('vtiger.entity.aftersave.processes', 'ProcessMakerHandler'));
$adb->addColumnToTable($table_prefix.'_changelog', 'request_id', 'C(63)');

Update::info('Processes now are triggered by the vtiger.entity.aftersave.processes event.');
Update::info('If you have customizations using the old event, please review them.');
Update::info('');


// crmv@177381 
$trans = array(
	'Reports' => array(
		'it_it' => array(
			'LBL_EXPORT_FILTERED' => 'Esporta dati filtrati',
		),
		'en_us' => array(
			'LBL_EXPORT_FILTERED' => 'Export filtered data',
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

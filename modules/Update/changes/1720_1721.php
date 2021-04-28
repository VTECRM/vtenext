<?php 

// crmv@164120

if ($adb->isMysql()) {
	$adb->query(
		"ALTER TABLE {$table_prefix}_changelog
			ADD COLUMN display_id INT(19),
			ADD COLUMN display_module VARCHAR(63),
			ADD COLUMN display_name VARCHAR(255)"
	);
} else {
	$adb->addColumnToTable($table_prefix.'_changelog', 'display_id', 'I(19)');
	$adb->addColumnToTable($table_prefix.'_changelog', 'display_module', 'C(63)');
	$adb->addColumnToTable($table_prefix.'_changelog', 'display_name', 'C(255)');
}

// crmv@164355

$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_EXPORT_AUDIT_TRAIL' => 'Esporta Controllo Utente',
		),
		'en_us' => array(
			'LBL_EXPORT_AUDIT_TRAIL' => 'Export Audit Trail',
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

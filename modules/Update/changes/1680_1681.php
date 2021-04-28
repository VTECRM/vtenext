<?php

// crmv@161368

$table = $table_prefix.'_touch_wipedata';
$schema_table =
'<schema version="0.3">
	<table name="'.$table.'">
		<opt platform="mysql">ENGINE=InnoDB</opt>
		<field name="userid" type="I" size="19">
			<KEY/>
		</field>
		<field name="wipe_date" type="T">
			<default value="0000-00-00 00:00:00" />
		</field>
	</table>
</schema>';
if(!Vtiger_Utils::CheckTable($table)) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
}


$trans = array(
	'Users' => array(
		'it_it' => array(
			'LBL_REMOTE_WIPE' => 'Elimina dati remoti',
		),
		'en_us' => array(
			'LBL_REMOTE_WIPE' => 'Remote wipe',
		),
	),
	'Settings' => array(
		'it_it' => array(
			'LBL_REMOTE_WIPE' => 'Elimina dati remoti',
		),
		'en_us' => array(
			'LBL_REMOTE_WIPE' => 'Remote wipe',
		),
	),
	'ALERT_ARR' => array(
		'it_it' => array(
			'LBL_CONFIRM_REMOTE_WIPE' => 'I dati scaricati dall\'utente su tutti i dispositivi associati verranno eliminati. Procedere?',
			'LBL_REMOTE_WIPE_OK' => 'Operazione completata. Al prossimo accesso via app, l\'utente verrà disconnesso.',
		),
		'en_us' => array(
			'LBL_CONFIRM_REMOTE_WIPE' => 'All donwloaded data from this user on associated devices will be deleted. Proceed?',
			'LBL_REMOTE_WIPE_OK' => 'Operation completed. At the next access via app the user will be disconnected.',
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

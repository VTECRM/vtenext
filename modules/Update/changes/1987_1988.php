<?php 

global $adb, $table_prefix;

$adb->query("UPDATE {$table_prefix}_settings_field SET iconpath = 'vtesync.png' WHERE name = 'LBL_SYNC_SETTINGS'");
$adb->query("UPDATE {$table_prefix}_settings_field SET iconpath = 'call_split' WHERE name = 'LBL_PROCESS_MAKER'");

/* crmv@194723 */

if (!Vtiger_Utils::CheckTable('tbl_s_calendar_resources')) {
	$schema = '<?xml version="1.0"?>
		<schema version="0.3">
		<table name="tbl_s_calendar_resources">
			<opt platform="mysql">ENGINE=InnoDB</opt>
			<field name="userid" type="R" size="19">
				<KEY/>
			</field>
			<field name="shownid" type="C" size="255">
				<KEY/>
			</field>
		</table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

$trans = array(
	'Calendar' => array(
		'it_it' => array(
			'LBL_RESOURCES' => 'Risorse',
			'LBL_SELECT_RESOURCES' => 'Seleziona risorse',
			'LBL_SELECT_RESOURCES_ALERT' => 'Puoi selezionare solo gli utenti che hanno deciso di condividere con te il calendario',
		),
		'en_us' => array(
			'LBL_RESOURCES' => 'Resources',
			'LBL_SELECT_RESOURCES' => 'Select resources',
			'LBL_SELECT_RESOURCES_ALERT' => 'You can only select users who have decided to share the calendar with you',
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

<?php

// crmv@199352

$res = $adb->pquery("SELECT fieldid FROM {$table_prefix}_settings_field WHERE name = ?", array('LBL_AUTOUPDATE'));
if ($res && $adb->num_rows($res) == 0) {
	// get privacy sequence 
	$res = $adb->pquery("SELECT sequence FROM {$table_prefix}_settings_field WHERE name = ?", array('LBL_PRIVACY'));
	$seq = $adb->query_result_no_html($res, 0, 'sequence');
	require_once('vtlib/Vtecrm/SettingsField.php');
	require_once('vtlib/Vtecrm/SettingsBlock.php');
	$field = new Vtecrm_SettingsField();
	$field->name = 'LBL_AUTOUPDATE';
	$field->sequence = $seq;
	$field->iconpath = 'update';
	$field->description = 'LBL_AUTOUPDATE_DESCRIPTION';
	$field->linkto = 'index.php?module=Update&action=ViewUpdate&parenttab=Settings';
	$block = Vtecrm_SettingsBlock::getInstance('LBL_OTHER_SETTINGS');
	$block->addField($field);
	
	// move down last 2 fields
	$res = $adb->pquery("UPDATE {$table_prefix}_settings_field SET sequence = sequence + 1 WHERE blockid = ? AND name IN (?,?)", array($block->id, 'LBL_PRIVACY', 'LBL_LOG_CONFIG'));
}


$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_AUTOUPDATE' => 'Aggiornamento sistema',
		),
		'en_us' => array(
			'LBL_AUTOUPDATE' => 'System updates',
		),
	),
	'Update' => array(
		'it_it' => array(
			'LBL_NO_UPDATES_AVAILABLE' => 'Al momento non ci sono aggiornamenti disponibili.',
			'LBL_NO_UPDATES_CRON' => 'Il controllo degli aggiornamenti non è attivo. Verifica la configurazione dei cron.',
			'LBL_LAST_CHECK' => 'Ultimo controllo: ',
			'LBL_CHECK_NOW' => 'Controlla ora',
			'LBL_CRON_FORCED' => 'Entro i prossimi minuti verrà controllata la disponibilità di aggiornamenti. Verrai notificato in caso ce e siano',
			'LBL_PROCESSING_UPDATE' => 'L\'aggiornamento è in fase di verifica, per favore attendi qualche minuto.',
		),
		'en_us' => array(
			'LBL_NO_UPDATES_AVAILABLE' => 'At the moment there are no updates available.',
			'LBL_NO_UPDATES_CRON' => 'Update check is not active, please verify cron configuration.',
			'LBL_LAST_CHECK' => 'Last check: ',
			'LBL_CHECK_NOW' => 'Check now',
			'LBL_CRON_FORCED' => 'Updates will be checked in a few minutes. You\'ll be notified in case of a new available version.',
			'LBL_PROCESSING_UPDATE' => 'The update is being verified, please wait a few minutes.',
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

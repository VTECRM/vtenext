<?php

global $adb, $table_prefix;

$adb->pquery("UPDATE {$table_prefix}_cronjobs SET repeat_sec = ? WHERE cronname = ? AND repeat_sec = ?", array(7200, 'CheckUpdates', 21600));

// crmv@182073 

$trans = array(
	'Update' => array(
		'it_it' => array(
			'LBL_ALERT_CHANGES' => 'Sono state rilevate differenze nei files di vte. Procedere comunque con l\'aggiornamento (potresti perdere delle personalizzazioni)?',
			'LBL_VIEW_FILES_LIST' => 'Vedi lista di files',
			'LBL_MODIFIED_FILES' => 'Files con differenze',
			'LBL_NOTIFICATION_TPL_TEXT' => 'E\' disponibile un aggiornamento di vtenext. Clicca <a href="{url}">qui</a> per i dettagli.',
			'LBL_ALREADY_CHOSEN' => 'Un altro utente ha già pianificato o ignorato l\'aggiornamento',
			'LBL_OS_NOT_SUPPORTED' => 'L\'aggiornamento automatico non è supportato su questo sistema operativo.',
			'LBL_OS_NOT_SUPPORTED_UPDATE' => 'L\'aggiornamento automatico non è supportato su questo sistema operativo. E\' necessario aggiornare manualmente.',
			'LBL_MANUAL_INFO_1' => 'Eseguire un backup dei files e del database di vtenext',
			'LBL_MANUAL_INFO_2' => 'Scaricare il pacchetto di aggiornamento da questo <b><a href="%s">indirizzo</a></b>',
			'LBL_MANUAL_INFO_3' => 'Sovrascrivere i files nella cartella di vtenext con quelli del pacchetto',
			'LBL_MANUAL_INFO_4' => 'Andare a questa <b><a href="%s">pagina</a></b> e cliccare <i>update</i>',
		),
		'en_us' => array(
			'LBL_ALERT_CHANGES' => 'Some changes have been found in vte files. Do you want to update anyway (you might loose some customizations)?',
			'LBL_VIEW_FILES_LIST' => 'View files list',
			'LBL_MODIFIED_FILES' => 'Files with differencies',
			'LBL_NOTIFICATION_TPL_TEXT' => 'An update of vtenext is available. Click <a href="{url}">here</a> for details.',
			'LBL_ALREADY_CHOSEN' => 'Another user already scheduled or ignored the update',
			'LBL_OS_NOT_SUPPORTED' => 'Automatic update is not supported on this operating system.',
			'LBL_OS_NOT_SUPPORTED_UPDATE' => 'Automatic update is not supported on this operating system. You have to update manually.',
			'LBL_MANUAL_INFO_1' => 'Backup vtenext files and database',
			'LBL_MANUAL_INFO_2' => 'Download the update package from this <b><a href="%s">address</a></b>',
			'LBL_MANUAL_INFO_3' => 'Overwrite the files in the root folder of vtenext with the ones in the downloaded package',
			'LBL_MANUAL_INFO_4' => 'Go to this <b><a href="%s">page</a></b> and click <i>update</i>',
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

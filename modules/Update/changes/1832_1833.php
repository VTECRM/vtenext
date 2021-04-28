<?php

/* crmv@183486 */

/* new release 19.07 ! */

global $enterprise_current_version, $enterprise_mode, $enterprise_website;
SDK::setLanguageEntries('APP_STRINGS', 'LBL_BROWSER_TITLE', array(
	'it_it'=>"$enterprise_mode $enterprise_current_version",
	'en_us'=>"$enterprise_mode $enterprise_current_version",
	'de_de'=>"$enterprise_mode $enterprise_current_version",
	'nl_nl'=>"$enterprise_mode $enterprise_current_version",
	'pt_br'=>"$enterprise_mode $enterprise_current_version")
);

$result = $adb->query("select templateid, body from {$table_prefix}_emailtemplates where body LIKE '%vtenext 19.06%'");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$body = $row['body'];
		$body = str_replace('VTENEXT 19.06', $enterprise_mode.' '.$enterprise_current_version, $body);
		$adb->updateClob($table_prefix.'_emailtemplates','body',"templateid = ".$row['templateid'],$body);
	}
}

// remove temporary alias for module manager
if (is_file("updater.html") && is_writable("updater.html")) {
	@unlink("updater.html");
}

if (is_file("updater.zip") && is_writable("updater.zip")) {
	@unlink("updater.zip");
}

if (is_file("modules/Update/md5_hashes.txt") && is_writable("modules/Update/md5_hashes.txt")) {
	@unlink("modules/Update/md5_hashes.txt");
}


/* crmv@183486 */

// install other labels
$trans = array(
	'Update' => array(
		'it_it' => array(
			'LBL_REMIND_IN_4_HOURS' => 'Tra 4 ore',
			'LBL_POPUP_TITLE' => '<b>La versione {version} di vtenext è disponibile!</b>',
			'LBL_MANUAL_INFO_1' => 'Eseguire un backup dei files e del database di vtenext, vedi <b><a href="%s" target="_blank">qui</a></b> per le istruzioni',
			'LBL_MANUAL_INFO_3' => 'Decomprimere il file scaricato e sovrascrivere con il suo contenuto i file nella cartella <i>%s</i>',
			'LBL_ALL_USERS' => 'Tutti gli utenti',
			'LBL_UPDATE_DEFAULT_MESSAGE' => "Gentile utente,
è stato pianificato un aggiornamento di vtenext per il giorno {date} alle {hour}.
Durante l'aggiornamento non sarà possibile utilizzare il sistema.

{cancel_text}",
			'LBL_UPDATE_DEFAULT_CANCEL_TEXT' => "Se vuoi annullare l'aggiornamento, clicca <b><a href=\"%s\">qui</a></b>.",
			'LBL_CANNOT_CANCEL' => 'Non è più possibile annullare l\'aggiornamento',
			'LBL_CANCEL_UPDATE_TITLE' => 'Annulla aggiornamento',
			'LBL_CANCEL_UPDATE_TEXT' => 'Un aggiornamento di vtenext è pianificato per il giorno {date} alle ore {hour}',
			'LBL_CANCEL_UPDATE_ASK' => 'Vuoi annullarlo?',
			'LBL_CANCEL_UPDATE_INFO' => 'Potrai riprogrammarlo cliccando sulla notifica originale',
			'LBL_CANCEL_BODY' => "Gentile utente,
l'aggiornamento pianificato per il giorno {date} alle {hour} è stato annullato.",
			'LBL_NEED_PHP_70' => 'La versione di PHP è troppo vecchia. La nuova versione di vtenext richiede almeno PHP 7.0',
			'LBL_PHP_OK_WAIT_CRON' => 'PHP è ora aggiornato. Aspetta qualche ora e verrai nuovamente notificato della disponibilità dell\'aggiornamento',
			'LBL_UPDATE_RUNNING_WAIT' => 'Aggiornamento in corso, attendere...',
			'LBL_UPDATE_FINISHED' => 'Aggiornamento completato',
			'LBL_UPDATE_FAILED' => 'Aggiornamento fallito. Verifica l\'errore, e riprova, ripristinando il backup.',
			'LBL_CONTINUE' => 'Prosegui',
			'LBL_DATE_IS_PAST' => 'La data non può essere nel passato',
			'LBL_DATE_TOO_CLOSE' => "La data deve essere almeno 10 minuti da ora",
			'LBL_UPDATE_MESSAGE_OK' => "Gentile {name},
l'aggiornamento di vtenext è stato completato correttamente.
Verifica i file allegati per i log dell'aggiornamento.",
	'LBL_UPDATE_MESSAGE_FAIL_RB' => "Gentile {name},
l'aggiornamento di vtenext non ha avuto successo, ed il sistema è stato ripristinato alla versione precedente.
E' necessario eseguire l'aggiornamento manualmente.
Verifica i file allegati per i log dell'aggiornamento.",
	'LBL_UPDATE_MESSAGE_FAIL' => "Gentile {name},
l'aggiornamento di vtenext non ha avuto successo, e non è stato possibile ripristinare il sistema alla versione precedente.
E' necessario ripristinare manualmente i backup e procedere con l'aggiornamento.
Verifica i file allegati per i log dell'aggiornamento.",
		),
		'en_us' => array(
			'LBL_REMIND_IN_4_HOURS' => 'In 4 hours',
			'LBL_POPUP_TITLE' => '<b>Version {version} of vtenext is available!</b>',
			'LBL_MANUAL_INFO_1' => 'Backup vtenext files and database, see <b><a href="%s" target="_blank">here</a></b> for instructions',
			'LBL_MANUAL_INFO_3' => 'Unzip the downloaded file and overwrite with its contents the folder <i>%s</i>',
			'LBL_ALL_USERS' => 'All users',
			'LBL_UPDATE_DEFAULT_MESSAGE' => "Dear user,
an update for vtenext has been planned for {date} at {hour}.
During the update the system will be unavailable.

{cancel_text}",
			'LBL_UPDATE_DEFAULT_CANCEL_TEXT' => "If you want to cancel the update, please click <b><a href=\"%s\">here</a></b>.",
			'LBL_CANNOT_CANCEL' => 'At this time it\'s not possible to cancel the update process',
			'LBL_CANCEL_UPDATE_TITLE' => 'Cancel Update',
			'LBL_CANCEL_UPDATE_TEXT' => 'An update for vtenext is planned for {date} at {hour}',
			'LBL_CANCEL_UPDATE_ASK' => 'Do you want to cancel it?',
			'LBL_CANCEL_UPDATE_INFO' => 'You can schedule it again by clicking on the original notification',
			'LBL_CANCEL_BODY' => "Dear user,
the update scheduled for {date} at {hour} has been canceled.",
			'LBL_NEED_PHP_70' => 'PHP version is too old. The new version of vtenext requires at least PHP 7.0',
			'LBL_PHP_OK_WAIT_CRON' => 'PHP is now updated. Wait a few hours and you\'ll be notified again about the update',
			'LBL_UPDATE_RUNNING_WAIT' => 'Updating, please wait...',
			'LBL_UPDATE_FINISHED' => 'Update finished',
			'LBL_UPDATE_FAILED' => 'Update failed. Please verify the error and try again, restoring the backup.',
			'LBL_CONTINUE' => 'Continue',
			'LBL_DATE_IS_PAST' => 'Date cannot be in the past',
			'LBL_DATE_TOO_CLOSE' => "Date must be at least 10 minutes from now",
			'LBL_UPDATE_MESSAGE_OK' => "Dear {name},
the update of vtenext has been completed succesfully.
Check the attached files for the update logs.",
	'LBL_UPDATE_MESSAGE_FAIL_RB' => "Dear {name},
the update of vtenext failed, and the system has been restored to the previous version.
It's necessary to proceed with a manual update.
Check the attached files for the update logs.",
	'LBL_UPDATE_MESSAGE_FAIL' => "Dear {name},
the update of vtenext failed, but it wasn't possible to restore the system to the previous version.
It's necessary to manually restore backups abd proceed with the update.
Check the attached files for the update logs.",
		),
	),
	'ALERT_ARR' => array(
		'it_it' => array(
			'update_canceled' => 'Aggiornamento annullato',
		),
		'en_us' => array(
			'update_canceled' => 'Update canceled',
		),
	)

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

// and remove old labels
SDK::deleteLanguageEntry('Update', 'it_it', 'LBL_REMIND_IN_1_HOUR');
SDK::deleteLanguageEntry('Update', 'en_us', 'LBL_REMIND_IN_1_HOUR');

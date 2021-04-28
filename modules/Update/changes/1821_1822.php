<?php

global $adb, $table_prefix;

if (isModuleInstalled('RecycleBin')) {
	$_SESSION['modules_to_update']['RecycleBin'] = 'packages/vte/optional/RecycleBin.zip';
}

// crmv@181317

if (Vtiger_Utils::CheckTable('erpbaseaccount')) {
	// check if it's empty
	$res = $adb->query("SELECT COUNT(*) as cnt FROM erpbaseaccount");
	if ($res && $adb->query_result_no_html($res, 0, 'cnt') == 0) {
		// remove it!
		$sqlarray = $adb->datadict->DropTableSQL("erpbaseaccount");
		$adb->datadict->ExecuteSQLArray($sqlarray);
	}
}


/* crmv@180825 */

$VP = VTEProperties::getInstance();
$list = $VP->get('performance.editview_changelog_force_writable_uitypes');
if (is_array($list) && !in_array(5, $list)) {
	$list = array_merge($list, array(5,6,23));
	$VP->set('performance.editview_changelog_force_writable_uitypes', $list);
}


// crmv@181161 - automatic updates

global $adb, $table_prefix;
if (file_exists('hash_version.txt')) {
	$hash_version = file_get_contents('hash_version.txt');
	$hash_version_d = Users::de_cryption($hash_version);
	
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$processMakerUtils = ProcessMakerUtils::getInstance();
	$hash_version_d = preg_replace('/\$this->limit_processes = [0-9]+;/','$this->limit_processes = '.$processMakerUtils->limit_processes.';',$hash_version_d);
	$hash_version = Users::changepassword($hash_version_d);

	$adb->updateClob($table_prefix.'_version','hash_version','id=1',$hash_version);
	@unlink('hash_version.txt');
}
$cache = Cache::getInstance('vteCacheHV');
$cache->clear();


// enable update module
if (!vtlib_isModuleActive('Update')) {
	vtlib_toggleModuleAccess('Update',true);
}

require_once('vtlib/Vtecrm/Module.php');

// force post install script
Vtecrm_Module::fireEvent('Update', Vtecrm_Module::EVENT_MODULE_POSTINSTALL);

// install other labels
$trans = array(
	'Update' => array(
		'it_it' => array(
			'LBL_POPUP_TITLE' => 'È disponibile la <b>versione {version} di vtenext</b>',
			'LBL_SCHEDULE' => 'Pianifica',
			'LBL_SCHEDULE_UPDATE' => 'Pianifica aggiornamento',
			'LBL_REMIND' => 'Ricordamelo',
			'LBL_REMIND_IN_1_HOUR' => 'Tra un\'ora',
			'LBL_REMIND_TOMORROW' => 'Domani',
			'LBL_REMIND_NEXT_WEEK' => 'La settimana prossima',
			'LBL_IGNORE_UPDATE' => 'Ignora aggiornamento',
			'LBL_WHEN_SCHEDULE_UPDATE' => 'Quando vuoi programmare l\'aggiornamento?',
			'LBL_ALERT_USER_OF_UPDATE' => 'Avvisa gli utenti dell\'aggiornamento',
			'LBL_SEND_THIS_MESSAGE' => 'Invia agli utenti questo messaggio di avviso',
			'LBL_UPDATE_SCHEDULED' => 'Aggiornamento pianificato con successo',
			'LBL_UPDATE_DEFAULT_MESSAGE' => "Gentile utente,
è stato pianificato un aggiornamento di vtenext per il giorno {date} alle {hour}.
Durante l'agggiornamento non sarà possibile utilizzare il sistema.

Se vuoi posticipare l\'aggiornamento, contatta l'utente {update_user}.",
			'LBL_UPDATE_MESSAGE_OK' => "Gentile {name},
l'aggiornamento di vtenext è stato completato correttamente.",
			'LBL_UPDATE_MESSAGE_FAIL_RB' => "Gentile {name},
l'aggiornamento di vtenext non ha avuto successo, ed il sistema è stato ripristinato alla versione precedente.
E' necessario eseguire l'aggiornamento manualmente.",
			'LBL_UPDATE_MESSAGE_FAIL' => "Gentile {name},
l'aggiornamento di vtenext non ha avuto successo, e non è stato possibile ripristinare il sistema alla versione precedente.
E' necessario ripristinare manualmente i backup e procedere con l'aggiornamento.",
		),
		'en_us' => array(
			'LBL_POPUP_TITLE' => 'The <b>new version {version} of vtenext</b> is avaialble</b>',
			'LBL_SCHEDULE' => 'Schedule',
			'LBL_SCHEDULE_UPDATE' => 'Schedule update',
			'LBL_REMIND' => 'Remind me',
			'LBL_REMIND_IN_1_HOUR' => 'In one hour',
			'LBL_REMIND_TOMORROW' => 'Tomorrow',
			'LBL_REMIND_NEXT_WEEK' => 'Next week',
			'LBL_IGNORE_UPDATE' => 'Ignore update',
			'LBL_WHEN_SCHEDULE_UPDATE' => 'When do you want to schedule the update?',
			'LBL_ALERT_USER_OF_UPDATE' => 'Alert users about the update',
			'LBL_SEND_THIS_MESSAGE' => 'Send this notice to users',
			'LBL_UPDATE_SCHEDULED' => 'Update scheduled succesfully',
			'LBL_UPDATE_DEFAULT_MESSAGE' => "Dear user,
an update for vtenext has been planned for {date} at {hour}.
During the update the system will be unavailable.

If you want to postpone the update, please contact the user {update_user}.",
			'LBL_UPDATE_MESSAGE_OK' => "Dear {name},
the update of vtenext has been completed succesfully.",
			'LBL_UPDATE_MESSAGE_FAIL_RB' => "Dear {name},
the update of vtenext failed, and the system has been restored to the previous version.
It's necessary to proceed with a manual update.",
			'LBL_UPDATE_MESSAGE_FAIL' => "Dear {name},
the update of vtenext failed, but it wasn't possible to restore the system to the previous version.
It's necessary to manually restore backups abd proceed with the update.",
		),
	),
	'ALERT_ARR' => array(
		'it_it' => array(
			'update_ignored' => 'Aggiornamento ignorato',
			'update_postponed' => 'Aggiornamento posticipato',
			'LBL_YOU_MUST_SELECT_USERS' => 'Devi selezionare degli utenti',
			'LBL_YOU_MUST_TYPE_A_MESSAGE' => 'Devi scrivere un messaggio',
			// vte sync labels, since they have troubles during install
			'LBL_VTESYNC_SELECT_TYPE' => 'Devi selezionare un sistema esterno',
			'LBL_VTESYNC_SELECT_MODS' => 'Devi selezionare almeno un modulo',
			'LBL_VTESYNC_SELECT_AUTH' => 'Devi selezionare un tipo di autenticazione',
			'LBL_VTESYNC_FILL_OAUTH2' => 'Devi inserire tutti i dati necessari all\'autenticazione',
			'LBL_VTESYNC_OAUTH2_AUTH' => 'Devi autorizzare le credenziali inserite',
		),
		'en_us' => array(
			'update_ignored' => 'Update ignored',
			'update_postponed' => 'Update postponed',
			'LBL_YOU_MUST_SELECT_USERS' => 'You should select some users',
			'LBL_YOU_MUST_TYPE_A_MESSAGE' => 'You should write a message',
			// vte sync labels, since they have troubles during install
			'LBL_VTESYNC_SELECT_TYPE' => 'You have to select an external system',
			'LBL_VTESYNC_SELECT_MODS' => 'You have to select at least one module',
			'LBL_VTESYNC_SELECT_AUTH' => 'You have to select an authentication method',
			'LBL_VTESYNC_FILL_OAUTH2' => 'You have to fill all fields needed for authentication',
			'LBL_VTESYNC_OAUTH2_AUTH' => 'You have to authorize the provided credentials',
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


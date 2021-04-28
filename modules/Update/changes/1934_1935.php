<?php 

// crmv@192033

@unlink("modules/Rss/Popup.html");
@unlink("modules/Rss/ListView.html");

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'DELETE_RSSFEED_CONFIRMATION' => 'Sicuro di voler cancellare questo feed Rss?',
			'LBL_AUDIT_TRAIL_ENABLED' => 'Controllo utente abilitato',
			'LBL_AUDIT_TRAIL_DISABLED' => 'Controllo utente disabilitato',
			'PLEASE_ENTER_TAG' => 'Prego inserire un tag',
		),
		'en_us' => array(
			'DELETE_RSSFEED_CONFIRMATION' => 'Are you sure to delete the rss feed?',
			'LBL_AUDIT_TRAIL_ENABLED' => 'Audit Trail Enabled',
			'LBL_AUDIT_TRAIL_DISABLED' => 'Audit Trail Disabled',
			'PLEASE_ENTER_TAG' => 'Please enter a tag',
		),
		'de_de' => array(
			'DELETE_RSSFEED_CONFIRMATION' => 'Wollen Sie den RSS Feed wirklich löschen?',
			'LBL_AUDIT_TRAIL_ENABLED' => 'Buchungsprotokoll aktiviert',
			'LBL_AUDIT_TRAIL_DISABLED' => 'Buchungsprotokoll deaktiviert',
			'PLEASE_ENTER_TAG' => 'Bitte einen Tag angeben',
		),
		'nl_nl' => array(
			'DELETE_RSSFEED_CONFIRMATION' => 'Weet u zeker dat u de RSS feed wilt verwijderen?',
			'LBL_AUDIT_TRAIL_ENABLED' => 'Audit Trail actief',
			'LBL_AUDIT_TRAIL_DISABLED' => 'Audit Trail niet actief',
			'PLEASE_ENTER_TAG' => 'Geef een tag in',
		),
		'pt_br' => array(
			'DELETE_RSSFEED_CONFIRMATION' => 'Você realmente deseja apagar o Feed RSS?',
			'LBL_AUDIT_TRAIL_ENABLED' => 'Verificar usuários habilitados',
			'LBL_AUDIT_TRAIL_DISABLED' => 'Verificar usuários desabilitados',
			'PLEASE_ENTER_TAG' => 'Por favor, digite uma tag',
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

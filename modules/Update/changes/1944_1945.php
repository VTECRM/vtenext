<?php 

// crmv@194449

@unlink('Smarty/templates/themes/next/Navbar.tpl');
@unlink('Smarty/templates/themes/next/Buttons/FolderViewButtons.tpl');
@unlink('Smarty/templates/themes/next/Buttons/ReportsButtons.tpl');

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'SERVERNAME_CANNOT_BE_EMPTY' => 'Il nome del server non puo` essere vuoto',
		),
		'en_us' => array(
			'SERVERNAME_CANNOT_BE_EMPTY' => 'Server Name cannot be empty',
		),
		'de_de' => array(
			'SERVERNAME_CANNOT_BE_EMPTY' => 'Bitte einen Servernamen angeben.',
		),
		'nl_nl' => array(
			'SERVERNAME_CANNOT_BE_EMPTY' => 'Servernaam kan niet leeg zijn',
		),
		'pt_br' => array(
			'SERVERNAME_CANNOT_BE_EMPTY' => 'Nome do servidor não pode estar vazio',
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

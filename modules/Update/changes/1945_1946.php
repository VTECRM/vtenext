<?php 

// crmv@194390

$trans = array(
	'APP_STRINGS' => array(
		'it_it' => array(
			'UNABLE_GENERATE_ADDRESS' => 'Impossibile generare la query dell\'indirizzo',
			'LBL_PRIMARY_ADDRESS' => 'Indirizzo Primario',
			'LBL_ALTERNATE_ADDRESS' => 'Altro Indirizzo',
			'CHOOSE_ADDRESS_TO_VIEW' => 'Scegli quale indirizzo visualizzare',
		),
		'en_us' => array(
			'UNABLE_GENERATE_ADDRESS' => 'Unable to generate address query',
			'LBL_PRIMARY_ADDRESS' => 'Primary Address',
			'LBL_ALTERNATE_ADDRESS' => 'Other Address',
			'CHOOSE_ADDRESS_TO_VIEW' => 'Choose which address to display',
		),
		'de_de' => array(
			'UNABLE_GENERATE_ADDRESS' => 'Adressabfrage kann nicht generiert werden',
			'LBL_PRIMARY_ADDRESS' => 'primäre Adresse',
			'LBL_ALTERNATE_ADDRESS' => 'andere Adresse',
			'CHOOSE_ADDRESS_TO_VIEW' => 'Wählen Sie die anzuzeigende Adresse',
		),
		'nl_nl' => array(
			'UNABLE_GENERATE_ADDRESS' => 'Kan adresquery niet genereren',
			'LBL_PRIMARY_ADDRESS' => 'Primair adres',
			'LBL_ALTERNATE_ADDRESS' => 'Adres (overig)',
			'CHOOSE_ADDRESS_TO_VIEW' => 'Kies welk adres u wilt weergeven',
		),
		'pt_br' => array(
			'UNABLE_GENERATE_ADDRESS' => 'Não foi possível gerar a consulta de endereço',
			'LBL_PRIMARY_ADDRESS' => 'Enderenço Principal',
			'LBL_ALTERNATE_ADDRESS' => 'Endereço Alternativo',
			'CHOOSE_ADDRESS_TO_VIEW' => 'Escolha qual endereço exibir',
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

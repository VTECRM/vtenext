<?php 

/* crmv@191909 */

$trans = array(
	'Charts' => array(
		'it_it' => array(
			'LBL_PARTIAL_DATA' => 'Elenco parziale',
			'LBL_PARTIAL_DATA_HELP' => 'Dato il grande numero di elementi, vengono mostrati solo i primi %i. Puoi attivare l\'opzione "Unisci spicchi piccoli" nel grafico per accorpare gli elementi meno significativi.',
		),
		'en_us' => array(
			'LBL_PARTIAL_DATA' => 'Partial list',
			'LBL_PARTIAL_DATA_HELP' => 'Given the big number of elements, only the first %i are shown. You can activate the option "Merge small slices" to join the smaller elements.',
		),
	),
);
$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module=>$modlang) {
	foreach ($modlang as $lang=>$translist) {
		if (array_key_exists($lang,$languages)) {
			foreach ($translist as $label=>$translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}



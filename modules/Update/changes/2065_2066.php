<?php 

// crmv@203075

$trans = array(
    'Settings' => array(
        'en_us' => array(
            'LBL_PM_ACTION_CycleRelated' => 'Cycle Related Records',
        ),
        'it_it' => array(
			'LBL_PM_ACTION_CycleRelated' => 'Cicla record relazionati',
        ),
    ),
    'APP_STRINGS' => array(
        'en_us' => array(
            'LBL_ON_MODULE'=>'on related module ',
        ),
        'it_it' => array(
			'LBL_ON_MODULE'=>'sul modulo ',
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


// crmv@203591

if (isModuleInstalled('ModLightProdAttr')) {
	$fields = array(
		'external_code' => array('module' => 'ModLightProdAttr',	'block' => 'LBL_INFORMATION', 'name' => 'external_code',	'label' => 'External Code', 'columntype' => 'C(50)', 'typeofdata' => 'V~O', 'uitype' => 1112, 'displaytype' => 3)
	);
	Update::create_fields($fields);
	
	// create an index on it
	$table = $table_prefix.'_modlightprodattr';
	$idxs = array_keys($adb->database->MetaIndexes($table));
	$index = "mlprodattr_extcode_idx";
	if (!in_array($index, $idxs)) {
		$sql = $adb->datadict->CreateIndexSQL($index, $table, array('external_code'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
	}
}

$trans = array(
	'ModLightProdAttr' => array(
		'en_us' => array(
            'External Code' => 'External code',
        ),
        'it_it' => array(
            'External Code' => 'Codice esterno',
        ),
	),
    'Settings' => array(
        'en_us' => array(
            'LBL_DIMPORT_FORMAT_ATTRIB_NAMES' => 'Only names, separated by',
            'LBL_DIMPORT_SEP_SPACE' => ' (space)',
            'LBL_DIMPORT_SEP_COMMA' => ', (comma)',
            'LBL_DIMPORT_SEP_COLON' => ': (colon)',
            'LBL_DIMPORT_SEP_SEMICOLON' => '; (semicolon)',
            'LBL_DIMPORT_FORMAT_ATTRIB_TABLE' => 'Names and values',
        ),
        'it_it' => array(
            'LBL_DIMPORT_FORMAT_ATTRIB_NAMES' => 'Solo nomi, separati da',
            'LBL_DIMPORT_SEP_SPACE' => ' (spazio)',
            'LBL_DIMPORT_SEP_COMMA' => ', (virgola)',
            'LBL_DIMPORT_SEP_COLON' => ': (due punti)',
            'LBL_DIMPORT_SEP_SEMICOLON' => '; (punto e virgola)',
            'LBL_DIMPORT_FORMAT_ATTRIB_TABLE' => 'Nomi e valori',
        ),
    ),
    'APP_STRINGS' => array(
        'en_us' => array(
            'SINGLE_ConfProducts' => 'Configurable product',
        ),
        
        'it_it' => array(
            'SINGLE_ConfProducts' => 'Prodotto configurabile',
        ),
    ),
);

foreach ($trans as $module=>$modlang) {
    foreach ($modlang as $lang=>$translist) {
        foreach ($translist as $label=>$translabel) {
            SDK::setLanguageEntry($module, $lang, $label, $translabel);
        }
    }
}

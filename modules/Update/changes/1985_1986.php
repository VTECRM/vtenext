<?php

/* crmv@195745 */

vtws_addModuleTypeWebserviceEntity('ProductsBlock', 'include/Webservices/VteProdBlockOperation.php', 'VteProdBlockOperation');

$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_PM_ACTION_Cycle' => 'Cicla righe',
			'LBL_SOURCE_ENTITY' => 'Entità di origine',
			'LBL_DEST_ENTITY' => 'Entità di destinazione',
			'LBL_PM_ACTION_CloneProductsBlock' => 'Copia il blocco prodotti',
			'LBL_PM_ACTION_InsertProductRow' => 'Inserisci riga prodotto',
			'LBL_PM_ACTION_DeleteProductRow' => 'Elimina riga prodotto',
			'LBL_PM_DISCOUNT_FIELD_INFO' => 'Lasciare il campo vuoto per non usare sconto, insire un numero seguito da % per sconto percentuale o un numero semplice per sconto diretto.',
		),
		'en_us' => array(
			'LBL_PM_ACTION_Cycle' => 'Cycle rows',
			'LBL_SOURCE_ENTITY' => 'Source entity',
			'LBL_DEST_ENTITY' => 'Destination entity',
			'LBL_PM_ACTION_CloneProductsBlock' => 'Copy products block',
			'LBL_PM_ACTION_InsertProductRow' => 'Insert product row',
			'LBL_PM_ACTION_DeleteProductRow' => 'Delete product row',
			'LBL_PM_DISCOUNT_FIELD_INFO' => 'Leave the field empty for no discount, a number followed by % for a percent discount or a simple number for direct discount.',
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


// crmv@197116

for ($i = 2; $i <= 7; ++$i) {
	$table = $table_prefix . "_rep_count_liv{$i}";
	if (Vtiger_Utils::CheckTable($table)) {
		$newIndex = "rep_count_levs{$i}_idx";
		$indexes = $adb->database->MetaIndexes($table);
		if (!array_key_exists($newIndex, $indexes)) {
			$fields = array();
			for ($j = 1; $j < $i; $j++) {
				$fields[] = 'id_liv'.$j;
			}
			$sql = $adb->datadict->CreateIndexSQL($newIndex, $table, $fields);
			if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		}
	}
}

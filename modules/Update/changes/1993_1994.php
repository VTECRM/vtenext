<?php
global $adb, $table_prefix;

/* new release 20.04 ! */

global $enterprise_current_version, $enterprise_mode, $enterprise_website;
SDK::setLanguageEntries('APP_STRINGS', 'LBL_BROWSER_TITLE', array(
	'it_it'=>"$enterprise_mode $enterprise_current_version",
	'en_us'=>"$enterprise_mode $enterprise_current_version",
	'de_de'=>"$enterprise_mode $enterprise_current_version",
	'nl_nl'=>"$enterprise_mode $enterprise_current_version",
	'pt_br'=>"$enterprise_mode $enterprise_current_version")
);

$result = $adb->query("select templateid, body from {$table_prefix}_emailtemplates where body LIKE '%vtenext 19.10%'");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$body = $row['body'];
		$body = str_replace('VTENEXT 19.10', $enterprise_mode.' '.$enterprise_current_version, $body);
		$adb->updateClob($table_prefix.'_emailtemplates','body',"templateid = ".$row['templateid'],$body);
	}
}


/* crmv@198024 */

if (!isModuleInstalled('ConfProducts')) {
	require_once('vtlib/Vtecrm/Package.php');
	$package = new Vtecrm_Package();
	$package->importByManifest('ConfProducts');
} else {
	require_once('modules/ConfProducts/ConfProducts.php');
	$cprods = CRMEntity::getInstance('ConfProducts');
	$cprods->vtlib_handler('ConfProducts', 'module.postinstall');
}

$prodModule = Vtecrm_Module::getInstance('Products');

$blocks = [
	'LBL_VARIANT_INFORMATION' => ['module' => 'Products', 'label' => 'LBL_VARIANT_INFORMATION'],
];
$iblocks = Update::create_blocks($blocks);

$vblock = $iblocks['LBL_VARIANT_INFORMATION'];
if ($vblock) {
	$pblock = Vtecrm_Block::getInstance('LBL_PRODUCT_INFORMATION', $prodModule);
	$vblock->moveAfter($pblock);
}

$fields = [
	'upc_code' => ['module' => 'Products', 'block' => 'LBL_PRODUCT_INFORMATION', 'name' => 'upc_code', 'label' => 'UPC Code', 'uitype' => 1, 'columntype'=>'C(63)', 'typeofdata'=>'V~O',],
	'confprodinfo'  => ['module' => 'Products', 'block' => 'LBL_VARIANT_INFORMATION', 'name' => 'confprodinfo', 'label' => 'VariantInfo', 'uitype' => 1, 'columntype'=>'XL', 'typeofdata'=>'V~O', 'readonly' => 100],
];
Update::create_fields($fields);


$trans = array(
	'Products' => array(
		'it_it' => array(
			'LBL_VARIANT_INFORMATION' => 'Informazioni variante',
			'Variant of' => 'Variante di',
			'Part Number' => 'Codice Prodotto / SKU',
			'UPC Code' => 'Codice UPC',
		),
		'en_us' => array(
			'LBL_VARIANT_INFORMATION' => 'Variant information',
			'Variant of' => 'Variant of',
			'Part Number' => 'Part Number / SKU',
			'UPC Code' => 'UPC Code',
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

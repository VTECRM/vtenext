<?php

// crmv@201442

SDK::setUitype('310', 'modules/SDK/src/310/310.php','modules/SDK/src/310/310.tpl', '', 'picklist');
SDK::setUitype('311', 'modules/SDK/src/311/311.php','modules/SDK/src/311/311.tpl', '', 'multipicklist');

// use a new uitype for country codes
$fields = array(
	'holiday_countries' => array('module'=>'Users','block'=>'LBL_CALENDAR_CONFIGURATION','name'=>'holiday_countries',	'label'=>'Holiday Countries',	'uitype'=>'311',	 'columntype'=>'C(255)','typeofdata'=>'V~O'),
);

Update::create_fields($fields);

$HU = HolidaysUtils::getInstance();

$field = Vtecrm_Field::getInstance('holiday_countries', Vtecrm_Module::getInstance('Users'));
if ($field) {
	$field->setFieldInfo(['show_flags' => true, 'default' => 'IT', 'only' => $HU->getSupportedCountries()]);
}

// and set all values to IT when upgrading
$adb->pquery("UPDATE {$table_prefix}_users SET holiday_countries = ?", array('IT'));

$trans = array(
	'Users' => array(
		'it_it' => array(
			'Holiday Countries' => 'Nazioni per le festività',
		),
		'en_us' => array(
			'Holiday Countries' => 'Holiday Countries',
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

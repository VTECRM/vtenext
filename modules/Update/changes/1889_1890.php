<?php 
global $adb, $table_prefix;

// crmv@187535 - add Sydney timezone

$result = $adb->pquery("select * from {$table_prefix}_user_timezone where user_timezone = ?", array('Australia/Sydney'));
if ($result && $adb->num_rows($result) == 0) {
	$field = Vtecrm_Field::getInstance('user_timezone', Vtecrm_Module::getInstance('Users'));
	$field->setPicklistValues(array('Australia/Sydney'));
}

// crmv@187922 - add relation employees - calendar

$module = 'Employees';
$module_instance = Vtecrm_Module::getInstance($module);
if($module_instance){
	$calendar_instance = Vtecrm_Module::getInstance(9);
	$events_instance = Vtecrm_Module::getInstance(16);

	$field_instance = Vtecrm_Field::getInstance('parent_id',$calendar_instance);
	$field_instance->setRelatedModules(Array($module));

	$field_instance1 = Vtecrm_Field::getInstance('parent_id',$events_instance);
	$field_instance1->setRelatedModules(Array($module));
}


$trans = array(
	'Users' => array(
		'it_it' => array(
			'Australia/Sydney'=>'(GMT+11:00) Sydney',
		),
		'en_us' => array(
			'Australia/Sydney'=>'(GMT+11:00) Sydney',
		),
	),
	'Calendar' => array(
		'it_it' => array(
			'Employees' => 'Collaboratori',
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

<?php

require_once('vtlib/Vtecrm/Module.php');

// crmv@184372
// add missing relation with processes
$mods = array('Calendar', 'Faq', 'PriceBooks', 'Services','Timecards', 'ProjectMilestone', 'ServiceContracts', 'Targets', 'Newsletter');
// Events seems not to be needed
// Charts doesn't make much sense
$processesInstance = Vtecrm_Module::getInstance('Processes');
$procFocus = CRMEntity::getInstance('Processes');
foreach ($mods as $mod) {
	// check if enabled
	$fieldInstance = Vtiger_Field::getInstance('related_to',$processesInstance);
	if ($fieldInstance) {
		$res = $adb->pquery("SELECT relmodule FROM {$table_prefix}_fieldmodulerel WHERE fieldid=? AND relmodule = ?", Array($fieldInstance->id, $mod));
		if ($res && $adb->num_rows($res) == 0) {
			$res = $adb->pquery("INSERT INTO {$table_prefix}_fieldmodulerel (fieldid, module, relmodule) VALUES (?,?,?)", Array($fieldInstance->id, 'Processes', $mod));
			$procFocus->enable($mod);
		}
	}
}


// crmv@181096

$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_OTHER_LOG_LIST' => 'Altri log',

		),
		'en_us' => array(
			'LBL_OTHER_LOG_LIST' => 'Other logs',
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

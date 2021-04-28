<?php

// crmv@174250
if (isModuleInstalled('Geolocalization')) {
	require_once('modules/Geolocalization/Geolocalization.php');
	Geolocalization::saveApiKey();
	Update::info('The API key used by Geolocalization module has been moved to the database.');
	Update::info('If you used a custom key, please update it accordingly in the vteprop table.');
	Update::info('');
}

SDK::setLanguageEntries('APP_STRINGS', 'LBL_MONTH_JANUARY', array('en_us' => 'January'));

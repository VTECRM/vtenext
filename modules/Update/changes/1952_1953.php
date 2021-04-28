<?php
SDK::setLanguageEntries('Settings', 'LBL_IMPORT_CF_PREFIX_ERROR', array(
	'it_it'=>'Il prefisso per i campi personalizzati di questo pacchetto è uguale a quello dell\'installazione corrente.',
	'en_us'=>'This package has the same prefix for custom fields of the current installation.'
));
SDK::setLanguageEntries('Settings', 'LBL_IMPORT_PICKLIST_DUPLICATES_ERROR', array(
	'it_it'=>'Esistono già delle picklist con lo stesso fieldname su altri moduli.',
	'en_us'=>'There are already picklists with the same fieldname on other modules.'
));
SDK::setLanguageEntries('Settings', 'VTLIB_LBL_PROCEED_WITH_IMPORT_ANYWAY', array(
	'it_it'=>'Vuoi procedere lo stesso con l\'importazione?',
	'en_us'=>'Do you want to proceed with the import anyway?'
));
SDK::setLanguageEntries('Settings', 'VTLIB_LBL_PROCEED_WITH_UPDATE_ANYWAY', array(
	'it_it'=>'Vuoi procedere lo stesso con l\'aggiornamento?',
	'en_us'=>'Do you want to proceed with the upgrade anyway?'
));

require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();
$VP->set('performance.cf_prefix_length', 3);

// add variable in config.inc
$success = CRMVUtils::writeCFPrefix();
if (!$success) {
	Update::info("Unable to update config.inc.php, please modify it manually.");
}
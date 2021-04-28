<?php
global $adb, $table_prefix;

// crmv@197606
if (Vtiger_Utils::CheckTable($table_prefix.'_processmaker_a_once')) {
	$sqlarray = $adb->datadict->DropTableSQL($table_prefix.'_processmaker_a_once');
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

// crmv@197876 add variables in config.inc
$configInc = file_get_contents('config.inc.php');
if (empty($configInc)) {
	Update::info("Unable to get config.inc.php contents, please modify it manually.");
} else {
	if (strpos($configInc, '$send_mail_ow') === false) {
		// backup it (only if it doesn't exist)
		$newConfigInc = 'config.inc.1984.php';
		if (!file_exists($newConfigInc)) {
			file_put_contents($newConfigInc, $configInc);
		}
		// alter config inc
		$configInc = str_replace('?>', "// option to overwrite the parameters of all emails sent, you can enable one or more of the following properties".
			"\n\$send_mail_ow = array(/*'to_email'=>'','cc'=>'','bcc'=>'','subject_prefix'=>''*/);\n\n?>", $configInc);
		if (is_writable('config.inc.php')) {
			file_put_contents('config.inc.php', $configInc);
		} else {
			Update::info("Unable to update config.inc.php, please modify it manually.");
		}
	}
}

// crmv@197127
SDK::setLanguageEntries('Settings', 'LBL_IMPORT_MODLIGHTS_ERROR', array(
	'it_it'=>'Uno o più campi tabella sono già presenti in altri moduli. Procedendo con l\'importazione il campo non sarà importato.',
	'en_us'=>'One or more table fields are already present in other modules. Proceeding with the import the field will not be imported.'
));
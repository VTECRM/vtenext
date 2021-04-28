<?php

// remove tables
if (Vtecrm_Utils::CheckTable("{$table_prefix}_klondike_classifier")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_klondike_classifier");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

if (Vtecrm_Utils::CheckTable("{$table_prefix}_process_discovery_agent")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_process_discovery_agent");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

if (Vtecrm_Utils::CheckTable("{$table_prefix}_process_discovery_agent_seq")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_process_discovery_agent_seq");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}


// remove stub klondike stuff
$adb->pquery(
	"DELETE FROM {$table_prefix}_settings_field WHERE name IN (?,?,?)",
	array('LBL_KLONDIKE_CLASSIFIER', 'LBL_PROCESS_DISCOVERY_AGENT', 'LBL_PROCESS_DISCOVERY')
);

// install new setting
$res = $adb->pquery("SELECT fieldid FROM {$table_prefix}_settings_field WHERE name = ?", array('LBL_KLONDIKE_CONFIG'));
if ($res && $adb->num_rows($res) == 0) {
	require_once('vtlib/Vtecrm/SettingsField.php');
	require_once('vtlib/Vtecrm/SettingsBlock.php');
	$field = new Vtecrm_SettingsField();
	$field->name = 'LBL_KLONDIKE_CONFIG';
	$field->sequence = 1;
	$field->iconpath = 'memory';
	$field->description = 'LBL_KLONDIKE_CONFIG_DESC';
	$field->linkto = 'index.php?module=Settings&action=KlondikeAI&parenttab=Settings';
	$block = Vtecrm_SettingsBlock::getInstance('LBL_KLONDIKE_AI');
	$block->addField($field);	
}

if(!Vtecrm_Utils::CheckTable($table_prefix.'_klondike_config')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_klondike_config">
	  <opt platform="mysql">ENGINE=InnoDB</opt>
	    <field name="id" type="I" size="19">
	      <KEY/>
	    </field>
	    <field name="klondike_url" type="C" size="255"/>
	    <field name="access_token" type="C" size="1000"/>
	    <field name="token_expire" type="C" size="31"/>
	    <field name="refresh_token" type="C" size="1000"/>
	  </table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

// crmv@215597 - removed klondike module

$trans = array(
	'ALERT_ARR' => array(
		'it_it' => array(
			'LBL_KLONDIKE_UNLINK_CONFIRM' => 'Rimuovendo il collegamento con Klondike non potrai più accedere alla tua istanza tramite questi pulsanti. Vuoi continuare?',
		),
		'en_us' => array(
			'LBL_KLONDIKE_UNLINK_CONFIRM' => 'Removing the link with Klondike you won\'t be able to access your Klondike with these buttons. Are you sure?',
		),
	),
	'Settings' => array(
		'it_it' => array(
			'LBL_KLONDIKE_CONFIG' => 'Configura KlondikeAI',
			'LBL_KLONDIKE_CONFIG_DESC' => 'Collega la tua istanza Klondike a Vtenext',
			'LBL_KLONDIKE_ADDRESS' => 'Indirizzo di Klondike',
			'LBL_KLONDIKE_ADDRESS_DESC' => 'Al posto di 0000 inserire l\'ID della propria istanza Klondike',
			'LBL_KLONDIKE_ADDRESS_DESC_URL' => 'L\'URL della tua istanza Klondike',
			'LBL_KLONDIKE_REFRESH_TOKEN' => 'Rinnova collegamento',
			'LBL_KLONDIKE_EXPIRED_TOKEN' => 'Token scaduto, rinnova il colllegamento',
			'LBL_KLONDIKE_TOKEN_OK' => 'L\'Istanza è collegata correttamente',
			'LBL_KLONDIKE_AD' => 'Non conosci Klondike? Scoprilo su ',
			'LBL_KLONDIKE_LINK' => 'Collega Vtenext a Klondike',
			'LBL_KLONDIKE_LINK_DESC' => 'Clicca il pulsante per collegare Vtenext alla tua istanza Klondike',
			'LBL_KLONDIKE_UNLINK' => 'Rimuovi collegamento con Klondike',
			'LBL_KLONDIKE_AD_REGISTER' => 'Non hai un account? <b><a href="https://www.klondike.ai/prova-gratuita/" target="_blank">Registrati!</a></b>',
		),
		'en_us' => array(
			'LBL_KLONDIKE_CONFIG' => 'KlondikeAI configuration',
			'LBL_KLONDIKE_CONFIG_DESC' => 'Link your Klondike instance to Vtenext',
			'LBL_KLONDIKE_ADDRESS' => 'Klondike address',
			'LBL_KLONDIKE_ADDRESS_DESC' => 'Replace 0000 with the ID of your Klondike instance',
			'LBL_KLONDIKE_ADDRESS_DESC_URL' => 'The URL of your Klondike instance',
			'LBL_KLONDIKE_REFRESH_TOKEN' => 'Refresh link',
			'LBL_KLONDIKE_EXPIRED_TOKEN' => 'Token expired, refresh the link',
			'LBL_KLONDIKE_TOKEN_OK' => 'Klondike instance is correctly linked',
			'LBL_KLONDIKE_AD' => 'New to Klondike? Discover it on ',
			'LBL_KLONDIKE_LINK' => 'Link Vtenext to Klondike',
			'LBL_KLONDIKE_LINK_DESC' => 'Click the button to link Vtenext to your Klondike instance',
			'LBL_KLONDIKE_UNLINK' => 'Remove link with Klondike',
			'LBL_KLONDIKE_AD_REGISTER' => 'Don\'t have an account? <b><a href="https://www.klondike.ai/en/free-trial/" target="_blank">Register!</a></b>',
		),
	),
	// crmv@215597 - removed klondike module
	'APP_STRINGS' => array(
		'it_it' => array(
			'LBL_ERROR_HAPPENED' => 'Si è verificato un errore',
			'LBL_CLOSE_WINDOW' => 'Chiudi finestra',
		),
		'en_us' => array(
			'LBL_ERROR_HAPPENED' => 'There was an error',
			'LBL_CLOSE_WINDOW' => 'Close window',
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


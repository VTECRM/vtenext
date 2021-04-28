<?php

// crmv@195835

global $adb, $table_prefix;

require_once('include/Webservices/Utils.php');

$res = $adb->pquery("SELECT operationid FROM {$table_prefix}_ws_operation WHERE name = ?", array('relate'));
if ($res && $adb->num_rows($res) == 0) {
	$operationId = vtws_addWebserviceOperation('relate', 'include/Webservices/Relate.php', 'vtws_relate', 'POST', 0, 'relate');
	vtws_addWebserviceOperationParam($operationId,'id','string',1);
	vtws_addWebserviceOperationParam($operationId,'relatelist','encoded',2);
	vtws_addWebserviceOperationParam($operationId,'relationid','string',3);
}


// crmv@190016

if (isModuleInstalled('VteSync')) {
	if(!Vtiger_Utils::CheckTable($table_prefix.'_vtesync_auth')) {
		$schema = '<?xml version="1.0"?>
					<schema version="0.3">
					<table name="'.$table_prefix.'_vtesync_auth">
					<opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="syncid" type="I" size="19">
						<KEY/>
					</field>
					<field name="username" type="C" size="255" />
					<field name="password" type="C" size="1000" />
					<field name="client_id" type="C" size="255" />
					<field name="client_secret" type="X" />
					<field name="scope" type="C" size="255" />
					</table>
				</schema>';
		$schema_obj = new adoSchema($adb->database);
		$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
		
		// migrate data from old table
		$adb->query("INSERT INTO {$table_prefix}_vtesync_auth (syncid, client_id, client_secret, scope) SELECT syncid, client_id, client_secret, scope FROM {$table_prefix}_vtesync_oauth2");
		
		if (Vtiger_Utils::CheckTable("{$table_prefix}_vtesync_oauth2")) {
			$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_vtesync_oauth2");
			$adb->datadict->ExecuteSQLArray($sqlarray);
		}
	}
	
	$adb->addColumnToTable("{$table_prefix}_vtesync", 'system_url', 'VARCHAR(255) NULL AFTER authtype');
	
	$adb->pquery("UPDATE {$table_prefix}_tab SET version = ? WHERE name = ? AND version = ?", array("1.5", 'VteSync', '1.4'));
	
	require_once('modules/VteSync/VteSync.php');
	$vsync = VteSync::getInstance();
	$vsync->vtlib_handler('VteSync', 'module.postupdate');
	
	$trans = array(
		'Settings' => array(
			'it_it' => array(
				'LBL_VTESYNC_USERNAME' => 'Nome utente',
				'LBL_VTESYNC_USERNAME_DESC' => 'L\'utente da usare per connettersi. Deve essere una utenza amministrativa',
				'LBL_VTESYNC_PASSWORD' => 'Password',
				'LBL_VTESYNC_SYSTEMURL' => 'Indirizzo dell\'istanza',
			),
			'en_us' => array(
				'LBL_VTESYNC_USERNAME' => 'Username',
				'LBL_VTESYNC_USERNAME_DESC' => 'It should be an administrative user',
				'LBL_VTESYNC_PASSWORD' => 'Password',
				'LBL_VTESYNC_SYSTEMURL' => 'Instance URL',
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

}


Update::info('The js library prototype.js has been removed.');
Update::info('If you have customizations using this old library, please review them in order to use jQuery or vanilla js.');
Update::info('');

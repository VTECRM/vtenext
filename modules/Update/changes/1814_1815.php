<?php
global $adb, $table_prefix;
$focus = CRMEntity::getInstance('Messages');
$indexes = $adb->database->MetaIndexes($focus->table_name);
if (!array_key_exists($table_prefix.'_messages_messagehash_idx', $indexes)) {
	$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_messagehash_idx', $focus->table_name, array('messagehash'));
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}

SDK::setLanguageEntries('ModComments', 'LBL_CONFIRM_SHARE_PARENT', array(
	'it_it'=>'Alcuni degli utenti selezionati non hanno i permessi per visualizzare le informazioni collegate a questa conversazione.',
	'en_us'=>'Some of the selected users do not have permission to view information related to this conversation.'
));
SDK::setLanguageEntries('ModComments', 'LBL_CONFIRM_SHARE_PARENT_PERMISSIONS', array(
	'it_it'=>'Seleziona una modalità di condivisione fra quelle disponibili:',
	'en_us'=>'Select a sharing mode from those available:'
));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_CONFIRM_SHARE_PARENT_HELP_0', array(
	'it_it'=>'Nessuna eccezione della visibilità verrà attiva per le informazioni collegate',
	'en_us'=>'No visibility exception will be activated for linked information'
));
SDK::setLanguageEntries('ModComments', 'LBL_CONFIRM_SHARE_PARENT_HELP_1', array(
	'it_it'=>'Verrà estesa la visibilità delle informazioni ma non saranno permesse modifiche',
	'en_us'=>'Information visibility will be extended but no changes will be allowed'
));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_CONFIRM_SHARE_PARENT_HELP_1', array(
	'it_it'=>'Verrà estesa la visibilità delle informazioni ma non saranno permesse modifiche',
	'en_us'=>'Information visibility will be extended but no changes will be allowed'
));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_CONFIRM_SHARE_PARENT_HELP_2', array(
	'it_it'=>'Verrà estesa la visibilità delle informazioni e saranno permesse modifiche',
	'en_us'=>'Information visibility will be extended and changes will be allowed'
));

$result = $adb->pquery("select fieldid from {$table_prefix}_field where tablename = ? and fieldname = ?", array("{$table_prefix}_modcomments",'related_to_perm'));
if ($result && $adb->num_rows($result) == 0) {
	$fields = array();
	$fields[] = array('module'=>'ModComments','block'=>'LBL_MODCOMMENTS_INFORMATION','name'=>'related_to_perm','label'=>'Related To Permissions','uitype'=>'1','columntype'=>'INT(1) DEFAULT 0');
	include('modules/SDK/examples/fieldCreate.php');
}

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('performance.modcomments_parent_perm', false); // default false
$VTEP->setProperty('performance.modcomments_parent_perm_users', array('all'=>true,'users'=>array(),'groups'=>array(),'roles'=>array()));
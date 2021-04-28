<?php
$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('performance.slave_handler', false);
$VTEP->setProperty('performance.slave_functions', array('Area','UnifiedSearch','ModNotificationsCount','ListViewCount','BadgeCount','TurboliftCount','Export','Reports'));
$VTEP->setProperty('performance.slave_connection', array(
	'db_server' => '',
	'db_port' => ':3306',
	'db_username' => '',
	'db_password' => '',
	'db_name' => '',
	'db_name_cache' => 'slave_cache',
	'db_type' => 'mysqli',
	'db_status' => 'true',
	'db_charset' => 'utf8',
	'db_dieOnError' => false,
));

$calendarInstance = Vtiger_Module::getInstance('Calendar');
$eventsInstance = Vtiger_Module::getInstance('Events');
$documensInstance = Vtiger_Module::getInstance('Documents');
$result = $adb->pquery("SELECT * FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = ?", array($calendarInstance->id, $documensInstance->id));
if ($result && $adb->num_rows($result) == 0) {
	$calendarInstance->setRelatedList($documensInstance, 'Documents', array('add','select'), 'get_attachments');
}
$result = $adb->pquery("SELECT * FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = ?", array($eventsInstance->id, $documensInstance->id));
if ($result && $adb->num_rows($result) == 0) {
	$eventsInstance->setRelatedList($documensInstance, 'Documents', array('add','select'), 'get_attachments');
}
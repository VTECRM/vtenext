<?php 

/* crmv@202577 */

global $adb, $table_prefix;

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('masscreate.limit', 50);

// create the table for the masscreate queue
$schema = '<?xml version="1.0"?>
	<schema version="0.3">
		<table name="' . $table_prefix . '_masscreate">
			<opt platform="mysql">ENGINE=InnoDB</opt>
			<field name="massid" type="I" size="19">
				<key/>
			</field>
			<field name="userid" type="I" size="19">
				<NOTNULL/>
			</field>
			<field name="module" type="C" size="63">
				<NOTNULL/>
			</field>
			<field name="inserttime" type="T">
				<NOTNULL/>
				<DEFAULT value="0000-00-00 00:00:00"/>
			</field>
			<field name="starttime" type="T">
				<NOTNULL/>
				<DEFAULT value="0000-00-00 00:00:00"/>
			</field>
			<field name="endtime" type="T">
				<NOTNULL/>
				<DEFAULT value="0000-00-00 00:00:00"/>
			</field>
			<field name="workflows" type="I" size="1">
				<NOTNULL/>
				<DEFAULT value="1"/>
			</field>
			<field name="status" type="I" size="3">
				<NOTNULL/>
				<DEFAULT value="0"/>
			</field>
			<field name="results" type="XL" />
			<index name="masscreate_status_idx">
				<col>status</col>
			</index>
		</table>
	</schema>';
if (!Vtiger_Utils::CheckTable($table_prefix . '_masscreate')) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

$schema = '<?xml version="1.0"?>
	<schema version="0.3">
		<table name="' . $table_prefix . '_masscreate_queue">
			<opt platform="mysql">ENGINE=InnoDB</opt>
			<field name="queueid" type="I" size="19">
				<key/>
			</field>
			<field name="massid" type="I" size="19">
				<NOTNULL/>
			</field>
			<field name="record" type="XL" />
			<field name="status" type="I" size="3">
				<NOTNULL/>
				<DEFAULT value="0"/>
			</field>
			<field name="info" type="C" size="255" />
			<index name="masscreate_queue_massid_idx">
				<col>massid</col>
			</index>
			<index name="masscreate_queue_status_idx">
				<col>status</col>
			</index>
		</table>
	</schema>';
if (!Vtiger_Utils::CheckTable($table_prefix . '_masscreate_queue')) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

// add massedit cronjob
require_once ('include/utils/CronUtils.php');
$cronname = 'MassCreate';
$CU = CronUtils::getInstance();
// install cronjob
$cj = CronJob::getByName($cronname);
if (empty($cj)) {
	$cj = new CronJob();
	$cj->name = $cronname;
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->timeout = 1800; // 30 min
	$cj->repeat = 120; // 2 min
	$cj->fileName = 'cron/modules/MassCreate/MassCreate.service.php';
	$CU->insertCronJob($cj);
}

// add notification type
$modNot = CRMEntity::getInstance('ModNotifications');
if (method_exists($modNot, 'addNotificationType')) {
	$modNot->addNotificationType('MassCreate', 'MassCreate', 0);
	$modNot->addNotificationType('MassCreateError', 'MassCreateError', 0);
} else {
	$params = array($adb->getUniqueID("{$table_prefix}_modnotifications_types"), 'MassCreate', 'MassCreate', 0);
	$adb->pquery("INSERT INTO {$table_prefix}_modnotifications_types (id, type, action, custom) VALUES (?, ?, ?, ?)", $params);
	$params = array($adb->getUniqueID("{$table_prefix}_modnotifications_types"), 'MassCreateError', 'MassCreateError', 0);
	$adb->pquery("INSERT INTO {$table_prefix}_modnotifications_types (id, type, action, custom) VALUES (?, ?, ?, ?)", $params);
}

$trans = array(
	'APP_STRINGS' => array(
		'it_it' => array(
			'LBL_MASSCREATE_OK' => 'MassCreate completato correttamente. {num_records} elementi sono stati creati.',
			'LBL_MASSCREATE_ERROR' => 'Si sono verificati degli errori durante il MassCreate. {num_fail_records} elementi non sono stati salvati correttamente, su un totale di {num_records} elementi. Controllare i file di log per i dettagli.',
			'LBL_MASSCREATE_OK_SUBJECT' => 'MassCreate completato',
			'LBL_MASSCREATE_OK_DESC' => "Il MassCreate sul modulo {module} e' stato completato correttamente.<br>\n{num_records} elementi sono stati creati.",
			'LBL_MASSCREATE_ERROR_SUBJECT' => 'Errore MassCreate',
			'LBL_MASSCREARW_ERROR_DESC' => "Si sono verificati degli errori durante il masscreate sul modulo {module}.<br>\n{num_fail_records} elementi non sono stati salvati correttamente, su un totale di {num_records} elementi. Controllare i file di log per i dettagli.",
		),
		'en_us' => array(
			'LBL_MASSCREATE_OK' => 'MassCreate completed correctly. {num_records} records have been created.',
			'LBL_MASSCREATE_ERROR' => 'Some errors occurred during MassCreate. {num_fail_records} records have not been saved correctly, on a total of {num_records} records. Please check the logfiles for details.',
			'LBL_MASSCREATE_OK_SUBJECT' => 'MassCreate completed',
			'LBL_MASSCREATE_OK_DESC' => "MassCreate for the module {module} has been completed correctly.<br>\n{num_records} records have been created.",
			'LBL_MASSCREATE_ERROR_SUBJECT' => 'MassCreate error',
			'LBL_MASSCREATE_ERROR_DESC' => "Some errors occurred during MassCreate for the module {module}.<br>\n{num_fail_records} records have not been saved correctly, on a total of {num_records} records. Please check the logfiles for details.",
		),
	),
	'ALERT_ARR' => array(
		'it_it' => array(
			'LBL_MASS_CREATE_ENQUEUE_TELEMARKETING' => 'Hai selezionato più di {max_records} elementi. L\'elaborazione verrà eseguita in background e verrai notificato al termine.',
		),
		'en_us' => array(
			'LBL_MASS_CREATE_ENQUEUE_TELEMARKETING' => 'You selected more than {max_records} items. The process will continue in background and you\'ll be notified at the end.',
		),
	),
);

foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		foreach ($translist as $label => $translabel) {
			SDK::setLanguageEntry($module, $lang, $label, $translabel);
		}
	}
}

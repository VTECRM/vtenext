<?php 

global $adb, $table_prefix;

// crmv@202301

require_once('modules/Settings/AuditTrail.php');

$AT = new AuditTrail();

// migrate old setting
if (is_file('user_privileges/audit_trail.php')) {
	include('user_privileges/audit_trail.php');
	if ($audit_trail == 'true') {
		$AT->enable();
	} else {	
		$AT->disable();
	}
	// and remove old file
	unlink('user_privileges/audit_trail.php');
}


$VP = VTEProperties::getInstance();
$VP->setProperty('security.audit.log_retention_time', 3); // keep logs for 3 months

if (!is_dir('logs/AuditTrail')) {
	mkdir('logs/AuditTrail', 0755);
}

$table = $table_prefix . '_audit_trial';
if ($adb->isMysql()) {
	$cols = $adb->getColumnNames($table);
	if (!in_array('source', $cols)) {
		$adb->query(
			"ALTER TABLE $table
				ADD COLUMN source VARCHAR(31) NULL,
				ADD COLUMN subaction VARCHAR(63) NULL,
				ADD COLUMN request_id VARCHAR(63) NULL,
				ADD COLUMN ip_address VARCHAR(31) NULL,
				ADD COLUMN extra VARCHAR(1000) NULL
				"
		);
	}
} else {
	$adb->addColumnToTable($table, 'source', 'C(31)');
	$adb->addColumnToTable($table, 'subaction', 'C(63)');
	$adb->addColumnToTable($table, 'request_id', 'C(63)');
	$adb->addColumnToTable($table, 'ip_address', 'C(31)');
	$adb->addColumnToTable($table, 'extra', 'C(1000)');
}


$adb->addColumnToTable($table_prefix.'_loginhistory', 'request_id', 'C(63)');


// and set the source to web now
$adb->pquery("UPDATE $table SET source = ? WHERE source IS NULL", array('web'));

$trans = array(
    'APP_STRINGS' => array(
        'en_us' => array(
            'LBL_IP_ADDRESS' => 'Indirizzo IP',
        ),
        'it_it' => array(
            'LBL_IP_ADDRESS' => 'Indirizzo IP',
        ),
    ),
);

foreach ($trans as $module=>$modlang) {
    foreach ($modlang as $lang=>$translist) {
        foreach ($translist as $label=>$translabel) {
            SDK::setLanguageEntry($module, $lang, $label, $translabel);
        }
    }
}



// crmv@200009 crmv@204673

$schema = '<?xml version="1.0"?>
	<schema version="0.3">
		<table name="' . $table_prefix . '_load_relations">
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
			<field name="crmid" type="I" size="19">
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
			<field name="status" type="I" size="3">
				<NOTNULL/>
				<DEFAULT value="0"/>
			</field>
			<field name="results" type="XL" />
			<index name="load_relations_status_idx">
				<col>status</col>
			</index>
		</table>
	</schema>';
if (!Vtiger_Utils::CheckTable($table_prefix . '_load_relations')) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

$schema = '<?xml version="1.0"?>
	<schema version="0.3">
		<table name="' . $table_prefix . '_load_relations_queue">
			<opt platform="mysql">ENGINE=InnoDB</opt>
			<field name="queueid" type="I" size="19">
				<key/>
			</field>
			<field name="massid" type="I" size="19">
				<NOTNULL/>
			</field>
			<field name="with_module" type="C" size="63">
				<NOTNULL/>
			</field>
			<field name="with_crmid" type="I" size="19">
				<NOTNULL/>
			</field>
			<field name="status" type="I" size="3">
				<NOTNULL/>
				<DEFAULT value="0"/>
			</field>
			<field name="info" type="C" size="255" />
			<index name="load_relations_queue_massid_idx">
				<col>massid</col>
			</index>
			<index name="load_relations_queue_status_idx">
				<col>status</col>
			</index>
		</table>
	</schema>';
if (!Vtiger_Utils::CheckTable($table_prefix . '_load_relations_queue')) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('modules.import.immediate_import_limit', 1000);
$VTEP->setProperty('modules.import.import_batch_Limit', 250);
$VTEP->setProperty('loadrelations.limit', 100);


require_once('include/utils/CronUtils.php');
$CU = CronUtils::getInstance();

$cronname = 'LoadRelations';
$cj = CronJob::getByName($cronname);
if (empty($cj)) {
	$cj = new CronJob();
	$cj->name = $cronname;
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->fileName = 'cron/modules/LoadRelations/LoadRelations.service.php';
	$cj->timeout = 1800; // 30 min
	$cj->repeat = 120; // 2 min
	$CU->insertCronJob($cj);
}

require_once 'include/events/include.inc';
$em = new VTEventsManager($adb);
$em->registerHandler('vtiger.entity.relate','modules/Settings/ProcessMaker/ProcessMakerHandler.php','ProcessMakerHandler');

$trans = array(
    'APP_STRINGS' => array(
        'en_us' => array(
            'LBL_LOAD_RELATIONS_OK' => 'Loading relations completed correctly.',
            'LBL_LOAD_RELATIONS_ERROR' => 'Some errors occurred during Loading relations. Please check the logfiles for details.',
            'LBL_LOAD_RELATIONS_OK_SUBJECT' => '[VTECRM] LoadRelations completed',
            'LBL_LOAD_RELATIONS_OK_DESC' => "Loading relations for the module {module} has been completed correctly.<br>
{num_records} records have been modified.",
            'LBL_LOAD_RELATIONS_ERROR_SUBJECT' => '[VTECRM] LoadRelations error',
            'LBL_LOAD_RELATIONS_ERROR_DESC' => "Some errors occurred during LoadRelations for the module {module}.<br>
{num_fail_records} records have not been saved correctly, on a total of {num_records} records. Please check the logfiles for details.",
        ),
    ),
    'ALERT_ARR' => array(
        'en_us' => array(
            'LBL_LOAD_RELATIONS_ENQUEUE' => 'You selected more than {max_records} items. The process will continue in background and you\'ll be notified at the end.',
        ),
    ),
    'Settings' => array(
        // language
        'en_us' => array(
            // translations
            'LBL_ON_MODULE_RELATION'=>'on relation with:',
        ),

        'it_it' => array(
            // translations
            'LBL_ON_MODULE_RELATION'=>'in relazione con:',
        ),
    ),
);

foreach ($trans as $module=>$modlang) {
    foreach ($modlang as $lang=>$translist) {
        foreach ($translist as $label=>$translabel) {
            SDK::setLanguageEntry($module, $lang, $label, $translabel);
        }
    }
}

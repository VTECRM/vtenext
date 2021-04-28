<?php
$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('performance.restapi_log',0);
$VTEP->setProperty('performance.editview_changelog',1);
$VTEP->setProperty('performance.editview_changelog_clean_interval',86400);
$VTEP->setProperty('performance.editview_changelog_force_writable_uitypes',Zend_Json::encode(array(220)));

global $adb, $table_prefix;
$adb->addColumnToTable($table_prefix.'_ws_operation', 'rest_name', 'C(50)');

require('include/RestApi/v1/VTERestApi.php');
$restApi = VTERestApi::getInstance();
$restApi->enableRestOperations();

if(!Vtiger_Utils::CheckTable($table_prefix.'_editview_changelog')) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$table_prefix.'_editview_changelog">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="etag" type="C" size="32">
				      <KEY/>
				    </field>
				    <field name="userid" type="I" size="19"/>
				    <field name="record" type="I" size="19"/>
				    <field name="column_fields" type="XL"/>
				    <field name="status" type="I" size="1">
				      <DEFAULT value="0"/>
				    </field>
				    <field name="createdtime" type="T"/>
				    <index name="editview_changelog_idx">
				      <col>createdtime</col>
				    </index>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

require_once('include/utils/CronUtils.php');
$CU = CronUtils::getInstance();
$cj = CronJob::getByName('CleanEditViewEtag');
if (empty($cj)) {
	$cj = new CronJob();
	$cj->name = 'CleanEditViewEtag';
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->fileName = 'cron/modules/ChangeLog/CleanEditViewEtag.php';
	$cj->timeout = 120; // 2 min
	$cj->repeat = 3600; // 60min
	$CU->insertCronJob($cj);
}
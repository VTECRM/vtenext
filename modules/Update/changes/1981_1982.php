<?php
global $adb, $table_prefix;

$VTEP = VTEProperties::getInstance();
if ($VTEP->getProperty('performance.mq_enabled')) {
	$VTEP->setProperty('performance.mq_rabbitmq_enabled', true);
	$VTEP->setProperty('performance.mq_webstomp_enabled', true);
} else {
	$VTEP->setProperty('performance.mq_rabbitmq_enabled', false);
	$VTEP->setProperty('performance.mq_webstomp_enabled', false);
}

// alter vte_trigger_queue
$type = $adb->datadict->ActualType('C');
Vtiger_Utils::AlterTable("{$table_prefix}_trigger_queue","`action` $type(20)");

$cols = $adb->getColumnNames($table_prefix.'_trigger_queue');
if (!in_array('master_dependent_on', $cols)) {
	$adb->addColumnToTable($table_prefix.'_trigger_queue', 'master_dependent_on', 'I(19) DEFAULT 0');
}
if (!in_array('working', $cols)) {
	$adb->addColumnToTable($table_prefix.'_trigger_queue', 'working', 'I(1) DEFAULT 0');
}
$indexes = $adb->database->MetaIndexes($table_prefix.'_trigger_queue');
if (!array_key_exists('trigger_queue_master_idx', $indexes)) {
	$index = $adb->datadict->CreateIndexSQL('trigger_queue_master_idx', $table_prefix.'_trigger_queue', 'master_dependent_on');
	$adb->datadict->ExecuteSQLArray((Array)$index);
}

if(!Vtiger_Utils::CheckTable($table_prefix.'_trigger_queue_failed')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_trigger_queue_failed">
	  <opt platform="mysql">ENGINE=InnoDB</opt>
	    <field name="id" type="I" size="19">
	      <KEY/>
	    </field>
	    <field name="crmid" type="I" size="19"/>
		<field name="module" type="C" size="25" />
		<field name="mode" type="C" size="6" />
		<field name="queue_time" type="T">
	      <DEFAULT value="0000-00-00 00:00:00"/>
	    </field>
	    <field name="userid" type="I" size="19"/>
		<field name="action" type="C" size="20" />
	    <field name="dependent_on" type="I" size="19"/>
	    <field name="master_dependent_on" type="I" size="19"/>
		<field name="info" type="XL"/>
	    <field name="freeze" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
	    <field name="attempts" type="I" size="5">
	      <DEFAULT value="0"/>
	    </field>
		<field name="working" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
		<field name="error" type="C" size="50" />
		<index name="trigger_queue_crmid_idx">
	      <col>crmid</col>
	    </index>
		<index name="trigger_queue_master_idx">
	      <col>master_dependent_on</col>
	    </index>
	  </table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
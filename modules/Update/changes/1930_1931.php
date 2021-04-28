<?php
global $adb, $table_prefix;

$procModuleInstance = Vtecrm_Module::getInstance('Processes');
$docModuleInstance = Vtecrm_Module::getInstance('Documents');
$result = $adb->pquery("SELECT * FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = ?", array($docModuleInstance->id, $procModuleInstance->id));
if ($result && $adb->num_rows($result) == 0) {
	$docModuleInstance->setRelatedList($procModuleInstance,'Processes',array('select','add'),'get_documents_dependents_list');
}

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('performance.mq_enabled', false);
$VTEP->setProperty('performance.mq_rabbitmq_connection', array());
$VTEP->setProperty('performance.mq_webstomp_connection', array());

if(!Vtiger_Utils::CheckTable($table_prefix.'_trigger_queue')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_trigger_queue">
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
		<field name="action" type="C" size="6" />
	    <field name="dependent_on" type="I" size="19"/>
		<field name="info" type="XL"/>
	    <field name="freeze" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
	    <field name="attempts" type="I" size="5">
	      <DEFAULT value="0"/>
	    </field>
		<index name="trigger_queue_crmid_idx">
	      <col>crmid</col>
	    </index>
	  </table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

SDK::setLanguageEntries('APP_STRINGS', 'LBL_IS_FREEZED', array('it_it'=>'In aggiornamento ...','en_us'=>'Updating ...'));
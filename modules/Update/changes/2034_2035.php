<?php
global $adb, $table_prefix;

// crmv@199641
if(!Vtiger_Utils::CheckTable($table_prefix.'_trigger_queue_history')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_trigger_queue_history">
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
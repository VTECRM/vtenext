<?php
global $adb, $table_prefix;

if(!Vtiger_Utils::CheckTable($table_prefix.'_messages_outofo_q')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_messages_outofo_q">
	  <opt platform="mysql">ENGINE=InnoDB</opt>
	    <field name="id" type="I" size="19">
	      <KEY/>
	    </field>
		<field name="from_email" type="C" size="255" />
		<field name="to_email" type="C" size="255" />
		<field name="subject" type="C" size="255" />
		<field name="body" type="XL"/>
		<field name="date" type="T">
	      <DEFAULT value="0000-00-00 00:00:00"/>
	    </field>
	    <field name="in_reply_to" type="I" size="19"/>
	    <field name="status" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
	    <field name="attempts" type="I" size="5">
	      <DEFAULT value="0"/>
	    </field>
		<field name="error" type="C" size="200" />
	  </table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
if(!Vtiger_Utils::CheckTable($table_prefix.'_messages_outofo_s')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_messages_outofo_s">
	  <opt platform="mysql">ENGINE=InnoDB</opt>
	    <field name="userid" type="I" size="19">
	      <KEY/>
	    </field>
	    <field name="active" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
		<field name="message_subject" type="C" size="255" />
		<field name="message_body" type="XL"/>
	    <field name="start_date_allday" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
		<field name="start_date" type="D"/>
		<field name="start_time" type="C" size="5" />
	    <field name="end_date_active" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
		<field name="end_date" type="D"/>
		<field name="end_time" type="C" size="5" />
	    <field name="only_known_addresses_active" type="I" size="1">
	      <DEFAULT value="0"/>
	    </field>
		<field name="accounts" type="X"/>
	  </table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

// add imap parameters and pec option to mail converter configuration
$table_name = "{$table_prefix}_mailscanner";
$cols = $adb->getColumnNames($table_name);
if (!in_array('is_pec', $cols)) {
	$adb->addColumnToTable($table_name, 'is_pec', 'INT(1) NOTNULL DEFAULT 0');
}
if (!in_array('imap_params', $cols)) {
	$adb->addColumnToTable($table_name, 'imap_params', 'X');
}

// move columns from crmentity to the Processes module table
$moduleName = 'Processes';
$focus = CRMEntity::getInstance($moduleName);
$cols = $adb->getColumnNames($focus->table_name);
if (!in_array('smownerid', $cols)) {
	
	// add new columns
	if ($adb->isMysql()) {
		$query = "ALTER TABLE $focus->table_name
		ADD COLUMN smownerid INT(19) NOT NULL DEFAULT 0,
		ADD COLUMN createdtime TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		ADD COLUMN modifiedtime TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		ADD COLUMN deleted INT(1) NOT NULL DEFAULT 0";
		$adb->query($query);
	} else {
		$adb->addColumnToTable($focus->table_name, 'smownerid', 'INT(19) NOTNULL DEFAULT 0');
		$adb->addColumnToTable($focus->table_name, 'createdtime', 'T NOTNULL DEFAULT \'0000-00-00 00:00:00\'');
		$adb->addColumnToTable($focus->table_name, 'modifiedtime', 'T NOTNULL DEFAULT \'0000-00-00 00:00:00\'');
		$adb->addColumnToTable($focus->table_name, 'deleted', 'INT(1) NOTNULL DEFAULT 0');
	}
	
	// update new columns
	if ($adb->isMySQL()) {
		$adb->query(
			"update {$focus->table_name} t
			inner join {$table_prefix}_crmentity c on c.crmid = t.{$focus->table_index}
			set
			t.smownerid = c.smownerid,
			t.createdtime = c.createdtime,
			t.modifiedtime = c.modifiedtime,
			t.deleted = c.deleted"
		);
	} elseif ($adb->isMssql()) {
		$adb->query(
			"update t
			set
			t.smownerid = c.smownerid,
			t.createdtime = c.createdtime,
			t.modifiedtime = c.modifiedtime,
			t.deleted = c.deleted
			from {$focus->table_name} t
			inner join {$table_prefix}_crmentity c on c.crmid = t.{$focus->table_index}"
		);
	} else {
		$res = $adb->pquery("select crmid, smownerid, createdtime, modifiedtime, deleted from {$table_prefix}_crmentity where setype = ?", array($moduleName));
		while ($row=$adb->fetchByAssoc($res,-1,false)) {
			$adb->pquery("update {$focus->table_name} set smownerid = ?, createdtime = ?, modifiedtime = ?, deleted = ? where {$focus->table_index} = ?",
			array($row['smownerid'], $row['createdtime'], $row['modifiedtime'], $row['deleted'], $row['crmid']));
		}
	}
	
	// update field
	$adb->pquery(
		"UPDATE {$table_prefix}_field SET tablename = ? WHERE tabid = ? and tablename = ? and columnname in (?,?,?,?)",
		array($focus->table_name, getTabid($moduleName), "{$table_prefix}_crmentity", array('smownerid','createdtime','modifiedtime','deleted','description'))
	);
	
	// delete rows from _crmentity
	$adb->pquery("delete from {$table_prefix}_crmentity where setype = ?", array($moduleName));
	
	// remove partition if needed
	if ($adb->isMysql() && PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
		// get partition name
		global $dbconfig;
		$res = $adb->pquery(
			"SELECT partition_name FROM information_schema.partitions WHERE table_schema = ? AND table_name = ? AND partition_description = '''{$moduleName}'''",
			array($dbconfig['db_name'], $table_prefix.'_crmentity')
		);
		// and remove it
		if ($res && $adb->num_rows($res) > 0) {
			$pname = $adb->query_result_no_html($res, 0, 'partition_name');
			$adb->query("ALTER TABLE {$table_prefix}_crmentity DROP PARTITION $pname");
		}
	}
	
	require_once('include/utils/VTEProperties.php');
	$VP = VTEProperties::getInstance();
	$VP->set('performance.modules_without_crmentity', array('Messages','Processes'));
	
	Update::info('The '.$moduleName.' module do not use the table '.$table_prefix.'_crmentity anymore,');
	Update::info('so if you have customizations relying on this aspect, please review them.');
	Update::info('');
}

// remove cf table
if (Vtiger_Utils::CheckTable($table_prefix.'_processescf')) {
	$sqlarray = $adb->datadict->DropTableSQL($table_prefix.'_processescf');
	$adb->datadict->ExecuteSQLArray($sqlarray);
	
	Update::info('The '.$moduleName.' module do not use the table '.$table_prefix.'_processescf anymore,');
	Update::info('so if you have customizations relying on this aspect, please review them.');
	Update::info('');
}
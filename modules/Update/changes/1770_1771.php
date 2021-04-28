<?php
SDK::setUtil('modules/SDK/src/Favorites/Utils.php');

//  move columns from crmentity to the module table
global $adb, $table_prefix;
$focus = CRMEntity::getInstance('Messages');
$cols = $adb->getColumnNames($focus->table_name);
if (!in_array('smownerid', $cols)) {
	
	// add new columns
	if ($adb->isMysql()) {
		$query = "ALTER TABLE $focus->table_name
			ADD COLUMN smownerid INT(19) NOT NULL DEFAULT 0,
			ADD COLUMN createdtime TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
			ADD COLUMN modifiedtime TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
			ADD COLUMN deleted INT(1) NOT NULL DEFAULT 0";
		if (!in_array('description', $cols)) {
			$query .= ", ADD COLUMN description LONGTEXT";
		}
		$adb->query($query);
	} else {
		$adb->addColumnToTable($focus->table_name, 'smownerid', 'INT(19) NOTNULL DEFAULT 0');
		$adb->addColumnToTable($focus->table_name, 'createdtime', 'T NOTNULL DEFAULT \'0000-00-00 00:00:00\'');
		$adb->addColumnToTable($focus->table_name, 'modifiedtime', 'T NOTNULL DEFAULT \'0000-00-00 00:00:00\'');
		$adb->addColumnToTable($focus->table_name, 'deleted', 'INT(1) NOTNULL DEFAULT 0');
		if (!in_array('description', $cols)) {
			$adb->addColumnToTable($focus->table_name, 'description', 'XL');
		}
	}
	
	// update indexes
	$indexes = $adb->database->MetaIndexes($focus->table_name);
	if (array_key_exists($table_prefix.'_messages_messageid_idx', $indexes)) {
		$sql = $adb->datadict->DropIndexSQL($table_prefix.'_messages_messageid_idx', $focus->table_name);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_messageid_idx', $focus->table_name, array('deleted','messageid'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		
		$sql = $adb->datadict->DropIndexSQL($table_prefix.'_messages_folder_idx', $focus->table_name);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_folder_idx', $focus->table_name, array('deleted','smownerid','folder'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		
		$sql = $adb->datadict->DropIndexSQL($table_prefix.'_messages_mtype_idx', $focus->table_name);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_mtype_idx', $focus->table_name, array('deleted','smownerid','mtype'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		
		$sql = $adb->datadict->DropIndexSQL($table_prefix.'_messages_seen_idx', $focus->table_name);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_seen_idx', $focus->table_name, array('deleted','smownerid','seen'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		
		$sql = $adb->datadict->DropIndexSQL($table_prefix.'_messages_acc_list_idx', $focus->table_name);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_acc_list_idx', $focus->table_name, array('deleted','smownerid','account','folder','mtype','seen'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		
		$sql = $adb->datadict->DropIndexSQL($table_prefix.'_messages_checkflagchanges_idx', $focus->table_name);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_checkflagchanges_idx', $focus->table_name, array('deleted','smownerid','mdate','mtype','account','folder','xuid','seen','answered','flagged','forwarded'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		
		$sql = $adb->datadict->DropIndexSQL($table_prefix.'_messages_flaggedcount_idx', $focus->table_name);
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
		$sql = $adb->datadict->CreateIndexSQL($table_prefix.'_messages_flaggedcount_idx', $focus->table_name, array('deleted','smownerid','mtype','account','flagged'));
		if ($sql) $adb->datadict->ExecuteSQLArray($sql);
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
		$res = $adb->pquery("select crmid, smownerid, createdtime, modifiedtime, deleted from {$table_prefix}_crmentity where setype = ?", array('Messages'));
		while ($row=$adb->fetchByAssoc($res,-1,false)) {
			$adb->pquery("update {$focus->table_name} set smownerid = ?, createdtime = ?, modifiedtime = ?, deleted = ? where {$focus->table_index} = ?",
				array($row['smownerid'], $row['createdtime'], $row['modifiedtime'], $row['deleted'], $row['crmid']));
		}
	}
	
	// update field
	$adb->pquery(
		"UPDATE {$table_prefix}_field SET tablename = ? WHERE tabid = ? and tablename = ? and columnname in (?,?,?,?)",
		array($focus->table_name, getTabid('Messages'), "{$table_prefix}_crmentity", array('smownerid','createdtime','modifiedtime','deleted','description'))
	);
	
	// delete rows from _crmentity
	$adb->pquery("delete from {$table_prefix}_crmentity where setype = ?", array('Messages'));
	
	// remove partition if needed
	if ($adb->isMysql() && PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
		// get partition name
		global $dbconfig;
		$res = $adb->pquery(
			"SELECT partition_name FROM information_schema.partitions WHERE table_schema = ? AND table_name = ? AND partition_description = '''Messages'''",
			array($dbconfig['db_name'], $table_prefix.'_crmentity')
		);
		// and remove it
		if ($res && $adb->num_rows($res) > 0) {
			$pname = $adb->query_result_no_html($res, 0, 'partition_name');
			$adb->query("ALTER TABLE {$table_prefix}_crmentity DROP PARTITION $pname");
			
		}
	}
	
	Update::info('The Messages module do not use the table '.$table_prefix.'_crmentity anymore,');
	Update::info('so if you have customizations relying on this aspect, please review them.');
	Update::info('');
}

// remove cf table
if (Vtiger_Utils::CheckTable("{$table_prefix}_messagescf")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_messagescf");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

Update::info('The Messages module do not use the table '.$table_prefix.'_messagescf anymore,');
Update::info('so if you have customizations relying on this aspect, please review them.');
Update::info('');
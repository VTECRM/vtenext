<?php 

// crmv@164122

// move description field of mod notitications if not already there!
$module = 'ModNotifications';
$modInst = Vtecrm_Module::getInstance($module);
$tabid = $modInst->id;
$table = $table_prefix.'_modnotifications';
$cols = $adb->getColumnNames($table);
if (!in_array('description', $cols)) {
	
	$focus = CRMEntity::getInstance($module);
	if ($focus && $focus->table_name) {

		$adb->addColumnToTable($focus->table_name, 'description', 'XL');
		// move the field in the module table
		$adb->pquery("UPDATE {$table_prefix}_field SET tablename = ? WHERE tabid = ? AND fieldname = ? AND tablename = ?", array(
			$focus->table_name, $tabid, 'description', $table_prefix.'_crmentity'
		));
		
		// move the content in the new column
		if ($adb->isMySQL()) {
			$adb->pquery(
				"UPDATE {$focus->table_name} t
				INNER JOIN {$table_prefix}_crmentity c ON c.crmid = t.{$focus->table_index}
				SET t.description = c.description
				WHERE c.setype = ? AND c.description IS NOT NULL",
				array($module)
			);
		} elseif ($adb->isMssql()) {
			$adb->pquery(
				"UPDATE t
				SET t.description = c.description
				FROM {$focus->table_name} t
				INNER JOIN {$table_prefix}_crmentity c ON c.crmid = t.{$focus->table_index}
				WHERE c.setype = ? AND c.description IS NOT NULL",
				array($module)
			);
		} else {
			$res2 = $adb->pquery("SELECT crmid, description FROM {$table_prefix}_crmentity WHERE setype = ? AND description IS NOT NULL", array($module));
			while ($row2 = $adb->fetchByAssoc($res2, -1, false)) {
				$adb->pquery("UPDATE {$focus->table_name} SET description = ? WHERE {$focus->table_index} = ?", array($row2['description'], $row2['crmid']));
			}
		}
		// and empty the original column
		$adb->pquery("UPDATE {$table_prefix}_crmentity SET description = NULL WHERE setype = ? AND description IS NOT NULL", array($module));

		// fix customview
		$res2 = $adb->pquery(
			"SELECT cl.*
			FROM {$table_prefix}_cvcolumnlist cl
			INNER JOIN {$table_prefix}_customview c ON c.cvid = cl.cvid
			WHERE cl.columnname LIKE '{$table_prefix}_crmentity:description:%' AND c.entitytype = ?",
			array($module)
		);
		while ($row2 = $adb->fetchByAssoc($res2, -1, false)) {
			$pieces = explode(':', $row2['columnname']);
			$pieces[0] = $focus->table_name;
			$newcol = implode(':', $pieces);
			$adb->pquery("UPDATE {$table_prefix}_cvcolumnlist SET columnname = ? WHERE cvid = ? AND columnindex = ?", array($newcol, $row2['cvid'], $row2['columnindex']));
		}
		
		$res2 = $adb->pquery(
			"SELECT caf.*
			FROM {$table_prefix}_cvadvfilter caf
			INNER JOIN {$table_prefix}_customview c ON c.cvid = caf.cvid
			WHERE caf.columnname LIKE '{$table_prefix}_crmentity:description:%' AND c.entitytype = ?",
			array($module)
		);
		while ($row2 = $adb->fetchByAssoc($res2, -1, false)) {
			$pieces = explode(':', $row2['columnname']);
			$pieces[0] = $focus->table_name;
			$newcol = implode(':', $pieces);
			$adb->pquery("UPDATE {$table_prefix}_cvadvfilter SET columnname = ? WHERE cvid = ? AND columnindex = ?", array($newcol, $row2['cvid'], $row2['columnindex']));
		}
		
		$res2 = $adb->pquery(
			"SELECT co.*
			FROM tbl_s_cvorderby co
			INNER JOIN {$table_prefix}_customview c ON c.cvid = co.cvid
			WHERE co.columnname LIKE '{$table_prefix}_crmentity:description:%' AND c.entitytype = ?",
			array($module)
		);
		while ($row2 = $adb->fetchByAssoc($res2, -1, false)) {
			$pieces = explode(':', $row2['columnname']);
			$pieces[0] = $focus->table_name;
			$newcol = implode(':', $pieces);
			$adb->pquery("UPDATE tbl_s_cvorderby SET columnname = ? WHERE cvid = ? AND columnindex = ?", array($newcol, $row2['cvid'], $row2['columnindex']));
		}
	}
	
}

// transform into utility module

$adb->pquery("UPDATE {$table_prefix}_tab SET isentitytype = 0 WHERE name = ?", array($module));
create_tab_data_file();

// create the new changelog seq table
$res = $adb->query("SELECT MAX(modnotificationsid) as maxid FROM $table");
$maxid = $adb->query_result_no_html($res, 0, 'maxid') ?: 1;
$adb->database->DropSequence($table_prefix.'_modnotifications_seq');
$adb->database->CreateSequence($table_prefix.'_modnotifications_seq', $maxid + 10); // limit race condition

// remove cf table
if (Vtiger_Utils::CheckTable("{$table_prefix}_modnotificationscf")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_modnotificationscf");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

//  move columns from crmentity and delete rows
if ($adb->isMysql()) {
	if (!in_array('createdtime', $cols)) {
		$adb->query(
			"ALTER TABLE $table
				ADD COLUMN smcreatorid INT(19) NOT NULL DEFAULT 0,
				ADD COLUMN smownerid INT(19) NOT NULL DEFAULT 0,
				ADD COLUMN createdtime TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
				ADD COLUMN modifiedtime TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00'"
		);
	}
} else {
	$adb->addColumnToTable($table, 'smcreatorid', 'INT(19) NOTNULL DEFAULT 0');
	$adb->addColumnToTable($table, 'smownerid', 'INT(19) NOTNULL DEFAULT 0');
	$adb->addColumnToTable($table, 'createdtime', 'T NOTNULL DEFAULT \'0000-00-00 00:00:00\'');
	$adb->addColumnToTable($table, 'modifiedtime', 'T NOTNULL DEFAULT \'0000-00-00 00:00:00\'');
}
if (in_array('notification_no', $cols)) {
	$sqlarray = $adb->datadict->DropColumnSQL($table,'notification_no');
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

// remove useless index
$indexes = $adb->database->MetaIndexes($table_prefix.'_modnotifications_modules') ?: array();
if (array_key_exists('vte_modnotifications_modules_idx', $indexes)) {
	$sql = $adb->datadict->DropIndexSQL('vte_modnotifications_modules_idx', $table_prefix.'_modnotifications_modules');
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}

// add good indexes
if (!array_key_exists('modnotifications_module_idx', $indexes)) {
	$sql = $adb->datadict->CreateIndexSQL("modnotifications_module_idx", $table_prefix.'_modnotifications_modules', 'module');
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}
if (!array_key_exists('modnot_owner_time_idx', $indexes)) {
	$sql = $adb->datadict->CreateIndexSQL("modnot_owner_time_idx", $table_prefix.'_modnotifications', array('smownerid', 'createdtime'));
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}
if (!array_key_exists('modnot_modtime_idx', $indexes)) {
	$sql = $adb->datadict->CreateIndexSQL("modnot_modtime_idx", $table_prefix.'_modnotifications', array('modifiedtime'));
	if ($sql) $adb->datadict->ExecuteSQLArray($sql);
}

if ($adb->isMysql()) {
	$adb->query(
		"UPDATE $table m
		INNER JOIN {$table_prefix}_crmentity c ON c.crmid = m.modnotificationsid AND c.setype = '$module'
		SET m.smcreatorid = c.smcreatorid, m.smownerid = c.smownerid, m.createdtime = c.createdtime, m.modifiedtime = c.modifiedtime"
	);
	$adb->query("DELETE $table FROM $table INNER JOIN {$table_prefix}_crmentity ON crmid = modnotificationsid AND setype = '$module' WHERE deleted = 1");
} else {
	$res = $adb->pquery("SELECT crmid, smcreatorid, smownerid, createdtime, modifiedtime, deleted FROM {$table_prefix}_crmentity WHERE setype = ?", array($module));
	while ($row = $adb->fetchByAssoc($res, -1, false)) {
		if ($row['deleted'] == '1') {
			$adb->pquery("DELETE FROM $table WHERE modnotificationsid = ?", array($row['crmid']));
		} else {
			$adb->pquery(
				"UPDATE $table SET smcreatorid = ?, smownerid = ?, createdtime = ?, modifiedtime = ? WHERE modnotificationsid = ?", 
				array($row['smcreatorid'],$row['smownerid'], $row['createdtime'], $row['modifiedtime'], $row['crmid'])
			);
		}
	}
}

// remove data from crmentity
$adb->pquery("DELETE FROM {$table_prefix}_crmentity WHERE setype = ?", array($module));

// remove partition if needed
if ($adb->isMysql() && PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
	// get partition name
	global $dbconfig;
	$res = $adb->pquery(
		"SELECT partition_name FROM information_schema.partitions WHERE table_schema = ? AND table_name = ? AND partition_description = '''$module'''", 
		array($dbconfig['db_name'], $table_prefix.'_crmentity')
	);
	// and remove it
	if ($res && $adb->num_rows($res) > 0) {
		$pname = $adb->query_result_no_html($res, 0, 'partition_name');
		$adb->query("ALTER TABLE {$table_prefix}_crmentity DROP PARTITION $pname");
		
	}
}

// remove fields
$adb->pquery("DELETE FROM {$table_prefix}_field WHERE tabid = ?", array($tabid));
$adb->pquery("DELETE FROM {$table_prefix}_fieldmodulerel WHERE module = ?", array($module));
$adb->pquery("DELETE FROM {$table_prefix}_modentity_num WHERE semodule = ?", array($module));

// remove other stuff
$cvFocus = CRMEntity::getInstance('CustomView');
$res = $adb->pquery("SELECT cvid FROM {$table_prefix}_customview WHERE entitytype = ?", array($module));
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$cvFocus->trash($module, $row['cvid']);
}

Update::info('The ModNotifications module is not an entity module anymore, so if you have customizations');
Update::info('relying on this aspect, please review them.');
Update::info('');

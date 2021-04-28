<?php 

// crmv@164120
// transform changelog to utility module

$adb->pquery("UPDATE {$table_prefix}_tab SET isentitytype = 0 WHERE name = ?", array('ChangeLog'));
create_tab_data_file();

// create the new changelog seq table
$res = $adb->query("SELECT MAX(changelogid) as maxid FROM {$table_prefix}_changelog");
$maxid = $adb->query_result_no_html($res, 0, 'maxid') ?: 1;
$adb->database->DropSequence($table_prefix.'_changelog_seq');
$adb->database->CreateSequence($table_prefix.'_changelog_seq', $maxid + 10); // limit race condition

// remove cf table
if (Vtiger_Utils::CheckTable("{$table_prefix}_changelogcf")) {
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_changelogcf");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

//  move columns from crmentity and delete rows
$adb->addColumnToTable($table_prefix.'_changelog', 'user_id', 'INT(19) NOTNULL DEFAULT 0');
if ($adb->isMysql()) {
	$adb->query("UPDATE {$table_prefix}_changelog INNER JOIN {$table_prefix}_crmentity ON crmid = changelogid AND setype = 'ChangeLog' SET user_id = smownerid");
	$adb->query("DELETE {$table_prefix}_changelog FROM {$table_prefix}_changelog INNER JOIN {$table_prefix}_crmentity ON crmid = changelogid AND setype = 'ChangeLog' WHERE deleted = 1");
} else {
	$res = $adb->pquery("SELECT crmid, smownerid, deleted FROM {$table_prefix}_crmentity WHERE setype = ?", array('ChangeLog'));
	while ($row = $adb->fetchByAssoc($res, -1, false)) {
		if ($row['deleted'] == '1') {
			$adb->pquery("DELETE FROM {$table_prefix}_changelog WHERE changelogid = ?", array($row['crmid']));
		} else {
			$adb->pquery("UPDATE {$table_prefix}_changelog SET user_id = ? WHERE changelogid = ?", array($row['smownerid'], $row['crmid']));
		}
	}
}

// remove data from crmentity
$adb->pquery("DELETE FROM {$table_prefix}_crmentity WHERE setype = ?", array('ChangeLog'));

// remove partition if needed
if ($adb->isMysql() && PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
	// get partition name
	global $dbconfig;
	$res = $adb->pquery(
		"SELECT partition_name FROM information_schema.partitions WHERE table_schema = ? AND table_name = ? AND partition_description = '''ChangeLog'''", 
		array($dbconfig['db_name'], $table_prefix.'_crmentity')
	);
	// and remove it
	if ($res && $adb->num_rows($res) > 0) {
		$pname = $adb->query_result_no_html($res, 0, 'partition_name');
		$adb->query("ALTER TABLE {$table_prefix}_crmentity DROP PARTITION $pname");
		
	}
}

$clModule = Vtecrm_Module::getInstance('ChangeLog');
$clid = $clModule->id;

// remove fields
$adb->pquery("DELETE FROM {$table_prefix}_field WHERE tabid = ?", array($clid));
$adb->pquery("DELETE FROM {$table_prefix}_fieldmodulerel WHERE module = ?", array('ChangeLog'));

// remove other stuff
$cvFocus = CRMEntity::getInstance('CustomView');
$res = $adb->pquery("SELECT cvid FROM {$table_prefix}_customview WHERE entitytype = ?", array('ChangeLog'));
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$cvFocus->trash('ChangeLog', $row['cvid']);
}

$adb->pquery("DELETE FROM {$table_prefix}_links WHERE tabid = ?", array($clid));

// remove useless values (can be interesting to allow it in the module manager, though)
$adb->pquery("DELETE FROM vte_hide_tab WHERE tabid = ?", array($clid));
//$adb->pquery("UPDATE vte_hide_tab SET hide_profile = 0, hide_report = 0 WHERE tabid = ?", array($clid));


Update::info('The ChangeLog module is not an entity module anymore, so if you have customizations');
Update::info('relying on this aspect, please review them.');
Update::info('');


// set other modules as core
$adb->pquery("UPDATE {$table_prefix}_tab SET customized = 0 WHERE name IN (?,?)", array('Touch', 'Geolocalization'));

// hide some modules
$m = Vtecrm_Module::getInstance('M');
if ($m) $m->hide(array('hide_module_manager' => 1));

$m = Vtecrm_Module::getInstance('Mobile');
if ($m) $m->hide(array('hide_module_manager' => 1));

$m = Vtecrm_Module::getInstance('WSAPP');
if ($m) $m->hide(array('hide_module_manager' => 1));


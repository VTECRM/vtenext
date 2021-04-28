<?php 

/* crmv@205309 */

$schema = 
	'<?xml version="1.0"?>
	<schema version="0.3">
		<table name="'.$table_prefix.'_filestorage">
			<opt platform="mysql">ENGINE=InnoDB</opt>
			<field name="fileid" type="I" size="19">
				<key/>
			</field>
			<field name="status" type="I" size="5">
				<NOTNULL/>
				<DEFAULT value="0" />
			</field>
			<field name="attempts" type="I" size="5"/>
			<field name="last_save_attempt" type="T">
				<DEFAULT value="0000-00-00 00:00:00"/>
			</field>
			<field name="path" type="C" size="1000">
				<NOTNULL/>
				<DEFAULT value="" />
			</field>
			<field name="filedata" type="B" />
			<index name="filestorage_status_idx">
				<col>status</col>
			</index>
			<index name="filestorage_path_idx">
				<col>path</col>
			</index>
		</table>
	</schema>';

if (!Vtiger_Utils::CheckTable($table_prefix.'_filestorage')) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
} 

$cronname = 'FileStorageDB';
$CU = CronUtils::getInstance();
// install cronjob
$cj = CronJob::getByName($cronname);
if (empty($cj)) {
	$cj = new CronJob();
	$cj->name = $cronname;
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->timeout = 1800;	// 30 min
	$cj->repeat = 1200;		// 20 min
	$cj->fileName = 'cron/modules/Documents/FileStorageDB.service.php';
	$CU->insertCronJob($cj);
}


/* crmv@205343 */

// migrate description column to the table module
$module = 'ConfProducts';
$modInst = Vtecrm_Module::getInstance($module);
$tabid = $modInst->id;
$table = $table_prefix.'_confproducts';
$cols = $adb->getColumnNames($table);
if (!in_array('description', $cols)) 

	$focus = CRMEntity::getInstance($module);
	if ($focus && $focus->table_name) {

		$adb->addColumnToTable($focus->table_name, 'description', 'XL');{
		
		// move the field in the module table
		$adb->pquery(
			"UPDATE {$table_prefix}_field SET tablename = ? WHERE tabid = ? AND fieldname = ? AND tablename = ?",
			array($focus->table_name, $tabid, 'description', $table_prefix.'_crmentity')
		);
		
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

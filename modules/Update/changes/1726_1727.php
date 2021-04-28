<?php 

// crmv@152701 - move Sms and Fax out from the activity table

global $adb, $table_prefix;

// -------------------------- SMS --------------------------

// fix entityname table
$adb->pquery(
	"UPDATE {$table_prefix}_entityname SET entityidfield = ?, entityidcolumn = ? WHERE modulename = ? AND entityidfield = ?",
	array('activityid', 'activityid', 'Sms', 'crmid')
);

// move sms to dedicated table
$smsInstance = Vtecrm_Module::getInstance('Sms');
$smstabid = $smsInstance->id;

$smstable = $table_prefix.'_sms';

// create new table for sms
if(!Vtiger_Utils::CheckTable($smstable)) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$smstable.'">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="smsid" type="I" size="19">
				      <KEY/>
				    </field>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

// get column types
$coldefs = array();
$movedcolumns = array();

// move fields
$res = $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE tabid = ?", array($smstabid));
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$fieldid = $row['fieldid'];
	$table = $row['tablename'];
	$column = $row['columnname'];
	$field = $row['fieldname'];
	
	if ($column == 'crmid') {
		//$newcolumn = 'parent_id';
		// delete this, use default crmentityrel
		$adb->pquery("DELETE FROM {$table_prefix}_field WHERE fieldid = ?", array($fieldid));
		continue;
	} else {
		$newcolumn = $column;
	}
	
	if ($table == $table_prefix.'_activity') {
		// get column info
		if (!isset($coldefs[$table])) {
			$coldefs[$table] = $adb->datadict->MetaColumns($table, true);
		}
		$ucol = strtoupper($column);
		if (isset($coldefs[$table][$ucol])) {
			$metatype = $adb->datadict->MetaType($coldefs[$table][$ucol]);
			if (in_array($metatype, array('I', 'C')) && $coldefs[$table][$ucol]->max_length > 0) {
				$metatype .= "({$coldefs[$table][$ucol]->max_length})";
			}
			// create column
			$adb->addColumnToTable($smstable, $newcolumn, $metatype);
		}
		
		// update field
		$adb->pquery(
			"UPDATE {$table_prefix}_field SET tablename = ?, columnname = ? WHERE fieldid = ?",
			array($smstable, $newcolumn, $fieldid)
		);
		
		$movedcolumns[$table][$column] = $newcolumn;
	}
}

// move the data
if (count($movedcolumns) > 0) {
	$insertcols = $selectcols = array();
	foreach ($movedcolumns as $table => $cols) {
		$oldcols = array_keys($cols);
		$newcols = array_values($cols);
		$adb->format_columns($oldcols);
		$adb->format_columns($newcols);
		$selectcols = array_merge($selectcols, $oldcols);
		$insertcols = array_merge($insertcols, $newcols);
	}
	$ignore = $adb->isMySQL() ? 'IGNORE' : '';
	$sql = "INSERT $ignore INTO $smstable (smsid, ".implode(',', $insertcols).") 
		SELECT {$table_prefix}_activity.activityid, ".implode(', ', $selectcols)." FROM {$table_prefix}_activity 
		WHERE activitytype = ?";
	$adb->pquery($sql, array('Sms'));
}

// move relations
$res = $adb->pquery(
	"SELECT sa.*, c.setype FROM {$table_prefix}_seactivityrel sa
	INNER JOIN {$table_prefix}_activity a ON sa.activityid = a.activityid
	INNER JOIN {$table_prefix}_crmentity c ON c.crmid = sa.crmid
	WHERE a.activitytype = ?",
	array('Sms')
);
$inserts = array();
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$inserts[] = array($row['crmid'], $row['setype'], $row['activityid'], 'Sms');
}
if (count($inserts) > 0) {
	$adb->bulkInsert($table_prefix.'_crmentityrel', array('crmid', 'module', 'relcrmid', 'relmodule'), $inserts);
}

// delete old data
$adb->pquery("DELETE FROM {$table_prefix}_seactivityrel WHERE activityid IN (SELECT activityid FROM {$table_prefix}_activity WHERE activitytype = ?)", array('Sms'));
$adb->pquery("DELETE FROM {$table_prefix}_activity WHERE activitytype = ?", array('Sms'));

// change entity field and rebuild cache
$ENU = EntityNameUtils::getInstance();
$ENU->changeEntityField('Sms', 'description');

// remove bad files
@unlink('modules/Sms/DetailView.php');


// -------------------------- FAX --------------------------

// move sms to dedicated table
$faxInstance = Vtecrm_Module::getInstance('Fax');
$faxtabid = $faxInstance->id;

$faxtable = $table_prefix.'_fax';

// create new table for fax
if(!Vtiger_Utils::CheckTable($faxtable)) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$faxtable.'">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="faxid" type="I" size="19">
				      <KEY/>
				    </field>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

// get column types
$coldefs = array();
$movedcolumns = array();

// move fields
$res = $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE tabid = ?", array($faxtabid));
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$fieldid = $row['fieldid'];
	$table = $row['tablename'];
	$column = $row['columnname'];
	$field = $row['fieldname'];
	
	if ($column == 'crmid') {
		$newcolumn = 'parent_id';
		// delete this, use default crmentityrel
		//$adb->pquery("DELETE FROM {$table_prefix}_field WHERE fieldid = ?", array($fieldid));
		//continue;
	} else {
		$newcolumn = $column;
	}
	
	if ($table == $table_prefix.'_activity' || $table == $table_prefix.'_seactivityrel') {
		// get column info
		if (!isset($coldefs[$table])) {
			$coldefs[$table] = $adb->datadict->MetaColumns($table, true);
		}
		$ucol = strtoupper($column);
		if (isset($coldefs[$table][$ucol])) {
			$metatype = $adb->datadict->MetaType($coldefs[$table][$ucol]);
			if (in_array($metatype, array('I', 'C')) && $coldefs[$table][$ucol]->max_length > 0) {
				$metatype .= "({$coldefs[$table][$ucol]->max_length})";
			}
			// create column
			$adb->addColumnToTable($faxtable, $newcolumn, $metatype);
		}
		
		// update field
		$adb->pquery(
			"UPDATE {$table_prefix}_field SET tablename = ?, columnname = ? WHERE fieldid = ?",
			array($faxtable, $newcolumn, $fieldid)
		);
		
		if ($table == $table_prefix.'_activity') {
			$movedcolumns[$table][$column] = $newcolumn;
		}
	}
}

// move the data
if (count($movedcolumns) > 0) {
	$insertcols = $selectcols = array();
	foreach ($movedcolumns as $table => $cols) {
		$oldcols = array_keys($cols);
		$newcols = array_values($cols);
		$adb->format_columns($oldcols);
		$adb->format_columns($newcols);
		$selectcols = array_merge($selectcols, $oldcols);
		$insertcols = array_merge($insertcols, $newcols);
	}
	$ignore = $adb->isMySQL() ? 'IGNORE' : '';
	$sql = "INSERT $ignore INTO $faxtable (faxid, ".implode(',', $insertcols).") 
		SELECT {$table_prefix}_activity.activityid, ".implode(', ', $selectcols)." FROM {$table_prefix}_activity 
		WHERE activitytype = ?";
	$adb->pquery($sql, array('Fax'));
}

// move relations
$res = $adb->pquery(
	"SELECT sa.*, c.setype FROM {$table_prefix}_seactivityrel sa
	INNER JOIN {$table_prefix}_activity a ON sa.activityid = a.activityid
	INNER JOIN {$table_prefix}_crmentity c ON c.crmid = sa.crmid
	WHERE a.activitytype = ?",
	array('Fax')
);
$inserts = array();
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$inserts[] = array($row['crmid'], $row['setype'], $row['activityid'], 'Fax');
}
if (count($inserts) > 0) {
	$adb->bulkInsert($table_prefix.'_crmentityrel', array('crmid', 'module', 'relcrmid', 'relmodule'), $inserts);
}

// delete old data
$adb->pquery("DELETE FROM {$table_prefix}_seactivityrel WHERE activityid IN (SELECT activityid FROM {$table_prefix}_activity WHERE activitytype = ?)", array('Fax'));
$adb->pquery("DELETE FROM {$table_prefix}_activity WHERE activitytype = ?", array('Fax'));

// change entity field and rebuild cache
$ENU = EntityNameUtils::getInstance();
$efields = $ENU->getEntityField('Fax');
if (empty($efields['tablename'])) {
	$params = array($faxtabid, 'Fax', $faxtable, 'subject', 'faxid', 'faxid');
	$adb->pquery("INSERT INTO {$table_prefix}_entityname (tabid, modulename, tablename, fieldname, entityidfield, entityidcolumn) VALUES (?,?,?,?,?,?)", $params);
}
// change and recalculate
$ENU->changeEntityField('Fax', 'subject');

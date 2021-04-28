<?php
global $adb, $table_prefix;

if (Vtiger_Utils::CheckTable($table_prefix.'_leadacctrel')) {
	$sqlarray = $adb->datadict->DropTableSQL($table_prefix.'_leadacctrel');
	$adb->datadict->ExecuteSQLArray($sqlarray);
}
if (Vtiger_Utils::CheckTable($table_prefix.'_leadcontrel')) {
	$sqlarray = $adb->datadict->DropTableSQL($table_prefix.'_leadcontrel');
	$adb->datadict->ExecuteSQLArray($sqlarray);
}
if (Vtiger_Utils::CheckTable($table_prefix.'_leadpotrel')) {
	$sqlarray = $adb->datadict->DropTableSQL($table_prefix.'_leadpotrel');
	$adb->datadict->ExecuteSQLArray($sqlarray);
}

$table = $table_prefix.'_leadconvrel';
$schema_table =
'<schema version="0.3">
	<table name="'.$table.'">
		<opt platform="mysql">ENGINE=InnoDB</opt>
		<field name="leadid" type="I" size="19">
			<KEY/>
		</field>
		<field name="userid" type="I" size="19"/>
		<field name="convtime" type="T">
			<default value="0000-00-00 00:00:00" />
		</field>
		<field name="accountid" type="I" size="19"/>
		<field name="contactid" type="I" size="19"/>
		<field name="potentialid" type="I" size="19"/>
	</table>
</schema>';
if(!Vtiger_Utils::CheckTable($table)) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
}

$result = $adb->query("SELECT l.leadid
		FROM {$table_prefix}_leaddetails l
		INNER JOIN {$table_prefix}_crmentity c ON l.leadid = c.crmid
		INNER JOIN {$table_prefix}_contactdetails co ON l.firstname = co.firstname AND l.lastname = co.lastname
		INNER JOIN {$table_prefix}_crmentity c2 ON co.contactid = c2.crmid AND c2.deleted = 0
		INNER JOIN {$table_prefix}_account a ON co.accountid = a.accountid
		WHERE c.deleted = 0 AND converted = 1 and l.leadid not in (select leadid from {$table_prefix}_leadconvrel)
		GROUP BY l.leadid");
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result)) {
		$result1 = $adb->pquery("SELECT l.leadid, c.modifiedby, c.modifiedtime, co.contactid, a.accountid
				FROM {$table_prefix}_leaddetails l
				INNER JOIN {$table_prefix}_crmentity c ON l.leadid = c.crmid
				INNER JOIN {$table_prefix}_contactdetails co ON l.firstname = co.firstname AND l.lastname = co.lastname
				INNER JOIN {$table_prefix}_crmentity c2 ON co.contactid = c2.crmid AND c2.deleted = 0
				INNER JOIN {$table_prefix}_account a ON co.accountid = a.accountid
				WHERE c.deleted = 0 AND converted = 1 and l.leadid = ?
				order by c2.createdtime", array($row['leadid']));
		if ($result1 && $adb->num_rows($result1) > 0) {
			$adb->pquery("insert into {$table_prefix}_leadconvrel (leadid, userid, convtime, accountid, contactid) values (?,?,?,?,?)", array(
					$row['leadid'], $adb->query_result($result1,0,'modifiedby'), $adb->query_result($result1,0,'modifiedtime'), $adb->query_result($result1,0,'accountid'), $adb->query_result($result1,0,'contactid')
			));
		}
	}
}
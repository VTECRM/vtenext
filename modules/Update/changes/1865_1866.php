<?php

// crmv@187020


// replace langs with vte_languages
require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();
$gck = $VP->get('performance.global_cache_keys');
if (is_array($gck)) {
	$k = array_search('langs', $gck);
	if ($k !== false) {
		unset($gck[$k]);
	}
	if (!in_array('vte_languages', $gck)) {
		$gck[] = 'vte_languages';
	}
	$VP->set('performance.global_cache_keys', array_values($gck));
}

$table = $table_prefix.'_reload_user_cache';
$schema_table =
'<schema version="0.3">
	<table name="'.$table.'">
		<opt platform="mysql">ENGINE=InnoDB</opt>
		<field name="userid" type="I" size="19">
			<KEY/>
		</field>
		<field name="storage_type" type="C" size="31">
			<KEY/>
		</field>
		<field name="varname" type="C" size="63">
			<KEY/>
		</field>
		<field name="reload_date" type="T">
			<DEFAULT value="0000-00-00 00:00:00"/>
		</field>
	</table>
</schema>';
if(!Vtiger_Utils::CheckTable($table)) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
}


if (Vtiger_Utils::CheckTable("{$table_prefix}_reload_session")) {
	// don't copy data, to force a cache reload
	//$adb->query("INSERT INTO $table SELECT userid, 'session' as storage_type, session_var FROM {$table_prefix}_reload_session");
	
	$sqlarray = $adb->datadict->DropTableSQL("{$table_prefix}_reload_session");
	$adb->datadict->ExecuteSQLArray($sqlarray);
}


$table = $table_prefix.'_reload_host_cache';
$schema_table =
'<schema version="0.3">
	<table name="'.$table.'">
		<opt platform="mysql">ENGINE=InnoDB</opt>
		<field name="hostid" type="C" size="63">
			<KEY/>
		</field>
		<field name="storage_type" type="C" size="31">
			<KEY/>
		</field>
		<field name="varname" type="C" size="63">
			<KEY/>
		</field>
		<field name="reload_date" type="T">
			<DEFAULT value="0000-00-00 00:00:00"/>
		</field>
	</table>
</schema>';
if(!Vtiger_Utils::CheckTable($table)) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
}

$table = $table_prefix.'_reload_global_cache';
$schema_table =
'<schema version="0.3">
	<table name="'.$table.'">
		<opt platform="mysql">ENGINE=InnoDB</opt>
		<field name="varname" type="C" size="63">
			<KEY/>
		</field>
		<field name="storage_type" type="C" size="31">
			<KEY/>
		</field>
		<field name="reload_date" type="T">
			<DEFAULT value="0000-00-00 00:00:00"/>
		</field>
	</table>
</schema>';
if(!Vtiger_Utils::CheckTable($table)) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
}


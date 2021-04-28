<?php

// crmv@168297

$table = $table_prefix.'_soapws_operation';
$schema_table =
'<schema version="0.3">
	<table name="'.$table.'">
		<opt platform="mysql">ENGINE=InnoDB</opt>
		<field name="operationid" type="I" size="19">
			<KEY/>
		</field>
		<field name="name" type="C" size="63">
			<NOTNULL/>
		</field>
		<field name="handler_path" type="C" size="255">
			<NOTNULL/>
		</field>
		<field name="handler_class" type="C" size="63">
			<NOTNULL/>
		</field>
		<field name="return_type" type="C" size="255">
			<NOTNULL/>
		</field>
	</table>
</schema>';
if(!Vtiger_Utils::CheckTable($table)) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
}

$table = $table_prefix.'_soapws_operation_params';
$schema_table =
'<schema version="0.3">
	<table name="'.$table.'">
		<opt platform="mysql">ENGINE=InnoDB</opt>
		<field name="operationid" type="I" size="19">
			<KEY/>
		</field>
		<field name="name" type="C" size="63">
			<KEY/>
		</field>
		<field name="param_type" type="C" size="255">
			<NOTNULL/>
		</field>
		<field name="sequence" type="I" size="5"/>
	</table>
</schema>';
if(!Vtiger_Utils::CheckTable($table)) {
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
}

// install standard ws
require_once('soap/SOAPWebservices.php');
SOAPWebservices::installWS();

Update::info('The SOAP Webservices used in the customer portal now are stored into the database and can be added dynamically.');
Update::info('If you have customizations on them, please check the latest SDK manual to know how to update them to the new system.');


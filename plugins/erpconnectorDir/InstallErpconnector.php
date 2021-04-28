<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$erpconnector_dir = dirname( __FILE__ ).'/';	//ex. $erpconnector_dir = 'plugins/erpconnectorDir/'; // crmv@177497
if ($erpconnector_dir == '') {
	die("Configurare cartella erpconnector in InstallErpconnector.php\n");
}

require("../../config.inc.php");
chdir($root_directory);
include_once('include/utils/utils.php');
include_once('vtlib/Vtecrm/Utils.php');//crmv@207871
require($erpconnector_dir.'config.php');
global $adb,$table_prefix;

if(!Vtecrm_Utils::CheckTable($log_script_state)) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$log_script_state.'">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="type" type="C" size="255"/>
				    <field name="state" type="I" size="1">
				    	<DEFAULT value="0"/>
    				</field>
				    <field name="working_id" type="I" size="19"/>
				    <field name="enabled" type="I" size="1">
				    	<DEFAULT value="1"/>
    				</field>
				    <field name="lastrun" type="T">
				    	<DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
if(!Vtecrm_Utils::CheckTable($log_script_content)) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$log_script_content.'">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="id" type="I" size="19">
				    	<KEY/>
    				</field>
				    <field name="type" type="C" size="255"/>
				    <field name="date_start" type="T">
				    	<DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
				    <field name="date_end" type="T">
				    	<DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
				    <field name="records_created" type="I" size="19"/>
				    <field name="records_updated" type="I" size="19"/>
				    <field name="records_deleted" type="I" size="19"/>
				    <field name="total_records" type="I" size="19"/>
				    <field name="duration" type="C" size="255"/>
				    <index name="type_idx">
						<col>type</col>
				    </index>
				    <index name="date_start_idx">
						<col>date_start</col>
				    </index>
				    <index name="date_end_idx">
						<col>date_end</col>
				    </index>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
$erpconnector_modules = array('Accounts','Contacts','Products','Quotes','SalesOrder','Invoice','Users');
$erpconnector_modules_row = array('Quotes','SalesOrder','Invoice');
foreach($erpconnector_modules as $erpconnector_module) {
	@require_once("{$erpconnector_dir}{$erpconnector_module}_import/{$erpconnector_module}_config.php");
	$fields = array_keys($mapping);
	if(!Vtecrm_Utils::CheckTable($table)) {
		$schema = '<?xml version="1.0"?>
					<schema version="0.3">
					  <table name="'.$table.'">
					  <opt platform="mysql">ENGINE=InnoDB</opt>';
		foreach($fields as $field) {
			$schema .= '<field name="'.$field.'" type="C" size="255"/>';
		}
		$schema .= '</table>
					</schema>';
		$schema_obj = new adoSchema($adb->database);
		$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
	}
	$moduleInstance = Vtecrm_Module::getInstance($erpconnector_module);
	$result = $adb->query("select * from {$table_prefix}_field where fieldname = 'external_code' AND tabid = ".$moduleInstance->id);
	if ($result && $adb->num_rows($result) > 0) {
		//do nothing
	} else {
		$result1 = $adb->query("select blocklabel from {$table_prefix}_blocks where tabid = ".$moduleInstance->id);
		if ($result1 && $adb->num_rows($result1) > 0) {
			$blocklabel = $adb->query_result($result1,0,'blocklabel');
			if ($blocklabel != '') {
				$fields = array();
				$fields[] = array('module'=>$erpconnector_module,'block'=>$blocklabel,'name'=>'external_code','label'=>'External Code','uitype'=>'1','readonly'=>'99');
				include('modules/SDK/examples/fieldCreate.php');
				SDK::setLanguageEntries($erpconnector_module, 'External Code', array('it_it'=>'Codice Esterno','en_us'=>'External Code','pt_br'=>'C�digo Externo'));
			}
		}
	}
	if (in_array($erpconnector_module,$erpconnector_modules_row)) {
		$fields_row = array_keys(array_merge($mapping_row,$additional_fields_row));
		if ($table_row != '' && !empty($fields_row)) {
			if(!Vtecrm_Utils::CheckTable($table_row)) {
				$schema = '<?xml version="1.0"?>
							<schema version="0.3">
							  <table name="'.$table_row.'">
							  <opt platform="mysql">ENGINE=InnoDB</opt>';
				foreach($fields_row as $field_row) {
					$schema .= '<field name="'.$field_row.'" type="C" size="255"/>';
				}
				$schema .= '</table>
							</schema>';
				$schema_obj = new adoSchema($adb->database);
				$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
			}
		}
	}
}
?>
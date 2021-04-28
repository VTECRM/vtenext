<?php
global $adb, $table_prefix;

SDK::setLanguageEntries('ALERT_ARR', 'LBL_FPOFV_RULE_NAME_DUPLICATED', array('it_it'=>'Nome Regola duplicato','en_us'=>'Rule name duplicated'));
@unlink('Smarty/templates/themes/next/modules/Conditionals/EditView.tpl');
@unlink('Smarty/templates/themes/next/modules/Conditionals/ListView.tpl');
@unlink('Smarty/templates/themes/next/modules/Conditionals/ListViewContents.tpl');
@rmdir('Smarty/templates/themes/next/modules/Conditionals');

if(!Vtiger_Utils::CheckTable($table_prefix.'_conditionals_versions')) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$table_prefix.'_conditionals_versions">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="id" type="I" size="11">
				      <KEY/>
				    </field>
				    <field name="version" type="C" size="10"/>
				    <field name="createdtime" type="T"/>
				    <field name="createdby" type="I" size="19"/>
				    <field name="modifiedtime" type="T"/>
				    <field name="modifiedby" type="I" size="19"/>
				    <field name="closed" type="I" size="1">
				      <DEFAULT value="0"/>
				    </field>
				    <field name="json" type="XL"/>
				    <index name="idx_version">
				      <col>version</col>
				    </index>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
if(!Vtiger_Utils::CheckTable($table_prefix.'_conditionals_versions_import')) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$table_prefix.'_conditionals_versions_import">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="version" type="C" size="10">
				      <KEY/>
				    </field>
				    <field name="sequence" type="I" size="10"/>
				    <field name="json" type="XL"/>
				    <field name="status" type="C" size="10"/>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
if(!Vtiger_Utils::CheckTable($table_prefix.'_conditionals_versions_rel')) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$table_prefix.'_conditionals_versions_rel">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="id" type="I" size="19">
				      <KEY/>
				    </field>
				    <field name="metalogid" type="I" size="19">
				      <KEY/>
				    </field>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

require_once('include/utils/EntityColorUtils.php');
$entityColorUtils = EntityColorUtils::getInstance();
$ECUUnsupportedModules = $entityColorUtils->getUnsupportedModules();
$ECUUnsupportedModulesT = array();
foreach($ECUUnsupportedModules as $m) $ECUUnsupportedModulesT[] = getTabid2($m);
$adb->pquery("delete from tbl_s_lvcolors where tabid in (".generateQuestionMarks($ECUUnsupportedModulesT).")", array($ECUUnsupportedModulesT));

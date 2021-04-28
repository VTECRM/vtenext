<?php
global $adb, $table_prefix;

// crmv@194733

if (!isModuleInstalled('JobOrder')) {
	$package = new Vtiger_Package();
	$package->importByManifest('JobOrder');
	
	$fields = array();
	$fields[] = array('module'=>'ProjectPlan','block'=>'LBL_PROJECT_INFORMATION','name'=>'joborderid','label'=>'Job Order','uitype'=>'10','columntype'=>'I(19)','typeofdata'=>'V~O','quickcreate'=>'2','masseditable'=>'0');
	include('modules/SDK/examples/fieldCreate.php');
	
	SDK::setLanguageEntries('ProjectPlan', 'Job Order', array('it_it'=>'Commessa', 'en_us'=>'Job Order'));
}

// crmv@190834 crmv@197445

$adb->query("update {$table_prefix}_settings_blocks set sequence = sequence + 1 where blockid >= 4");

require_once('vtlib/Vtiger/SettingsBlock.php');
require_once('vtlib/Vtiger/SettingsField.php');
$block = new Vtiger_SettingsBlock();
$block->label = 'LBL_KLONDIKE_AI';
$block->sequence = 4;
$blockid = $block->save();
$adb->query("update {$table_prefix}_settings_blocks set image = 'gavel' where blockid = $blockid");

$field = new Vtiger_SettingsField();
$field->name = 'LBL_KLONDIKE_CLASSIFIER';
$field->iconpath = 'gavel';
$field->description = 'LBL_KLONDIKE_CLASSIFIER_DESC';
$field->linkto = 'index.php?module=Settings&action=KlondikeClassifier&parenttab=Settings';
$block->addField($field);

$field = new Vtiger_SettingsField();
$field->name = 'LBL_PROCESS_DISCOVERY_AGENT';
$field->iconpath = 'gavel';
$field->description = 'LBL_PROCESS_DISCOVERY_AGENT_DESC';
$field->linkto = 'index.php?module=Settings&action=ProcessDiscoveryAgent&parenttab=Settings';
$block->addField($field);

$field = new Vtiger_SettingsField();
$field->name = 'LBL_PROCESS_DISCOVERY';
$field->iconpath = 'call_split';
$field->description = 'LBL_PROCESS_DISCOVERY_DESC';
$field->linkto = 'index.php?module=Settings&action=ProcessDiscovery&parenttab=Settings';
$block->addField($field);

if(!Vtiger_Utils::CheckTable($table_prefix.'_process_discovery_agent')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_process_discovery_agent">
	  <opt platform="mysql">ENGINE=InnoDB</opt>
	    <field name="id" type="I" size="19">
	      <KEY/>
	    </field>
	    <field name="tabid" type="I" size="19"/>
	    <field name="viewid" type="I" size="19"/>
	  </table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

if(!Vtiger_Utils::CheckTable($table_prefix.'_klondike_classifier')) {
	$schema = '<?xml version="1.0"?>
	<schema version="0.3">
	  <table name="'.$table_prefix.'_klondike_classifier">
	  <opt platform="mysql">ENGINE=InnoDB</opt>
	    <field name="id" type="I" size="19">
	      <KEY/>
	    </field>
	    <field name="tabid" type="I" size="19"/>
	    <field name="viewid" type="I" size="19"/>
	    <field name="training_columns" type="XL"/>
		<field name="training_target" type="C" size="50" />
	  </table>
	</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_KLONDIKE_AI' => 'Klondike AI',
			'LBL_KLONDIKE_CLASSIFIER' => 'Classificatore',
			'LBL_KLONDIKE_CLASSIFIER_DESC' => 'Permette di configurare il classificatore',
			'LBL_PROCESS_DISCOVERY_AGENT' => 'Agente process discovery',
			'LBL_PROCESS_DISCOVERY_AGENT_DESC' => 'Permette di configurare la rilevazione dei processi',
			'LBL_TRAINING_COLUMNS' => 'Campi di training',
			'LBL_TRAINING_TARGET' => 'Campo da indovinare',
			'LBL_PROCESS_DISCOVERY' => 'Process discovery',
			'LBL_PROCESS_DISCOVERY_DESC' => 'Permette di vedere i processi rilevati',
			'LBL_PROCESS_DISCOVERY_ID' => 'Id',
			'LBL_PROCESS_DISCOVERY_ATTR_SET' => 'Set attributi',
			'LBL_PROCESS_DISCOVERY_EVENTS' => 'Eventi',
			'LBL_PROCESS_DISCOVERY_METRICS' => 'Metriche',
			'LBL_UPLOAD_DISCOVERED_BPMN' => 'Importa in VTE',
		),
		'en_us' => array(
			'LBL_KLONDIKE_AI' => 'Klondike AI',
			'LBL_KLONDIKE_CLASSIFIER' => 'Classifier',
			'LBL_KLONDIKE_CLASSIFIER_DESC' => 'Allow to configure the classifier',
			'LBL_PROCESS_DISCOVERY_AGENT' => 'Process discovery agent',
			'LBL_PROCESS_DISCOVERY_AGENT_DESC' => 'Allow to configure process detection',
			'LBL_TRAINING_COLUMNS' => 'Training fields',
			'LBL_TRAINING_TARGET' => 'Training target',
			'LBL_PROCESS_DISCOVERY' => 'Process discovery',
			'LBL_PROCESS_DISCOVERY_DESC' => 'Allow to view discovered processes',
			'LBL_PROCESS_DISCOVERY_ID' => 'Id',
			'LBL_PROCESS_DISCOVERY_ATTR_SET' => 'Attributes set',
			'LBL_PROCESS_DISCOVERY_EVENTS' => 'Events',
			'LBL_PROCESS_DISCOVERY_METRICS' => 'Metrics',
			'LBL_UPLOAD_DISCOVERED_BPMN' => 'Upload in VTE',
		),
	),
);
foreach ($trans as $module=>$modlang) {
	foreach ($modlang as $lang=>$translist) {
		foreach ($translist as $label=>$translabel) {
			SDK::setLanguageEntry($module, $lang, $label, $translabel);
		}
	}
}
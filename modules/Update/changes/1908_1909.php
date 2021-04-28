<?php
global $adb, $table_prefix;

$em = new VTEventsManager($adb);
$em->registerHandler('vtiger.entity.aftersave','modules/Calendar/CalendarHandler.php','CalendarHandler');

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('calendar_tracking.status_fields', array());
$VTEP->setProperty('calendar_tracking.status_values', array());

$ptModuleInstance = Vtecrm_Module::getInstance('ProjectTask');
$blockInstance = Vtecrm_Block::getInstance('LBL_BUDGET_INFORMATION', $ptModuleInstance);
if (!$blockInstance) {
	$blockInstance = new Vtecrm_Block();
	$blockInstance->label = 'LBL_BUDGET_INFORMATION';
	$blockInstance->save($ptModuleInstance);
}
SDK::setLanguageEntries('ProjectTask', 'LBL_BUDGET_INFORMATION', array('it_it'=>'Informazioni Budget','en_us'=>'Budget Information'));

$fields = array();
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'servicetype'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'servicetype','label'=>'Service type','quickcreate'=>2,'uitype'=>'15','picklist'=>array('','Project','Package','Consumptive'));
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'salesprice'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'salesprice','label'=>'Sales price','quickcreate'=>1,'uitype'=>'71','columntype'=>'N(14.2)','typeofdata'=>'N~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'expected_hours'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'expected_hours','label'=>'Expected hours','quickcreate'=>1,'uitype'=>'7','columntype'=>'N(5.2)','typeofdata'=>'N~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'package_hours'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'package_hours','label'=>'Package hours','quickcreate'=>1,'uitype'=>'7','columntype'=>'N(5.2)','typeofdata'=>'N~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'invoiced_hours'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'invoiced_hours','label'=>'Invoiced hours','quickcreate'=>1,'uitype'=>'7','columntype'=>'N(5.2)','typeofdata'=>'N~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'used_hours'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'used_hours','label'=>'Used hours','quickcreate'=>1,'uitype'=>'7','columntype'=>'N(5.2)','typeofdata'=>'N~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'residual_hours'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'residual_hours','label'=>'Residual hours','quickcreate'=>1,'uitype'=>'7','columntype'=>'N(5.2)','typeofdata'=>'NN~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'used_budget'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'used_budget','label'=>'Used budget','quickcreate'=>1,'uitype'=>'71','columntype'=>'N(14.2)','typeofdata'=>'N~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'residual_budget'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'residual_budget','label'=>'Residual budget','quickcreate'=>1,'uitype'=>'71','columntype'=>'N(14.2)','typeofdata'=>'NN~O');
}
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($ptModuleInstance->id, 'hours_to_be_invoiced'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'ProjectTask','block'=>'LBL_BUDGET_INFORMATION','name'=>'hours_to_be_invoiced','label'=>'Hours to be invoiced','quickcreate'=>1,'uitype'=>'7','columntype'=>'N(5.2)','typeofdata'=>'N~O');
}
$accModuleInstance = Vtecrm_Module::getInstance('Accounts');
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?",array($accModuleInstance->id, 'daily_cost'));
if ($adb->num_rows($result) == 0) {
	$fields[] = array('module'=>'Accounts','block'=>'LBL_ACCOUNT_INFORMATION','name'=>'daily_cost','label'=>'Daily cost','quickcreate'=>1,'uitype'=>'71','columntype'=>'N(14.2)','typeofdata'=>'N~O');
}
if (!empty($fields)) include('modules/SDK/examples/fieldCreate.php');

SDK::setLanguageEntries('ProjectTask', 'Service type', array('it_it'=>'Erogazione servizio','en_us'=>'Service type'));
SDK::setLanguageEntries('ProjectTask', 'Project', array('it_it'=>'Progetto','en_us'=>'Project'));
SDK::setLanguageEntries('ProjectTask', 'Package', array('it_it'=>'Pacchetto','en_us'=>'Package'));
SDK::setLanguageEntries('ProjectTask', 'Consumptive', array('it_it'=>'Consuntivo','en_us'=>'Consumptive'));
SDK::setLanguageEntries('ProjectTask', 'Sales price', array('it_it'=>'Prezzo di vendita','en_us'=>'Sales price'));
SDK::setLanguageEntries('ProjectTask', 'Expected hours', array('it_it'=>'Ore previste','en_us'=>'Expected hours'));
SDK::setLanguageEntries('ProjectTask', 'Package hours', array('it_it'=>'Ore pacchetto','en_us'=>'Package hours'));
SDK::setLanguageEntries('ProjectTask', 'Invoiced hours', array('it_it'=>'Ore fatturate','en_us'=>'Invoiced hours'));
SDK::setLanguageEntries('ProjectTask', 'Used hours', array('it_it'=>'Ore usate','en_us'=>'Used hours'));
SDK::setLanguageEntries('ProjectTask', 'Residual hours', array('it_it'=>'Ore residue','en_us'=>'Residual hours'));
SDK::setLanguageEntries('ProjectTask', 'Used budget', array('it_it'=>'Budget usato','en_us'=>'Used budget'));
SDK::setLanguageEntries('ProjectTask', 'Residual budget', array('it_it'=>'Budget residuo','en_us'=>'Residual budget'));
SDK::setLanguageEntries('ProjectTask', 'Hours to be invoiced', array('it_it'=>'Ore da fatturare','en_us'=>'Hours to be invoiced'));
SDK::setLanguageEntries('Accounts', 'Daily cost', array('it_it'=>'Prezzo a giornata','en_us'=>'Daily cost'));

SDK::setProcessMakerTaskCondition('vte_get_projecttask_usage_percent', 'modules/SDK/src/ProcessMaker/Utils.php', 'Project Task usage %');

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
$PMUtils = ProcessMakerUtils::getInstance();
$result = $adb->pquery("select id from {$table_prefix}_processmaker where name = ?", array('70% hours exceeded'));
if ($adb->num_rows($result) == 0) {
	$PMUtils->importFile('modules/SDK/src/ProcessMaker/vtebpmn/70_hours_exceeded.vtebpmn',true);
}
$result = $adb->pquery("select id from {$table_prefix}_processmaker where name = ?", array('90% hours exceeded'));
if ($adb->num_rows($result) == 0) {
	$PMUtils->importFile('modules/SDK/src/ProcessMaker/vtebpmn/90_hours_exceeded.vtebpmn',true);
}
<?php 

/* crmv@205899 */

global $adb, $table_prefix;

$moduleInstance = Vtecrm_Module::getInstance('ProjectTask');
$serviceTypeField = Vtecrm_Field::getInstance('servicetype', $moduleInstance);
if ($serviceTypeField !== false) {
	$checkPicklist = $adb->pquery("SELECT 1 FROM {$table_prefix}_picklist WHERE name = ?", ['servicetype']);
	if (!!$checkPicklist) {
		if (intval($adb->num_rows($checkPicklist)) === 0) {
			$picklistId = $serviceTypeField->__getPicklistUniqueId();
			$adb->pquery("INSERT INTO {$table_prefix}_picklist (picklistid, name) VALUES (?, ?)", [$picklistId, 'servicetype']);
		}
	}
	$checkValues = $adb->query("SELECT 1 FROM {$table_prefix}_servicetype");
	if (!!$checkValues) {
		if (intval($adb->num_rows($checkValues)) === 0) {
			$serviceTypeField->setPicklistValues(['', 'Project', 'Package', 'Consumptive']);
		}
	}
}

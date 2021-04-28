<?php
// crmv@201190
SDK::setLanguageEntries('Messages', 'LBL_MASS_MOVE_MULTIPLE_ACCOUNT_ERR', array('it_it'=>'Non è possibile spostare messaggi di account diversi','en_us'=>'It is not possible to move messages from different accounts'));

// crmv@201101
SDK::deleteLanguageEntry('ProjectPlan', NULL, 'JobOrder');
SDK::setLanguageEntries('ProjectPlan', 'Job Order', array('it_it'=>'Commessa', 'en_us'=>'Job Order'));

$ppModuleInstance = Vtiger_Module::getInstance('ProjectPlan');
$joModuleInstance = Vtiger_Module::getInstance('JobOrder');
$result = $adb->pquery("select * from {$table_prefix}_relatedlists where tabid = ? and related_tabid = ?", array($joModuleInstance->id,$ppModuleInstance->id));
if ($adb->num_rows($result) == 0) {
	$joModuleInstance->setRelatedList($ppModuleInstance,'Project Plans',array('add','select'),'get_dependents_list');
} elseif ($adb->num_rows($result) == 2) {
	$adb->pquery("delete from {$table_prefix}_relatedlists where tabid = ? and related_tabid = ? and label = ?", array($joModuleInstance->id,$ppModuleInstance->id,'ProjectPlan'));
}
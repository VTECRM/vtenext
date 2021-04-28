<?php
global $adb, $table_prefix;
$moduleInstanceD = Vtiger_Module::getInstance('Documents');
$moduleInstanceE = Vtiger_Module::getInstance('Employees');
$result = $adb->pquery("select relation_id from {$table_prefix}_relatedlists where tabid = ? and related_tabid = ? and name = ?", array($moduleInstanceD->id,$moduleInstanceE->id,'get_documents_dependents_list'));
if ($result && $adb->num_rows($result) == 0) {
	$moduleInstanceD->setRelatedList($moduleInstanceE,'Employees',array('select','add'),'get_documents_dependents_list');
}
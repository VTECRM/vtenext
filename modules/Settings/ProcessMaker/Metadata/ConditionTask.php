<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 */

global $adb, $table_prefix;

if ($PMUtils->isStartTask($id,$elementid)) {
	// crmv@200009	
	$modules_array = $PMUtils->getModuleList('picklist',$vte_metadata_arr['moduleName']);
	$modules_array['Documents'] = array(getSingleModuleName('Documents'),'');
	
	$unsuppMods = $PMUtils->modules_not_supported;
	// allow documents for relations
	if (($k = array_search('Documents', $unsuppMods)) !== false) {
		unset($unsuppMods[$k]);
		$unsuppMods = array_values($unsuppMods);
	}

	$RM = RelationManager::getInstance();
	foreach($modules_array as $key => $value){
		$relationsNN = $RM->getRelations($key, ModuleRelation::$TYPE_NTON, [], $unsuppMods);
			
		$rel_array2 = array();
		foreach ($relationsNN as $relobj) {
			$rel_array2[] =$relobj->getSecondModule();
		}
		$rel_array[$key] = $rel_array2;
	}
	$modules_array2 = $PMUtils->getModuleList('picklist', $vte_metadata_arr['moduleName1']);

	$smarty->assign("moduleNames", $modules_array);
	$smarty->assign("moduleNames1", $modules_array2);
	$smarty->assign("rel_array", $rel_array);
	$smarty->assign('IS_START_TASK',true);
	// crmv@200009e
} else {
	$modules = $PMUtils->getRecordsInvolvedOptions($id, $vte_metadata_arr['moduleName']);
	//crmv@96450
	require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
	$processDynaFormObj = ProcessDynaForm::getInstance();
	$dynaforms = $processDynaFormObj->getOptions($id, $vte_metadata_arr['moduleName']);
	if (!empty($dynaforms)) $modules = array_merge($modules,$dynaforms);
	//crmv@96450e
    
	$smarty->assign("moduleNames", $modules);
}

$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerTaskConditions());

if ($PMUtils->showRunProcessesButton('Settings')) $smarty->assign('ENABLE_MANUAL_MODE',true);	//crmv@100495
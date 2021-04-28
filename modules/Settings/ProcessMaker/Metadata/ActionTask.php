<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@102879 crmv@106857 */

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
$PDynaForm = ProcessDynaForm::getInstance();

$actionTypes = $PMUtils->getActionTypes();

if ($actionTypes['Cycle']) {
	// crmv@195745
	// check if there are table fields
	$tableFields = $PMUtils->getAllTableFields($id);
	$dFtableFields = $PDynaForm->getAllTableFields($id);
	$tableFields = array_replace_recursive($tableFields, $dFtableFields);
	if (is_array($tableFields) && count($tableFields) > 0) {
		$smarty->assign("tableFields", $tableFields);
	}
	
	// retrieve product block fields
	$pblockFields = $PMUtils->getAllProductsBlocks($id);
	if (is_array($pblockFields) && count($pblockFields) > 0) {
		$smarty->assign("pblockFields", $pblockFields);
	}
	// all special fields with several rows
	$rowFields = array_replace_recursive($tableFields, $pblockFields);
	
	if (is_array($rowFields) && count($rowFields) > 0) {
		$smarty->assign("rowFields", $rowFields);
		$cycleActionTypes = array();
		foreach($actionTypes['Cycle']['actions'] as $a) {
			$cycleActionTypes[$a] = $actionTypes[$a];
		}
		$smarty->assign("cycleActionTypes", $cycleActionTypes);
	} else {
		unset($actionTypes['InsertTableRow']);
	}
	if (empty($pblockFields)) {
		unset($actionTypes['InsertProductRow']);
		unset($actionTypes['CloneProductsBlock']);
	}
	if (empty($tableFields) && empty($pblockFields)) unset($actionTypes['Cycle']);
	// crmv@195745e
}
//crmv@203075
if ($actionTypes['CycleRelated']) {
    // crmv@195745
    // check if there are table fields

    $tableFields = $PMUtils->getAllRelatedModulesForCycle($id);
    if (is_array($tableFields) && count($tableFields) > 0) {
        $smarty->assign("tableFieldsRelated", $tableFields);
    }

    // all special fields with several rows
    $rowFields = $tableFields;

    if (is_array($rowFields) && count($rowFields) > 0) {
        $smarty->assign("rowFieldsRelated", $rowFields);
        $cycleActionTypes = array();
        foreach($actionTypes['CycleRelated']['actions'] as $a) {
            $cycleActionTypes[$a] = $actionTypes[$a];
        }
        $smarty->assign("cycleActionTypesRelated", $cycleActionTypes);
    } else {
        unset($actionTypes['InsertTableRow']);
    }
    if (empty($tableFields)) unset($actionTypes['CycleRelated']);
    // crmv@195745e
}
//crmv@203075e
//crmv@164486 only one CallExtWS in a ScriptTask
if (!empty($vte_metadata_arr['actions'])) {
	foreach($vte_metadata_arr['actions'] as $vte_metadata_action) {
		if ($vte_metadata_action['action_type'] == 'CallExtWS') {
			unset($actionTypes['CallExtWS']);
			break;
		}
	}
}
//crmv@164486e
$smarty->assign("actionTypes", $actionTypes);

$_REQUEST['enable_editoptions'] = 'yes';
$_REQUEST['editoptionsfieldnames'] = implode('|',array('assigned_user_id','related_to','process_status','process_name','description'));	//crmv@109685 crmv@160843
$_REQUEST['assigned_user_id'] = $helper_arr['assigned_user_id'];
if (isset($helper_arr['sdk_params_assigned_user_id'])) $_REQUEST['sdk_params_assigned_user_id'] = $helper_arr['sdk_params_assigned_user_id'];	//crmv@113527

$smarty->assign('PMH_RELATEDTO', getOutputHtml(10, 'related_to', 'LBL_PMH_RELATEDTO', 100, array('related_to'=>$helper_arr['related_to']),1,'Settings','',1,'I~M')); //crmv@160843
$smarty->assign('PMH_ASSIGNEDTO', getOutputHtml(53, 'assigned_user_id', 'LBL_ASSIGNED_TO', 100, array('assigned_user_id'=>$helper_arr['assigned_user_id']),1,'Settings','',1,'I~M'));
//crmv@103450
if (empty($helper_arr['process_status'])) {
	$helper_arr['process_status'] = $PMUtils->getProcessHelperDefault($id,$elementid,$type);
}
$smarty->assign('PMH_STATUS', getOutputHtml(15, 'process_status', 'Status', 100, array('process_status'=>$helper_arr['process_status']),1,'Processes','',1,'V~O'));
//crmv@103450e

$tmp_helper = $helper_arr;
unset($tmp_helper['dynaform']);
//crmv@109685
$tmp_helper = addslashes(Zend_Json::encode($tmp_helper));
$smarty->assign('JSON_HELPER_ARR',$tmp_helper);
//crmv@109685e

$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());

$involvedRecords = $PMUtils->getRecordsInvolved($id,true);
$smarty->assign('JSON_INVOLVED_RECORDS',Zend_Json::encode($involvedRecords));

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
$processDynaFormObj = ProcessDynaForm::getInstance();
$dynaFormOptions = $processDynaFormObj->getFieldsOptions($id,true);
$smarty->assign('JSON_DYNAFORM_OPTIONS',Zend_Json::encode($dynaFormOptions));

//crmv@100591
$elementsActors = $PMUtils->getElementsActors($id);
$smarty->assign('JSON_ELEMENTS_ACTORS',Zend_Json::encode($elementsActors));
//crmv@100591e

//crmv@106856
//crmv@160843 codes removed
$PMUtils->setAdvancedFieldAssignment('assigned_user_id',$helper_arr['advanced_field_assignment']['assigned_user_id']);
//crmv@106856e
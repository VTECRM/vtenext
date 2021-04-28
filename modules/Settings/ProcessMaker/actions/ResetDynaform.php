<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@105685 crmv@192951 */

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');

class PMActionResetDynaform extends SDKExtendableClass {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		$dynaform_involved = '';
		$empty_fields = 0;
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$dynaform_involved = $metadata_action['dynaform_involved'];
				$empty_fields = intval($metadata_action['empty_fields']);
			}
			$smarty->assign('METADATA', $metadata_action);
		}
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$dynaforms = $processDynaFormObj->getOptions($id, $dynaform_involved);
		$smarty->assign("DYNAFORMS_INVOLVED", $dynaforms);
		$smarty->assign('EMPTY_FIELDS', $empty_fields);
	}
	
	function execute($engine,$actionid) {
		$action = $engine->vte_metadata['actions'][$actionid];
		list($metaid,$module) = explode(':',$action['dynaform_involved']);
		
		$engine->log("Action ResetDynaform","action $actionid - {$action['action_title']}");
		
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$result = $processDynaFormObj->resetDynaForm($metaid, $engine, (isset($action['empty_fields']) && $action['empty_fields'] == 1));
		($result) ? $result = 'SUCCESS' : $result = 'FAILED';
		$engine->log("Action ResetDynaform","action $actionid ".$result);
	}
}
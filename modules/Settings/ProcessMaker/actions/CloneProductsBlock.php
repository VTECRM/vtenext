<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@195745 */

require_once(dirname(__FILE__).'/Base.php');

class PMActionCloneProductsBlock extends PMActionBase {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		$from_record = '';
		$to_record = '';
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$from_record = $metadata_action['from_record'];
				$to_record = $metadata_action['to_record'];
			}
			$smarty->assign('METADATA', $metadata_action);
		}
		$from_record_pick = $PMUtils->getRecordsInvolvedOptions($id, $from_record, false, null, null, true, true);	//crmv@135190
		$smarty->assign("FROM_RECORD", $from_record_pick);
		
		if ($action_id != '') {
			$to_record_pick = $PMUtils->getRecordsInvolvedOptions($id, $to_record, false, null, null, true, true);	//crmv@135190
			$smarty->assign("TO_RECORD", $to_record_pick);
		} else {
			// no need to recalculate the same picklist
			$smarty->assign("TO_RECORD", $from_record_pick);
		}
		
	}
	
	function execute($engine,$actionid) {
	
		$action = $engine->vte_metadata['actions'][$actionid];
		
		// first record
		
		list($metaid1,$module1,$reference1,$meta_processid,$relatedModule1) = explode(':',$action['from_record']);
		$record1 = $engine->getCrmid($metaid1,null,$reference1,$meta_processid);
		
		$recordModule1 = getSalesEntityType($record1);
		if (!empty($relatedModule1)) {
			if ($relatedModule1 != $recordModule1) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['from_record']} not found");
				return;
			}
			$module1 = $relatedModule1;
		} elseif (!empty($reference1)) {
			$module1 = $recordModule1;
		}
		
		if (!isInventoryModule($module1)) {
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['from_record']} is not an inventory record");
			return;
		}
		
		// second record
		
		list($metaid2,$module2,$reference2,$meta_processid,$relatedModule2) = explode(':',$action['to_record']);
		$record2 = $engine->getCrmid($metaid2,null,$reference2,$meta_processid);
		
		$recordModule2 = getSalesEntityType($record2);
		if (!empty($relatedModule2)) {
			if ($relatedModule2 != $recordModule2) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['to_record']} not found");
				return;
			}
			$module2 = $relatedModule2;
		} elseif (!empty($reference2)) {
			$module2 = $recordModule2;
		}
		
		if (!isInventoryModule($module2)) {
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['to_record']} is not an inventory record");
			return;
		}
		
		// and now, the real action
		
		$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} - record1:$record1 record2:$record2");
		
		$IUtils = InventoryUtils::getInstance();
		$IUtils->cloneProductsBlock($module1, $record1, $module2, $record2);
		
	}
	
}
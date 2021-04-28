<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@113775 crmv@115268 crmv@191351 */

class PMActionRelate extends SDKExtendableClass {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		$record1 = '';
		$record2 = '';
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$record1 = $metadata_action['record1'];
				$record2 = $metadata_action['record2'];
			}
			$smarty->assign('METADATA', $metadata_action);
		}
		$record_pick_1 = $PMUtils->getRecordsInvolvedOptions($id, $record1, false, null, null, true);
		$smarty->assign("RECORDPICK1", $record_pick_1);
		if (!empty($record2)) {
			$record_pick_2 = $PMUtils->getRecordsInvolvedOptions($id, $record2, false, false, null, true);
			$smarty->assign("RECORDPICK2", $record_pick_2);
		}
	}
	
	function execute($engine,$actionid) {
		$action = $engine->vte_metadata['actions'][$actionid];
		list($metaid1,$module1,$reference1,$meta_processid1,$relatedModule1) = explode(':',$action['record1']);
		$record1 = $engine->getCrmid($metaid1,null,$reference1,$meta_processid1);
		$recordModule1 = getSalesEntityType($record1);
		if (!empty($relatedModule1)) {
			if ($relatedModule1 != $recordModule1) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['record1']} do not found");
				return;
			}
			$module1 = $relatedModule1;
		} elseif (!empty($reference1)) {
			$module1 = $recordModule1;
		}
		if (empty($record1)) {
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['record1']} do not found");
		} elseif ($record1 !== false) {
			list($metaid2,$module2,$reference2,$meta_processid2,$relatedModule2) = explode(':',$action['record2']);
			$record2 = $engine->getCrmid($metaid2,null,$reference2,$meta_processid2);
			$recordModule2 = getSalesEntityType($record2);
			if (!empty($relatedModule2)) {
				if ($relatedModule2 != $recordModule2) {
					$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['record2']} do not found");
					return;
				}
				$module2 = $relatedModule2;
			} elseif (!empty($reference2)) {
				$module2 = $recordModule2;
			}
			if (empty($record2)) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['record2']} do not found");
			} elseif ($record2 !== false) {
				if ($record1 !== false && $record2 !== false) {
					$record1exists = isRecordExists($record1);
					$record2exists = isRecordExists($record2);
					if (!$record1exists) {
						$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record1:$record1 deleted");
						return false;
					} elseif (!$record2exists) {
						$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record2:$record2 deleted");
						return false;
					}
					$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} - record1:$record1 record2:$record2");
					
					// invert order of modules and records
					if ($module2 == 'MyNotes') {
						$module2 = $module1;
						$module1 = 'MyNotes';
						$tmp_record1 = $record1;
						$record1 = $record2;
						$record2 = $tmp_record1;
					}
					
					$relationManager = RelationManager::getInstance();
					$relationManager->relate($module1, $record1, $module2, $record2);
					
					$engine->logElement($engine->elementid, array(
						'action_type'=>$action['action_type'],
						'action_title'=>$action['action_title'],
						'metaid1'=>$metaid1,
						'crmid1'=>$record1,
						'module1'=>$module1,
						'metaid2'=>$metaid2,
						'crmid2'=>$record2,
						'module2'=>$module2,
					));
				}
			}
		}
	}
}
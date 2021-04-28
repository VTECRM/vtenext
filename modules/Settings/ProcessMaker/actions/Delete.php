<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 */

class PMActionDelete extends SDKExtendableClass {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		$record_involved = '';
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$record_involved = $metadata_action['record_involved'];
				$record_to_load = $metadata_action['record_to_load']; // crmv@200816
			}
			$smarty->assign('METADATA', $metadata_action);
		}
		$records_pick = $PMUtils->getRecordsInvolvedOptions($id, $record_involved, false, null, null, true); // crmv@192142
		$smarty->assign("RECORDS_INVOLVED", $records_pick);
		// crmv@200816
		$records_pick = $PMUtils->getRecordsInvolvedOptions($id, $record_to_load, false, null, null, true);
		$smarty->assign("RECORD_TO_LOAD", $records_pick);
		// crmv@200816e
	}
	
	function execute($engine,$actionid) {
		$action = $engine->vte_metadata['actions'][$actionid];
		// crmv@192142
		list($metaid,$module,$reference,$meta_processid,$relatedModule) = explode(':',$action['record_involved']);
		$record = $engine->getCrmid($metaid,null,$reference,$meta_processid);
		$recordModule = getSalesEntityType($record);
		if (!empty($relatedModule)) {
			if ($relatedModule != $recordModule) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['record_involved']} do not found");
				return;
			}
			$module = $relatedModule;
		} elseif (!empty($reference)) {
			$module = $recordModule;
		}
		if (empty($record)) {
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record {$action['record_involved']} do not found");
		} elseif ($record !== false) {
			// crmv@192142e
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']}");
			$focus = CRMEntity::getInstance($module);
			$focus->trash($module, $record);
			//crmv@112539
			$engine->logElement($engine->elementid, array(
				'action_type'=>"{$action['action_type']}",
				'action_title'=>$action['action_title'],
				'metaid'=>$metaid,
				'crmid'=>$record,
				'module'=>$module,
			));
			//crmv@112539e
			// crmv@200816
			list($r2l_metaid,$r2l_module,$r2l_reference,$r2l_meta_processid,$r2l_relatedModule) = explode(':',$action['record_to_load']);
			$r2l_record = $engine->getCrmid($r2l_metaid,null,$r2l_reference,$r2l_meta_processid);
			if (!empty($r2l_record)) {
				$r2l_recordModule = getSalesEntityType($r2l_record);
				
				// for popup reload
				VteSession::set('PM_DELETED_RECORD',$r2l_record);
				
				// for redirect to detailview
				if (isset($_REQUEST['return_id']) && $_REQUEST['return_id'] == $record) {
					$_REQUEST['return_module'] = getSalesEntityType($r2l_record);
					$_REQUEST['return_id'] = $r2l_record;
					$_REQUEST['return_viewname'] = '';
					$_REQUEST['pagenumber'] = '';
					$_REQUEST['search_url'] = '';
					$_REQUEST['module'] = ''; // this is for empty from_module
				}
			}
			// crmv@200816e
		}
	}
}
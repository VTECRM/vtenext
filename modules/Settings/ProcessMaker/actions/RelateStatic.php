<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@126184 crmv@191351 */

class PMActionRelateStatic extends SDKExtendableClass {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		$record1 = '';
		$module2 = '';
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$record1 = $metadata_action['record1'];
				$module2 = $metadata_action['record2'];
			}
			$smarty->assign('METADATA', $metadata_action);
		}
		$record_pick_1 = $PMUtils->getRecordsInvolvedOptions($id, $record1, false, null, null, true);
		$smarty->assign("RECORDPICK1", $record_pick_1);
		
		$smarty->assign("STATICRECORD", '1');
		
		if (!empty($module2)) {
			
			list($metaid1,$module1) = explode(':',$record1);
			
			$RM = RelationManager::getInstance();
			$relations = $RM->getRelations($module1, ModuleRelation::$TYPE_NTON, array(), $PMUtils->modules_excluded_link);
			
			$values = array(''=>array(getTranslatedString('LBL_PLEASE_SELECT'),''));
			foreach ($relations as $rel) {
				$relmod = $rel->getSecondModule();
				$selected = ($relmod == $module2 ? 'selected=""' : '');
				$values[$relmod] = array(getTranslatedString($relmod, $relmod), $selected);
			}
			
			$smarty->assign("RECORDPICK2", $values);
			
			$list = $metadata_action['sel_static_records'];
			$list2 = array_filter(explode(',', $list));
			$smarty->assign("SELRECORDS", $list);
			$this->renderRelated($smarty, $module1, $module2, $list2);
		}
	}
	
	function renderRelated(&$smarty, $module1, $module2, $list) {
		global $current_user, $table_prefix;
		
		$other = CRMEntity::getInstance($module2);
		
		$jsonExtra = Zend_Json::encode(array(
			'show_module' => $module2,
			'modules_list' => $module2,
			'relation_id' => 0,
			'callback_link' => 'parent.ActionTaskScript.addStaticRelatedRecord',
			'callback_addselected' => 'parent.ActionTaskScript.addStaticRelatedRecords',
			'callback_cancel' => 'parent.ActionTaskScript.quickClosePopup',
			//crmv@153819
			'check_action_permissions' => 'false',
			'force_show_module' => 'true',
			//crmv@153819e
		));
		$jsonExtra = ListViewUtils::encodeForHtmlAttr($jsonExtra, '"');
		$buttons = "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($module2). "' class='crmbutton small edit' type='button' ".
					"onclick=\"LPOP.openPopup('$module1', '', '', $jsonExtra)\" ".
					"value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($module2,$module2) ."'>&nbsp;"; //crmv@21048m
		
		// generate query
		$listWhere = '';
		if (count($list) > 0) {
			$listWhere = $table_prefix.'_crmentity.crmid IN ('.implode(',',$list).')';
		} else {
			$listWhere = '1 = 0';
		}
		
		$queryGenerator = QueryGenerator::getInstance($module2, $current_user);
		$queryGenerator->initForAllCustomView();
		$query = $queryGenerator->getQuery();
		$query .= " AND $listWhere";
		
		global $currentModule;
		$currentModule = $module1;
		$return_value = GetRelatedList($module1, $module2, $other, $query, $buttons, $returnset);
		$currentModule = 'Settings';
		
		// modify header
		if (is_array($return_value['header'])) {
			foreach ($return_value['header'] as $k => $header) {
				$return_value['header'][$k] = trim(preg_replace('/arrow_drop.*/', '', strip_tags($header)));
			}
		}
		
		// modify entries
		if (is_array($return_value['entries'])) {
			foreach ($return_value['entries'] as $crmid => $entry) {
				if (is_array($entry)) {
					foreach ($entry as $k => $col) {
						// set the target for the links
						if ($k == 0 && strpos($col, 'vteicon') !== false) {
							$col = '<i class="vteicon md-link" data-crmid="'.$crmid.'" onclick="ActionTaskScript.removeStaticLinkedRecord(this)">clear</i>';
							$return_value['entries'][$crmid][$k] = $col;
						} elseif (preg_match('/<a.*?href=/i', $col)) {
							$col = preg_replace('/target=[\'"].*?[\'"]/i', '', $col);
							$col = preg_replace('/<a/i', '<a target="_blank"', $col);
							$return_value['entries'][$crmid][$k] = $col;
						}
					}
				}
			}
		}
		
		// remove navigation
		if (is_array($return_value['navigation'])) {
			$return_value['navigation'] = array();
		}
		
		$smarty->assign('SELRECORDS', implode(',',$list));
		$smarty->assign('RELATEDLISTDATA', $return_value);
		$smarty->assign('BUTTONS', $buttons);
		
		return $smarty;
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
			$module2 = $action['record2'];
			$list = $action['sel_static_records'];
			$list = array_filter(explode(',', $list));
			if ($record1 && $module2 && count($list) > 0) {
				$record1exists = isRecordExists($record1);
				$module2Active = vtlib_isModuleActive($module2);
				if (!$record1exists) {
					$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED record1:$record1 deleted");
					return false;
				} elseif (!$module2Active) {
					$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED module:$module2 not active");
					return false;
				}
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} - record1:$record1 module2:$module2 list:".implode(',',$list));
				
				$relationManager = RelationManager::getInstance();
				foreach ($list as $record2) {
					// invert order of modules and records
					if ($module2 == 'MyNotes') {
						$module2 = $module1;
						$module1 = 'MyNotes';
						$tmp_record1 = $record1;
						$record1 = $record2;
						$record2 = $tmp_record1;
					}
					$relationManager->relate($module1, $record1, $module2, $record2);
				}
				
				$engine->logElement($engine->elementid, array(
					'action_type'=>$action['action_type'],
					'action_title'=>$action['action_title'],
					'metaid1'=>$metaid1,
					'crmid1'=>$record1,
					'module1'=>$module1,
					'module2'=>$module2,
					'list'=>$list
				));
			}
		}
	}
}
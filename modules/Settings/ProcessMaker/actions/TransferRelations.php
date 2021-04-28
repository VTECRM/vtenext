<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@185548 */

class PMActionTransferRelations extends SDKExtendableClass {

	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		global $table_prefix;
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$record1 = '';
		$record2 = '';
		$show_pick2 = false;
		$record_involved = '';
		$mode = 'create';
		
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$record1 = $metadata_action['record1'];
				$record2 = $metadata_action['record2'];
				$record_involved = $metadata_action['record_involved'];
			}
			$smarty->assign('METADATA', $metadata_action);
		}

		$record_pick_1 = $PMUtils->getRecordsInvolvedOptions($id, $record_involved, false, null, null, true);
		
		if (!empty($record2)) {
			$record_pick_2 = $PMUtils->getRecordsInvolvedOptions($id, $record2, false, null, null, true);
		}
		else{
			$records_involved = $record_pick_1;
			foreach($records_involved as $single_module__involved => $single_record__involved){
				if($single_record__involved[1] == 'selected'){
					unset($records_involved[$single_module__involved]);
				}
			}
			$record_pick_2 = $records_involved;
		}
		
		//search if a module1 is selected
		foreach($record_pick_1 as $module_list1 => $current_list1){
			foreach($current_list1 as $current_module1 => $current_value1){
				if(isset($current_value1[$record1])){
					$record_pick_1[$module_list1][$current_module1][$record1][1] = 'selected';
					$mode = 'edit';
					break;
				}
			}
		}
		
		//search if a module2 is selected
		foreach($record_pick_2 as $module_list2 => $current_list2){
			foreach($current_list2 as $current_module2 => $current_value2){
				if($current_value2 == 'selected'){
					$mode = 'edit';
					break;
				}
			}
		}

		if (empty($metadata_action['modules'])) {
			list($metaid1,$module1,$reference1) = explode(':',$record1);
			list($metaid2,$module2,$reference2) = explode(':',$record2);
			
			if(!empty($reference1)){
				$module1 = getSingleFieldValue($table_prefix.'_fieldmodulerel', 'relmodule', 'fieldid', $reference1);
			}
			if(!empty($reference2)){
				$module2 = getSingleFieldValue($table_prefix.'_fieldmodulerel', 'relmodule', 'fieldid', $reference2);
			}
			
			$RM = RelationManager::getInstance();
			$related_modules1 = $RM->getRelations($module1);
			$related_modules2 = $RM->getRelations($module2);
			
			$PMUtils = ProcessMakerUtils::getInstance();
			
			$rel_modules1 = $PMUtils->get_all_related_modules($related_modules1);
			$rel_modules2 = $PMUtils->get_all_related_modules($related_modules2);
			
			$rel_modules = array_intersect($rel_modules1, $rel_modules2);	
			$rel_modules = array_unique($rel_modules);	
			
			foreach($rel_modules as $related_modules => $module){
				$modules_list[] = $module;
			}
			$metadata_action['modules'] = Zend_Json::encode($modules_list);
		
		}

		$rel_modules_list = Zend_Json::decode($metadata_action['modules']);
		$vte_metadata[$elementid]['actions'][$action_id]['modules'] = $rel_modules_list;
		if(!empty($rel_modules_list)){
			foreach($rel_modules_list as $module_index => $module){
				if($module == $module1 || $module == $module2){
					unset($rel_modules_list[$module_index]); //unset 1st or 2nd module if is in array
				}
			}
		
			foreach($metadata_action as $parameters => $value){
				if(in_array($parameters, $rel_modules_list)){
					if($value === 'on'){
						$selected_modules[] = $parameters;
					}
				}
			}
		}
		
		
		if($mode == 'edit'){
			$show_pick2 = true;
			$show_list = true;
		}
		else{
			$show_pick2 = false;
			$show_list = false;
		}		

		$smarty->assign("RECORDPICK1", $record_pick_1);
		$smarty->assign("RECORDPICK2", $record_pick_2);
		$smarty->assign("MODE", $mode);
		$smarty->assign("RELOAD", false);
		$smarty->assign("SHOWPICK2", $show_pick2);
		$smarty->assign("SHOW_LIST", $show_list);
		$smarty->assign("MODULES_LIST", $rel_modules_list);
		$smarty->assign("SELECTED_MODULES_LIST", $selected_modules);
	}
	
	function execute($engine,$actionid) {
		global $adb,$table_prefix;
		
		$action = $engine->vte_metadata['actions'][$actionid];		
		list($metaid1,$module1,$reference1,,$referenceModule1) = explode(':',$action['record1']);
		list($metaid2,$module2,$reference2,,$referenceModule2) = explode(':',$action['record2']);
		
		$record1 = $engine->getCrmid($metaid1, null, $reference1);
		$record2 = $engine->getCrmid($metaid2, null, $reference2);
		$module1 = getSalesEntityType($record1);
		$module2 = getSalesEntityType($record2);
		
		if ($record1 !== false && $record2 !== false) {
			$record1exists = isRecordExists($record1);
			$record2exists = isRecordExists($record2);
			if (!$record1exists) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED: record1:$record1 deleted");
				return false;
			} elseif (!$record2exists) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED: record2:$record2 deleted");
				return false;
			} elseif(!empty($referenceModule1) && $referenceModule1 != $module1) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED: record1:$record1 is $module1 instead of $referenceModule1");
				return false;
			} elseif(!empty($referenceModule2) && $referenceModule2 != $module2) {
				$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED: record2:$record2 is $module2 instead of $referenceModule2");
				return false;
			}
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} - record1:$record1 record2:$record2");
			$focus = CRMEntity::getInstance($module1);
			$focus->retrieve_entity_info_no_html($record1, $module1);
			$RM = RelationManager::getInstance();
			$related_modules1 = $RM->getRelations($module1);
			$related_modules2 = $RM->getRelations($module2);
			
			$PMUtils = ProcessMakerUtils::getInstance();
			
			$rel_modules1 = $PMUtils->get_all_related_modules($related_modules1);
			$rel_modules2 = $PMUtils->get_all_related_modules($related_modules2);
			
			$rel_modules = array_intersect($rel_modules1, $rel_modules2);
			$rel_modules = array_unique($rel_modules);
			
			foreach($action as $parameters => $value){
				if(in_array($parameters, $rel_modules)){
					if($value === 'on'){
						$selected_modules[] = $parameters;
					}
				}
			}
			
			$selected_modules_list = implode(",", $selected_modules);
			//$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} - modules: {$selected_modules_list}");
			
			foreach($selected_modules as $modules){
				if($modules == 'Events' || $modules == 'Calendar'){
					$query = "
						SELECT activityid FROM {$table_prefix}_activity
						WHERE activityid IN
						  (SELECT activityid FROM {$table_prefix}_seactivityrel WHERE crmid = ?) 
						  OR activityid IN (SELECT activityid FROM {$table_prefix}_cntactivityrel WHERE contactid = ?)";
						
					if($modules == 'Events'){
						$query.= " AND activitytype <> 'Task'";
					}
					elseif($modules == 'Calendar'){
						$query.= " AND activitytype = 'Task'";
					}
						
					$res = $adb->pquery($query, array($record1, $record1));
					
					if($res && $adb->num_rows($res) > 0){
						while($row = $adb->fetchByAssoc($res, -1, false)){
							$activityid = $row['activityid'];
							
							if(($module1 == 'Contacts' && $module2 != 'Contacts') || ($module1 != 'Contacts' && $module2 == 'Contacts')){
								if($module2 == 'Contacts'){
									// crmv@188844
									if ($adb->isMysql()) {
										$adb->pquery("INSERT IGNORE INTO {$table_prefix}_cntactivityrel VALUES(?,?)", array($record2, $activityid));
									} else {
										$res_check = $adb->pquery("SELECT * FROM {$table_prefix}_cntactivityrel WHERE contactid = ? AND activityid = ?", array($record2, $activityid));
										if ($adb->num_rows($res_check) == 0) $adb->pquery("INSERT INTO {$table_prefix}_cntactivityrel VALUES(?,?)", array($record2, $activityid));
									}
									// crmv@188844e
								}
								else{
									// crmv@188844
									if ($adb->isMysql()) {
										$adb->pquery("INSERT IGNORE INTO {$table_prefix}_seactivityrel VALUES(?,?)", array($record2, $activityid));
									} else {
										$res_check = $adb->pquery("SELECT * FROM {$table_prefix}_seactivityrel WHERE crmid = ? AND activityid = ?", array($record2, $activityid));
										if ($adb->num_rows($res_check) == 0) $adb->pquery("INSERT INTO {$table_prefix}_seactivityrel VALUES(?,?)", array($record2, $activityid));
									}
									// crmv@188844e
								}
							}
							else{
								if($module1 == 'Contacts' && $module2 == 'Contacts'){
									//update compiti, insert eventi
									if($modules == 'Calendar'){
										$adb->pquery("UPDATE {$table_prefix}_cntactivityrel SET contactid = ? WHERE activityid = ?", array($record2, $activityid));
									}
									elseif($modules == 'Events'){
										// crmv@188844
										if ($adb->isMysql()) {
											$adb->pquery("INSERT IGNORE INTO {$table_prefix}_cntactivityrel VALUES(?,?)", array($record2, $activityid));
										} else {
											$res_check = $adb->pquery("SELECT * FROM {$table_prefix}_cntactivityrel WHERE contactid = ? AND activityid = ?", array($record2, $activityid));
											if ($adb->num_rows($res_check) == 0) $adb->pquery("INSERT INTO {$table_prefix}_cntactivityrel VALUES(?,?)", array($record2, $activityid));
										}
										// crmv@188844e
									}
								}
								elseif($module1 != 'Contacts' && $module2 != 'Contacts'){
									//update compiti, update eventi
									if($modules == 'Calendar'){
										$adb->pquery("UPDATE {$table_prefix}_seactivityrel SET contactid = ? WHERE activityid = ?", array($record2, $activityid));
									}
									elseif($modules == 'Events'){
										$adb->pquery("UPDATE {$table_prefix}_seactivityrel SET crmid = ? WHERE activityid = ?", array($record2, $activityid));
									}
								}
							}
						}
					}
				// crmv@202102					
				} elseif ($modules == 'Messages') {
					$messages_query = "SELECT messagesid
						FROM {$table_prefix}_messages
						INNER JOIN {$table_prefix}_messagesrel ON {$table_prefix}_messages.messagehash = {$table_prefix}_messagesrel.messagehash
						WHERE {$table_prefix}_messagesrel.crmid = ? AND {$table_prefix}_messages.mvisibility = ?";
					$messages_res = $adb->pquery($messages_query,array($record1,'Public'));
					
					if($messages_res && $adb->num_rows($messages_res) > 0){
						while($message_row = $adb->fetchByAssoc($messages_res, -1, false)){
							$messageid = $message_row['messagesid'];
							$adb->pquery("UPDATE {$table_prefix}_messagesrel
								INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagehash = {$table_prefix}_messagesrel.messagehash
								SET {$table_prefix}_messagesrel.crmid = ?
								WHERE {$table_prefix}_messagesrel.crmid = ?
								AND {$table_prefix}_messages.mvisibility = ?
								AND {$table_prefix}_messages.messagesid = ?",
							array($record2,$record1,'Public',$messageid));
						}
					}
				} else {				
				// crmv@202102e
					$related_ids = $RM->getRelatedIds($module1, $record1, array($modules));
					foreach($related_ids as $related_crmid){						
						//$engine->log("{$related_crmid}");
						$RM->relate($module2, $record2, $modules, $related_crmid); //relate record1 and record2
					}
				}
			}
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} SUCCESS");
			
		} else {
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED: record1 or record2 is empty");
			return false;
		}
	}
}
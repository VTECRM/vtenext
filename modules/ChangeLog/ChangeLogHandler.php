<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@82326 crmv@104566 crmv@164120 crmv@164655 */

require_once('modules/ChangeLog/ChangeLog.php');

class ChangeLogHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $adb, $table_prefix;
		global $current_user,$current_auth_record,$record_init,$record_last,$currentModule;
		
		if (!vtlib_isModuleActive('ChangeLog')) return;
		
		$currentModule_tmp = $currentModule;
		$currentModule = 'ChangeLog';
		
		$moduleName = $entityData->getModuleName();
		$entityData->date = date('Y-m-d H:i:s');
		$id = $entityData->getId();
		$obj = ChangeLog::getInstance();
		
		if($moduleName == 'Activity'){
			$moduleName = 'Calendar';
		}
		
		if ($obj->isEnabled($moduleName) && isRecordExists($id)) { //crmv@47905 crmv@115268
			if($moduleName == 'Calendar'){
				if($_REQUEST['activity_mode'] == 'Events'){
					$moduleName = 'Events';
				}
			}
			
			if ($entityData->isNew()) {
				if ($eventName == 'history_last') {
					$proj = CRMEntity::getInstance($moduleName);
					$proj->retrieve_entity_info_no_html($id,$moduleName);
					
					$obj->column_fields['modified_date'] = $proj->column_fields['createdtime'];
					$obj->column_fields['audit_no'] = $obj->get_revision_id($id);
					$obj->column_fields['user_id'] = $current_user->id;
					$obj->column_fields['parent_id'] = $id;
					$obj->column_fields['user_name'] = $current_user->column_fields['user_name'];
					$obj->column_fields['description'] = Zend_Json::encode(array('ChangeLogCreation'));
					$obj->column_fields['request_id'] = RequestHandler::getId(); // crmv@177677
					if (!empty($current_auth_record)) {
						$obj->column_fields['display_id'] = $current_auth_record['id'];
						$obj->column_fields['display_module'] = $current_auth_record['module'];
						$obj->column_fields['display_name'] = getEntityName($current_auth_record['module'],$current_auth_record['id'],true);
					}
					$obj->save(); //crmv@47905
				}
			} else {
				if($eventName == 'history_first' && empty($record_init[$moduleName][$id]) && empty($record_last[$moduleName][$id])) { //se il record � modificato
					$proj = CRMEntity::getInstance($moduleName);
					$proj->overrideTimezone('default'); // crmv@163361
					$proj->mode = 'edit';
					$proj->id = $id;
					$proj->retrieve_entity_info_no_html($id,$moduleName);
					$record_init[$moduleName][$id] = $proj->column_fields; //scrivo la column_fields attuale in json nel campo description
				}
				elseif($eventName == 'history_last' && !empty($record_init[$moduleName][$id])){
					$data = array_filter($entityData->getData());
					$data_encoded = Zend_Json::encode($data);
					$id = $entityData->getId();

					$nr_rev = $obj->get_revision_id($id);	//crmv@103534
					$obj->column_fields['audit_no'] = $nr_rev;//versione record calcolata
					$obj->column_fields['user_id'] = $current_user->id; //utente corrente
					$obj->column_fields['parent_id'] = $id; //id entit� collegata
					$obj->column_fields['request_id'] = RequestHandler::getId(); // crmv@177677
					$proj = CRMEntity::getInstance($moduleName);
					$proj->overrideTimezone('default'); // crmv@163361
					$proj->mode = 'edit';
					$proj->id = $id;
					$proj->retrieve_entity_info_no_html($id,$moduleName);
					$record_last[$moduleName][$id] = $proj->column_fields;
					$result = array_diff_assoc($record_init[$moduleName][$id], $record_last[$moduleName][$id]);
					$final_record = Array();
					$campi = array();
					$obj->column_fields['user_name'] = $current_user->column_fields['user_name'];
					
					// TODO: cache field info
					$q = "SELECT fieldname, fieldlabel, fieldtype, readonly, {$table_prefix}_field.uitype 
						FROM ".$table_prefix."_field 
						LEFT JOIN ".$table_prefix."_ws_fieldtype ON ".$table_prefix."_field.uitype = ".$table_prefix."_ws_fieldtype.uitype 
						WHERE tabid = (SELECT tabid FROM ".$table_prefix."_tab WHERE name = ?)"; // crmv@31240 crmv@37679
					$ress = $adb->pquery($q, array($moduleName));
					// crmv@109801
					$label = array();
					$types = array();
					$readonly = array();
					$uitypes = array();
					while($row = $adb->fetchByAssoc($ress)){
						$label[$row['fieldname']] =  $row['fieldlabel'];
						$types[$row['fieldname']] =  $row['fieldtype'];
						$readonly[$row['fieldname']] =  $row['readonly']; // crmv@31240
						$uitypes[$row['fieldname']] = $row['uitype'];
					}
					// crmv@109801e
					
					$reference_changelogs = array();
					foreach ($result as $key=>$value){
	
						if($readonly[$key] == '100') continue; // crmv@31240
	
						$previous_value = $record_init[$moduleName][$id][$key];
						$current_value = $record_last[$moduleName][$id][$key];
						
						$saveReference = false;
						
						if ($types[$key] == 'owner' || ($types[$key] == 'reference' && in_array($uitypes[$key], array(50,51,52,53,54)))) {
							global $showfullusername;
							if (!empty($previous_value)) {
								$prevModule = getOwnerType($previous_value);
								$prevName = getOwnerName($previous_value, $showfullusername);
							} else {
								$prevModule = '';
								$prevName = '';
							}
							if (!empty($current_value)) {
								$nextModule = getOwnerType($current_value);
								$nextName = getOwnerName($current_value, $showfullusername);
							} else {
								$nextModule = '';
								$nextName = '';
							}
						} elseif ($types[$key] == 'reference') {
							$thisName = getEntityName($moduleName, $id, true);
							if (!empty($previous_value)) {
								$prevModule = getSalesEntityType($previous_value);
								$prevName = getEntityName($prevModule, array($previous_value), true);
							} else {
								$prevModule = '';
								$prevName = '';
							}
							if (!empty($current_value)) {
								$nextModule = getSalesEntityType($current_value);
								$nextName = getEntityName($nextModule, array($current_value), true);
							} else {
								$nextModule = '';
								$nextName = '';
							}
							$saveReference = true;
						// crmv@205568
						} elseif ($uitypes[$key] == 83) {
							$types[$key] = 'json';
							
							$tax_names = array();
							$InventoryUtils = InventoryUtils::getInstance();
							$tax_details = $InventoryUtils->getTaxDetailsForProduct($id,'available_associated');
							if (!empty($tax_details)) {
								foreach($tax_details as $tax_detail) {
									$tax_names[$tax_detail['taxname']] = $tax_detail['taxlabel'];
								}
							}
							if (!empty($previous_value)) {
								$previous_value_new = array();
								$previous_value = Zend_Json::decode($previous_value);
								if (!empty($previous_value)) {
									foreach($previous_value as $k => $v) {
										$previous_value_new[$tax_names[$k]] = $v;
									}
								}
								$previous_value = Zend_Json::encode($previous_value_new);
							}
							if (!empty($current_value)) {
								$current_value_new = array();
								$current_value = Zend_Json::decode($current_value);
								if (!empty($current_value)) {
									foreach($current_value as $k => $v) {
										$current_value_new[$tax_names[$k]] = $v;
									}
								}
								$current_value = Zend_Json::encode($current_value_new);
							}
						// crmv@205568e
						} else {
							$prevModule = $nextModule = '';
							$prevName = $nextName = '';
						}
	
						if (strtolower($key) == 'modifiedtime') {
							$obj->column_fields['modified_date'] = $proj->column_fields[$key]; 
						} elseif ($obj->isFieldSkipped($moduleName, $key, $uitypes[$key])) { // crmv@109801
							// skip field, don't save it!
						} else {
							$campi[] = array($label[$key], $previous_value, $current_value, $key, $types[$key], $prevModule, $prevName, $nextModule, $nextName);
						}
						
						// save also a changelog in the linked record
						if ($saveReference && (!empty($current_value) || !empty($previous_value))) {
							$reference_changelogs[] = array($previous_value,$current_value,$key, $thisName);
						}
					}
					$record_init[$moduleName][$id] = '';
					$record_last[$moduleName][$id] = '';
					if (!empty($campi)) {	//se non c'� nessuna differenza non creo il ChangeLog
						$obj->column_fields['description'] = Zend_Json::encode($campi);
						if (!empty($current_auth_record)) {
							$obj->column_fields['display_id'] = $current_auth_record['id'];
							$obj->column_fields['display_module'] = $current_auth_record['module'];
							$obj->column_fields['display_name'] = getEntityName($current_auth_record['module'],$current_auth_record['id'],true);
						} else {
							$obj->column_fields['display_id'] = $current_user->id;
							$obj->column_fields['display_module'] = 'Users';
							$obj->column_fields['display_name'] = getUserFullName($current_user->id);
						}
						$obj->save(); //crmv@47905
						/* crmv@103534
						if ($moduleName == 'Processes') {
							require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
							$processDynaFormObj = ProcessDynaForm::getInstance();
							$processDynaFormObj->propagateParallelsChangeLog($id,$obj);
						}
						crmv@103534e */
					}
					if (!empty($reference_changelogs)) {
						foreach($reference_changelogs as $tmp) {
							$previous_value = $tmp[0];
							$current_value = $tmp[1];
							$key = $tmp[2];
							if (!empty($previous_value)) {
								$obj1 = ChangeLog::getInstance();
								$obj1->column_fields['modified_date'] = $obj->column_fields['modified_date'];
								$obj1->column_fields['audit_no'] = $obj1->get_revision_id($previous_value);
								$obj1->column_fields['user_id'] = $current_user->id;
								$obj1->column_fields['parent_id'] = $previous_value;
								$obj1->column_fields['user_name'] = $current_user->column_fields['user_name'];
								$obj1->column_fields['description'] = Zend_Json::encode(array('ChangeLogRemoveRelation1N',$id,$moduleName,$key,$previous_value,$current_value,$tmp[3]));
								$obj1->column_fields['request_id'] = RequestHandler::getId(); // crmv@177677
								if (!empty($current_auth_record)) {
									$obj1->column_fields['display_id'] = $current_auth_record['id'];
									$obj1->column_fields['display_module'] = $current_auth_record['module'];
									$obj1->column_fields['display_name'] = getEntityName($current_auth_record['module'],$current_auth_record['id'],true);
								} else {
									$obj1->column_fields['display_id'] = $current_user->id;
									$obj1->column_fields['display_module'] = 'Users';
									$obj1->column_fields['display_name'] = getUserFullName($current_user->id);
								}
								$obj1->save(); //crmv@47905
							}
							if (!empty($current_value)) {
								//crmv@170349
								$related_module = getSalesEntityType($current_value);
								if ($obj->isEnabled($related_module)) {
									$obj1 = ChangeLog::getInstance();
									$obj1->column_fields['modified_date'] = $obj->column_fields['modified_date'];
									$obj1->column_fields['audit_no'] = $obj1->get_revision_id($current_value);
									$obj1->column_fields['user_id'] = $current_user->id;
									$obj1->column_fields['parent_id'] = $current_value;
									$obj1->column_fields['user_name'] = $current_user->column_fields['user_name'];
									$obj1->column_fields['description'] = Zend_Json::encode(array('ChangeLogRelation1N',$id,$moduleName,$current_value,$related_module,$key,$tmp[3]));
									$obj1->column_fields['request_id'] = RequestHandler::getId(); // crmv@177677
									if (!empty($current_auth_record)) {
										$obj1->column_fields['display_id'] = $current_auth_record['id'];
										$obj1->column_fields['display_module'] = $current_auth_record['module'];
										$obj1->column_fields['display_name'] = getEntityName($current_auth_record['module'],$current_auth_record['id'],true);
									} else {
										$obj1->column_fields['display_id'] = $current_user->id;
										$obj1->column_fields['display_module'] = 'Users';
										$obj1->column_fields['display_name'] = getUserFullName($current_user->id);
									}
									$obj1->save(); //crmv@47905
								}
								//crmv@170349e
							}
						}
					}
				}
			}
		}
		
		$currentModule = $currentModule_tmp;
	}
}
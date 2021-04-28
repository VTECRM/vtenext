<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@96450 crmv@120769 */

require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
require_once('modules/com_workflow/VTSimpleTemplate.inc');//crmv@207901
require_once('include/Webservices/DescribeObject.php');

class PMActionUpdate {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		$record_involved = '';
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$record_involved = $metadata_action['record_involved'];
			}
			$smarty->assign('METADATA', $metadata_action);
		}
		$records_pick = $PMUtils->getRecordsInvolvedOptions($id, $record_involved, false, null, null, true);	//crmv@135190
		$smarty->assign("RECORDS_INVOLVED", $records_pick);
		
		$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());
	}
	
	function execute($engine,$actionid) {
        //crmv@203075
        (!empty($this->cycleRow['id'])) ? $cycleIndex = $this->cycleRow['id'] : $cycleIndex = $this->cycleIndex;
        (!empty($this->cycleRow['row']['record_id'])) ? $cycleRelatedId = $this->cycleRow['row']['record_id'] : $cycleRelatedId = null;//crmv@203075
        //crmv@203075e
		$action = $engine->vte_metadata['actions'][$actionid];
		//crmv@135190 crmv@160368 crmv@160859
		list($metaid,$module,$reference,$meta_processid,$relatedModule) = explode(':',$action['record_involved']);

		//crmv@203075
		if($cycleRelatedId == null)
		    $record = $engine->getCrmid($metaid,null,$reference,$meta_processid);
		else
		    $record = $cycleRelatedId;
        //crmv@203075e
        
		$recordModule = getSalesEntityType($record);
		if (!empty($relatedModule)) {
			if ($relatedModule != $recordModule) {
				$engine->log("Action Update","action $actionid - {$action['action_title']} FAILED record {$action['record_involved']} do not found");
				return;
			}
			$module = $relatedModule;
		} elseif (!empty($reference)) {
			$module = $recordModule;
		}
		if (empty($record)) {
			$engine->log("Action Update","action $actionid - {$action['action_title']} FAILED record {$action['record_involved']} do not found");
		} elseif (!empty($action['form'])) {
		//crmv@135190e crmv@160368e crmv@160859e
			$PMUtils = ProcessMakerUtils::getInstance();
			
			$engine->log("Action Update","action $actionid - {$action['action_title']}");
			
			// init variabiles to replace tags
			global $log, $adb;
			
			$util = new VTWorkflowUtils();
			$admin = $util->adminUser();
			$entityCache = new VTEntityCache($admin);
			
			$webserviceObject = VtenextWebserviceObject::fromName($adb,$module);//crmv@207871
			$handlerPath = $webserviceObject->getHandlerPath();
			$handlerClass = $webserviceObject->getHandlerClass();
			require_once $handlerPath;
			$handler = new $handlerClass($webserviceObject,$admin,$adb,$log);
			$meta = $handler->getMeta();
			$referenceFields = $meta->getReferenceFieldDetails();
			if (!empty($referenceFields)) $referenceFields = array_keys($referenceFields);
			$ownerFields = $meta->getOwnerFields();
			$dataFields = $meta->getDataFields();
			$hourFields = $meta->getHourFields();	//crmv@128159
			$util->revertUser();
			// end
			
			// track record
			if (empty($reference)) $engine->trackRecord($record,$metaid,$engine->prev_elementid,$engine->elementid); //crmv@172015
			
			$PMUtils->preserveRequest();
			
			// update record
			$focus = CRMEntity::getInstance($module);
			$focus->retrieve_entity_info_no_html($record,$module);
			$focus->mode = 'edit';
			// crmv@198968
			// avoid sending portal emails when changing unrelated fields
			if ($module == 'HelpDesk') {
				$focus->column_fields['comments'] = '';
			}


			// crmv@198968e
			foreach($action['form'] as $fieldname => $value) {
				$prepareValue = !(VTSimpleTemplate::preparedValue($value, $fieldname, $module, $action['form'])); //crmv@177561
				$value = $engine->replaceTags($fieldname,$value,$referenceFields,$ownerFields,$actionid,$cycleIndex,false,$prepareValue,$cycleRelatedId);	//crmv@106856 crmv@177561 crmv@203075
				$focus->column_fields[$fieldname] = $value;
			}
			//crmv@108227
			
			$date_fields = array();
			if (!empty($dataFields)) {
				foreach($dataFields as $dataField) {
					$date_fields[$dataField] = '';
				}
			}
			if (in_array($module,array('Calendar','Events'))) {
				$date_fields['date_start'] = 'time_start';
				$date_fields['due_date'] = 'time_end';
			}
			if (!empty($date_fields)) {
				foreach($date_fields as $date_field => $time_field) {
					if (array_key_exists($date_field,$action['form'])) {
						$date = ''; //crmv@169700
						$date_arr = Zend_Json::decode($focus->column_fields[$date_field]);
						if ($date_arr['options'] == 'custom') {
							$date = $date_arr['custom'];
						} else {
							if ($date_arr['options'] == 'now') {
								$date = date('Y-m-d');
							} else {
								//$date = $engine->replaceTags($date_field,$date_arr['options'],$referenceFields,$ownerFields,$actionid,$this->cycleIndex);
								$date = $date_arr['options'];
							}
							//crmv@122245
							if (!empty($date_arr['num'])) {
								$date_arr['num'] = $engine->replaceTags($date_field,$date_arr['num'],$referenceFields,$ownerFields,$actionid);
							}
							//crmv@122245e
							if (!empty($date_arr['num'])) {
								$advanced = (($date_arr['operator']=='add')?'+':'-').' '.$date_arr['num'].' '.$date_arr['unit'];
							}
						}
						if (!empty($date)) $date = date('Y-m-d',strtotime("$date $advanced")); //crmv@169700
						$focus->column_fields[$date_field] = $date;
					}
					if (array_key_exists($time_field,$action['form'])) {
						if ($module == 'Calendar' && $time_field == 'time_end') {
							$focus->column_fields[$time_field] = '';
						} else {
							$time = ''; //crmv@169700
							$time_arr = Zend_Json::decode($focus->column_fields[$time_field]);
							if ($time_arr['options'] == 'custom') {
								$time = $time_arr['custom'];
							} else {
								if ($time_arr['options'] == 'now') {
									$time = date('H:i');
								} else {
									//$time = $engine->replaceTags($time_field,$time_arr['options'],$referenceFields,$ownerFields,$actionid,$this->cycleIndex);
									//crmv@150966
									if (is_numeric($time_arr['options'])) {
										require_once('modules/SDK/src/73/73Utils.php');
										$uitypeTimeUtils = UitypeTimeUtils::getInstance();
										$time = $uitypeTimeUtils->seconds2Time($time_arr['options']);
									} else {
										$time = $time_arr['options'];
									}
									//crmv@150966e
								}
								//crmv@122245
								if (!empty($time_arr['num'])) {
									$time_arr['num'] = $engine->replaceTags($time_field,$time_arr['num'],$referenceFields,$ownerFields,$actionid);
								}
								//crmv@122245e
								if (!empty($time_arr['num'])) {
									$advanced = (($time_arr['operator']=='add')?'+':'-').' '.$time_arr['num'].' '.$time_arr['unit'];
								}
							}
							if (!empty($time)) $time = date('H:i',strtotime("$time $advanced")); //crmv@169700
							$focus->column_fields[$time_field] = $time;
						}
					}
				}
			}
			//crmv@108227e
			//crmv@128159
			if (!empty($hourFields)) {
				foreach($hourFields as $time_field) {
					if (array_key_exists($time_field,$action['form'])) {
						$time = ''; //crmv@169700
						$time_arr = Zend_Json::decode($focus->column_fields[$time_field]);
						if ($time_arr['options'] == 'custom') {
							$time = $time_arr['custom'];
						} else {
							if ($time_arr['options'] == 'now') {
								$time = date('H:i');
							} else {
								//$time = $engine->replaceTags($time_field,$time_arr['options'],$referenceFields,$ownerFields,$actionid,$this->cycleIndex);
								if (is_numeric($time_arr['options'])) {
									require_once('modules/SDK/src/73/73Utils.php');
									$uitypeTimeUtils = UitypeTimeUtils::getInstance();
									$time = $uitypeTimeUtils->seconds2Time($time_arr['options']);
								} else {
									$time = $time_arr['options'];
								}
							}
							//crmv@122245
							if (!empty($time_arr['num'])) {
								$time_arr['num'] = $engine->replaceTags($time_field,$time_arr['num'],$referenceFields,$ownerFields,$actionid);
							}
							//crmv@122245e
							if (!empty($time_arr['num'])) {
								$advanced = (($time_arr['operator']=='add')?'+':'-').' '.$time_arr['num'].' '.$time_arr['unit'];
							}
						}
						if (!empty($time)) $time = date('H:i',strtotime("$time $advanced")); //crmv@169700
						$focus->column_fields[$time_field] = $time;
					}
				}
			}
			//crmv@128159e
			$focus->save($module);
			
			$PMUtils->restoreRequest();
			
			//crmv@112539
			$engine->logElement($engine->elementid, array(
				'action_type'=>'Update',
				'action_title'=>$action['action_title'],
				'metaid'=>$metaid,
				'crmid'=>$record,
				'module'=>$module,
			));
			//crmv@112539e
			
			if (isset($focus->column_fields['assigned_user_id']) && empty($focus->column_fields['assigned_user_id'])) {
				global $current_user;
				$focus->column_fields['assigned_user_id'] = $current_user->id;
				$engine->log('ERROR','Owner of the record '.$focus->id.' forced to '.$current_user->id.' because it was empty');
			}
			
			VTEntityCache::setResetCache(vtws_getWebserviceEntityId($module,$record));	//crmv@105312 crmv@OPER10174
		}
	}
}
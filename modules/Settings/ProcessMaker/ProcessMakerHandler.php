<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@96450 crmv@97566 crmv@150751 crmv@197606 */

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
require_once('modules/Settings/ProcessMaker/ProcessMakerEngine.php');

class ProcessMakerHandler extends VTEventHandler {
	
	var $real_save = true;
	static public $manual_mode = array();	 //crmv@100495
	var $parallel_current_info = '';	//crmv@115579
	static public $running_processes = array();	//crmv@97575
	//crmv@OPER10174
	static public $first_event_list = array();
	var $first_event_level = false;
	//crmv@OPER10174e
	
	function handleEvent($eventName, $entityData) {
		global $adb, $table_prefix, $current_user;
		
		// TODO check if module Processes is installed!
		
		// crmv@171524 crmv@199641
		$VTEP = VTEProperties::getInstance();
		if ($VTEP->getProperty('performance.mq_enabled') && !TriggerQueueManager::isConsumerActive()) return false;
		// crmv@171524e crmv@199641e
		
		$PMUtils = ProcessMakerUtils::getInstance();
		
		//crmv@OPER10174 skip modules not supported
		if (($key = array_search('Processes', $PMUtils->modules_not_supported)) !== false) unset($PMUtils->modules_not_supported[$key]);
		if (in_array($entityData->getModuleName(),$PMUtils->modules_not_supported) && ( $eventName != 'vte.entity.relate' && $entityData->getModuleName() != 'Documents') ) return false; // crmv@200009
		//crmv@OPER10174e
		
		require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
		$util = new VTWorkflowUtils();
		$isNew = $entityData->isNew();
		require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
		$entityCache = new VTEntityCache($current_user);
		$wsModuleName = $util->toWSModuleName($entityData);
		$wsId = vtws_getWebserviceEntityId($wsModuleName,$entityData->getId());
		$PMUtils->setDefaultDataFormat();
		// crmv@200009
        if ($eventName == 'vte.entity.relate') {
            $reldata = $entityData->relatedRecord;
            $rel_id = $reldata['crmid'];
            $entityData = $entityCache->forId($wsId);
            $entityData->relatedRecord = $reldata;
        } else {
			$entityData = $entityCache->forId($wsId);
        }
		// crmv@200009e
		$PMUtils->restoreDataFormat();
		
		$moduleName = $entityData->getModuleName();
		$id = $entityData->getId();
		$entity_id = vtws_getIdComponents($id);
		$entity_id = $entity_id[1];
		
		// skip light modules
		require_once('include/utils/ModLightUtils.php');
		$MLUtils = ModLightUtils::getInstance();
		$light_modules = $MLUtils->getModuleList();
		if (!empty($light_modules) && in_array($moduleName,$light_modules)) return false;
		
		global $iAmAProcess; $iAmAProcess = true;	//crmv@105685 crmv@170650
		
		//crmv@97575
		static $recursion_count = 0;
		$recursion_count++;
		//crmv@97575e
		//crmv@100495
		if ($_REQUEST['run_processes'] == 'yes') {
			unset($_REQUEST['run_processes']);
			self::$manual_mode[$_REQUEST['module']] = true;
		}
		//crmv@100495e
		
		// check start events
		// crmv@200009
        ($isNew  || $eventName == 'vte.entity.relate') ? $nextEvents = array() : $nextEvents = $PMUtils->getNextEvents($entity_id, $moduleName, $this->parallel_current_info); //crmv@115579 crmv@171524
        if($eventName == 'vte.entity.relate')
            $startingEvents = $PMUtils->getStartingEvents($entity_id, $moduleName, "", "", $eventName, $reldata['module']);
        else
            $startingEvents = $PMUtils->getStartingEvents($entity_id, $moduleName, "", "", $eventName, '');
        // crmv@200009e
		$events = array_merge($nextEvents,$startingEvents);
		
		if (!$this->real_save && $eventName != 'vte.entity.relate') { // crmv@200009
			// search for EVERY_TIME conditions of other records involved in the process
			$otherEvents = $PMUtils->getOtherEvents($entity_id, $moduleName, $this->parallel_current_info);	//crmv@115579
			$events = array_merge($events,$otherEvents);
			$PMUtils->cleanDuplicateEvents($events);
		}
		//crmv@OPER10174
		if (empty(self::$first_event_list)) {
			self::$first_event_list = $events;
			$this->first_event_level = true;
		} else {
			$new_events = array();
			if (!empty($events)) {
				foreach($events as $e) {
					$check = $PMUtils->checkDuplicateEvent(self::$first_event_list,$e);
					if (!$check) $new_events[] = $e;
				}
			}
			$events = $new_events;
		}
		//crmv@OPER10174e
		//echo '<br>HANDLER:'; preprint($events); die;
		$evaluated_events = array();
		if (!empty($events)) {
			foreach($events as $eventid => $event) { //crmv@OPER10174
				
				$bk_id = $id;
				$bk_entity_id = $entity_id;
				$bk_moduleName = $moduleName;
				
				//crmv@OPER10174
				if ($this->first_event_level) {
					unset(self::$first_event_list[$eventid]);
				}
				//crmv@OPER10174e
				$running_process = $event['running_process'];
				$processid = $event['processid'];
				$elementid = $event['elementid'];
				$metaid = $event['metaid'];
				$executionCondition = $event['metadata']['execution_condition'];
				$conditions = $event['metadata']['conditions'];
				if (!isset($conditions)) continue;
				//crmv@129012
				if (strpos($event['metadata']['moduleName'],':') !== false) {
					list($condMetaId,$condModuleName) = explode(':',$event['metadata']['moduleName']);
					if ($condModuleName != 'DynaForm' && !empty($condMetaId) && $condMetaId != $metaid && $executionCondition != 'EVERY_TIME') continue;
				}
				//crmv@129012e
				if (isset($event['entity'])) {
					$id = $event['entity']['id'];
					$entity_id = $event['entity']['entity_id'];
					$moduleName = $event['entity']['moduleName'];
				}
				
				if (!empty($event['dynaformvalues'])) $this->addDynaFormData($entityData, $event);
				switch ($executionCondition) {
					case 'ON_FIRST_SAVE':
						if ($isNew && $this->real_save) {
							$doEvaluate = true;
						} else {
							$doEvaluate = false;
						}
						break;
					case 'ON_EVERY_SAVE':
						if ($this->real_save) {
							$doEvaluate = true;
						} else {
							$doEvaluate = false;
						}
						break;
					case 'ON_MODIFY':
						if (!$isNew && $this->real_save) {
							$doEvaluate = true;
						} else {
							$doEvaluate = false;
						}
						break;
					case 'EVERY_TIME':
						$doEvaluate = true;
						break;
						//crmv@97575
					case 'ON_SUBPROCESS':
						break;
						//crmv@97575e
						//crmv@100495
					// crmv@200009
                    case 'ON_RELATE_RECORD':
						if ($eventName == 'vte.entity.relate') {
							$doEvaluate = true;
						} else {
							$doEvaluate = false;
						}
						break;
					// crmv@200009e
					case 'MANUAL_MODE':
						if (self::$manual_mode[$moduleName]) {
							$doEvaluate = true;
						}
						break;
						//crmv@100495e
					default:
						throw new Exception("Should never come here! Execution Condition:".$executionCondition);
				}
				$evaluated = false;
				
				// check if the condition is preceded by a timer (delay or start)
				$incoming = $PMUtils->getIncoming($processid,$elementid,$running_process);
				(!empty($incoming)) ? $prev_elementid = $incoming[0]['shape']['id'] : $prev_elementid = false;
				$prev_structure = $PMUtils->getStructureElementInfo($processid,$prev_elementid,'shapes',$running_process);
				$prevEngineType = $PMUtils->getEngineType($prev_structure);
				if ($prevEngineType == 'TimerStart') {
					continue;	// if is a start timer continue because only the cron can start these processes
				} elseif ($running_process && $prevEngineType == 'TimerIntermediate') {
					$checkTimer = true;
					$timerResult = $adb->pquery("select timer from {$table_prefix}_running_processes_timer where mode = ? and running_process = ? and elementid = ?", array('intermediate',$running_process,$prev_elementid));
					if ($timerResult && $adb->num_rows($timerResult) > 0) {
						while($row=$adb->fetchByAssoc($timerResult,-1,false)) {
							if (strtotime($row['timer']) > time()) {
								// OK
								$checkTimer = false;
								break;
							}
						}
					}
					if (!$checkTimer) continue;
				}
				// end check timer
				
				// calculate all processes that satisfy the conditions
				if ($doEvaluate) {
					$event['doEvaluate'] = true;
					if ($PMUtils->evaluateCondition($entityCache, $id, $conditions)){
						$event['evaluateCondition'] = true;
					} else { // for gateway else condition
						$event['evaluateCondition'] = false;
					}
				} else {
					$event['doEvaluate'] = false;
				}
				$evaluated_events[] = $event;
				
				$id = $bk_id;
				$entity_id = $bk_entity_id;
				$moduleName = $bk_moduleName;
			}
		}
		if (!empty($evaluated_events)) {
			
			/*
			 * ordino i processi:
			 * prima quelli da getNextEvents e poi quelli da getStartingEvents,
			 * entrambi gli insiemi ordinati per quelli con + condizioni
			 */
			if (!function_exists('cmp')) {
				function cmp($a, $b) {
					$a_count = substr_count($a['metadata']['conditions'],'"fieldname"');
					$b_count = substr_count($b['metadata']['conditions'],'"fieldname"');
					return (intval($a['start']) >= intval($b['start']) && $a_count < $b_count);
				}
			}
			usort($evaluated_events,'cmp');
			
			foreach($evaluated_events as $eventid => $event) {
				
				$bk_id = $id;
				$bk_entity_id = $entity_id;
				$bk_moduleName = $moduleName;
				
				$running_process = $event['running_process'];
				$processid = $event['processid'];
				$elementid = $event['elementid'];
				$metaid = $event['metaid'];
				$metaid2 = $metaid+1; // crmv@200009
				if (isset($event['entity'])) {
					$id = $event['entity']['id'];
					$entity_id = $event['entity']['entity_id'];
					$moduleName = $event['entity']['moduleName'];
				}
				
				if ($event['doEvaluate']) {
					if ($event['evaluateCondition']) {
						
						$evaluated = true;
						//crmv@100495
						if ($running_process) {
							foreach(self::$running_processes as $i => $info) {
								if ($running_process == $info['running_process']) {
									self::$running_processes[$i]['evaluated'] = $evaluated;
									break;
								}
							}
						}
						//crmv@100495e
						$outgoings = $PMUtils->getOutgoing($processid,$elementid,$running_process);
						if (!empty($outgoings)) {
							foreach($outgoings as $outgoing) {
								// track start condition
								if (isset($event['start']) && $event['start'] === true) {
									$incoming = $PMUtils->getIncoming($processid,$elementid,$running_process);
									(!empty($incoming)) ? $current_elementid = $incoming[0]['shape']['id'] : $current_elementid = false;
									if ($current_elementid !== false) {
										//crmv@104023
										$check = $PMUtils->getRunningProcess($entity_id,$metaid,$processid);
										if ($eventName != 'vte.entity.relate' && $check !== false) continue; // crmv@200009
										$PMEngine = ProcessMakerEngine::getInstance($running_process,$processid,$current_elementid,$elementid,$id,$metaid,$entityCache);
										$PMEngine->trackRecord($PMEngine->entity_id,$PMEngine->metaid,$current_elementid,$elementid);
										$PMEngine->trackProcess($current_elementid,$elementid);
										$running_process = $PMEngine->running_process;
										self::$running_processes[] = array('new'=>true,'running_process'=>$running_process,'processid'=>$processid,'evaluated'=>$evaluated,'record'=>$entity_id,'metaid'=>$metaid);	//crmv@97575	//crmv@100495
										//crmv@200009
                                        if ($eventName == 'vte.entity.relate') {
                                            $PMEngine = ProcessMakerEngine::getInstance($running_process,$processid,$current_elementid,$elementid,$rel_id,$metaid2,$entityCache);
                                            $PMEngine->trackRecord($rel_id,$metaid2,$current_elementid,$elementid);
                                            $PMEngine->trackProcess($current_elementid,$elementid);
                                            $running_process = $PMEngine->running_process;
                                            self::$running_processes[] = array('new'=>true,'running_process'=>$running_process,'processid'=>$processid,'evaluated'=>$evaluated,'record'=>$entity_id,'metaid'=>$metaid2);
                                        }
                                        // crmv@200009e
									}
								}
								// execute actions
								$engineType = $PMUtils->getEngineType($outgoing['shape']);
								$PMEngine = ProcessMakerEngine::getInstance($running_process,$processid,$elementid,$outgoing['shape']['id'],$id,$metaid,$entityCache);
								$PMEngine->sdk_data = $entityData; // crmv@200009
								$PMEngine->execute($engineType,$outgoing['shape']['type']);
								$running_process = $PMEngine->running_process;
								
								//TODO execute $engineType == 'Condition'
							}
						}
					} else {	// for gateway else condition
						$outgoings = $PMUtils->getOutgoing($processid,$elementid,$running_process);
						if (!empty($outgoings)) {
							foreach($outgoings as $outgoing) {
								$engineType = $PMUtils->getEngineType($outgoing['shape']);
								if ($engineType == 'Gateway') {
									$vte_metadata = $PMUtils->getMetadata($processid,$elementid,$running_process);
									if ($vte_metadata['cond_else'] != '') {
										$evaluated = true;
										// execute actions
										$PMEngine = ProcessMakerEngine::getInstance($running_process,$processid,$elementid,$outgoing['shape']['id'],$id,$metaid,$entityCache);
										$PMEngine->execute($engineType,$outgoing['shape']['type']);
										$running_process = $PMEngine->running_process;
									}
								}
							}
						}
					}
				}
				
				if (empty($running_process)) continue;
				
				if ($evaluated) {
					// delete boundary timers
					$PMUtils->deleteTimer('boundary',$running_process,$elementid);
					//crmv@93990
					if ($moduleName == 'Processes' && !empty($event['dynaformmetaid'])) $adb->pquery("UPDATE {$table_prefix}_process_dynaform SET done = ? WHERE running_process = ? AND metaid = ?", array(1,$running_process,$event['dynaformmetaid']));
					//crmv@93990e
				} else {
					// set boundary timers
					$structure = $PMUtils->getStructureElementInfo($processid,$elementid,'tree',$running_process);
					$attachers = $structure['attachers'];
					if (!empty($attachers)) {
						foreach($attachers as $attacher) {
							$attacher_structure = $PMUtils->getStructureElementInfo($processid,$attacher,'shapes',$running_process);
							if ($attacher_structure['subType'] == 'TimerEventDefinition') {
								$engineType = $PMUtils->getEngineType($attacher_structure);
								$PMEngine = ProcessMakerEngine::getInstance($running_process,$processid,$elementid,$attacher,$id,$metaid,$entityCache);
								$PMEngine->execute($engineType,$attacher_structure['type']);
							}
						}
					}
				}
				
				$id = $bk_id;
				$entity_id = $bk_entity_id;
				$moduleName = $bk_moduleName;
			}
		}
		//crmv@97575
		$recursion_count--;
		if ($recursion_count == 0) {
			$PMUtils->relateSubProcessesRun(self::$running_processes);
			$iAmAProcess = false;	//crmv@105685
		}
		//crmv@97575e
	}
	
	function addDynaFormData(&$entityData, $event) {
		if (empty($entityData->data)) return;
		
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$blocks = $processDynaFormObj->getStructure($event['processid'], false, $event['dynaformmetaid'], $event['running_process']);
		if (!empty($blocks)) {
			foreach($blocks as $block) {
				foreach($block['fields'] as $field) {
					$typeDetails = $processDynaFormObj->getFieldTypeDetails($field);
					if ($typeDetails['name'] == 'reference') {
						if (isset($event['dynaformvalues'][$field['fieldname']]) && !empty($event['dynaformvalues'][$field['fieldname']])) {
							(in_array($field['uitype'],array(52,51,50,77))) ? $module = 'Users' : $module = getSalesEntityType($event['dynaformvalues'][$field['fieldname']]);
							$event['dynaformvalues'][$field['fieldname']] = vtws_getWebserviceEntityId($module,$event['dynaformvalues'][$field['fieldname']]);
						}
					}
				}
			}
		}
		$entityData->data = array_merge($entityData->data,$event['dynaformvalues']);
	}
}
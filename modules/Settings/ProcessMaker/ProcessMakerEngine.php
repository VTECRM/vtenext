<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@96450 crmv@97566 crmv@108227 crmv@106857 crmv@150751 */

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');

class ProcessMakerEngine extends SDKExtendableClass {
	
	var $processid;
	var $prev_elementid;
	var $elementid;
	var $id;
	var $entity_id;
	var $metaid;
	var $process_data;
	var $vte_metadata;
	var $helper;
	var $running_process;
	var $log_rollback = false;	//crmv@112539
	static $processHelper;
	
	function __construct($running_process,$processid,$prev_elementid,$elementid,$id,$metaid,$entityCache) {
		global $adb, $table_prefix;
		$PMUtils = ProcessMakerUtils::getInstance();
		
		$this->processid = $processid;
		$this->prev_elementid = $prev_elementid;
		$this->elementid = $elementid;
		$this->id = $id;
		$this->entity_id = vtws_getIdComponents($id);
		$this->entity_id = $this->entity_id[1];
		$this->metaid = $metaid;
		
		if (empty($running_process)) {
			if ($entityCache::$cache[$id]->moduleName == 'Processes') {
				$idComponents = vtws_getIdComponents($id);
				$running_process = getSingleFieldValue("{$table_prefix}_processes", 'running_process', 'processesid', $idComponents[1]);
			} else {
				//$running_process = $this->getRunningProcess();
				$running_process = $adb->getUniqueID("{$table_prefix}_running_processes");
			}
		}
		$this->running_process = $running_process;
		$xml_version = $PMUtils->getSystemVersion4RunningProcess($this->running_process,array('processmaker','xml_version')); // forced version (xml_version_forced)
		
		$process_data = $PMUtils->retrieve($processid, $xml_version);
		$this->process_data = $process_data;
		$vte_metadata = Zend_Json::decode($process_data['vte_metadata']);
		$helper = Zend_Json::decode($process_data['helper']);
		$this->vte_metadata = $vte_metadata[$elementid];
		$this->helper = $helper[$elementid];
	}
	
	function getEntityCache($wsId='',$dynaformmetaid='') {
		global $current_user, $table_prefix;
		if (empty($wsId)) {
			$crmid = $this->entity_id;
			$wsId = $this->id;
		} else {
			list($m,$crmid) = vtws_getIdComponents($wsId);
		}
		$PMUtils = ProcessMakerUtils::getInstance();
		$entityCache = new VTEntityCache($current_user);
		$PMUtils->setDefaultDataFormat();
		$entityCache->forId($wsId);
		if ($entityCache::$cache[$wsId]->moduleName == 'Processes') {
			// add dynaform fields
			$processDynaForm = ProcessDynaForm::getInstance();
			$focus = CRMEntity::getInstance('Processes');
			$focus->id = $crmid;
			$focus->column_fields['processmaker'] = $this->processid;
			$focus->column_fields['running_process'] = getSingleFieldValue("{$table_prefix}_processes", 'running_process', 'processesid', $crmid);
			if (empty($dynaformmetaid)) {
				$processDynaForm->getCurrentDynaForm($focus, $elementid);
				$meta = $processDynaForm->getMeta($focus->column_fields['processmaker'], $elementid, false, $focus->column_fields['running_process']);
				$dynaformmetaid = $meta['id'];
			}
			$dynaform = $processDynaForm->getValues($focus->column_fields['running_process'], $dynaformmetaid);
			if (!empty($dynaform)) $dynaform['crmid'] = $focus->id;
			$entityCache::$cache[$wsId]->data = array_merge($entityCache::$cache[$wsId]->data,$dynaform); //crmv@174158
		}
		$PMUtils->restoreDataFormat();
		return $entityCache;
	}
	
	//crmv@173186 crmv@176614
	function log($title,$str='') {
		global $current_user;
		
		static $sid = '';
		if (empty($sid)) {
			$sid = session_id();
			if (empty($sid) && isset($_REQUEST['app_key'])) $sid = 'cron'; else $sid = 'vte';
			VTESystemLogger::log('processes', null, str_pad(' '.$sid.' ', 250, '-', STR_PAD_BOTH));
		}
		
		$text = "processid:{$this->processid} running_process:{$this->running_process} userid:{$current_user->id} [{$title}] {$str}";
		VTESystemLogger::log('processes', null, $text);
	}
	//crmv@173186e crmv@176614e
	
	function trackRecord($entity_id,$metaid,$prev_elementid,$elementid,$track_update=true) {
		global $adb, $table_prefix;
		// crmv@129940
		$currentCol = 'current';
		$adb->format_columns($currentCol);
		// crmv@129940e
		if ($metaid > 0) { // crmv@102879
			$result = $adb->pquery("select * from {$table_prefix}_processmaker_rec where crmid = ? and id = ? and running_process = ?", array($entity_id,$metaid,$this->running_process));
			if ($result && $adb->num_rows($result) > 0) {
				if ($track_update) {
					$adb->pquery("update {$table_prefix}_processmaker_rec set $currentCol = ? where crmid = ? and id = ? and running_process = ?", array($elementid,$entity_id,$metaid,$this->running_process)); // crmv@129940
				}
			} else {
				$adb->pquery("insert into {$table_prefix}_processmaker_rec(crmid,id,running_process,$currentCol) values(?,?,?,?)", array($entity_id,$metaid,$this->running_process,$elementid)); // crmv@129940
			}
			$this->log("trackRecord","entity_id:$entity_id metaid:$metaid");
		} else {
			$this->log("editingDynaform","processesid:$entity_id");
		}
	}
	
	function trackProcess($prev_elementid,$elementid) {
		global $adb, $table_prefix;
		// crmv@129940
		$currentCol = 'current';
		$adb->format_columns($currentCol);
		$result = $adb->pquery("select $currentCol from {$table_prefix}_running_processes where id = ?", array($this->running_process));
		// crmv@129940e
		if ($result && $adb->num_rows($result) > 0) {
			$current = $adb->query_result($result,0,'current');
			if (strpos($current,'|##|') !== false) {
				$current = explode('|##|',$current);
				if (in_array($elementid,$current)) {
					foreach($current as &$el) {
						if ($prev_elementid == $el) $el = $elementid;
					}
				} elseif (in_array($prev_elementid,$current)) {
					foreach($current as &$el) {
						if ($prev_elementid == $el) $el = $elementid;
					}
				}
				$adb->pquery("update {$table_prefix}_running_processes set $currentCol = ? where id = ?", array(implode('|##|',array_unique($current)),$this->running_process)); // crmv@129940
			} else {
				$adb->pquery("update {$table_prefix}_running_processes set $currentCol = ? where id = ?", array($elementid,$this->running_process)); // crmv@129940
			}
		} else {
			$adb->pquery("insert into {$table_prefix}_running_processes(id,processmakerid,$currentCol,xml_version) values(?,?,?,?)", array($this->running_process,$this->processid,$elementid,$this->process_data['xml_version'])); // crmv@129940
		}
		// set last dynaform available
		if ($this->helper['active'] == 'on') {
			$adb->pquery("update {$table_prefix}_running_processes set current_dynaform = ? where id = ?", array($elementid,$this->running_process));
		}
		// log running process
		$this->logTimeProcess($this->running_process,$this->processid,$prev_elementid,$elementid);
		$this->log("trackProcess","$prev_elementid -> $elementid");
	}
	
	//crmv@112539
	function logTimeProcess($running_process, $processid, $prev_elementid, $elementid) {
		global $adb, $table_prefix, $current_user;
		$rollback = 0;
		if ($this->log_rollback) {
			$rollback = 1;
			$this->log_rollback = false;
			$this->log("Manually changed position");
		}
		$adb->pquery("insert into {$table_prefix}_running_processes_logs(id,running_process,prev_elementid,elementid,userid,logtime,rollbck) values(?,?,?,?,?,?,?)",
		array($adb->getUniqueID("{$table_prefix}_running_processes_logs"), $running_process, $prev_elementid, $elementid, $current_user->id, date('Y-m-d H:i:s'), $rollback));
		//crmv@126096
		if (!empty($current_user->id)) {
			if ($adb->isMysql()) {
				$adb->pquery("insert ignore into {$table_prefix}_actor_running_processes(userid,running_process) values(?,?)",array($current_user->id,$running_process));
			} else {
				$result = $adb->pquery("select * from {$table_prefix}_actor_running_processes where userid = ? and running_process = ?", array($current_user->id,$running_process));
				if ($result && $adb->num_rows($result) == 0) {
					$adb->pquery("insert into {$table_prefix}_actor_running_processes(userid,running_process) values(?,?)",array($current_user->id,$running_process));
				}
			}
		}
		//crmv@126096e
	}
	
	function logElement($elementid, $info) {
		global $adb, $table_prefix;
		$adb->pquery("insert into {$table_prefix}_running_processes_logsi(id,running_process,elementid,info) values(?,?,?,?)",
		array($adb->getUniqueID("{$table_prefix}_running_processes_logsi"), $this->running_process, $elementid, Zend_Json::encode($info)));
	}
	//crmv@112539e
	
	function execute($engineType,$bpmnType) {
		$method = "execute{$engineType}";
		//echo "execute $method($bpmnType) $this->entity_id,$this->metaid,$this->processid,$this->prev_elementid,$this->elementid,$bpmnType,$this->running_process<br>\n";
		if ($engineType != '' && method_exists($this,$method)) {
			$this->$method($bpmnType);
		} else {
			// if the bpmn element is not implemented go to next steps
			$this->trackProcess($this->prev_elementid,$this->elementid);
			// execute next steps
			$this->executeNextSteps();
		}
	}
	
	function executeNextSteps() {
		$PMUtils = ProcessMakerUtils::getInstance();
		$outgoings = $PMUtils->getOutgoing($this->processid,$this->elementid,$this->running_process);
		if (!empty($outgoings)) {
			foreach($outgoings as $outgoing) {
				$entityCache = $this->getEntityCache();
				$nextPMEngine = ProcessMakerEngine::getInstance($this->running_process,$this->processid,$this->elementid,$outgoing['shape']['id'],$this->id,$this->metaid,$entityCache);
				$nextPMEngine->execute($PMUtils->getEngineType($outgoing['shape']),$outgoing['shape']['type']);
			}
		}
	}
	
	function executeCondition($bpmnType) {
		$this->trackProcess($this->prev_elementid,$this->elementid);
		// crmv@OPER16448
		if (empty($this->entity_id)) {
			$this->log("ERROR","entity_id is empty");
			return false;
		}
		$module = getSalesEntityType($this->entity_id,true);
		if (empty($this->entity_id)) {
			$this->log("ERROR","module not found");
			return false;
		}
		// crmv@OPER16448e
		$focus = CRMEntity::getInstance($module);
		// crmv@168055 crmv@173270
		$error = $focus->retrieve_entity_info_no_html($this->entity_id,$module,false);
		if (!empty($error)) {
			$this->log("ERROR","retrieve_entity_info_no_html({$this->entity_id},{$module}): {$error}");
			return false;
		}
		// crmv@168055e crmv@173270e
		$focus->mode = 'edit';
		
		require_once("include/events/include.inc");
		require_once("modules/Settings/ProcessMaker/ProcessMakerHandler.php");
		$em = new VTEventsManager($adb);
		// Initialize Event trigger cache
		$em->initTriggerCache();
		$entityData  = VTEntityData::fromCRMEntity($focus);
		$processMakerHandler = new ProcessMakerHandler();
		$processMakerHandler->real_save = false;
		if ($this->isParallelCondition($this->running_process, $this->elementid)) $processMakerHandler->parallel_current_info = array('running_process'=>$this->running_process, 'elementid'=>$this->elementid); //crmv@115579
		$processMakerHandler->handleEvent('vte.entity.aftersave.processes', $entityData); // crmv@177677 crmv@207852
	}
	
	function executeAction($bpmnType) {
		global $adb, $table_prefix;
		
		$this->trackProcess($this->prev_elementid,$this->elementid);
		
		$processHelper = $this->queueProcessHelper();
		
		//crmv@135193
		$PMUtils = ProcessMakerUtils::getInstance();
		if ($PMUtils->skip_notifications_actions) {
			global $global_skip_notifications;
			$tmp_global_skip_notifications = $global_skip_notifications;
			$global_skip_notifications = true;
		}
		if ($PMUtils->hide_changelog) {
			global $global_hide_changelog;
			$tmp_global_hide_changelog = $global_hide_changelog;
			$global_hide_changelog = true;
		}
		//crmv@135193e
		
		// execute all actions
		$actions = $this->vte_metadata['actions'];
		if (!empty($actions)) {
			foreach($actions as $actionid => $action) {
				if ($action['action_type'] == 'SDK') {
					$sdkActions = SDK::getProcessMakerActions();
					$sdkAction = $sdkActions[$action['function']];
					if (!empty($sdkAction)) {
						require_once($sdkAction['src']);
						call_user_func_array($sdkAction['funct'], array($this,$actionid));
					}
				} else {
					require_once("modules/Settings/ProcessMaker/actions/{$action['action_type']}.php");
					$actionClass = 'PMAction'.$action['action_type'];
					$actionObj = new $actionClass;
					$actionObj->execute($this,$actionid);
				}
			}
		}
		
		$global_skip_notifications = $tmp_global_skip_notifications;	//crmv@135193
		
		$processEnded = $this->isEndProcess($bpmnType);
		
		//crmv@103534 kill parallels fake processes
		if ($processEnded) {
			$result = $adb->pquery("select processesid, elementid from {$table_prefix}_process_gateway_conn where running_process = ? and casperid <> processesid and casperid is not null", array($this->running_process));
			if ($result && $adb->num_rows($result) > 0) {
				$focus = CRMEntity::getInstance('Processes');
				while($row=$adb->fetchByAssoc($result)) {
					$focus->trash('Processes',$row['processesid']);
				}
				$adb->pquery("delete from {$table_prefix}_process_gateway_conn where running_process = ?", array($this->running_process));
			}
		}
		//crmv@103534e
		
		$this->saveProcessHelper($processHelper);
		
		$global_hide_changelog = $tmp_global_hide_changelog;	//crmv@135193
		
		$this->applyAdvancedPermissions();	//crmv@100731 apply advanced permissions
		
		$this->applyConditionals($processEnded);	//crmv@112297
		
		// execute next steps
		$this->executeNextSteps();
		
		if ($processEnded) {
			$this->endProcess();
		}
	}
	
	//crmv@100731
	function applyAdvancedPermissions() {
		global $adb, $table_prefix;
		$adv_perm_tmp = array();
		$advanced_permissions = $this->vte_metadata['advanced_permissions'];
		if (!empty($advanced_permissions)) {
			foreach($advanced_permissions as $advanced_permission) {
				list($metaid,$module) = explode(':',$advanced_permission['record_involved']);
				$record_involved = $this->getCrmid($metaid);
				$resource = $this->getResource($advanced_permission['resource']);
				if (!empty($resource)) {
					$read_perm = 0;
					$write_perm = 0;
					if (in_array($advanced_permission['permission'],array('ro','rw'))) $read_perm = 1;
					if ($advanced_permission['permission'] == 'rw') $write_perm = 1;
					// if I have more rows for the same record/resource use the most restrictive condition
					if (!isset($adv_perm_tmp[$record_involved][$resource]) || $read_perm < $adv_perm_tmp[$record_involved][$resource]['read_perm'] || $write_perm < $adv_perm_tmp[$record_involved][$resource]['write_perm'])
						$adv_perm_tmp[$record_involved][$resource] = array('read_perm'=>$read_perm,'write_perm'=>$write_perm,'resource_type'=>$advanced_permission['resource_type']);
				}
			}
		}
		/*
		 // apply permission from $this->elementid to the next action task
		 $this->setPermissionRecursively($this->processid,$this->running_process,$this->elementid,$adv_perm_tmp);
		 */
		// the permission is valid until another permission is registered for the same crmid and user
		foreach($adv_perm_tmp as $crmid => $tmp) {
			foreach($tmp as $resource => $perm) {
				$this->log("Set Advanced Permissions","entity_id:{$crmid} resource:{$resource} read:{$perm['read_perm']} write:{$perm['write_perm']}");
				$adb->pquery("delete from {$table_prefix}_process_adv_permissions where running_process = ? and crmid = ? and resource = ?", array($this->running_process,$crmid,$resource));
				$adb->pquery("insert into {$table_prefix}_process_adv_permissions (running_process,crmid,resource,resource_type,elementid,read_perm,write_perm) values (?,?,?,?,?,?,?)",
				array($this->running_process,$crmid,$resource,$perm['resource_type'],$this->elementid,$perm['read_perm'],$perm['write_perm']));
			}
		}
	}
	/*
	 function setPermissionRecursively($processid,$running_process,$elementid,$adv_perm_tmp) {
	 global $adb, $table_prefix;
	 foreach($adv_perm_tmp as $crmid => $tmp) {
	 foreach($tmp as $resource => $perm) {
	 $adb->pquery("delete from {$table_prefix}_process_adv_permissions where running_process = ? and elementid = ?", array($this->running_process,$elementid));
	 $adb->pquery("insert into {$table_prefix}_process_adv_permissions (running_process,elementid,crmid,resource,read_perm,write_perm) values (?,?,?,?,?,?)",
	 array($running_process,$elementid,$crmid,$resource,$perm['read_perm'],$perm['write_perm']));
	 }
	 }
	 $PMUtils = ProcessMakerUtils::getInstance();
	 $outgoings = $PMUtils->getOutgoing($processid,$elementid,$running_process);
	 if (!empty($outgoings)) {
	 foreach($outgoings as $outgoing) {
	 $engineType = $PMUtils->getEngineType($outgoing['shape']);
	 if ($engineType == 'Action') continue;
	 else $this->setPermissionRecursively($processid,$running_process,$outgoing['shape']['id'],$adv_perm_tmp);
	 }
	 }
	 } */
	//crmv@100731e
	
	//crmv@112297
	function applyConditionals($processEnded) {
		global $adb, $table_prefix;
		if ($processEnded) {
			// if process is ended delete all conditionals and win conditionals defined in the End-Task or the standard ones
			$adb->pquery("delete from {$table_prefix}_processmaker_conditionals where running_process = ?", array($this->running_process));
		}
		$conditionals = $this->vte_metadata['conditionals'];
		if (!empty($conditionals)) {
			foreach($conditionals as $conditional) {
				list($metaid,$module) = explode(':',$conditional['moduleName']);
				$crmid = $this->getCrmid($metaid,$this->running_process);
				
				// clean previous rules applied to that crmid
				$adb->pquery("delete from {$table_prefix}_processmaker_conditionals where running_process = ? and crmid = ?", array($this->running_process,$crmid));
				
				// save only the elementid in whitch are saved the advanced conditionals and retrieve them later
				$result = $adb->pquery("select * from {$table_prefix}_processmaker_conditionals where running_process = ? and crmid = ? and elementid = ?", array($this->running_process,$crmid,$this->elementid));
				if ($result && $adb->num_rows($result) > 0) {}
				else $adb->pquery("insert into {$table_prefix}_processmaker_conditionals(id,running_process,crmid,elementid) values(?,?,?,?)", array($adb->getUniqueID("{$table_prefix}_processmaker_conditionals"),$this->running_process,$crmid,$this->elementid));
			}
		}
	}
	//crmv@112297e
	
	function executeGateway($bpmnType) {
		global $adb, $table_prefix;
		
		$this->trackProcess($this->prev_elementid,$this->elementid);
		
		$PMUtils = ProcessMakerUtils::getInstance();
		
		if ($bpmnType == 'ParallelGateway') {
			
			// clear required2go
			$closing_gateway = $this->vte_metadata['closing_gateway'];
			if (!empty($closing_gateway)) $adb->pquery("delete from {$table_prefix}_process_gateway_req where running_process = ? and gateway_elementid = ?", array($this->running_process,$closing_gateway));
			
			//crmv@149014 crmv@185647 TODO manage also nested parallel gateways
			$result = $adb->pquery("select processesid from {$table_prefix}_processes where deleted = 0 and running_process = ?", array($this->running_process));
			if ($result && $adb->num_rows($result) > 0) $primary_processesid = $adb->query_result($result,0,'processesid');
			//crmv@149014e crmv@185647e
			
			$outgoings = $PMUtils->getOutgoing($this->processid,$this->elementid,$this->running_process);
			if (!empty($outgoings)) {
				$next = array();
				foreach($outgoings as $seq => $outgoing) {
					$next[] = $outgoing['shape']['id'];
					//crmv@103534 crmv@115579
					$elementsons = array();
					$conditionssons = array();
					$PMUtils->getParallelFlowSons($this->processid,$this->running_process,$this->elementid,$outgoing['shape']['id'],$elementsons,$conditionssons);
					$elementsons = Zend_Json::encode($elementsons);
					$conditionssons = Zend_Json::encode($conditionssons);
					$adb->pquery("delete from {$table_prefix}_process_gateway_conn where running_process = ? and elementid = ? and flow = ?", array($this->running_process,$this->elementid,$outgoing['connection']['id']));
					$adb->pquery("insert into {$table_prefix}_process_gateway_conn(running_process,elementid,flow,seq,bpmn_type,elementsons,conditionssons,primary_processesid) values(?,?,?,?,?,?,?,?)", array($this->running_process,$this->elementid,$outgoing['connection']['id'],$seq,$bpmnType,$elementsons,$conditionssons,$primary_processesid));	//crmv@149014
					//crmv@103534e crmv@115579e
				}
				$this->trackProcess($this->elementid,implode('|##|',$next));
			}
		} else {
			$gatewayConditions = $PMUtils->getGatewayConditions($this->processid,$this->elementid,$this->vte_metadata,$show_required2go_check=false,$this->running_process);
			// check current condition
			$required2go_all = $this->vte_metadata['required2go'];
			$required2go = array();
			if (!empty($gatewayConditions)) {
				$next = false;
				foreach($gatewayConditions as $gatewayCondition) {
					if ($required2go_all == 'on' && isset($gatewayCondition['elementid'])) {
						$required2go[] = $gatewayCondition['elementid'];
					}
					if ($this->prev_elementid == $gatewayCondition['elementid']) {
						if (!empty($gatewayCondition['conditions'])) {
							$wsId = $this->id;
							$cond_metaid = '';
							if (!empty($gatewayCondition['metaid'])) {
								//crmv@160805
								if ($PMUtils->isStartTask($this->processid,$gatewayCondition['elementid'],$this->running_process)) {
									$cond_module = $gatewayCondition['metaid'];
									$metaRec = $PMUtils->getMetaRecords($this->processid,$gatewayCondition['elementid'],$this->running_process);
									$cond_metaid = $metaRec[$gatewayCondition['elementid']]['id'];
								} else {
									list($cond_metaid,$cond_module) = explode(':',$gatewayCondition['metaid']);
								}
								//crmv@160805e
								$running_process = $this->running_process;
								if ($cond_module == 'DynaForm') {
									$cond_module = 'Processes';
									$processDynaFormObj = ProcessDynaForm::getInstance();
									// processo principale
									$crmid = $processDynaFormObj->getProcessesId($this->running_process,$cond_metaid);
									// verifico se sono in un parallelo
									$result_g = $adb->pquery("select processesid from {$table_prefix}_process_gateway_conn where running_process = ? and (processesid = ? or casperid = ?) and conditionssons like '%\"".$gatewayCondition['elementid']."\"%'", array($this->running_process,$crmid,$crmid));
									if ($result_g && $adb->num_rows($result_g) > 0) $crmid = $adb->query_result($result_g,0,'processesid');
								} else {
									$crmid = ProcessMakerEngine::getCrmid($cond_metaid,$this->running_process);
								}
								$wsId = vtws_getWebserviceEntityId($cond_module, $crmid);
							}
							$entityCache = $this->getEntityCache($wsId,$cond_metaid);
							foreach($gatewayCondition['conditions'] as $condition) {
								$cond = Zend_Json::encode(array(array('conditions'=>Zend_Json::decode($condition['json_condition']))));
								if ($PMUtils->evaluateCondition($entityCache, $wsId, $cond)) {
									$next = $condition['elementid'];
									break;
								}
							}
							// else
							if ($next === false && $this->vte_metadata['cond_else'] != '') {
								$next = $this->vte_metadata['cond_else'];
							}
						}
					}
				}
			}
			if (!empty($required2go) && $next !== false) {
				$this->setGatewayRequired($this->elementid,$this->prev_elementid,$next);
				$requiredCheck = $this->getGatewayRequired($this->elementid);
				foreach($required2go as $el) {
					if (!isset($requiredCheck[$el])) {
						$next = false;
						break;
					}
				}
			}
		}
		if (!empty($next)) {
			//crmv@103534 kill parallels fake processes
			$result = $adb->pquery("select distinct elementid from {$table_prefix}_process_gateway_conn where running_process = ?", array($this->running_process));
			if ($result && $adb->num_rows($result) > 0) {
				$change_entity = false;
				$vte_metadata = Zend_Json::decode($this->process_data['vte_metadata']);
				while($row=$adb->fetchByAssoc($result)) {
					$elementid = $row['elementid'];
					if ($vte_metadata[$elementid]['closing_gateway'] == $this->elementid) {
						//crmv@149014
						$result1 = $adb->pquery("select processesid from {$table_prefix}_process_gateway_conn where running_process = ? and elementid = ? and casperid is not null", array($this->running_process,$elementid));
						if ($result1 && $adb->num_rows($result1) > 0) {
							$focus = CRMEntity::getInstance('Processes');
							while($row1=$adb->fetchByAssoc($result1)) {
								$focus->trash('Processes',$row1['processesid']);
								if ($row1['processesid'] == $this->entity_id) $change_entity = true;
							}
						}
						//crmv@149014e
						// query spostata fuori dall'if in modo da svuotare tutta la coda sempre
						$adb->pquery("delete from {$table_prefix}_process_gateway_conn where running_process = ? and elementid = ?", array($this->running_process,$elementid));
					}
				}
				if ($change_entity) {
					// select another Processes not deleted
					$result = $adb->limitpQuery("SELECT processesid FROM {$table_prefix}_processes WHERE deleted = 0 AND processmaker = ? AND running_process = ? ORDER BY processesid", 0, 1, array($this->processid, $this->running_process)); //crmv@185647
					if ($result && $adb->num_rows($result) > 0) {
						$this->entity_id = $adb->query_result($result,0,'processesid');
						$webserviceObject = VtenextWebserviceObject::fromName($adb,'Processes');//crmv@207871
						$this->id = vtws_getId($webserviceObject->getEntityId(),$this->entity_id);
					}
				}
			}
			//crmv@103534e
			if (!is_array($next)) $next = array($next);
			foreach($next as $n) {
				$structure = $PMUtils->getStructure($this->processid,$this->running_process);
				$engineType = $PMUtils->getEngineType($structure['shapes'][$n]);
				$entityCache = $this->getEntityCache();
				$nextPMEngine = ProcessMakerEngine::getInstance($this->running_process,$this->processid,$this->elementid,$n,$this->id,$this->metaid,$entityCache);
				$nextPMEngine->execute($engineType,$structure['shapes'][$n]['type']);
			}
		}
	}
	
	function executeTimerIntermediate($bpmnType) {
		global $adb, $table_prefix;
		$PMUtils = ProcessMakerUtils::getInstance();
		
		// check if the timer is already registered
		// crmv@181433
		$occurrence = 0;
		if ($PMUtils->checkTimerExists('intermediate',$this->running_process,$this->prev_elementid,$this->elementid,$occurrence)) return;
		// crmv@181433e
		
		$this->trackProcess($this->prev_elementid,$this->elementid);
		
		//crmv@182148
		$datetime = 'now';
		if ($this->vte_metadata['trigger_date_type'] == 'date' && !empty($this->vte_metadata['trigger_date_value'])) {
			$datetime = $this->vte_metadata['trigger_date_value'];
			if (!empty($this->vte_metadata['trigger_hour_value'])) $datetime .= ' '.$this->vte_metadata['trigger_hour_value'];
		} elseif ($this->vte_metadata['trigger_date_type'] == 'other') {
			$datetime = $this->replaceTags('trigger_other_value',$this->vte_metadata['trigger_other_value'],array(),array());
		}
		$delay = new DateTime($datetime);
		$interval = 'P'.intval($this->vte_metadata['months']).'M'.intval($this->vte_metadata['days']).'D'.'T'.intval($this->vte_metadata['hours']).'H'.intval($this->vte_metadata['min']).'M'; //crmv@179893
		$dateInterval = new DateInterval($interval);
		if ($this->vte_metadata['trigger_direction'] == 'before') {
			$delay->sub($dateInterval);
		} else {
			$delay->add($dateInterval);
		}
		$timer_delay = $delay->format('Y-m-d H:i:s');
		//crmv@182148e
		
		//init id and metaid
		if (empty($this->id)) {
			// if next element is a condition use the record used there
			$outgoings = $PMUtils->getOutgoing($this->processid,$this->elementid,$this->running_process);
			if (!empty($outgoings)) {
				$outgoing = $outgoings[0];
				$entityCache = $this->getEntityCache();
				$nextPMEngine = ProcessMakerEngine::getInstance($this->running_process,$this->processid,$this->elementid,$outgoing['shape']['id'],$this->id,$this->metaid,$entityCache);
				if ($PMUtils->getEngineType($outgoing['shape']) == 'Condition') {
					$metadata = $PMUtils->getMetadata($this->processid,$outgoing['shape']['id'],$this->running_process);
					if (strpos($metadata['moduleName'],':') !== false) {
						list($metaid,$moduleName) = explode(':',$metadata['moduleName']);
					}
				}
			}
			if (!empty($metaid)) {
				$result = $adb->pquery("SELECT * FROM {$table_prefix}_processmaker_rec WHERE running_process = ? and id = ? ORDER BY id, crmid DESC", array($this->running_process,$metaid));
				if ($result && $adb->num_rows($result) > 0) {
					$crmid = $adb->query_result($result,0,'crmid');
					$webserviceObject = VtenextWebserviceObject::fromName($adb,getSalesEntityType($crmid,true));//crmv@207871
					$this->id = vtws_getId($webserviceObject->getEntityId(),$crmid);
					$this->metaid = $adb->query_result($result,0,'id');
				}
			} else {
				// else choose last rec created
				$result = $adb->pquery("SELECT * FROM {$table_prefix}_processmaker_rec WHERE running_process = ? ORDER BY id, crmid DESC", array($this->running_process));
				if ($result && $adb->num_rows($result) > 0) {
					$crmid = $adb->query_result($result,0,'crmid');
					$webserviceObject = VtenextWebserviceObject::fromName($adb,getSalesEntityType($crmid,true));//crmv@207871
					$this->id = vtws_getId($webserviceObject->getEntityId(),$crmid);
					$this->metaid = $adb->query_result($result,0,'id');
				}
			}
		}
		$info = array('processid'=>$this->processid,'prev_elementid'=>$this->prev_elementid,'elementid'=>$this->elementid,'id'=>$this->id,'metaid'=>$this->metaid,'running_process'=>$this->running_process);
		$PMUtils->createTimer('intermediate',$timer_delay,$this->running_process,$this->prev_elementid,$this->elementid,'',$info);	//crmv@127048
		
		$this->log("Set Timer Delay","interval:{$interval} datetime:$timer_delay");
	}
	
	function executeTimerBoundaryInterr($bpmnType) {
		$this->executeTimerBoundary($bpmnType,true);
	}
	function executeTimerBoundaryNonInterr($bpmnType) {
		$this->executeTimerBoundary($bpmnType,false);
	}
	function executeTimerBoundary($bpmnType, $cancelActivity) {
		global $adb, $table_prefix;
		$PMUtils = ProcessMakerUtils::getInstance();
		
		// check if the timer is already registered
		//crmv@105312
		$occurrence = 0;
		if ($PMUtils->checkTimerExists('boundary',$this->running_process,$this->prev_elementid,$this->elementid,$occurrence)) return;
		//crmv@105312e
		
		$delay = new DateTime();
		$interval = 'P'.intval($this->vte_metadata['months']).'M'.intval($this->vte_metadata['days']).'D'.'T'.intval($this->vte_metadata['hours']).'H'.intval($this->vte_metadata['min']).'M'; //crmv@179893
		$delay->add(new DateInterval($interval));
		$timer_delay = $delay->format('Y-m-d H:i:s');
		
		$info = array('processid'=>$this->processid,'prev_elementid'=>$this->prev_elementid,'elementid'=>$this->elementid,'id'=>$this->id,'metaid'=>$this->metaid,'running_process'=>$this->running_process,'cancelActivity'=>$cancelActivity);
		$PMUtils->createTimer('boundary',$timer_delay,$this->running_process,$this->prev_elementid,$this->elementid,$occurrence,$info);	//crmv@105312
		
		$this->log("Set Timer Boundary","interval:{$interval} datetime:$timer_delay");
	}
	
	//crmv@97575
	function executeSubProcess($bpmnType) {
		global $adb, $table_prefix;
		$PMUtils = ProcessMakerUtils::getInstance();
		$this->trackProcess($this->prev_elementid,$this->elementid);
		
		if (!empty($this->vte_metadata['subprocess'])) {
			$events = $PMUtils->getStartingEvents(false,'',$this->vte_metadata['subprocess']);
			$event = $events[0];
			$running_process = $event['running_process'];
			$processid = $event['processid'];
			$elementid = $event['elementid'];
			$incoming = $PMUtils->getIncoming($processid,$elementid,$running_process); //crmv@160368
			(!empty($incoming)) ? $current_elementid = $incoming[0]['shape']['id'] : $current_elementid = false;
			$structure = $PMUtils->getStructure($processid,$running_process);
			$engineType = $PMUtils->getEngineType($structure['shapes'][$elementid]);
			
			if ($engineType == 'Condition' && $event['metadata']['execution_condition'] == 'ON_SUBPROCESS') {
				// if the start element is a condition execute next elements of the condition
				$entityCache = $this->getEntityCache();
				$PMEngine = ProcessMakerEngine::getInstance($running_process,$processid,$current_elementid,$elementid,$this->id,$event['metaid'],$entityCache);
				ProcessMakerHandler::$running_processes[] = array('new'=>true,'running_process'=>$PMEngine->running_process,'processid'=>$processid,'record'=>$this->entity_id,'metaid'=>$event['metaid']);
				//crmv@161211
				if ($PMUtils->getRunningProcess($this->entity_id,$this->metaid,$this->processid) != $this->running_process) {
					ProcessMakerHandler::$running_processes[] = array('new'=>false,'running_process'=>$this->running_process,'processid'=>$this->processid,'record'=>$this->entity_id,'metaid'=>$this->metaid);
				}
				//crmv@161211e
				$PMEngine->trackProcess($current_elementid,$elementid);
				$PMEngine->executeNextSteps();
			} elseif ($engineType == 'Action') {
				// if the start element is an action execute it
				$entityCache = $this->getEntityCache();
				$PMEngine = ProcessMakerEngine::getInstance($running_process,$processid,$current_elementid,$elementid,$this->id,$event['metaid'],$entityCache);
				ProcessMakerHandler::$running_processes[] = array('new'=>true,'running_process'=>$PMEngine->running_process,'processid'=>$processid,'record'=>$this->entity_id,'metaid'=>$event['metaid']);
				//crmv@161211
				if ($PMUtils->getRunningProcess($this->entity_id,$this->metaid,$this->processid) != $this->running_process) {
					ProcessMakerHandler::$running_processes[] = array('new'=>false,'running_process'=>$this->running_process,'processid'=>$this->processid,'record'=>$this->entity_id,'metaid'=>$this->metaid);
				}
				//crmv@161211e
				$PMEngine->execute($engineType,$structure['shapes'][$elementid]['type']);
			}
		}
		// execute next steps
		$this->executeNextSteps();
	}
	//crmv@97575e
	
	function getRunningProcess() {
		global $adb, $table_prefix;
		$PMUtils = ProcessMakerUtils::getInstance();
		$running_process = $PMUtils->getRunningProcess($this->entity_id,$this->metaid,$this->processid);
		if ($running_process === false) {
			$running_process = $adb->getUniqueID("{$table_prefix}_running_processes");
		}
		return $running_process;
	}
	
	function queueProcessHelper() {
		$process_helper = array();
		if ($this->helper['active'] == 'on') {
			if ($this->helper['assigned_user_id'] != '') $process_helper['assigned_user_id'] = $this->helper['assigned_user_id'];
			if ($this->helper['sdk_params_assigned_user_id'] != '') $process_helper['sdk_params_assigned_user_id'] = $this->helper['sdk_params_assigned_user_id'];	//crmv@113527
			if ($this->helper['description'] != '') $process_helper['requested_action'] = $this->helper['description'];
			if ($this->helper['related_to'] != '') $process_helper['related_to'] = $this->helper['related_to'];
			if ($this->helper['process_status'] != '') $process_helper['process_status'] = $this->helper['process_status'];	//crmv@103450
			if ($this->helper['process_name_mass_edit_check'] == 'on' && $this->helper['process_name'] != '') $process_helper['process_name'] = $this->helper['process_name'];	//crmv@109685
			if (!empty($process_helper)) {
				$process_helper['processmaker'] = $this->processid;
				$process_helper['running_process'] = $this->running_process;
				if (empty($process_helper['process_name'])) $process_helper['process_name'] = $this->process_data['name'];	//crmv@109685
			}
		}
		return $process_helper;
	}
	
	//crmv@103534
	function saveProcessHelper($processHelper) {
		global $adb, $table_prefix, $current_user;
		if (!empty($processHelper)) {
			//crmv@135193: check skip_notifications flag
			$PMUtils = ProcessMakerUtils::getInstance();
			if ($PMUtils->skip_notifications_helper) {
				global $global_skip_notifications;
				$tmp_global_skip_notifications = $global_skip_notifications;
				$global_skip_notifications = true;
			}
			//crmv@135193e
			// check if I am in a parallel flow
			$primary = true;
			$processesid = false;
			$parallel = false;
			$result = $adb->pquery("select * from {$table_prefix}_process_gateway_conn where running_process = ? order by seq", array($this->running_process));
			if ($result && $adb->num_rows($result) > 0) {
				// foreach flow there is a different process: a primary and the others phantoms of the primary
				while($row=$adb->fetchByAssoc($result,-1,false)) {
					$seq = $row['seq'];
					$gateway = $row['elementid'];
					$flow = $row['flow'];
					$elementsons = Zend_Json::decode($row['elementsons']);
					if (in_array($this->elementid,$elementsons)) {
						$parallel = true;
						$processesid = $row['processesid'];
						//crmv@149014
						$checkPrimary = $adb->pquery("select processesid from {$table_prefix}_process_gateway_conn where running_process = ? and elementid = ? and processesid is not null", array($this->running_process,$gateway));
						if ($checkPrimary && $adb->num_rows($checkPrimary) == 0 && !empty($row['primary_processesid'])) {
							// se tutte le righe della _process_gateway_conn hanno processesid vuoto allora il primo ramo che viene eseguito diventa il primary
							$primary = true;
							$processesid = $row['primary_processesid'];
						} elseif (empty($row['primary_processesid']) || $row['primary_processesid'] == $processesid) {
							// se non esiste un processo prima di entrare nel parallelo allora il primo ramo che ne crea un processhelper diventa il primary
							$primary = true;
							//crmv@149014e
						} else {
							$primary = false;
							$casperid = $row['casperid'];
							if (empty($casperid)) {
								$resultCasper = $adb->pquery("select processesid from {$table_prefix}_process_gateway_conn where running_process = ? and elementid = ? and processesid is not null order by seq", array($this->running_process,$gateway));
								if ($resultCasper && $adb->num_rows($resultCasper) > 0) {
									$casperid = $adb->query_result($resultCasper,0,'processesid');
								}
							}
						}
						break;
					}
				}
			}
			if ($primary && empty($processesid)) {
				// search if I am inside to another parallel flow
				if ($parallel) {
					$PMUtils = ProcessMakerUtils::getInstance();
					$processesid = $PMUtils->searchParentParallelFlow($this->running_process,$gateway);
				}
				if (empty($processesid)) {
					$result = $adb->pquery("select processesid from {$table_prefix}_processes where deleted = 0 and running_process = ? order by processesid", array($this->running_process)); //crmv@185647
					if ($result && $adb->num_rows($result) > 0) {
						$processesid = $adb->query_result($result,0,'processesid');
					}
				}
			}
			$request = $_REQUEST; $_REQUEST = array();	// preserve request
			$focus = CRMEntity::getInstance('Processes');
			if (!empty($processesid)) {
				$focus->retrieve_entity_info_no_html($processesid,'Processes');
				$focus->mode = 'edit';
				unset($processHelper['processmaker']);
				unset($processHelper['running_process']);
			} else {
				// in create force assigned_user_id if empty
				if (empty($processHelper['assigned_user_id'])) {
					$processHelper['assigned_user_id'] = $current_user->id;
				}
			}
			foreach($processHelper as $fieldname => $value) {
				if ($fieldname == 'related_to') {
					//crmv@135190 crmv@160843
					if (strpos($value,'$') === 0) {
						$crmid = $this->replaceTags($fieldname,$value,array('related_to'),array());
					} else {
						list($metaid,$module,$reference) = explode(':',$value);
						if (!empty($reference)) {
							$crmid = $this->getCrmid($metaid,null,$reference);
							if ($crmid === false) $crmid = $this->getCrmid($metaid);
						} else {
							$crmid = $this->getCrmid($metaid);
						}
					}
					//crmv@135190e crmv@160843e
					if ($crmid !== false) $value = $crmid;
				} elseif ($fieldname == 'assigned_user_id') {
					$value = $this->getResource($value);
					//crmv@109685
				} elseif (in_array($fieldname,array('process_name','requested_action'))) {
					$value = $this->replaceTags($fieldname,$value,array(),array());
					//crmv@109685e
				}
				$focus->column_fields[$fieldname] = $value;
			}
			$focus->engine = $this;
			$focus->parallel = $parallel;
			$focus->primary = $primary;
			$focus->casperid = $casperid;
			$focus->gateway = $gateway;
			$focus->flow = $flow;
			$focus->save('Processes');
			//crmv@149014
			$processesid = $focus->id;
			
			if ($primary && !empty($gateway)) {
				$query = "update {$table_prefix}_process_gateway_conn set primary_processesid = ? where running_process = ? and elementid = ?";
				$adb->pquery($query,array($processesid,$this->running_process,$gateway));
			}
			//crmv@149014e
			
			$_REQUEST = $request;	// restore request
			//crmv@135193: restore skip_notifications flag
			$global_skip_notifications = $tmp_global_skip_notifications;
			//crmv@135193e
		}
	}
	
	function isEndProcess($bpmnType) {
		global $adb, $table_prefix;
		$ended = false;
		$PMUtils = ProcessMakerUtils::getInstance();
		if ($PMUtils->isEndTask($bpmnType)) {
			// crmv@129940
			$currentCol = 'current';
			$adb->format_columns($currentCol);
			$result = $adb->pquery("select $currentCol from {$table_prefix}_running_processes where id = ?", array($this->running_process));
			// crmv@129940e
			if ($result && $adb->num_rows($result) > 0) {
				$current = $adb->query_result($result,0,'current');
				if (strpos($current,'|##|') !== false) {
					$current = explode('|##|',$current);
					$PMUtils = ProcessMakerUtils::getInstance();
					$structure = $PMUtils->getStructure($this->processid,$this->running_process);
					foreach($current as $elementid) {
						$bpmnType = $PMUtils->formatType($structure['shapes'][$elementid]['type']);
						if ($PMUtils->isEndTask($bpmnType)) {
							$ended = true;
						} else {
							$ended = false;
							break;
						}
					}
				} else {
					$ended = true;
				}
			}
		}
		return $ended;
	}
	
	//crmv@112539
	function endProcess($end=1) {
		global $adb, $table_prefix;
		// crmv@129940
		$endCol = 'end';
		$adb->format_columns($endCol);
		$adb->pquery("update {$table_prefix}_running_processes set $endCol = ? where id = ?", array($end,$this->running_process));
		// crmv@129940e
		if ($end == 1) {
			//$adb->pquery("delete from {$table_prefix}_process_adv_permissions where running_process = ? and elementid <> ?", array($this->running_process,$current));	////crmv@100731 TODO check for parallels
			//crmv@178000
			$PMUtils = ProcessMakerUtils::getInstance();
			$PMUtils->deleteTimer('boundary',$this->running_process);
			$PMUtils->deleteTimer('intermediate',$this->running_process);
			//crmv@178000e
			$this->log("End Process");
		} else {
			$this->log("Process re-started");
		}
	}
	function activateProcess($mode=true) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_running_processes set active = ? where id = ?", array(($mode)?1:0,$this->running_process));
		if ($mode) {
			$this->log("Activate Process");
		} else {
			$this->log("Disactivate Process");
		}
	}
	//crmv@112539e
	
	//crmv@135190 crmv@160368
	function getCrmid($metaid,$running_process='',$fieldid='',$meta_processid='') {
		global $adb, $table_prefix;
		if (empty($running_process) && isset($this)) $running_process = $this->running_process; //crmv@159135 only if you call getCrmid as method
		if (!empty($meta_processid)) {
			$result = $adb->pquery("select father from {$table_prefix}_running_processes where id = ? and father is not null and father <> ''", array($running_process));
			if ($result && $adb->num_rows($result) > 0) {
				$running_process = $adb->query_result($result,0,'father');
			}
		}
		if (!empty($running_process)) {
			$result = $adb->limitpQuery("select crmid from {$table_prefix}_processmaker_rec where id = ? and running_process = ? order by crmid desc", 0, 1, array($metaid,$running_process));	//crmv@105312
			if ($result && $adb->num_rows($result) > 0) {
				$crmid = $adb->query_result($result,0,'crmid');
				if (!empty($fieldid)) {
					$result1 = $adb->pquery("select columnname, tablename, {$table_prefix}_tab.name from {$table_prefix}_field inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid where fieldid = ?", array($fieldid));
					if ($result1 && $adb->num_rows($result1) > 0) {
						$tablename = $adb->query_result($result1,0,'tablename');
						$columnname = $adb->query_result($result1,0,'columnname');
						$focus = CRMEntity::getInstance($adb->query_result($result1,0,'name'));
						$related = getSingleFieldValue($tablename, $columnname, $focus->tab_name_index[$tablename], $crmid);
						if (empty($related))
							return false;
						else
							return $related;
					}
				}
				return $crmid;
			}
		}
		return false;
	}
	//crmv@135190e crmv@160368e
	
	function getResource($value) {
		if (!is_numeric($value)) {
			$PMUtils = ProcessMakerUtils::getInstance();
			if (strpos($value,':') == 3) {	// old mode
				list($meta_processid,$metaid,$module,$user_fieldname) = explode(':',$value);
				$meta_processid = str_replace('$', '', $meta_processid); //crmv@206002
				(empty($meta_processid)) ? $running_process = '' : $running_process = $PMUtils->getRelatedRunningProcess($this->running_process,$this->processid,$meta_processid);
				$crmid = $this->getCrmid($metaid,$running_process);
				//crmv@206002
                if($module === null){
                    $module = getSalesEntityType($this->entity_id,true);
                }
                //crmv@206002e
				if ($crmid !== false) {
					$entityFocus = CRMEntity::getInstance($module);
					$entityFocus->retrieve_entity_info($crmid,$module);
					$value = $entityFocus->column_fields[$user_fieldname];
				}
			} else {
				$value = $this->replaceTags('assigned_user_id',$value,array(),array('assigned_user_id'));
			}
		}
		return $value;
	}
	
	function setGatewayRequired($gateway_elementid,$prev_elementid,$next_elementid) {
		global $adb, $table_prefix;
		$adb->pquery("insert ignore into {$table_prefix}_process_gateway_req values (?,?,?,?)", array($this->running_process,$gateway_elementid,$prev_elementid,$next_elementid));
	}
	
	function getGatewayRequired($gateway_elementid) {
		global $adb, $table_prefix;
		$return = array();
		$result = $adb->pquery("select prev_elementid, next_elementid from {$table_prefix}_process_gateway_req where running_process = ? and gateway_elementid = ?", array($this->running_process,$gateway_elementid));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByASsoc($result)) {
				$return[$row['prev_elementid']] = $row['next_elementid'];
			}
		}
		return $return;
	}
	
	// crmv@102879
	function replaceTags($fieldname,$value,$referenceFields,$ownerFields,$actionid=false,$cycleIndex=null,$advanced_field_condition=false,$prepareValue=true,$cycle_related_crmid=null, $toHtml=false) {	//crmv@106856 crmv@177561 crmv@203075 crmv@205151
		static $cacheWsEntities = array();
		global $current_user, $adb;
		$PMUtils = ProcessMakerUtils::getInstance();
		//crmv@105312
		global $engine, $current_process_actionid;
		$engine = $this;
		$current_process_actionid = $actionid;
		//crmv@105312e
		$entityCache = $this->getEntityCache();
		$PMUtils->setDefaultDataFormat();
		
		//crmv@106856
		if ($value == 'advanced_field_assignment') {
			$advanced_value = false;
			if ($actionid !== false) {
				$conditions = $this->vte_metadata['actions'][$actionid]['advanced_field_assignment'][$fieldname];
			} else {
				$conditions = $this->helper['advanced_field_assignment'][$fieldname];
			}
			if (!empty($conditions)) {
				foreach($conditions as $conditionid => $condition) {
					$cond = Zend_Json::encode($condition['conditions']);
					list($metaid,$module) = explode(':',$condition['meta_record']);
					if ($module == 'DynaForm') {
						$processDynaFormObj = ProcessDynaForm::getInstance();
						$crmid = $processDynaFormObj->getProcessesId($this->running_process,$metaid);
						
						$webserviceObject = VtenextWebserviceObject::fromName($adb,'Processes');//crmv@207871
						$wsId = vtws_getId($webserviceObject->getEntityId(),$crmid);
						$entityData = $entityCache->forId($wsId);
						
						require_once("include/events/include.inc");
						require_once("modules/Settings/ProcessMaker/ProcessMakerHandler.php");
						$processMakerHandler = new ProcessMakerHandler();
						$processMakerHandler->addDynaFormData($entityData, array(
								'processid' => $this->processid,
								'dynaformmetaid' => $metaid,
								'dynaformvalues' => $processDynaFormObj->getValues($this->running_process, $metaid),
						));
						
						if ($PMUtils->evaluateCondition($entityCache, $wsId, $cond)) {
							$advanced_value = $condition['value'];
							$advanced_field_condition = $conditionid;
							break;
						}
					} else {
						$crmid = $this->getCrmid($metaid,$this->running_process);
						$webserviceObject = VtenextWebserviceObject::fromName($adb,$module);//crmv@207871
						$wsId = vtws_getId($webserviceObject->getEntityId(),$crmid);
						if ($PMUtils->evaluateCondition($entityCache, $wsId, $cond)) {
							$advanced_value = $condition['value'];
							$advanced_field_condition = $conditionid;
							break;
						}
					}
				}
			}
			if ($advanced_value !== false) {
				return $this->replaceTags($fieldname,$advanced_value,$referenceFields,$ownerFields,$actionid,$cycleIndex,$advanced_field_condition);
			}
		}
		//crmv@106856e
		// replace tags
		preg_match_all('/(\$([0-9:]+)-([a-zA-Z0-9_)( :]+))/', $value, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			$PMUtils->sortTags($matches); //crmv@163717 crmv@182554
			foreach($matches as $match) {
				$tag = trim($match[0]);
				$tag_metaid = $tag_metaid_orig = $match[2]; //crmv@159329
				//crmv@149520 crmv@151737_5
				$tag_fieldname = trim($match[3]);
				if (substr_count($tag_fieldname,')') == (substr_count($tag_fieldname,'(') + 1)) {
					$pos = strrpos($tag,')');
					if (strrpos($tag,')') !== false) $tag = substr_replace($tag,'',$pos,strlen(')'));
					$pos = strrpos($tag_fieldname,')');
					if(strrpos($tag_fieldname,')') !== false) $tag_fieldname = substr_replace($tag_fieldname,'',$pos,strlen(')'));
				} elseif (substr_count($tag_fieldname,'(') != substr_count($tag_fieldname,')')) {
					$tag = trim(trim($tag,')'));
					$tag_fieldname = trim(trim($tag_fieldname,')'));
				}
				//crmv@149520e crmv@151737_5e
				if (strpos($tag_metaid,':') === false) {
					$running_process = $engine->running_process;
				} else {
					list($meta_processid,$tag_metaid) = explode(':',$tag_metaid);
					$running_process = $PMUtils->getRelatedRunningProcess($engine->running_process,$engine->processid,$meta_processid);
				}
				if (!isset($cacheWsEntities[$running_process][$tag_metaid]) || $cycle_related_crmid != null) {//crmv@203075
					$mrecord = $engine->getCrmid($tag_metaid,$running_process);
					//crmv@203075
					if(empty($mrecord) && $cycle_related_crmid != null)
					    $mrecord = $cycle_related_crmid;
					//crmv@203075e

					if (!empty($mrecord)) {
						$cacheWsEntities[$running_process][$tag_metaid]['entityId'] = vtws_getWebserviceEntityId(getSalesEntityType($mrecord,true),$mrecord);
					}
				}
				$entityId = $cacheWsEntities[$running_process][$tag_metaid]['entityId'];
				if (!empty($entityId)) {
					if (strpos($tag_fieldname,'::') !== false) {	//crmv@149520
						// table field
						//crmv@182891 crmv@195745
						$matches2 = $matches3 = [];
						preg_match('/\((\w+) : \(([_\w]+)\) (\w+)::(\w+)\)/', $tag_fieldname, $matches2);
						
						if (count($matches2) == 0) {
							// ex: (ml1::f3 : (Accounts) accountname):last
							preg_match('/\((\w+)::(\w+) : \(([_\w]+)\) (\w+)\)/', $tag_fieldname, $matches3);
						}
						
						if (count($matches2) > 0) {
							// ex. (parent_id : (Accounts) ml3::f8):curr
							list($entityWsModule,$entityCrmid) = explode('x',$entityId);
							$entityModule = getSalesEntityType($entityCrmid,true);
							$entityFocus = CRMEntity::getInstance($entityModule);
							$fieldInstance = Vtecrm_Field::getInstance($matches2[1], Vtecrm_Module::getInstance($entityModule));
							$tmp = getSingleFieldValue($fieldInstance->table, $fieldInstance->column, $entityFocus->tab_name_index[$fieldInstance->table], $entityCrmid); // crmv@194759
							$entityId = vtws_getWebserviceEntityId(getSalesEntityType($tmp,true),$tmp);
							$tfield = $matches2[3];
							$tcol = $matches2[4];
						} elseif (count($matches3) > 0) {
 							$tfield = $matches3[1];
 							$tcol = $matches3[2];
 							$relmod = $matches3[3];
 							$relfield = $matches3[4];
						} else {
							list($tfield, $tcol) = explode('::', $tag_fieldname);	//crmv@149520
							if (($sp = strpos($tcol, ' ')) !== false) {
								$tcol = substr($tcol, 0, $sp);
							}
						}
						//crmv@182891e
						
						$tfield = ltrim($tfield, '(');
						if ($tfield == 'prodblock') {
							/*if (empty($relmod)) {
								list($entityWsModule,$entityCrmid) = explode('x',$entityId);
								$relmod = getSalesEntityType($entityCrmid,true);
							}*/
							$replacement = $PMUtils->replacePBlockFieldTag($entityId, $tfield, $tcol, $cycleIndex, $relmod, $relfield);
						} else {
							$replacement = $PMUtils->replaceTableFieldTag($entityId, $tfield, $tcol, $cycleIndex, $relmod, $relfield);
						}
						// crmv@195745e
						if ($toHtml) $replacement = $this->replacementToHtml($replacement); // crmv@205151
						$value = str_replace($tag,$replacement,$value);
					} else {
						$simpleTag = str_replace('$'.$tag_metaid_orig.'-','$',$tag); //crmv@159329
						$st = new VTSimpleTemplate($simpleTag,$prepareValue); //crmv@177561
						$replacement = $st->render($entityCache,$entityId);
						if ($tag != $replacement && (in_array($fieldname,$referenceFields) || in_array($fieldname,$ownerFields)) && stripos($replacement,'x') !== false) {
							list($wsModule,$replacement) = explode('x',$replacement);
						}
						if ($toHtml) $replacement = $this->replacementToHtml($replacement); // crmv@205151
						$value = str_replace($tag,$replacement,$value);
					}
				}
			}
		}
		// end
		// replace dynaform tags
		//preg_match_all('/(\$DF([0-9]+)-([a-zA-Z0-9_)( :]+))/', $value, $matches, PREG_SET_ORDER);
		preg_match_all('/(\$DF([0-9:]+)-([a-zA-Z0-9_)( :]+))/', $value, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			$PMUtils->sortTags($matches); //crmv@163717 crmv@182554
			foreach($matches as $match) {
				$tag = trim($match[0]);
				$dynaform_metaid = $match[2];
				$dynaform_fieldname = trim($match[3]);
				//crmv@149520 crmv@151737_5
				if (substr_count($dynaform_fieldname,')') == (substr_count($dynaform_fieldname,'(') + 1)) {
					$pos = strrpos($tag,')');
					if (strrpos($tag,')') !== false) $tag = substr_replace($tag,'',$pos,strlen(')'));
					$pos = strrpos($dynaform_fieldname,')');
					if(strrpos($dynaform_fieldname,')') !== false) $dynaform_fieldname= substr_replace($dynaform_fieldname,'',$pos,strlen(')'));
				} elseif (substr_count($dynaform_fieldname,'(') != substr_count($dynaform_fieldname,')')) {
					$tag = trim(trim($tag,')'));
					$dynaform_fieldname = trim(trim($dynaform_fieldname,')'));
				}
				//crmv@149520e crmv@151737_5e
				if (strpos($dynaform_metaid,':') === false) {
					$running_process = $engine->running_process;
				} else {
					list($meta_processid,$dynaform_metaid) = explode(':',$dynaform_metaid);
					$running_process = $PMUtils->getRelatedRunningProcess($engine->running_process,$engine->processid,$meta_processid);
				}
				$processDynaFormObj = ProcessDynaForm::getInstance();
				$dynaform_values = $processDynaFormObj->getValues($running_process,$dynaform_metaid);
				if (strpos($dynaform_fieldname, '::') !== false) {
					// table field
					list($tfield, $tcol) = explode('::', $dynaform_fieldname);
					if (($sp = strpos($tcol, ' ')) !== false) {
						$tcol = substr($tcol, 0, $sp);
					}
					$replacement = $PMUtils->applyTableFieldFunct('dynaform', $dynaform_values[$tfield], $tfield, $tcol, $cycleIndex);
					if ($toHtml) $replacement = $this->replacementToHtml($replacement); // crmv@205151
					$value = str_replace($tag,$replacement,$value);
				} else {
					// crmv@176245
					preg_match('/\((\w+) : \(([_\w]+)\) (\w+)\)/', $dynaform_fieldname, $reference_matches);
					if (count($reference_matches) > 0) {
						$referenceId = $dynaform_values[$reference_matches[1]];
						$referenceFocus = CRMEntity::getInstance($reference_matches[2]);
						$error = $referenceFocus->retrieve_entity_info_no_html($referenceId,$reference_matches[2],false);
						(empty($error)) ? $referenceValue = $referenceFocus->column_fields[$reference_matches[3]] : $referenceValue = '';
						$value = str_replace($tag,$referenceValue,$value);
					} else {
						// crmv@176245e
						// crmv@177561
						$uitype = null;
						$blocks = $processDynaFormObj->getStructure($engine->processid, false, $dynaform_metaid, $running_process);
						foreach($blocks as $block) {
							foreach($block['fields'] as $field) {
								if ($field['fieldname'] == $dynaform_fieldname) $uitype = $field['uitype'];
							}
						}
						if (!empty($prepareValue)) {
							$st = new VTSimpleTemplate('',true);
							$dynaform_values[$dynaform_fieldname] = $st->prepareValue($dynaform_values[$dynaform_fieldname], $dynaform_fieldname, '', $dynaform_values, $uitype);
						}
						// crmv@177561e
						$value = str_replace($tag,$dynaform_values[$dynaform_fieldname],$value);
					}
				}
			}
		}
		// end
		// crmv@146671
		// replace Webservice tags
		preg_match_all('/(\$WS([0-9:]+)-([a-zA-Z0-9_)( :]+))/', $value, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach($matches as $match) {
				$tag = trim($match[0]);
				$metaid = $match[2];
				$fieldname = trim($match[3]);
				//crmv@149520
				if (substr_count($fieldname,'(') != substr_count($fieldname,')')) {
					$tag = trim(trim($tag,')'));
					$fieldname = trim(trim($fieldname,')'));
				}
				//crmv@149520e
				if (strpos($metaid,':') === false) {
					$running_process = $engine->running_process;
				} else {
					list($meta_processid,$metaid) = explode(':',$metaid);
					$running_process = $PMUtils->getRelatedRunningProcess($engine->running_process,$engine->processid,$meta_processid);
				}
				$wsvalues = $PMUtils->getExtWSResultValues($running_process,$metaid,$actionid,$this); // crmv@147433
				$value = str_replace($tag,$wsvalues[$fieldname],$value);
			}
		}
		// crmv@146671e
		//crmv@100591 replace actors tags
		preg_match_all('/(\$ACTOR-([a-zA-Z0-9_]+))/', $value, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach($matches as $match) {
				$tag = trim($match[0]);
				$elementid = trim($match[2]);
				$replacement = $PMUtils->getActor($engine->running_process, $elementid);
				if (!empty($replacement)) {
					$value = str_replace($tag,$replacement,$value);
				}
			}
		}
		//crmv@100591e
		// apply sdk functions
		preg_match_all('/\$sdk:([a-zA-Z0-9_]+)\(([^)]*)\)/', $value, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			$sdkFieldConditions = SDK::getProcessMakerFieldActions();
			foreach($matches as $match) {
				$tag = trim($match[0]);
				$funct = $match[1];
				if (isset($sdkFieldConditions[$funct])) {
					$params = trim($match[2]);
					//crmv@113527
					if (empty($params)) {
						if ($actionid !== false && $advanced_field_condition !== false) {
							$params = trim($this->vte_metadata['actions'][$actionid]['advanced_field_assignment'][$fieldname][$advanced_field_condition]['sdk_params']);
						} elseif ($actionid !== false) {
							$params = trim($this->vte_metadata['actions'][$actionid]['sdk_params'][$fieldname]);
						} elseif ($advanced_field_condition !== false) {
							$params = trim($this->helper['advanced_field_assignment'][$fieldname][$advanced_field_condition]['sdk_params']);
						} else {
							$params = trim($this->helper['sdk_params_'.$fieldname]);
						}
						if (!empty($params)) {
							$params = $this->replaceTags($fieldname,$params,$referenceFields,$ownerFields,$actionid,$cycleIndex);
						}
					}
					//crmv@113527e
					if (!empty($params)) {
						$params = explode(',',$params);
						array_walk($params, function(&$v,$k) {
							$v = trim($v);
						});
					} else {
						$params = array();
					}
					require_once($sdkFieldConditions[$funct]['src']);
					$replacement = call_user_func_array($funct, $params);
				} else {
					$replacement = '';
				}
				$value = str_replace($tag,$replacement,$value);
			}
		}
		// end
		$value = str_replace('[\n]',"\n",$value);	//crmv@128159
		$PMUtils->restoreDataFormat();
		return $value;
	}
	// crmv@102879e
	
	// crmv@205151
	/**
	 * Function used when replacing a variable that has to be seen/sent in html format (eg: email action)
	 */
	protected function replacementToHtml($replacement) {
		// add here other translations, if needed
		return nl2br($replacement);
	}
	// crmv@205151e
	
	//crmv@115579
	function isParallelCondition($running_process, $elementid) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select conditionssons from {$table_prefix}_process_gateway_conn where running_process = ?", array($running_process));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$conditionssons = Zend_Json::decode($row['conditionssons']);
				if (in_array($elementid,$conditionssons)) {
					return true;
				}
			}
		}
		return false;
	}
	//crmv@115579e
}
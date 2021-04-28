<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@97566 crmv@123809 */

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
require_once('modules/Settings/ProcessMaker/ProcessMakerEngine.php');
require_once('modules/Users/Users.php');

global $adb, $table_prefix, $current_user;

$bk_current_user = false;
if ($current_user) $bk_current_user = $current_user;
$current_user = Users::getActiveAdminUser();

require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
$entityCache = new VTEntityCache($current_user);

$PMUtils = ProcessMakerUtils::getInstance();

/* TODO : eliminare da _running_processes_timer
 * i timer boundary con executed 1 di running_process terminati
 * i timer intermediate di running_process terminati (per gli intermediate non viene gestito il flag executed)
 */

//crmv@173270 check errors in boundary timers
$result = $adb->pquery("select id, current FROM {$table_prefix}_running_processes where end = 0 and current LIKE ?", array('%BoundaryEvent_%'));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$running_process = $row['id'];
		$boundary_elements = explode('|##|',$row['current']);
		foreach($boundary_elements as $boundary_element) {
			$result1 = $adb->limitpQuery("select id, prev_elementid, executed from {$table_prefix}_running_processes_timer where mode = ? and running_process = ? and elementid = ? order by id desc", 0, 1, array('boundary',$running_process,$boundary_element));
			if ($result1 && $adb->num_rows($result1) > 0) {
				$timerid = $adb->query_result($result1,0,'id');
				$prev_elementid = $adb->query_result($result1,0,'prev_elementid');
				$executed = $adb->query_result($result1,0,'executed');
				if ($executed == 1) {
					$new_current = str_replace($boundary_element,$prev_elementid,$row['current']);
					$adb->pquery("update {$table_prefix}_running_processes_timer set executed = 0 where id = ?", array($timerid));
					$adb->pquery("update {$table_prefix}_running_processes set current = ? where id = ?", array($new_current,$running_process));
				}
			}
		}
	}
}
//crmv@173270e

// execute timers
$result = $adb->pquery("SELECT {$table_prefix}_running_processes_timer.*
	FROM {$table_prefix}_running_processes_timer
	INNER JOIN {$table_prefix}_running_processes ON {$table_prefix}_running_processes.id = {$table_prefix}_running_processes_timer.running_process
	INNER JOIN {$table_prefix}_processmaker ON {$table_prefix}_running_processes.processmakerid = {$table_prefix}_processmaker.id
	WHERE {$table_prefix}_processmaker.active = ? AND timer <= ? and executed = ?", array(1,$adb->formatDate(date('Y-m-d H:i:s'), true),0));

if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		// set immediately executed = 1 in order to prevent stop of the cron in case of errors
		//crmv@178000
		$result_upd = $adb->pquery("update {$table_prefix}_running_processes_timer set executed = ? where id = ?", array(1,$row['id']));
		if ($adb->getAffectedRowCount($result_upd) == 0) continue;
		//crmv@178000e
		
		$info = Zend_Json::decode($row['info']);
		// crmv@189720
		if (!empty($info['id'])) {
			list($wsModId,$crmid) = explode('x',$info['id']);
			// crmv@204789
			$result_ws = $adb->pquery("select name from {$table_prefix}_ws_entity where id=?",array($wsModId));
			if ($result_ws) {
				$module = $adb->query_result($result_ws,0,'name');
				$focus = CRMEntity::getInstance($module);
				$r = $focus->checkRetrieve($crmid, $module, false);
				if ($r !== null) continue;
			}
			// crmv@204789e
			require_once('data/VTEntityDelta.php');
			$entityDelta = new VTEntityDelta();
			$entityDelta->setOldEntity(getSalesEntityType($crmid),$crmid);
		}
		// crmv@189720e
		$PMEngine = ProcessMakerEngine::getInstance($info['running_process'],$info['processid'],$info['prev_elementid'],$info['elementid'],$info['id'],$info['metaid'],$entityCache);
		if ($row['mode'] == 'start') {
			$PMEngine->log("StartEvent Timer","elementid:{$info['elementid']} timer:{$row['timer']}");
			$vte_metadata = $PMEngine->vte_metadata;
		} elseif ($row['mode'] == 'intermediate') {
			$PMEngine->log("Timer Delay ends","elementid:{$info['elementid']} timer:{$row['timer']}");
		} elseif ($row['mode'] == 'boundary') {
			$PMEngine->log("Timer Boundary ends","elementid:{$info['elementid']} timer:{$row['timer']}");
			if ($info['cancelActivity'] === true) {
				$PMEngine->trackProcess($info['prev_elementid'],$info['elementid']);
			} else {
				$PMEngine->trackProcess($info['prev_elementid'],implode('|##|',array($info['prev_elementid'],$info['elementid'])));
			}
		}
		$outgoings = $PMUtils->getOutgoing($info['processid'],$info['elementid'],$info['running_process']); //crmv@150751
		if (!empty($outgoings)) {
			$outgoing = $outgoings[0];
			$engineType = $PMUtils->getEngineType($outgoing['shape']);
			$PMEngine = ProcessMakerEngine::getInstance($info['running_process'],$info['processid'],$info['elementid'],$outgoing['shape']['id'],$info['id'],$info['metaid'],$entityCache);
			//crmv@178000 check if timer boundary is not deleted
			if ($row['mode'] == 'boundary') {
				$result_check = $adb->pquery("select id from {$table_prefix}_running_processes_timer where id = ?", array($row['id']));
				if ($result_check && $adb->num_rows($result_check) == 0) continue;
			}
			//crmv@178000e
			$PMEngine->execute($engineType,$outgoing['shape']['type']);
		}
		if ($row['mode'] == 'start') {
			// delete current timer
			$adb->pquery("delete from {$table_prefix}_running_processes_timer where id = ?", array($row['id']));
			if ($PMEngine->process_data['active'] == 1) {
				// schedule the next occourence
				if ($info['calculate_next_occourence'] === true) {
					($vte_metadata['date_end_mass_edit_check'] == 'on') ? $date_end = getValidDBInsertDateValue($vte_metadata['date_end']).' '.$vte_metadata['endhr'].':'.$vte_metadata['endmin'] : $date_end = false;
					$date_start = $row['timer'];
					$timer = $PMUtils->getTimerRecurrences($date_start,$date_end,($vte_metadata['recurrence'] == 'on'),$vte_metadata['cron_value'],2);
					$timer = $timer[1];
					// check if the next occourence is in the past
					if (!empty($timer) && strtotime($timer) < time()) {
						// get all the missed occourences
						if ($date_end === false || (strtotime($date_end) > time())) $date_end = date('Y-m-d H:i:s');
						$timer = $PMUtils->getTimerRecurrences($timer,$date_end,($vte_metadata['recurrence'] == 'on'),$vte_metadata['cron_value'],10);
					}
					if (!empty($timer)) {
						if (!is_array($timer)) $timer = array($timer);
						foreach ($timer as $i => $t) {
							$calculate_next_occourence = ($i == count($timer)-1);
							$running_process = $adb->getUniqueID("{$table_prefix}_running_processes");
							$adb->pquery("insert into {$table_prefix}_running_processes(id,processmakerid,current,xml_version) values(?,?,?,?)", array($running_process,$info['processid'],$info['elementid'],$PMEngine->process_data['xml_version'])); //crmv@150751
							$info = array('processid'=>$info['processid'],'elementid'=>$info['elementid'],'running_process'=>$running_process,'calculate_next_occourence'=>$calculate_next_occourence);
							$PMUtils->createTimer('start',$t,$running_process,null,$info['elementid'],'',$info);	//crmv@127048
						}
					}
				}
			}			
		} elseif ($row['mode'] == 'intermediate') {
			$adb->pquery("delete from {$table_prefix}_running_processes_timer where id = ?", array($row['id']));
		}
	}
}

if ($bk_current_user) $current_user = $bk_current_user;
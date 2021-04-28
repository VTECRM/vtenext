<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150024 */

require_once("modules/Targets/Targets.php");

class DynamicTargets extends SDKExtendableUniqueClass {

	const STATUS_IDLE = 0;
	const STATUS_RUNNING = 1;
	const STATUS_ERROR = 5;
	
	public $enableLog = true;
	public $staleTimeout = 3600;	// if a filter/report is in running status for more than this time (s), 
									// the status is cleared
									
	public $linkedModules = array(); //crmv@181281
	
	function __construct() {
		//crmv@181281
		$focusNewsletter = CRMEntity::getInstance('Newsletter');
		$this->linkedModules = array_keys($focusNewsletter->email_fields);
		//crmv@181281e
	}

	/**
	 * Calculate the dynamic targets.
	 */
	public function runDynamicTargets($targetid = null) {
		global $adb, $table_prefix;
		
		$this->cleanStaleTargets();
		
		$targetCond = '';
		if ($targetid > 0) {
			$targetCond = " AND t.targetsid = ".intval($targetid);
		}
		
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND c.setype = 'Targets'";
		}
		
		// search for dynamic targets with no filters/reports in a related and remove all relations
		$res = $adb->pquery(
			"SELECT t.targetsid
			FROM {$table_prefix}_targets t
			INNER JOIN {$table_prefix}_crmentity c ON c.crmid = t.targetsid $setypeCond
			WHERE c.deleted = 0 AND t.target_type = ? AND t.target_sync_type = ? $targetCond", 
			array('TargetTypeDynamic', 'TargetSyncComplete')
		);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$targetid = $row['targetsid'];
			$res2 = $adb->pquery(
				"SELECT DISTINCT formodule
				FROM {$table_prefix}_targets_cvrel 
				WHERE targetid = ?", 
				array($targetid)
			);	
			$usedMods = array();
			while ($row2 = $adb->FetchByAssoc($res2, -1, false)) {
				$usedMods[] = $row2['formodule'];
			}
			$diffMods = array_diff($this->linkedModules, $usedMods);
			foreach ($diffMods as $dmod) {
				$this->removeAllRelations($targetid, $dmod);
			}
		}
		
		$res = $adb->pquery(
			"SELECT tr.*, t.target_sync_type
			FROM {$table_prefix}_targets t
			INNER JOIN {$table_prefix}_crmentity c ON c.crmid = t.targetsid $setypeCond
			INNER JOIN {$table_prefix}_targets_cvrel tr ON tr.targetid = t.targetsid
			WHERE c.deleted = 0 AND t.target_type = ? AND tr.status = ? $targetCond
			ORDER BY tr.last_sync ASC", 
			array('TargetTypeDynamic', self::STATUS_IDLE)
		);
		
		$fullSyncList = array();
		
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$syncType = $row['target_sync_type'];
			if ($syncType == 'TargetSyncIncremental') {
				$this->executeDynamicTarget($row['targetid'], $row['cvtype'], $row['objectid'], $row['formodule'], $row['target_sync_type']);
			} else {
				// group the ids by target and related
				$fullSyncList[$row['targetid']][$row['formodule']][] = array('cvtype' => $row['cvtype'], 'objectid' => $row['objectid']);
			}
		}
		
		foreach ($fullSyncList as $targetid => $syncLists) {
			foreach ($syncLists as $formodule => $objects) {
				$this->executeFullSync($targetid, $formodule, $objects);
			}
		}
	}
	
	public function cleanStaleTargets() {
		global $adb, $table_prefix;
		
		//$this->log("Cleaning old dynamic targets...");
		
		$timeLimit = date('Y-m-d H:i:s', time()-$this->staleTimeout);
		$res = $adb->pquery(
			"UPDATE {$table_prefix}_targets_cvrel 
			SET status = ? WHERE status = ? AND last_sync < ?", 
			array(self::STATUS_IDLE, self::STATUS_RUNNING, $timeLimit)
		);
	}
	
	public function setStatus($targetid, $type, $objectid, $formodule, $status) {
		global $adb, $table_prefix;
		
		if ($status == self::STATUS_RUNNING) {
			$params = array($status, date('Y-m-d H:i:s'), $targetid, $objectid, $type, $formodule);
			$sql = "UPDATE {$table_prefix}_targets_cvrel SET status = ?, last_sync = ? WHERE targetid = ? AND objectid = ? AND cvtype = ? AND formodule = ?";
		} else {
			$params = array($status, $targetid, $objectid, $type, $formodule);
			$sql = "UPDATE {$table_prefix}_targets_cvrel SET status = ? WHERE targetid = ? AND objectid = ? AND cvtype = ? AND formodule = ?";
		}
		
		$adb->pquery($sql, $params);
	}
	
	public function removeAllRelations($targetid, $relmodule) {
		global $adb, $table_prefix;
		
		$this->log("Removing all relations with $relmodule...");
		
		// one way
		$adb->pquery(
			"DELETE FROM {$table_prefix}_crmentityrel WHERE crmid = ? AND module = ? AND relmodule = ?",
			array($targetid, 'Targets', $relmodule)
		);
		
		// and return!
		$adb->pquery(
			"DELETE FROM {$table_prefix}_crmentityrel WHERE relcrmid = ? AND relmodule = ? AND module = ?",
			array($targetid, 'Targets', $relmodule)
		);
	}
	
	public function executeDynamicTarget($targetid, $type, $objectid, $formodule, $syncType) {
		$this->log("Executing target #$targetid ($type #$objectid for $formodule)...");
		$t0 = microtime(true);
		
		$this->setStatus($targetid, $type, $objectid, $formodule, self::STATUS_RUNNING);
		
		$focus = CRMEntity::getInstance('Targets');
		
		if ($syncType == 'TargetSyncIncremental') {
			if ($type == 'CustomView') {
				$focus->loadCVList($targetid, $formodule, $objectid, false);
			} elseif ($type == 'Report') {
				$focus->loadReportList($targetid, $formodule, $objectid, false);
			}
		} elseif ($syncType == 'TargetSyncComplete') {
			// execute the filter/report and put the ids in the table
			if ($type == 'CustomView') {
				$sql = $focus->getCVListIdsQuery($targetid, $objectid, $formodule);
			} elseif ($type == 'Report') {
				$sql = $focus->getReportListIdsQuery($targetid, $objectid, $formodule);
			}
			$this->insertIntoSyncTable($sql, $targetid, $formodule);
		} else {
			$this->log("Unknown sync type: $syncType");
		}
		
		$this->setStatus($targetid, $type, $objectid, $formodule, self::STATUS_IDLE);
		
		$t1 = microtime(true);
		$this->log("Completed in ".round($t1-$t0, 2)."s");
	}
	
	public function executeFullSync($targetid, $formodule, $objects) {
		global $adb, $table_prefix;
		
		$this->log("Executing full sync for target #$targetid ($formodule)...");
		
		$this->cleanSyncTable($targetid, $formodule);
		
		// first insert all the ids in the sync table
		foreach ($objects as $oinfo) {
			$this->executeDynamicTarget($targetid, $oinfo['cvtype'], $oinfo['objectid'], $formodule, 'TargetSyncComplete');
		}
		
		// then remove the ones not in the list
		$adb->pquery(
			"DELETE FROM {$table_prefix}_crmentityrel
			WHERE crmid = ? AND module = ? AND relmodule = ? AND relcrmid NOT IN (
				SELECT crmid 
				FROM {$table_prefix}_targets_cvrel_sync
				WHERE targetid = ? AND formodule = ?
			)",
			array($targetid, 'Targets', $formodule, $targetid, $formodule)
		);
		// other direction
		$adb->pquery(
			"DELETE FROM {$table_prefix}_crmentityrel
			WHERE relcrmid = ? AND relmodule = ? AND module = ? AND crmid NOT IN (
				SELECT crmid 
				FROM {$table_prefix}_targets_cvrel_sync
				WHERE targetid = ? AND formodule = ?
			)",
			array($targetid, 'Targets', $formodule, $targetid, $formodule)
		);
		
		// and then add them all!
		$res = $adb->pquery(
			"SELECT crmid 
			FROM {$table_prefix}_targets_cvrel_sync
			WHERE targetid = ? AND formodule = ?",
			array($targetid, $formodule)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$ids = array();
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$ids[] = $row['crmid'];
			}
			$focus = CRMEntity::getInstance('Targets');
			$focus->save_related_module('Targets', $targetid, $formodule, $ids);
		}
	}
	
	public function insertIntoSyncTable($sql, $targetid, $forModule) {
		global $adb, $table_prefix;
		
		$targetid = intval($targetid);
		$fullsql = preg_replace("/^SELECT /i", "SELECT $targetid AS targetid, '$forModule' AS formodule, ", $sql);
		
		if ($adb->isMySQL()) {
			$sql = "INSERT IGNORE INTO {$table_prefix}_targets_cvrel_sync (targetid, formodule, crmid) $fullsql";
		} else {
			// fallback, remove common ids, and insert them
			$sqldel = "DELETE FROM {$table_prefix}_targets_cvrel_sync cvs WHERE cvs.targetid = ? AND cvs.formodule = ? AND cvs.crmid IN ($sql)";
			$adb->pquery($sqldel, array($targetid, $forModule));
			
			$sql = "INSERT INTO {$table_prefix}_targets_cvrel_sync (targetid, formodule, crmid) $fullsql";
		}
		
		$adb->query($sql);
	}
	
	public function cleanSyncTable($targetid, $forModule) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM {$table_prefix}_targets_cvrel_sync WHERE targetid = ? AND formodule = ?", array($targetid, $forModule));
	}
	
	protected function log($message) {
		if ($this->enableLog) {
			echo date('Y-m-d H:i:s')." $message\n";
		}
	}
	
}
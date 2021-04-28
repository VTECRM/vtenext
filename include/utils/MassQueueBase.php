<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@91571 crmv@202577 */

require_once('include/BaseClasses.php');

/**
 * A base class for mass operations
 */

abstract class MassQueueBase extends SDKExtendableUniqueClass {

	public $table;
	public $queueTable;
	
	public $chunkSize = 400;	// retrieve the record to update in chunks of this size
	public $purgeDays = 15;		// old jobs, completed or not, will be purged after this number of days
	public $chunkInterval = 2;	// number of seconds to wait between each chunk of records
	
	const MASS_STATUS_IDLE = 0;
	const MASS_STATUS_PROCESSING = 1;
	const MASS_STATUS_COMPLETE = 2;
	const MASS_STATUS_ERROR = 3;
	
	const MASSQUEUE_STATUS_IDLE = 0;
	const MASSQUEUE_STATUS_PROCESSING = 1;
	const MASSQUEUE_STATUS_COMPLETE = 2;
	const MASSQUEUE_STATUS_ERROR = 3;

	// some private vars
	protected $cachedInstances = array();
	protected $userStack = array();
	
	public function __construct() {
		if (empty($this->table) || empty($this->queueTable)) {
			die('You must setup table and queueTable variables to use the functionality of the this class.');
		}
	}
	
	/**
	 * Clean the mass queue from the succesful updates
	 */
	public function cleanQueue($massid = null) {
		global $adb;
		
		if ($massid > 0) {
			$adb->pquery("DELETE FROM {$this->queueTable} WHERE massid = ? AND status = ?", array($massid, self::MASSQUEUE_STATUS_COMPLETE));
		} else {
			$adb->pquery("DELETE FROM {$this->queueTable} WHERE status = ?", array(self::MASSQUEUE_STATUS_COMPLETE));
		}
	}
	
	/**
	 * Remove from the queue everything related to old jobs (failed or not)
	 */
	public function cleanOldJobs() {
		global $adb;
		
		$massids = array();
		$timeLimit = date('Y-m-d H:i:s', time()-($this->purgeDays*24*3600)); // crmv@155585
		$params = array($timeLimit, self::MASS_STATUS_COMPLETE, self::MASS_STATUS_ERROR, self::MASS_STATUS_PROCESSING);
		$res = $adb->pquery("SELECT massid FROM {$this->table} WHERE inserttime < ? AND status IN (?,?,?)", $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$massids[] = intval($row['massid']);
			}
		}
		
		if (count($massids) > 0) {
			$adb->pquery("DELETE FROM {$this->queueTable} WHERE massid IN (".generateQuestionMarks($massids).")", array($massids));
			$adb->pquery("DELETE FROM {$this->table} WHERE massid IN (".generateQuestionMarks($massids).")", array($massids));
		}
	}
	
	/**
	 * Resume failed jobs which are blocked in the processing state
	 */
	public function cleanFailedJobs() {
		global $adb;
		
		/*
			This is the idea: get the jobs started more than X time ago (default 1 hour) and
			see how many records they have processed. Then using a heuristic formula calculate
			how many records should have been processed, and if it seems to be too slow, 
			set it in idle state, so it can continue the normal operation
		*/
		
		$startedHours = 1;		// check only the jobs started this number of hours ago (or earlier)
		$timePerRecords = 0.2;	// max number of seconds that each save() is allowed to consume
		$multiplier = 2;		// if the elapsed time exceeds the calculated time * multiplier assume it's blocked
		
		$timeLimit = date('Y-m-d H:i:s', time()-($startedHours*3600));
		$params = array($timeLimit, self::MASS_STATUS_PROCESSING);
		$res = $adb->pquery("SELECT massid, starttime FROM {$this->table} WHERE starttime IS NOT NULL AND starttime != '0000-00-00 00:00:00' AND starttime < ? AND status = ?", $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$massid = intval($row['massid']);
				$completed = $this->countCompletedEditJobs($massid);
				
				// calculate the allowed time
				$chunks = ceil($completed/$this->chunkSize);
				$allowedTime = $multiplier * ($completed*$timePerRecords + $chunks * $this->chunkInterval);
				
				// calculate the elapsed time
				$elapsedTime = time() - strtotime($row['starttime']);
				
				if ($elapsedTime > $allowedTime) {
					// suppose it's stuck, resume it!
					$this->log("The job #$massid has been resumed ($elapsedTime vs $allowedTime)");
					$this->cleanQueue($massid);
					$rparams = array(date('Y-m-d H:i:s'), self::MASS_STATUS_IDLE, $massid);
					$adb->pquery("UPDATE {$this->table} SET starttime = ?, status = ? WHERE massid = ?", $rparams);
				}
			}
		}
		
	}
	
	/**
	 * Get the first runnable job (in IDLE state)
	 */
	public function getRunnableJob() {
		global $adb;
		
		$massid = 0;
		$res = $adb->limitpQuery("SELECT massid FROM {$this->table} WHERE status = ? ORDER BY massid ASC", 0, 1, array(self::MASS_STATUS_IDLE));
		if ($res && $adb->num_rows($res) > 0) {
			$massid = intval($adb->query_result_no_html($res, 0, 'massid'));
		}
		
		return $massid;
	}
	
	public function getJobInfo($massid) {
		global $adb;
		
		$info = null;
		$res = $adb->pquery("SELECT * FROM {$this->table} WHERE massid = ?", array($massid));
		if ($res && $adb->num_rows($res) > 0) {
			$info = $adb->FetchByAssoc($res, -1, false);
			if (!empty($info['results'])) $info['results'] = Zend_Json::decode($info['results']);
		}
		
		return $info;
	}
	
	public function setJobStatus($massid, $status) {
		global $adb;
		
		$sql = "UPDATE {$this->table} SET status = ?";
		$params = array('status' => $status);
		
		if ($status == self::MASS_STATUS_PROCESSING) {
			$sql .= ", starttime = ?";
			$params['starttime'] = date('Y-m-d H:i:s');
		} elseif ($status == self::MASS_STATUS_COMPLETE || $status == self::MASS_STATUS_ERROR) {
			$sql .= ", endtime = ?";
			$params['endtime'] = date('Y-m-d H:i:s');
		}
		
		$sql .= " WHERE massid = ?";
		$params['massid'] = $massid;
		
		$adb->pquery($sql, $params);
	}
	
	public function setJobResults($massid, $results) {
		global $adb;
		
		$adb->updateClob($this->table, 'results', "massid = $massid", Zend_Json::encode($results));
	}
	
	/**
	 * Push the specified user on to the user stack
	 * and make it the $current_user
	 *
	 */
	protected function switchUser($userid) {
		global $current_user;
		
		array_push($this->userStack, $current_user);
		$user = CRMEntity::getInstance('Users');
		$user->retrieveCurrentUserInfoFromFile($userid);
		
		$current_user = $user;
		return $user;
	}

	/**
	 * Revert to the previous use on the user stack
	 */
	protected function revertUser(){
		global $current_user;
		if (count($this->userStack) > 0) {
			$current_user = array_pop($this->userStack);
		} else {
			$current_user = null;
		}
		return $current_user;
	}
	
	public function processCron($massid = 0) {
	
		if (empty($massid)) {
			// clean queue and do generic stuff
			$this->cleanOldJobs();
			$this->cleanFailedJobs();
			
			// get the massid
			$massid = $this->getRunnableJob();
		}
		
		if (empty($massid)) {
			// nothing to do!
			return true;
		}
		
		// do the job!
		$r = $this->process($massid);
		
		return $r;
	}
	
	public function process($massid) {
		global $adb;
		
		$info = $this->getJobInfo($massid);
		
		// change to the user who made the request
		$this->switchUser($info['userid']); // crmv@169571
		
		$module = $info['module'];
		$result = array(
			'processed' => 0,
			'completed' => 0,
			'error' => 0,
			'message' => '',
		);
		
		if (!isModuleInstalled($module) || !vtlib_isModuleActive($module)) {
			$result['message'] = "Module $module is not active";
			$this->setJobStatus($massid, self::MASS_STATUS_ERROR);
			$this->setJobResults($massid, $result);
			$this->notifyUser($info, $result);
			return false;
		}
		
		$this->setJobStatus($massid, self::MASS_STATUS_PROCESSING);
		
		// disable die on error, enable exception
		$oldDieOnError = $adb->dieOnError;
		$adb->setDieOnError(false);
		$adb->setExceptOnError(true);
		
		$rtot = true;
		$list = $this->getRecordsChunk($massid);
		while (count($list) > 0) {
			foreach ($list as $editjob) {
				$error = '';
				$r = $this->processJob($massid, $info, $editjob, $error);
				$rtot &= $r;
				$result['processed']++;
				if ($r) {
					$result['completed']++;
				} else {
					$result['error']++;
				}
			}
			// wait a couple of seconds between the chunks
			sleep($this->chunkInterval);
			// retrieve the next chunk
			$list = $this->getRecordsChunk($massid);
		}
		
		// restore die on error
		$adb->setDieOnError($oldDieOnError);
		
		// now check for jobs in processing status (something went very wrong during the process and it was resumed)
		$processing = $this->countProcessingJobs($massid);
		if ($processing > 0) {
			$rtot = false;
			$result['error'] += $processing;
			$result['processed'] += $processing;
			$this->error("Some records have not been saved corectly, and caused the script to terminate.");
			$this->error("The process resumed, but the status of those records is unknown. Check the table {$this->queueTable}.");
			$adb->pquery("UPDATE {$this->queueTable} SET status = ? WHERE massid = ? AND status = ?", array(self::MASSQUEUE_STATUS_ERROR, $massid, self::MASSQUEUE_STATUS_PROCESSING));
		}
		
		$this->notifyUser($info, $result);
		$this->setJobResults($massid, $result);
		
		if ($rtot) {
			// everything ok
			$this->setJobStatus($massid, self::MASS_STATUS_COMPLETE);
		} else {
			// some errors
			$this->setJobStatus($massid, self::MASS_STATUS_ERROR);
		}
		
		// clean the queue
		$this->cleanQueue($massid);
		
		$this->revertUser(); // crmv@169571
		
		return $rtot;
	}
	
	public function getRecordsChunk($massid) {
		global $adb;
		
		$list = array();
		$params = array($massid, self::MASSQUEUE_STATUS_IDLE);
		$res = $adb->limitpQuery("SELECT * FROM {$this->queueTable} WHERE massid = ? AND status = ?", 0, $this->chunkSize, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$list[] = $row;
		}
		return $list;
	}

	public abstract function notifyUser($massinfo, $result);

	public abstract function processJob($massid, $massinfo, $editjob, &$error = '');
	
	public function countCompletedEditJobs($massid) {
		global $adb;
		
		$count = 0;
		$res = $adb->pquery("SELECT COUNT(*) as count FROM {$this->queueTable} WHERE massid = ? AND status = ?", array($massid, self::MASSQUEUE_STATUS_COMPLETE));
		if ($res && $adb->num_rows($res) > 0) {
			$count = intval($adb->query_result_no_html($res, 0, 'count'));
		}
		
		return $count;
	}
	
	public function countProcessingJobs($massid) {
		global $adb;
		
		$count = 0;
		$res = $adb->pquery("SELECT COUNT(*) as count FROM {$this->queueTable} WHERE massid = ? AND status = ?", array($massid, self::MASSQUEUE_STATUS_PROCESSING));
		if ($res && $adb->num_rows($res) > 0) {
			$count = intval($adb->query_result_no_html($res, 0, 'count'));
		}
		
		return $count;
	}
	
	public function getModuleInstance($module) {
		if (!array_key_exists($module, $this->cachedInstances)) {
			$crmModule = $module;
			if ($module == 'Events') $crmModule = 'Calendar';
			$this->cachedInstances[$module] = CRMEntity::getInstance($crmModule);
			vtlib_setup_modulevars($module, $this->cachedInstances[$module]);
		}
		
		// reset some internal values
		if ($this->cachedInstances[$module]) {
			$this->cachedInstances[$module]->id = null;
			$this->cachedInstances[$module]->mode = null;
			$this->cachedInstances[$module]->parentid = null;
		}
		
		return $this->cachedInstances[$module];
	}
	
	public function getNotificationInfo($massid) {
		$info = $this->getJobInfo($massid);
		
		$notInfo = array(
			'status' => $info['status'],
			'num_records' => $info['results']['processed'],
			'num_fail_records' => $info['results']['error'],
			'num_ok_records' => $info['results']['completed'],
		);
		
		return $notInfo;
	}

	public abstract function getNotificationHtml($massid, $html = '');
	
	// logging function
	protected function log($msg) {
		$this->outputLog('[INFO] '.$msg);
		return true;
	}
	
	protected function warning($msg) {
		$this->outputLog('[WARNING] '.$msg);
		return true;
	}
	
	protected function error($msg) {
		$this->outputLog('[ERROR] '.$msg);
		return false;
	}
	
	protected function outputLog($msg) {
		echo $msg."\n";
	}
	
}
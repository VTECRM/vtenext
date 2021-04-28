<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */

require_once('modules/Settings/DataImporter/DataImporterUtils.php');
require_once('modules/Settings/DataImporter/DataImporterErp.php');

class DataImporterCron {

	protected $id = null;
	protected $cronjob;
	protected $importInfo;
	
	protected $diutils;
	
	public function __construct($importid = 0) {
		$this->id = $importid;
		$this->diutils = new DataImporterUtils();
		if ($importid > 0) {
			$this->importInfo = $this->diutils->getImporterInfo($importid);
			$cronname = 'DataImporter_'.$importid;
			$this->cronjob = CronJob::getByName($cronname);
			
		}
	}
	
	protected function log($text) {
		echo $text."\n";
	}
	
	public function shouldRunNow() {
		if (!$this->importInfo['enabled']) return false;
		if ($this->importInfo['running']) return false;
		
		// check the override flag
		if ($this->importInfo['override_runnow'] && !$this->importInfo['override_abort']) return true;

		$scheduling = $this->importInfo['scheduling'];
		$every = intval($scheduling['dimport_sched_every']);
		$what = $scheduling['dimport_sched_everywhat'];
		$at = $scheduling['dimport_sched_at'];
		
		$go = false;
		$now_ts = time();
		$now = date('Y-m-d H:i:s', $now_ts);
		$lastrun = $this->importInfo['lastimport'];
				
		$firstTime = (empty($lastrun) || $lastrun == '0000-00-00 00:00:00');
		$lastrun_ts = $firstTime ? 0 : strtotime($lastrun);
		$cronInterval = max(60, $this->cronjob->repeat);
		$tolerance = 0; // crmv@149338
			
		if ($what == 'minute') {
			$period = 60 * $every;
			$pad = $nowm = 0;
		} elseif ($what == 'hour') {
			$period = 3600 * $every;
			$pad = intval($at)*60;
			$nowm = $now_ts%3600;
			$tolerance = 300; // crmv@149338
		} elseif ($what == 'day') {
			// crmv@197635
			// i have to take care of the timezone
			$pad = strtotime("$at:00")%(3600*24);
			$nowm = $now_ts%(3600*24);
			$tolerance = 600; // crmv@91561
			// when scheduled every day, ignore the last import time
			// so running it by hand doesn't avoid it running at a specific time
			if ($every == 1) {
				$period = $tolerance*2;
			} else {
				$period = 3600*24 * $every;
			}
			// crmv@197635e
		}
		
		// crmv@91561 crmv@149338
		// add some tolerance, because some other cron processes can take long
		if ($tolerance > 0) {
			$cronInterval = max($tolerance, $cronInterval);
			if ($lastrun_ts > 0) $lastrun_ts -= $cronInterval;
		}
		// crmv@91561e crmv@149338e
		
		$tdiff = $now_ts - $lastrun_ts;
		$nowdiff = $nowm - $pad;
		$go = ($tdiff >= 0 && $tdiff >= $period) && ($nowdiff >= 0 && $nowdiff < $cronInterval);

		return $go;
	}
	
	/**
	 * Check if the import should start now and execute it
	 */
	public function process() {
		// check if should run now
		if (!$this->shouldRunNow()) return true;
		$ret = $this->run();		
		return $ret;
	}
	
	/**
	 * Run the import
	 */
	public function run() {
		// clear the run override
		$this->diutils->setOverride($this->id, 'runnow', 0);
		// set running
		$this->diutils->setRunning($this->id, true);
		$this->diutils->updateSingleField($this->id, 'errors', 0);
		$this->diutils->updateSingleField($this->id, 'lastimport', date('Y-m-d H:i:s'));
		
		$errors = false;
		$erpClass = DataImporterErp::getInstance($this->id, $this->importInfo);
		try {
			// set a more strict error reporting
			$r = $erpClass->run();
			if (!$r) $errors = true;
		} catch (Exception $e) {
			$errors = true;
			// log locally
			$this->log("Exception during import: ".$e->getMessage());
			$this->log($e->getTraceAsString());
			// log also in erp class 
			if ($erpClass->log) {
				$erpClass->log->fatal("Exception during import: ".$e->getMessage());
				$erpClass->log->fatal($e->getTraceAsString());
			}
			$this->diutils->sendFailNotification($this->id, $e->getMessage());
		}
		
		if (!$errors) {
			// clear the error flag
			$this->diutils->updateSingleField($this->id, 'errors', 0);
		}
		
		// clear the running flag
		$this->diutils->setRunning($this->id, false);
		// clear the abort flag
		$this->diutils->setOverride($this->id, 'abort', 0);
		return true;
	}
	
	protected function isProcessActive($pid) {
		if (!$pid) return false;
		
		if (PHP_OS == 'Linux') {
			return file_exists("/proc/{$pid}");
		} else {
			// TODO
			// not implemented for other systems
			return null;
		}
		
	}
	
	// Check for blocked imports
	public function check() {
		$checkStatus = array('PROCESSING');
		$list = $this->diutils->getList();
		
		foreach ($list as $import) {
			$cronname = 'DataImporter_'.$import['id'];
			$cronjob = CronJob::getByName($cronname);
			if ($import['running'] && $cronjob && $cronjob->getId() > 0) {
				$status = $cronjob->status;
				$pid = $cronjob->getPid();
				if (in_array($status, $checkStatus) && $pid) {
					if ($this->isProcessActive($pid) === false) {
						// reset the cron
						$cronjob->setStatus(CronJob::$STATUS_EMPTY);
						$cronjob->clearPid();
						// reset the import
						$this->diutils->resetFailedImport($import['id']);
						$this->diutils->updateSingleField($import['id'], 'errors', 1);
						// notify
						$this->diutils->sendFailNotification($import['id']);
					}
				} elseif (!$pid) {
					// running without pid? hmmm, something is wrong
					// reset the import
					$this->diutils->resetFailedImport($import['id']);
					$this->diutils->updateSingleField($import['id'], 'errors', 1);
					// notify
					$this->diutils->sendFailNotification($import['id']);
				}
			}
			
		}
	}
	
}
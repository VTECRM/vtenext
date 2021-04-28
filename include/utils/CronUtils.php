<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42264
require_once('include/BaseClasses.php');
require_once('include/utils/LogUtils.php');

class CronUtils extends SDKExtendableUniqueClass {

	public function insertCronJob($cb) {
		$cb->write($cb->getId());
	}

	public function editCronJob($cb) {
		// TODO
	}

	public function deleteCronJob($cronid) {
		$cronjob = new CronJob($cronid);
		$cronjob->delete();
	}

	public function suspendCronJob($cronid, $suspend = true) {
		$cronjob = new CronJob($cronid);
		$cronjob->activate(!$suspend);
	}

	public function suspendAll() {
		global $adb, $table_prefix;
		$adb->query("update {$table_prefix}_cronjobs set active = 0");
	}

	// crmv@106069
	public static function cleanLogs() {
		$CM = new CronManager();
		$CM->cleanLogs();
	}
	// crmv@106069e
	
	// crmv@181161
	/**
	 * Freeze all active jobs
	 */
	public function freezeAllActive() {
		global $adb, $table_prefix;
		$adb->query("UPDATE {$table_prefix}_cronjobs SET active = 2 WHERE active = 1");
	}
	
	/**
	 * Unfreeze all frozen jobs
	 */
	public function unfreezeAll() {
		global $adb, $table_prefix;
		$adb->query("UPDATE {$table_prefix}_cronjobs SET active = 1 WHERE active = 2");
	}
	
	/**
	 * Wait until all crons finish processing or when reaching the timeout
	 * There might be false positives: cron in processing status, but crashed
	 * @param int $timeout A timeout in seconds
	 * @return bool true = ok, false = timeout reached
	 */
	public function waitForAllCron($timeout = 300, $skipids = array()) {
		global $adb, $table_prefix;
		
		$sql = "SELECT COUNT(*) as count FROM {$table_prefix}_cronjobs WHERE active = 1 AND status = ?";
		$params = array(CronJob::$STATUS_PROCESSING);
		
		if (count($skipids) > 0) {
			$sql .= " AND cronid NOT IN (".generateQuestionMarks($skipids).")";
			$params = array_merge($params, $skipids);
		}
		
		$time = microtime(true);
		$res = $adb->pquery($sql, $params);
		$count = $adb->query_result_no_html($res, 0, 'count');
		
		while ($count > 0) {
			$t = microtime(true);
			if ($t-$time > $timeout) return false;
			
			sleep(3);
			
			$res = $adb->pquery($sql, $params);
			$count = $adb->query_result_no_html($res, 0, 'count');
		}
		
		return true;
 	}
 	// crmv@181161e

}

class CronManager {

	public $logDir = 'logs/cron/';
	public $logMaxSize = 5242880;	// 5MB per logfile (more or less)

	// crmv@106069
	public $logPurgeAge = 90;		// delete logs (both raw and compressed) after this amount of days (only if there is more than 1 log, 0 = disable)
	public $logCompressAge = 30;	// compress logs after they are older than this amount of days and there is more than 1 file (0 = disable)
	// crmv@106069e

	public $logToStdout = false;
	public $request = array();

	static $current_processing_jobid = null;	// crmv@65455
	static $termination_requested = false; 		// crmv@194059
	
	function __construct($request='') {
		if (!empty($request)) {
			$this->request = $request;
		}
	}
	
	// crmv@194059
	static function requestCronTermination() {
		self::$termination_requested = true;
	}
	// crmv@194059e

	// this method may be called while another instance is running
	public function run($idlist = array(), $force = false) { // crmv@181265
		global $adb, $table_prefix;

		if (!is_array($idlist)) $idlist = array($idlist);
		$idlist = array_filter($idlist);

		$now = date('Y-m-d H:i:s');

		// first check for dead jobs (marked as running in timeout, but not terminated or not running due to reboot)
		$this->checkOprhanedJobs();

		// then check running jobs for timeout
		$this->checkTimeoutJobs();
		
		// crmv@181265
		if ($force && count($idlist) == 1) {
			$cronjob = new CronJob($idlist[0]);
			if ($cronjob) {
				$_REQUEST = $this->request;
				$this->runJob($cronjob);
			}
			return;
		}
		// crmv@181265e

		// now get available jobs
		$runnableStatuses = array(CronJob::$STATUS_ERROR, CronJob::$STATUS_TIMEOUT_END, CronJob::$STATUS_EMPTY);
		$params = array_merge($runnableStatuses, array($now, $now), $idlist);
		//crmv@58330 crmv@112251
		if($adb->isMssql() || $adb->isOracle()){
			$res = $adb->pquery(
			"SELECT cronid FROM {$table_prefix}_cronjobs
			WHERE active = 1
				AND (status IS NULL OR status IN (".generateQuestionMarks($runnableStatuses)."))
				AND (starttime IS NULL OR starttime = '1900-01-01 00:00:00' OR starttime <= ?)
				AND (endtime IS NULL OR endtime = '1900-01-01 00:00:00' OR endtime > ?)
				AND (max_attempts IS NULL OR max_attempts = 0 OR attempts IS NULL OR attempts < max_attempts) ".
				(count($idlist) > 0 ? ' AND cronid in ('.generateQuestionMarks($idlist).')' : '')." ORDER BY lastrun ASC",
			$params
			);
		}
		else{ //crmv@58330e crmv@112251e
			$res = $adb->pquery(
			"SELECT cronid FROM {$table_prefix}_cronjobs
			WHERE active = 1
				AND (status IS NULL OR status IN (".generateQuestionMarks($runnableStatuses)."))
				AND (starttime IS NULL OR starttime = '0000-00-00 00:00:00' OR starttime <= ?)
				AND (endtime IS NULL OR endtime = '0000-00-00 00:00:00' OR endtime > ?)
				AND (max_attempts IS NULL OR max_attempts = 0 OR attempts IS NULL OR attempts < max_attempts) ".
				(count($idlist) > 0 ? ' AND cronid in ('.generateQuestionMarks($idlist).')' : '')." ORDER BY lastrun ASC",
			$params
			);
		} //crmv@58330
		if ($res) {
			$runnableJobs = array();
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$cronid = $row['cronid'];
				$cronjob = new CronJob($cronid);
				// just store the id because running a job might be long and the time calculations can go wrong
				if ($cronjob->shouldRunNow()) $runnableJobs[] = $cronjob;
			}
			foreach ($runnableJobs as $rjob) {
				// regenerate _REQUEST every script job
				if ($this->checkRunnable($rjob)) {
					$_REQUEST = $this->request;
					$this->runJob($rjob);
					if (self::$termination_requested) break; // crmv@194059
				}
			}
		}
	}
	
	// crmv@181281 // crmv@201562
	function checkRunnable($job) {
		global $adb, $table_prefix;
		$runnableStatuses = array(CronJob::$STATUS_ERROR, CronJob::$STATUS_TIMEOUT_END, CronJob::$STATUS_EMPTY);
		$res = $adb->pquery(
			"UPDATE {$table_prefix}_cronjobs SET status = ? WHERE cronid = ? AND (status IS NULL OR status in (".generateQuestionMarks($runnableStatuses)."))",
			array(CronJob::$STATUS_PROCESSING, $job->getId(), $runnableStatuses)
		);
		if ($res && $adb->getAffectedRowCount($res) > 0) {
			return true;
		}
		// echo "SKIPCRON ".date('Y-m-d H:i:s')." {$job->name}\n";
		return false;
	}
	// crmv@181281e // crmv@201562e

	// check for running jobs which are already in timeout, and mark them
	// so their calling process can mark them as timeout (if they'll ever terminate)
	protected function checkTimeoutJobs() {
		global $adb, $table_prefix;

		$now = date('Y-m-d H:i:s');
		if ($adb->isMysql()) {
			$diffExpr = "TIMESTAMPDIFF(SECOND, lastrun, ?)";
		} elseif ($adb->isOracle()) {
			$diffExpr = "(lastrun - to_date(?, 'YYYY-MM-DD HH24:MI:SS'))*24*60*60";
		} elseif ($adb->isMssql()) {
			$diffExpr = "DATEDIFF(SECOND, lastrun, ?)";
		} else {
			throw new Exception('Unknown database type');
		}
		$res = $adb->pquery(
			"SELECT cronid
			FROM {$table_prefix}_cronjobs
			WHERE active = 1 AND status = ? AND lastrun IS NOT NULL AND timeout IS NOT NULL AND timeout > 0
				AND $diffExpr > timeout",
			array(CronJob::$STATUS_PROCESSING, $now)
		);
		if ($res && $adb->num_rows($res) > 0) {
			// there are jobs still running, already in timeout
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$cronjob = new CronJob($row['cronid']);
				$cronjob->setStatus(CronJob::$STATUS_TIMEOUT);
				//TODO reset flag if MessageCron
			}
		}
	}

	// check for jobs who are signed as timeout, but probably their controlling process doesn't exist anymore (or will never terminate)
	protected function checkOprhanedJobs() {
		global $adb, $table_prefix;

		$now = date('Y-m-d H:i:s');
		if ($adb->isMysql()) {
			$diffExpr = "TIMESTAMPDIFF(SECOND, lastrun, ?)";
		} elseif ($adb->isOracle()) {
			$diffExpr = "(lastrun - to_date(?, 'YYYY-MM-DD HH24:MI:SS'))*24*60*60";
		} elseif ($adb->isMssql()) {
			$diffExpr = "DATEDIFF(SECOND, lastrun, ?)";
		} else {
			throw new Exception('Unknown database type');
		}
		$res = $adb->pquery(
			"SELECT cronid, pid
			FROM {$table_prefix}_cronjobs
			WHERE active = 1 AND status = ? AND lastrun IS NOT NULL AND timeout IS NOT NULL AND timeout > 0
			AND $diffExpr > 4*timeout",	// check for 4 times the timeout
			array(CronJob::$STATUS_TIMEOUT, $now)
			);
		if ($res && $adb->num_rows($res) > 0) {
			// there are jobs still running, already in timeout
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$cronjob = new CronJob($row['cronid']);
				$pid = $row['pid'];
				// kill the process (warning: might kill something else!!)
				if ($pid > 0 && PHP_OS == 'Linux') {
					$cronjob->kill();
				}
				$cronjob->setStatus(CronJob::$STATUS_EMPTY);
				$cronjob->clearPid();
				//TODO reset flag if MessageCron
			}
		}
	}

	// catches only fatal errors
	static function errorHandler($job, $logfile = null) { // crmv@146653
		global $adb, $table_prefix;

		$error = error_get_last();
		$catchTypes = array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);

		if ($job && $job->getId() == self::$current_processing_jobid && $error !== null && in_array($error['type'], $catchTypes)) { // crmv@65455
			$job->setStatus(CronJob::$STATUS_ERROR);
			$now = date('Y-m-d H:i:s');
			if ($logfile) file_put_contents($logfile, "[$now] FATAL ERROR DURING JOB {$job->name}: {$error['message']} at {$error['file']}:{$error['line']}\n\n", FILE_APPEND);
		}
	}

	// beware! this method may need a lot of time
	public function runJob(&$job) {
		global $adb, $table_prefix;

		$now = date('Y-m-d H:i:s');
		$job->setStatus(CronJob::$STATUS_PROCESSING, $now, 0); // crmv@201562
		
		$nowts0 = time();
		$error = false;

		$job->setPid();

		// initialize log file
		$logfile = $this->decideLogFile($job);
		if ($logfile) file_put_contents($logfile, "[$now] STARTING JOB {$job->name}\n\n", FILE_APPEND);

		// set exit handler to catch fatal errors
		register_shutdown_function(array($this, 'errorHandler'), $job, $logfile);

		// crmv@65455
		// global var to avoid multiple call to the error handler
		self::$current_processing_jobid = $job->getId();
		// crmv@65455e

		// capture output
		if (!$this->logToStdout) {
			ob_start();
			ob_clean();
		}

		$errorMsg = '';
		$error = false;
		try {
			if (file_exists($job->fileName)) {
				$job->incrementAttempts();
				//crmv@49395
				if (!empty($job->params)) {
					foreach($job->params as $k => $v) {
						$_REQUEST[$k] = $v;
					}
				}
				//crmv@49395e
				/* ---------- SCRIPT EXECUTION ---------- */
				require $job->fileName;
				/* ---------------- END ----------------- */
				//crmv@49395
				if (!empty($job->params)) {
					foreach($job->params as $k => $v) {
						unset($_REQUEST[$k]);
					}
				}
				//crmv@49395e
			} else {
				$error = true;
				$errorMsg = "\nFILE NOT READABLE: {$job->fileName}\n";
			}
		} catch (Exception $e) {
			$error = true;
			$errorMsg = "\nEXCEPTION IN {$e->getFile()} AT LINE {$e->getLine()} : {$e->getMessage()}\n";
			$errorMsg .= "TRACE: {$e->getTraceAsString()}\n";
		}

		// get the output
		if ($this->logToStdout) {
			$output = $errorMsg;
		} else {
			$output = ob_get_clean() . $errorMsg;
			ob_end_clean();
		}

		// crmv@65455
		// global var to avoid multiple call to the error handler
		self::$current_processing_jobid = null;
		// crmv@65455e

		// write log file
		if ($logfile) file_put_contents($logfile, $output, FILE_APPEND);

		$job->clearPid();

		// check timeout
		$nowts1 = time();
		$deltats = $nowts1 - $nowts0;
		if ($error) {
			$jobEndStatus = CronJob::$STATUS_ERROR;
		} elseif ($job->timeout > 0 && $deltats > $job->timeout) {
			$jobEndStatus = CronJob::$STATUS_TIMEOUT_END;
		} else {
			$jobEndStatus = CronJob::$STATUS_EMPTY;
			$job->resetAttempts();
		}

		$job->setStatus($jobEndStatus, null, $deltats); // crmv@102956

		// final row of log file
		if ($logfile) {
			$nowEnd = date('Y-m-d H:i:s');
			if ($jobEndStatus === CronJob::$STATUS_EMPTY) $jobEndStatus = 'OK';
			file_put_contents($logfile, "\n[$nowEnd] JOB FINISHED ({$job->name}) WITH STATUS $jobEndStatus\n\n", FILE_APPEND);
		}

	}

	public function decideLogFile(&$job) {
		if ($this->logToStdout) return 'php://stdout';

		if (!is_dir($this->logDir)) {
			mkdir($this->logDir, 0755);
		}
		$logbasename = $this->logDir.'cron_'.$job->name;
		// find a free name
		for ($i=1; $i<1000; ++$i) {
			$curname = $logbasename.'_'.str_pad(strval($i), 2, '0', STR_PAD_LEFT).'.log';
			if (!file_exists($curname) || filesize($curname) < $this->logMaxSize) return $curname;
		}
		return false;
	}

	public function sendAlert() {

	}

	// crmv@106069
	public function cleanLogs() {
		if (!is_dir($this->logDir)) return;
		
		$now = time();
		$purgeTime = $now - $this->logPurgeAge*3600*24;
		$compressTime = $now - $this->logCompressAge*3600*24;
		
		// first purge old compressed logs
		if ($this->logPurgeAge > 0) {
			$list = glob($this->logDir.'*.gz', GLOB_NOSORT);
			foreach ($list as $zlog) {
				if (filemtime($zlog) < $purgeTime) {
					$this->deleteLog($zlog);;
				}
			}
		}
		
		// then get all log files, grouped by cron name
		$logfiles = array();
		$list = glob($this->logDir.'*.log', GLOB_NOSORT);
		foreach ($list as $file) {
			$pieces = explode('_', basename($file));
			$logfiles[$pieces[1]][] = $file;
		}
		
		foreach ($logfiles as $logname => $filelist) {
			// only if more than 1 log is present
			if (count($filelist) > 1) {
				// get the times
				$times = array_map('filemtime', $filelist);
				$filetimes = array_combine($filelist, $times);
				asort($filetimes);
				$i = 0;
				$count = count($filetimes);
				// keep the last one, and delete/compress the others
				foreach ($filetimes as $file=>$logtime) {
					if ($this->logPurgeAge > 0 && $logtime < $purgeTime) {
						$this->deleteLog($file);
					} elseif ($this->logCompressAge > 0 && $logtime < $compressTime) {
						$this->compressLog($file);
					}
					if (++$i == $count - 1) break;
				}
			}
		}
		
	}
	
	protected function deleteLog($logfile) {
		if (is_writable($logfile)) {
			$r = @unlink($logfile);
			if ($r) {
				echo "Deleted logfile $logfile\n";
			}
		}
	}
	
	protected function compressLog($logfile) {
		if (is_readable($logfile) && is_writable($logfile)) {
			$r = LogUtils::gzCompressFile($logfile);
			if ($r) {
				@unlink($logfile);
				echo "Compressed logfile $logfile\n";
			}
		}
	}
	// crmv@106069e

}

class CronJob {

	protected $id;			// id of the job

	public $name;			// name of the job
	public $active;			// if false, job is not executed
	public $singleRun;		// if true, executes only one time
	public $fileName;		// name of the php script to run (relative to VTE root path, or absolute)
	public $params = array();	//crmv@49395 : simulate $_REQUEST parameters
	public $start;			// datetime since the job is executed (leave empty to ignore)
	public $end;			// datetime when the job stops from being executed (leave empty to ignore)
	public $timeout;		// max duration allowed for a job (seconds)
	public $repeat;			// repeat the job every X seconds (can be 0 or null if using the next param, > 60)
	public $runHours;		// array of hours::minute of the day to start the job (they must be ordered)
							//		(example: 09:00, 10:00, 14:30, 15:30)
	public $maxAttempts;	// number of failed attempts to run (because of error or timeout) before suspending the job

	// parameters about last execution
	public $status;			// status of the job
	public $lastRun;		// last datetime the job has been executed (started)
	public $attempts;		// number of failed consecutive run attempts
	protected $pid;			// pid of the PHP process that is running this job

	// status constants
	static $STATUS_EMPTY = '';						// ready to run
	static $STATUS_PROCESSING = 'PROCESSING';		// running
	static $STATUS_TIMEOUT_END = 'TIMEOUT_END';		// job terminated, timeout
	static $STATUS_ERROR = 'ERROR';					// job terminated, with some other error
	static $STATUS_TIMEOUT = 'TIMEOUT';				// job still running, already in timeout
	static $STATUS_SUSPENDED = 'SUSPENDED';			// job automatically suspended due to errors or timeout

	// constructor, sets some defaults
	public function __construct($id = null) {
		$this->active = true;
		$this->runHours = array();
		$this->singleRun = false;
		$this->timeout = 600;
		$this->maxAttempts = 5;
		// if and id is provided, read it
		if ($id > 0) $this->read($id);
	}

	static public function getByName($name) {
		global $adb, $table_prefix;

		$res = $adb->pquery("select cronid from {$table_prefix}_cronjobs where cronname= ?", array($name));
		if ($res && $adb->num_rows($res) > 0) {
			$id = $adb->query_result($res, 0, 'cronid');
			return new CronJob($id);
		}
		return null;
	}

	// read an existing cronjob
	public function read($id) {
		global $adb, $table_prefix;

		$res = $adb->pquery("select * from {$table_prefix}_cronjobs where cronid = ?", array($id));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			$this->id = $id;
			$this->name = $row['cronname'];
			//crmv@49395
			if (strpos($row['filename'],'?') !== false) {
				list($file,$params) = explode('?',$row['filename']);
				$this->fileName = $file;
				$params = explode('&',$params);
				foreach($params as $p) {
					list($k,$v) = explode('=',$p);
					if (!empty($k)) {
						$this->params[$k] = $v;
					}
				}
			} else {
				$this->fileName = $row['filename'];
			}
			//crmv@49395e
			$this->active = ($row['active'] == 1);
			$this->singleRun = ($row['singlerun'] == 1);
			$this->status = $row['status'];
			$this->lastRun = $row['lastrun'];
			$this->attempts = intval($row['attempts']);
			$this->start = $row['starttime'];
			$this->end = $row['endtime'];
			$this->timeout = intval($row['timeout']);
			$this->repeat = intval($row['repeat_sec']);
			$this->runHours = array_map('trim',explode(',',$row['run_hours']));
			$this->maxAttempts = intval($row['max_attempts']);
			$this->pid = intval($row['pid']);
		}
	}

	public function getId() {
		return $this->id;
	}

	// crmv@65455
	public function getPid() {
		return $this->pid;
	}
	// crmv@65455e

	// insert (or update) a job
	public function write($id = null) {
		if (empty($id))
			return $this->insert();
		else
			return $this->update();
	}

	// insert as a new job
	public function insert() {
		global $adb, $table_prefix;

		$id = $adb->getUniqueId($table_prefix.'_cronjobs');
		$columns = array(
			'cronid' => $id,
			'cronname' => $this->name,
			'active' => ($this->active ? 1 : 0),
			'singlerun' => ($this->singleRun ? 1 : 0),
			'status' => self::$STATUS_EMPTY,
			'filename' => $this->fileName,
			'starttime' => (empty($this->start) ? '' : $this->start),
			'endtime' => (empty($this->end) ? '' : $this->end),
			'timeout' => intval($this->timeout),
			'repeat_sec' => intval($this->repeat),
			'run_hours' => implode(',', $this->runHours),
			'max_attempts' => intval($this->maxAttempts),
			//'pid' => '',
		);
		$query = "insert into {$table_prefix}_cronjobs (".implode(',',array_keys($columns)).") values (".generateQuestionMarks($columns).")";
		$res = $adb->pquery($query, $columns);
		$this->id = $id;
	}

	// update a job
	public function update() {
		global $adb, $table_prefix;

		$id = $this->id;
		$columns = array(
			//'cronname' => $this->name,
			'active' => ($this->active ? 1 : 0),
			'singlerun' => ($this->singleRun ? 1 : 0),
			//'status' => self::$STATUS_EMPTY,
			'filename' => $this->fileName,
			'starttime' => $this->start,
			'endtime' => $this->end,
			'timeout' => intval($this->timeout),
			'repeat_sec' => intval($this->repeat),
			'run_hours' => implode(',', $this->runHours),
			'max_attempts' => intval($this->maxAttempts),
		);
		$sets = $params = array();
		foreach ($columns as $k=>$v) {
			$params[] = $v;
			$sets[] = "$k = ?";
		}
		$sets = implode(',', $sets);
		$params[] = $id;
		$query = "update {$table_prefix}_cronjobs set $sets where cronid = ?";
		$res = $adb->pquery($query, $params);
	}

	public function activate($active = true) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_cronjobs set active = ? where cronid = ?", array(($active ? 1 : 0), $this->id));
		$this->active = $active;
	}

	public function deactivate() {
		return $this->activate(false);
	}
	
	// crmv@181161
	public function freeze($freeze = true) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_cronjobs set active = ? where cronid = ?", array(($freeze ? 2 : 1), $this->id));
		$this->active = !$freeze;
	}
	
	public function unfreeze() {
		return $this->freeze(false);
	}
	// crmv@181161e

	public function delete() {
		global $adb, $table_prefix;
		$adb->pquery("delete from {$table_prefix}_cronjobs where cronid = ?", array($this->id));
		$this->id = null;
	}

	// this function works only in linux
	public function kill() {
		global $adb, $table_prefix;
		$pid = $this->pid;
		if ($pid > 0) {
			exec("kill -s TERM $pid && sleep 5 && kill -s KILL $pid");
			sleep(3);
			// at this point it should be killed, so the next one should fail and print the message
			exec("kill -s KILL $pid || echo 'NOTKILLED'", $output, $ret);
			if (is_array($output)) $output = implode("\n", $output);
			if (strpos($output, 'NOTKILLED') !== false) {
				$adb->pquery("update {$table_prefix}_cronjobs set pid = NULL where cronid = ?", array($this->id));
				$this->pid = null;
				return true;
			}
		}
		return false;
	}

	// return false if above or equal max number of attempts
	public function incrementAttempts() {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_cronjobs set attempts = attempts + 1 where cronid = ?", array($this->id));
		++$this->attempts;
		return ($this->attempts < $this->maxAttempts);
	}

	public function resetAttempts($what = 0) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_cronjobs set attempts = ? where cronid = ?", array($what, $this->id));
		$this->attempts = $what;
	}

	// set status for execution
	// crmv@102956
	public function setStatus($status, $lastrun = null, $duration = null) { 
		global $adb, $table_prefix;

		$this->status = $status;
		$query = "update {$table_prefix}_cronjobs set status = ?";
		$params = array($status);
		if (!empty($lastrun)) {
			$query .= ", lastrun = ?";
			$params[] = $lastrun;
		}
		if (!is_null($duration)) {
			$query .= ", last_duration = ?";
			$params[] = $duration;
		}
		$query .= " where cronid = ?";
		$params[] = $this->id;
		return $adb->pquery($query, $params); //crmv@181281
	}
	// crmv@102956e

	public function setPid($pid = 0) {
		global $adb, $table_prefix;
		if ($pid === 0) $pid = getmypid();
		$adb->pquery("update {$table_prefix}_cronjobs set pid = ? where cronid = ?", array($pid, $this->id));
		$this->pid = $pid;
	}

	public function clearPid() {
		$this->setPid(null);
	}

	// check wether this job should run now or not
	// $cronInterval is usually 1 minute
	public function shouldRunNow($cronInterval = 300) { // crmv@152744 - 5min range
		/*
		 * 1. check start time and end time
		 * 2. if repeat_sec >0
		 * 		[YES]	2a. check lastrun + repeat_sec < now ? -> TRUE else FALSE
		 * 		[NO]	2b. check run hours:
		 * 			find maximum hour which is < now
		 * 			if now - hour < cronInterval -> TRUE else FALSE
		 */

		// check global start and end
		$now = new DateTime();
		if (!empty($this->start) && $this->start != '0000-00-00 00:00:00' && $this->start != '1900-01-01 00:00:00') { // crmv@112251
			$start = DateTime::createFromFormat('Y-m-d H:i:s', $this->start);
			if ($start !== false && $start > $now) return false;
		}
		if (!empty($this->end) && $this->end != '0000-00-00 00:00:00' && $this->end != '1900-01-01 00:00:00') { // crmv@112251
			$end = DateTime::createFromFormat('Y-m-d H:i:s', $this->end);
			if ($end !== false && $end <= $now) return false;
		}

		// now check with repetitions
		if ($this->repeat > 0) {
			// if never run before, then run!
			if (empty($this->lastRun) || $this->lastRun == '0000-00-00 00:00:00' || $this->lastRun == '1970-01-01 01:00:00') return true; // crmv@112251
			$lastrun = DateTime::createFromFormat('Y-m-d H:i:s', $this->lastRun);
			if ($lastrun !== false) {
				$lastrun->setTimestamp($lastrun->getTimestamp() + $this->repeat);
				return ($lastrun <= $now);
			} else {
				return true;
			}

		// and now with hours
		} elseif (!empty($this->runHours)) {

			// build array of hours
			$hoursArrays = array();
			//crmv@60443
			$today = clone $now;
			$yesterday = clone $now;
			$tomorrow = clone $now;
			$yesterday->sub(new DateInterval('P1D'));
			$tomorrow->add(new DateInterval('P1D'));
			foreach ($this->runHours as $rh) {
				// sanity check and format validation
				if (preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?/', $rh)) {
					list($h, $m, $s) = explode(':', $rh);
					// build array for yesterday, today and tomorrow
					// this trick is to get around the changing day problem
					// crmv@152744
					$yesterday_tmp = clone $yesterday;
					$today_tmp = clone $today;
                    $tomorrow_tmp = clone $tomorrow;
					$hoursArrays[0][] = $yesterday_tmp->setTime($h, $m, $s);
					$hoursArrays[1][] = $today_tmp->setTime($h, $m, $s);
					$hoursArrays[2][] = $tomorrow_tmp->setTime($h, $m, $s);
					// crmv@152744e
				}
			}

			// now find the greatest hour < now
			$hoursArrays = array_merge($hoursArrays[0], $hoursArrays[1], $hoursArrays[2]);
			$lasth = $hoursArrays[0];	// this one is for sure < now (it's yesterday)
			for ($i=1; $i<count($hoursArrays); ++$i) {
				if ($now <= $hoursArrays[$i]) break; // crmv@152744
				$lasth = $hoursArrays[$i];
			}
			//crmv@60443 e
			$diff = abs($now->getTimeStamp() - $lasth->getTimestamp());
			
			// crmv@152744
			// avoid multiple execution inside the same cronInterval range
			if (!empty($this->lastRun) && !in_array($this->lastRun, array('0000-00-00 00:00:00', '1970-01-01 01:00:00'))) {
				$lastrun = DateTime::createFromFormat('Y-m-d H:i:s', $this->lastRun);
				if ($lastrun !== false) {
					$diff_lastrun = abs($now->getTimeStamp() - $lastrun->getTimestamp());
					if ($diff_lastrun < $cronInterval){
						return false;
					}
				}
			}
			// crmv@152744e
                        
			return ( $diff < $cronInterval);
		}
		
		// otherwise return false
		return false;
	}

	// extimated the next execution time for this job
	public function getNextExecution() {

	}
}
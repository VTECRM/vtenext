<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@205309 */

require_once('include/BaseClasses.php');

/**
 * This class handles the table vte_filestorage, used to store files
 * not save to the storage folder due to disk errors
 */
class FileStorageDB extends SDKExtendableUniqueClass {

	public $table = '';
	
	public $max_attempts = 48;			// max number of attempts, 0 ti sdisable this check
	public $attempts_interval = 3600;	// try to save the file again after these seconds, 0 to disable this check
	public $cleanup_time = 7200;		// If a file is in SAVING status for these seconds, reset it!
	
	const STATUS_IDLE = 0;		// file is ready to be saved to disk
	const STATUS_SAVING = 1;	// file is being saved to disk
	const STATUS_IGNORED = 2;	// too many attempts or skipped manually... the file won't be saved to disk anymore
	
	public function __construct() {
		global $table_prefix;
		$this->table = $table_prefix.'_filestorage';
	}
	
	/**
	 * Copy the file data in the database
	 */
	public function saveFile($srcfile, $destfile, $fileid) {
		global $adb;
		
		if (!is_readable($srcfile)) return false;
		
		if ($this->isFilePresent($fileid)) return false;
		
		$sql = "INSERT INTO {$this->table} (fileid, status, attempts, last_save_attempt, path) VALUES (?,?,?,?,?)";
		$params = [$fileid, self::STATUS_IDLE, 1, date('Y-m-d H:i:s'), $destfile];
		
		$res = $adb->pquery($sql, $params);
		if (!$res) return false;
		
		$res = $adb->UpdateBlob($this->table, 'filedata', 'fileid = '.intval($fileid), file_get_contents($srcfile));
		if (!$res) return false;
		
		return true;
	}
	
	/**
	 * Save the passed raw data in the database
	 */
	public function saveFileData($data, $destfile, $fileid) {
		global $adb;
		
		if ($this->isFilePresent($fileid)) return false;
		
		$sql = "INSERT INTO {$this->table} (fileid, status, attempts, last_save_attempt, path) VALUES (?,?,?,?,?)";
		$params = [$fileid, self::STATUS_IDLE, 1, date('Y-m-d H:i:s'), $destfile];
		
		$res = $adb->pquery($sql, $params);
		if (!$res) return false;
		
		$res = $adb->UpdateBlob($this->table, 'filedata', 'fileid = '.intval($fileid), $data);
		if (!$res) return false;
		
		return true;
	}
	
	/**
	 * Return true if the file is in the db storage
	 */
	public function isFilePresent($fileid) {
		global $adb;
		$res = $adb->pquery("SELECT fileid FROM {$this->table} WHERE fileid = ?", [$fileid]);
		return ($res && $adb->num_rows($res) > 0);
	}
	
	/**
	 * Return the fileid if the file is in the db storage, false otherwise
	 */
	public function isFilePresentByPath($path) {
		global $adb;
		$res = $adb->pquery("SELECT fileid FROM {$this->table} WHERE path = ?", [$path]);
		if ($res && $adb->num_rows($res) > 0) {
			return intval($adb->query_result_no_html($res, 0, 'fileid'));
		} else {
			return false;
		}
	}
	
	/**
	 * Copy the data from database to a temporary file to be used later
	 */
	public function createTempFile($fileid) {
		global $adb;
		
		$res = $adb->pquery("SELECT filedata FROM {$this->table} WHERE fileid = ?", [$fileid]);
		if ($res && $adb->num_rows($res) > 0) {
			$data = $adb->query_result_no_html($res, 0, 'filedata');
			$fname = tempnam(sys_get_temp_dir(), 'vtefs_');
			if ($fname !== false) {
				$ok = file_put_contents($fname, $data);
				if ($ok === false) return false;
				return $fname;
			}
		}
		
		return false;
	}
	
	/**
	 * Function executed when running the cron 
	 */
	public function runCron() {
		global $adb;
		
		$this->cleanPendingFiles();
		
		$sql = "SELECT fileid, path FROM {$this->table} WHERE fileid > ? AND status = ?";
		$params = [0, self::STATUS_IDLE];
		
		if ($this->attempts_interval > 0) {
			$time = date('Y-m-d H:i:s', time() - $this->attempts_interval);
			$sql .= " AND last_save_attempt < ?";
			$params[] = $time;
		}
		
		if ($this->max_attempts > 0) {
			$sql .= " AND attempts < ?";
			$params[] = $this->max_attempts;
		}
		
		$sql .= " ORDER BY fileid ASC";
		
		$totalAttempts = 0;
		do {
			// take 1 at time
			$res = $adb->limitpQuery($sql, 0, 1, $params);
			if ($adb->num_rows($res) == 0) break;
			
			$fileid = intval($adb->query_result_no_html($res, 0, 'fileid'));
			$path = $adb->query_result_no_html($res, 0, 'path');
			
			$this->log("Trying to save file #$fileid to $path ...");
			
			// check if filename is available
			if (file_exists($path) && filesize($path) > 0) {
				$this->log("File $path already exists... ignoring the file");
				$adb->pquery(
					"UPDATE {$this->table} SET status = ? WHERE fileid = ?",
					[self::STATUS_IGNORED, $fileid]
				);
				continue;
			}
			
			++$totalAttempts;
			$params[0] = $fileid; // prepare for the next query to skip the latest one
			
			// set the status
			$adb->pquery(
				"UPDATE {$this->table} SET status = ?, attempts = attempts+1, last_save_attempt = ? WHERE fileid = ?",
				[self::STATUS_SAVING, date('Y-m-d H:i:s'), $fileid]
			);
			
			// create paths
			$dir = dirname($path);
			$FS = FileStorage::getInstance();
			$r = $FS->createStorageDir($dir."/");
			if ($r === false) {
				// not writable
				$this->log("Path $path is not writable... skipping file, will try later");
				// reset status
				$adb->pquery("UPDATE {$this->table} SET status = ? WHERE fileid = ?", [self::STATUS_IDLE, $fileid]);
				if ($totalAttempts >= 3) {
					$this->log("Already 3 files weren't writable. Interrupting and waiting for the next cron run.");
					break;
				} else {
					continue;
				}
			}

			// check if that file is writable
			$r = file_put_contents($path, 'TEST STRING');
			if ($r === false || $r === 0) {
				// not writable
				$this->log("Path $path is not writable... skipping file, will try later");
				// reset status
				$adb->pquery("UPDATE {$this->table} SET status = ? WHERE fileid = ?", [self::STATUS_IDLE, $fileid]);
				if ($totalAttempts >= 3) {
					$this->log("Already 3 files weren't writable. Interrupting and waiting for the next cron run.");
					break;
				} else {
					continue;
				}
			} else {
				unlink($path);
			}
			
			// read the data
			$dres = $adb->pquery("SELECT filedata FROM {$this->table} WHERE fileid = ?", [$fileid]);
			$data = $adb->query_result_no_html($dres, 0, 'filedata');
			$size = strlen($data);
			
			$r = file_put_contents($path, $data);
			if ($r === false || $r !== $size) {
				// write error or incomplete write
				$this->log("Unable to write the file $path... skipping file, will try later");
				@unlink($path);
				$adb->pquery(
					"UPDATE {$this->table} SET status = ? WHERE fileid = ?",
					[self::STATUS_IDLE, $fileid]
				);
				
			} else {
				// went ok
				$this->log("File $path saved correctly");
				$adb->pquery("DELETE FROM {$this->table} WHERE fileid = ?",	[$fileid]);
			}
			
		} while ($adb->num_rows($res) > 0);
		
	}
	
	/**
	 * Clean the status of files if in saving status for too long
	 */
	public function cleanPendingFiles() {
		global $adb;
		
		$time = date('Y-m-d H:i:s', time() - $this->cleanup_time);
		$adb->pquery("UPDATE {$this->table} SET status = ? WHERE status = ? AND last_save_attempt < ?", [self::STATUS_IDLE, self::STATUS_SAVING, $time]);
	}
	
	protected function log($text) {
		echo $text."\n";
	}
}
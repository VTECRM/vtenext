<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@106069 */

/**
 * Some utilities function to deal with log files
 */
class LogUtils extends SDKExtendableUniqueClass { //crmv@173186
	
	//crmv@173186
	function getGlobalConfig() {
		$vteProp = VTEProperties::getInstance();
		return $vteProp->get('performance.log_globalconfig');
	}
	
	function setGlobalConfig($prop, $value) {
		$vteProp = VTEProperties::getInstance();
		$log_config = $vteProp->get('performance.log_globalconfig');
		$log_config[$prop]['value'] = $value;
		$vteProp->set('performance.log_globalconfig',$log_config);
	}
	
	function getLogConfig($logConfId='') {
		$vteProp = VTEProperties::getInstance();
		$log_config = $vteProp->get('performance.log_config');
		if ($logConfId !== '') {
			return $log_config[$logConfId];
		}
		return $log_config;
	}
	
	function toggleLogProp($logConfId='') {
		$vteProp = VTEProperties::getInstance();
		$log_config = $vteProp->get('performance.log_config');
		if ($logConfId !== '' && isset($log_config[$logConfId])) {
			if ($log_config[$logConfId]['enabled'] == '1') {
				$log_config[$logConfId]['enabled'] = 0;
			} else {
				$log_config[$logConfId]['enabled'] = 1;
			}
			$vteProp->set('performance.log_config',$log_config);
		}
	}
	
	function rotateAllLogConfig() {
		$vteProp = VTEProperties::getInstance();
		$log_config = $vteProp->get('performance.log_config');
		if (!empty($log_config)) {
			foreach($log_config as $log_conf) {
				if (!empty($log_conf['filepath'])) {
					$logs = glob($log_conf['filepath'].'*.log');
					if ($logs && is_array($logs)) {
						foreach ($logs as $logfile) {
							self::rotateLog($logfile, array('maxsize' => $log_conf['rotate_maxsize']));
						}
					}
				}
			}
		}
	}
	//crmv@173186e
	
	// crmv@181096
	/**
	 * Return a list of other non-configurable logs
	 */
	public function getOtherLogs() {
		$list = array(
			array(
				// a namefor the log
				'name' => 'dataimporter',
				// label shown to user
				'label' => getTranslatedString('LBL_DATA_IMPORTER', 'Settings'),
				// if this item represent multiple logs
				'multiple' => true,
				// link to the page with the log content
				'url' => 'index.php?module=Settings&action=DataImporter&parentTab=Settings',
			)
		);
		
		return $list;
	}
	// crmv@181096e
	
	/**
	 * Rename the original file in "$log.X" and rotate the old logs
	 */
	static function rotateLog($log, $options = array()) {
		$options = array_merge(array(
			'logs' => 5,			// number of old logs to keep
			'maxage' => 0,			// rotate only if log is older than this number of days (0 = no check)
			'maxsize' => 0,			// rotate only if log is bigger than this number of MB (0 = no check)
			'compress' => true,		// if true, compress the old logs
			'method' => 'gzip',		// compression method: gzip o bzip2
		), $options);
		
		// check if file exists and is readable
		if (!is_file($log) || !is_readable($log)) return false;
		
		$now = time();
		
		// age check
		if ($options['maxage'] > 0 && filemtime($log) >= $now - ($options['maxage']*3600*24)) return;
		
		// size check
		if ($options['maxsize'] > 0 && filesize($log) <= $options['maxsize']*1024*1024) return;
		
		// ok, rotate!
		$basedir = dirname($log);
		$basename = basename($log);
		
		// find old logs and rotate them!
		$oldlogs = glob($basedir.'/'.$basename.'.*');
		if ($oldlogs && is_array($oldlogs)) {
			usort($oldlogs, array(self, 'logCompare'));
			
			// remove logs
			if (count($oldlogs) >= $options['logs']) {
				for ($i=count($oldlogs)-1; $i>=$options['logs']-1; --$i) {
					@unlink($oldlogs[$i]);
					unset($oldlogs[$i]);
				}
				$oldlogs = array_values($oldlogs);
			}
			
			// shift old logs
			for ($i=count($oldlogs)-1; $i>=0; --$i) {
				$lastpart = $num = substr($oldlogs[$i], strlen($log)+1);
				if (($ppos = strpos($lastpart, '.')) !== false) {
					$num = substr($num, 0, $ppos);
				}
				$num = (int)$num;
				if ($num > 0) {
					$nextnum = $num+1;
					$newname = $log.'.'.$nextnum;
					if ($ppos !== false) {
						$newname .= substr($lastpart, $ppos);
					}
					rename($oldlogs[$i], $newname);
				}
			}
		}
		
		// rename the current log
		$newname = $log.".1";
		$r = rename($log, $newname);
		if (!$r) return false;
		
		// compress the moved log
		if ($options['compress']) {
			if ($options['method'] == 'gzip') {
				$r = self::gzCompressFile($newname);
			} elseif ($options['method'] == 'bzip2') {
				$r = self::bzCompressFile($newname);
			}
			if ($r) @unlink($newname);
		}
		
		return true;
	}
	
	// compare with numerical sorting
	static function logCompare($name1, $name2) {
		$b1 = basename($name1);
		$b2 = basename($name2);
		if ($b1 === $b2) return 0;
		$n1 = filter_var($b1, FILTER_SANITIZE_NUMBER_INT);
		$n2 = filter_var($b2, FILTER_SANITIZE_NUMBER_INT);
		if ($n1 !== '' && $n2 !== '') {
			return (intval($n1) < intval($n2) ? -1 : +1);
		}
		return strcmp($b1, $b2);
	}
	
	
	/**
	 * GZIPs a file on disk (appending .gz to the name)
	 *
	 * From http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
	 * Based on function by Kioob at:
	 * http://www.php.net/manual/en/function.gzwrite.php#34955
	 *
	 * @param string $source Path to file that should be compressed
	 * @param integer $level GZIP compression level (default: 6)
	 * @return string New filename (with .gz appended) if success, or false if operation fails
	 */
	static function gzCompressFile($source, $level = 6, $dest = null) {
		if (!$dest) $dest = $source . '.gz';
		$mode = 'wb' . $level;
		$error = false;
		if ($fp_out = gzopen($dest, $mode)) {
			if ($fp_in = fopen($source,'rb')) {
				while (!feof($fp_in))
					gzwrite($fp_out, fread($fp_in, 1024 * 512));
					fclose($fp_in);
			} else {
				$error = true;
			}
			gzclose($fp_out);
		} else {
			$error = true;
		}
		if ($error)
			return false;
		else
			return $dest;
	}
	
	/**
	 * Compress a file with bzip2 compression
	 */
	static function bzCompressFile($source, $dest = null) {
		if (!$dest) $dest = $source . '.bz2';
		$error = false;
		if ($fp_out = bzopen($dest, 'w')) {
			if ($fp_in = fopen($source,'rb')) {
				while (!feof($fp_in))
					bzwrite($fp_out, fread($fp_in, 1024 * 512));
					fclose($fp_in);
			} else {
				$error = true;
			}
			bzclose($fp_out);
		} else {
			$error = true;
		}
		if ($error)
			return false;
		else
			return $dest;
	}
}
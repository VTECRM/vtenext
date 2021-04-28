<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */

require_once('include/BaseClasses.php');

/**
 * A simple general use logging class, which can be extend easily
 *
 * Examples:
 *   $logger = new VTEBaseLogger($config);
 *   $logger->debug('Starting application...');
 *   $logger->info('Doing things');
 *   $logger->error('Bad error');
 *
 * You can also log the time elapsed:
 *   $logger->info('Starting operation', true);			// the true enable the tic, available for info and debug only
 *   $logger->info('Operation completed in {tac}');		// the magic tag {tac} uses the previous tic to calculate the time
 * In this mode, the tics are nested (LIFO queue)
 *
 * Or if you want to interleave calls:
 *   $logger->info('Starting something...', 'mytic');
 *	 $logger->info('Starting something else ...', 'mytic2');
 *   $logger->info('Finished in {tac}', 'mytic', true);					// the first tic is printed
 *   $logger->info('Finished the second in {tac}', 'mytic2', true);		// the second one is printed
 *
 */
class VTEBaseLogger extends OptionableClass {
	
	/*
	 TODO:
	 1. log to stderr or stdout
	 */
	
	// these are the default values
	protected $options = array(
		'enabled' => true,						// if true, the logging is enabled
		'level' => 4,							// default log level, from 1 = fatal to 5 = debug, default = 4
		'name' => null,							// name of this logger, can be shown in the logs
		'show_timestamp' => true,				// if true, print the timestamp for each log
		'timestamp_format' => 'Y-m-d H:i:s',	// the format used to print the timestamp
		'show_level_type' => true,				// if true, print the log level
		'show_name' => true,					// if true, print the log name
		'replace_tac_string' => '{tac}',		// the tag in the output string which is replaced with the elapsed time since the last tic or the last log function
		'auto_replace_tac' => true,				// if true the previous tag is automatically replaced when found
	);
	
	protected $ticHeap = array();
	protected $ticNamedHeap = array();
	
	protected $newline = "\n";
	protected $levelTypes = array(
		'fatal' => '[FATAL]',
		'error' => '[ERROR]',
		'warn' => '[WARNING]',
		'info' => '[INFO]',
		'debug' => '[DEBUG]',
	);
	
	public function __construct($options = array()) {
		parent::__construct($options);
		if (php_sapi_name() != 'cli') {
			$this->newline = "<br>\n";
		}
	}
	
	public function __destruct() {
		if (count($this->ticHeap) > 0) {
			//$this->warn("Missing tac in Logger");
		}
		if (count($this->ticNamedHeap) > 0) {
			//$this->warn("Missing named tac in Logger");
		}
	}
	
	public function tic($name = null) {
		if (is_null($name)) {
			$this->ticHeap[] = microtime(true);
		} else {
			$this->ticNamedHeap[$name] = microtime(true);
		}
	}
	
	public function tac($name = null) {
		if (empty($name)) {
			if (count($this->ticHeap) == 0) return $this->warn('Not enough tic logged');
			$t0 = array_pop($this->ticHeap);
		} else {
			if (!isset($this->ticNamedHeap[$name])) return $this->warn('Not tic logged found');
			$t0 = $this->ticNamedHeap[$name];
			unset($this->ticNamedHeap[$name]);
		}
		
		$d = microtime(true)-$t0;
		return $d;
	}
	
	public function formatTac($time) {
		if ($time < 60) {
			return round($time,2)."s";
		} elseif ($time < 3600) {
			$m = floor($time/60);
			$s = $time % 60;
			return "{$m}m {$s}s";
		} else {
			$h = floor($time/3600);
			$m = floor($time/60) % 60;
			$s = $time % 60;
			return "{$h}h {$m}m {$s}s";
		}
	}
	
	public function fatal($msg, $tacName = null) {
		if ($this->getOption('level') >= 1) {
			$this->output($msg, 'fatal', $tacName);
		}
		return false;
	}
	
	public function error($msg, $tacName = null) {
		if ($this->getOption('level') >= 2) {
			$this->output($msg, 'error', $tacName);
		}
		return false;
	}
	
	public function warn($msg, $tacName = null) {
		if ($this->getOption('level') >= 3) {
			$this->output($msg, 'warn', $tacName);
		}
		return true;
	}
	
	// alias of warn
	public function warning($msg, $tacName = null) {
		return $this->warn($msg, $tacName);
	}
	
	public function info($msg, $tic = false, $tac = false) {
		if ($this->getOption('level') >= 4) {
			if ($tic && !$tac) $this->tic(is_string($tic) ? $tic : null);
			$this->output($msg, 'info', $tac ? $tic : null);
		}
		return true;
	}
	
	public function debug($msg, $tic = false, $tac = false) {
		if ($this->getOption('level') >= 5) {
			if ($tic && !$tac) $this->tic(is_string($tic) ? $tic : null);
			$this->output($msg, 'debug', $tac ? $tic : null);
		}
		return true;
	}
	
	protected function output($msg, $level = "", $tacName = null) {
		if (!$this->getOption('enabled')) return;
		
		if ($this->getOption('auto_replace_tac') && strpos($msg, $this->getOption('replace_tac_string')) !== false) {
			$tacstr = $this->formatTac($this->tac($tacName));
			$msg = str_replace($this->getOption('replace_tac_string'), $tacstr, $msg);
		}
		$ts = '';
		if ($this->getOption('show_timestamp')) {
			$ts = date($this->getOption('timestamp_format')).' ';
		}
		$name = '';
		$myname = $this->getOption('name');
		if ($this->getOption('show_name') && !empty($myname)) {
			$name = ' ['.$myname.'] ';
		}
		$levelStr = "";
		if ($this->getOption('show_level_type') && !empty($level)) {
			$levelStr = $this->levelTypes[$level].' ';
		}
		//crmv@173186
		$pieces = array(
			'timestamp' => $ts,
			'name' => $name,
			'level' => $levelStr,
			'msg' => $msg,
			'newline' => $this->newline,
		);
		//crmv@173186e
		$this->rawOutput($pieces);
	}
	
	protected function rawOutput($pieces) {
		if (is_array($pieces)) $pieces = implode('', $pieces);
		echo $pieces;
	}
	
}


/**
 * Extension of the base logger, allows to log to a file
 */
class VTEFileLogger extends VTEBaseLogger {
	/*
	 TODO:
	 1. log compression
	 */
	
	// add some options
	protected $fileOptions = array(
		'file' => 'vtelog.log',			// The log file you want to log to, the extension should be .log
		'clean_on_start' => false,		// if true, the logfile is cleared on object initialization
		'rotate' => true,				// crmv@173186 enable rotation
		'rotate_size' => 0,				// file size, in MB, after which the logs are rotated, 0 do disable log rotation
										//    The rotation renames the logs from "logname.log -> logname.log.1 and logname.log.N to logname.log.N+1
										//    It may add a little overhead to the log time when the files are rotated, especially if in the log dir
										//    there are many files
	);
	
	private $fileSize = 0;
	
	public function __construct($options = array()) {
		$options = self::array_merge_recursive_simple($this->fileOptions, $options);
		parent::__construct($options);
		// change the newline according to the os PHP is running on
		$this->newline = PHP_EOL;
		if ($this->getOption('clean_on_start')) {
			$this->clearLog();
		} else {
			// get the current file size
			$fileName = $this->getOption('file');
			if (file_exists($fileName)) $this->fileSize = filesize($fileName);
		}
	}
	
	public function clearLog() {
		$fileName = $this->getOption('file');
		if (file_exists($fileName)) file_put_contents($fileName, ''); // crmv@176614
		$this->fileSize = 0;
	}
	
	public function deleteLog() {
		$fileName = $this->getOption('file');
		if (file_exists($fileName)) unlink($fileName);
		$this->fileSize = 0;
	}
	
	// crmv@176614
	protected function rawOutput($pieces) {
		if (is_array($pieces)) $pieces = implode('', $pieces);
		$fileName = $this->getOption('file');
		
		// create dir if missing
		$dir = dirname($fileName);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
			$this->fixOwner($dir);
		}
		
		file_put_contents($fileName, $pieces, FILE_APPEND);
		
		// set permissions and owner
		@chmod($fileName, 0644);
		$this->fixOwner($fileName);
		
		$this->fileSize += strlen($pieces);
		$this->rotateLogs();
	}
	
	protected function fixOwner($path) {
		global $new_folder_storage_owner;
		if (!empty($new_folder_storage_owner)) {
			if ($new_folder_storage_owner['user'] != '') @chown($path, $new_folder_storage_owner['user']);
			if ($new_folder_storage_owner['group'] != '') @chgrp($path, $new_folder_storage_owner['group']);
		}
	}
	// crmv@176614e
	
	//crmv@173186
	protected function rotateLogs() {
		if ($this->getOption('rotate')) {
			$limit = $this->getOption('rotate_size');
			if ($limit > 0 && $this->fileSize > $limit*1024*1024) {
				// do the rotation
				$renames = array();
				$fileName = $this->getOption('file');
				$list = glob($fileName.'.*', GLOB_NOSORT);
				if (is_array($list)) {
					rsort($list);
					foreach ($list as $oldlog) {
						$n = intval(str_replace($fileName.'.', '', $oldlog));
						if ($n > 0) {
							$renames[$oldlog] = $fileName.'.'.($n+1);
						}
					}
				}
				$renames[$fileName] = $fileName.'.1';
				// now rename, be careful to the order!!
				foreach ($renames as $old => $new) {
					rename($old, $new);
				}
				$this->fileSize = 0;
			}
		}
	}
	
	public function display() {
		$fileName = $this->getOption('file');
		if (file_exists($fileName)) echo nl2br(file_get_contents($fileName));
	}
	//crmv@173186e
}

//crmv@173186
class VTEDBLogger extends VTEBaseLogger {
	
	// add some options
	protected $dbOptions = array(
		'clean_on_start' => false,
		'db' => array('external' => false),
	);
	
	private $db = null;
	
	public function __construct($options = array()) {
		$options = self::array_merge_recursive_simple($this->dbOptions, $options);
		parent::__construct($options);
		$this->newline = '';
		$db_conf = $this->getOption('db');
		if (!$db_conf['external']) {
			global $adb;
			$this->db = $adb;
		} else {
			$this->db = new PearDatabase($db_conf['type'],$db_conf['server'].$db_conf['port'],$db_conf['name'],$db_conf['username'],$db_conf['password'],$db_conf['charset']);
			$this->db->connect();
		}
		if ($this->db && !$this->db->table_exist($this->getOption('table'))) {
			$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$this->getOption('table').'">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="id" type="I" size="11">
				      <KEY/>
				    </field>
					<field name="logtime" type="T">
				      <DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
				    <field name="logname" type="C" size="100"/>
				    <field name="loglevel" type="C" size="10"/>
				    <field name="logmsg" type="XL"/>
				  </table>
				</schema>';
			$schema_obj = new adoSchema($this->db->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
		}
		if ($this->getOption('clean_on_start')) {
			$this->clearLog();
		}
	}
	
	public function clearLog() {
		if ($this->db) $this->db->query("delete from {$this->getOption('table')}");
	}
	
	public function deleteLog() {
		if ($this->db) $this->db->query("drop table {$this->getOption('table')}");
	}
	
	protected function rawOutput($pieces) {
		if ($this->db) {
			$this->db->pquery("insert into {$this->getOption('table')}(id,logtime,logname,loglevel,logmsg) values(?,?,?,?,?)",
			array($this->db->getUniqueID($this->getOption('table')),trim($pieces['timestamp']),trim($pieces['name']),trim($pieces['level']),trim($pieces['msg'])));
		}
		$this->rotateLogs();
	}
	
	protected function rotateLogs() {} // TODO
	
	// crmv@176614
	public function display() {
		$result = $this->db->query("select count(*) as cnt from {$this->getOption('table')}");
		if ($result) $count = $this->db->query_result($result,0,"cnt");
		if ($count > 0) {
			$result = $this->db->limitQuery("select * from {$this->getOption('table')} order by id",$count-1000,1000); // get last 1000 rows
			if ($result && $this->db->num_rows($result) > 0) {
				$logger = new VTEBaseLogger($this->options);
				while($row=$this->db->fetchByAssoc($result,-1,false)) {
					$logger->rawOutput(array(
						'timestamp' => (!empty($row['logtime'])) ? $row['logtime'].' ' : '',
						'name' => (!empty($row['logname'])) ? $row['logname'].' ' : '',
						'level' => (!empty($row['loglevel'])) ? $row['loglevel'].' ' : '',
						'msg' => (!empty($row['logmsg'])) ? nl2br($row['logmsg']).' ' : '',
						'newline' => $logger->newline,
					));
				}
			}
		}
	}
	// crmv@176614e
}
//crmv@173186e

/**
 * Multiplex several loggers into one logger, allowing to log in several locations at once
 */
class VTEMultiLogger extends VTEBaseLogger {
	
	private $loggers = array();
	
	// config is an array of configurations, they must contain the key "type", see addLogger method
	public function __construct($config = array()) {
		foreach ($config as $lcfg) {
			$obj = $this->createLoggerObject($lcfg);
			if ($obj) $this->loggers[] = $obj;
		}
	}
	
	protected function createLoggerObject($cfg) {
		$logger = false;
		$type = $cfg['type'];
		unset($cfg['type']);
		if ($type == 'file') {
			$logger = new VTEFileLogger($cfg);
		} elseif ($type == 'db' || $type == 'database') {
			$logger = new VTEDBLogger($cfg);
		} elseif ($type == 'standard' || $type == '') {
			$logger = new VTEBaseLogger($cfg);
		}
		return $logger;
	}
	
	public function setLoggerOption($id, $option, $value) {
		$this->loggers[$id]->setOption($option, $value);
	}
	
	public function getLoggerOption($id, $option) {
		return $this->loggers[$id]->getOption($option);
	}
	
	public function getLogger($id) {
		return $this->loggers[$id];
	}
	
	// add a new logger at runtime, you can pass a logger object or a config array.
	// If you pass a config array, the array must contain the "type" key which specifies
	// the type of logger: "file", "db" or "database", "standard" or empty value
	public function addLogger($configOrClass) {
		$logid = count($this->loggers);
		if (is_a($configOrClass, 'VTEBaseLogger')) {
			$loggers[] = $configOrClass;
		} elseif (is_array($configOrClass)) {
			$logger = $this->createLoggerObject($configOrClass);
			if ($logger) {
				$loggers[] = $logger;
			} else {
				return false;
			}
		} else {
			return false;
		}
		return $logid;
	}
	
	public function removeLogger($id) {
		unset($this->loggers[$id]);
	}
	
	/*public function tic($name = null) {
		return $this->callMultiFunction(__FUNCTION__, func_get_args());
	}
	 
	public function tac($name = null) {
		return $this->callMultiFunction(__FUNCTION__, func_get_args());
	}*/
	
	public function fatal($msg, $tacName = null) {
		return $this->callMultiFunction(__FUNCTION__, func_get_args());
	}
	
	public function error($msg, $tacName = null) {
		return $this->callMultiFunction(__FUNCTION__, func_get_args());
	}
	
	public function warn($msg, $tacName = null) {
		return $this->callMultiFunction(__FUNCTION__, func_get_args());
	}
	
	// alias for warn
	public function warning($msg, $tacName = null) {
		return $this->warn($msg, $tacName);
	}
	
	public function info($msg, $tic = false, $tac = false) {
		return $this->callMultiFunction(__FUNCTION__, func_get_args());
	}
	
	public function debug($msg, $tic = false, $tac = false) {
		return $this->callMultiFunction(__FUNCTION__, func_get_args());
	}
	
	protected function callMultiFunction($func, $args) {
		foreach ($this->loggers as $log) {
			if (method_exists($log, $func)) {
				$r = call_user_func_array(array($log, $func), $args);
			}
		}
		return $r;
	}
	
}

//crmv@173186 crmv@176614
class VTESystemLogger {
	
	public static function getLogger($configuration) {
		global $table_prefix;
		
		static $loggers = array();
		
		if (!array_key_exists($configuration, $loggers)) {
		
			$logUtils = LogUtils::getInstance();
			$global = $logUtils->getGlobalConfig();
			$config = $logUtils->getLogConfig($configuration);

			$options = array(
				'enabled' => $config['enabled'],
				'level' => $config['level'], // default log level, from 1 = fatal to 5 = debug, default = 4
				'file' => $config['file'],
				'rotate' => false, // do the rotation in the cron Cleaner
				'rotate_size' => $config['rotate_maxsize'],
				'table' => $table_prefix.$config['table'],
				'db' => $global['type']['db'],
			);
			
			$type = $global['type']['value'];
			if ($type == 'file') {
				$logger = new VTEFileLogger($options);
			} elseif ($type == 'db' || $type == 'database') {
				$logger = new VTEDBLogger($options);
			} elseif ($type == 'standard' || $type == '') {
				$logger = new VTEBaseLogger($options);
			}
			
			$loggers[$configuration] = $logger;
		}
		
		
		return $loggers[$configuration];
	}
	
	public static function log($logname, $title, $str = '', $new = false) {
		
		$logger = self::getLogger($logname);
		if (!$logger) return;
	
		if ($new) $logger->info(str_repeat('-', 250));
		if (empty($title)) {
			$message = $str;
		} else {
			$message = strtoupper($title).': '.$str;
		}	
		$logger->info($message);
	}
	
}
//crmv@173186e crmv@176614e
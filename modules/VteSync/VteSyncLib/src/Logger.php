<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
namespace VteSyncLib;

class Logger {

	const LOG_TYPE_DEBUG = 5;
	const LOG_TYPE_INFO = 4;
	const LOG_TYPE_WARNING = 3;
	const LOG_TYPE_ERROR = 2;
	const LOG_TYPE_FATAL = 1;

	public $log_level = self::LOG_TYPE_INFO;
	public $color = 'auto'; // 'auto', true or false
	
	protected $prefix = '';
	protected $useColor = false;
	
	protected static $ansiColours = array(
		'black' => "0;30m",
		'darkgray' => "1;30m",
		'blue' => "0;34m",
		'lightblue' => "1;34m",
		'green' => "0;32m",
		'lightgreen' => "1;32m",
		'cyan' => "0;36m",
		'lightcyan' => "1;36m",
		'red' => "0;31m",
		'lightred' => "1;31m",
		'purple' => "0;35m",
		'lightpurple' => "1;35m",
		'brown' => "0;33m",
		'yellow' => "1;33m",
		'lightgray' => "0;37m",
		'white' => "1;37m",
	);

	public function __construct($loglevel = null, $color = 'auto') {
		$this->color = $color;
		
		if (!is_null($loglevel) && $loglevel > 0) {
			$this->log_level = max(self::LOG_TYPE_FATAL, min($loglevel, self::LOG_TYPE_DEBUG));
		}
		
		if ($this->color == 'auto') {
			if (php_sapi_name() === 'cli') {
				// detect if the current terminal support ansi codes
				$this->useColor = (function_exists('posix_isatty') && posix_isatty(STDOUT));
			} else {
				$this->useColor = false;
			}
		} else {
			$this->useColor = !!$this->color;
		}
	}
	
	public function setLevel($loglevel) {
		$this->log_level = max(self::LOG_TYPE_FATAL, min($loglevel, self::LOG_TYPE_DEBUG));
	}
	
	// crmv@190016
	// return the current level in monolog format
	public function getMonologLevel() {
		if ($this->log_level == self::LOG_TYPE_DEBUG) {
			return 'DEBUG';
		} elseif ($this->log_level == self::LOG_TYPE_INFO) {
			return 'INFO';
		} elseif ($this->log_level == self::LOG_TYPE_WARNING) {
			return 'WARNING';
		} elseif ($this->log_level == self::LOG_TYPE_ERROR) {
			return 'ERROR';
		} elseif ($this->log_level == self::LOG_TYPE_FATAL) {
			return 'CRITICAL';
		}
	}
	// crmv@190016e

	public function setPrefix($p) {
		$this->prefix = $p;
	}

	protected function logMessage($message, $class = '') {
		echo "[".date('Y-m-d H:i:s')."] ".($class ? "$class " : "").($this->prefix ? $this->prefix." " : "")."$message\n";
	}
	
	protected function makeColored($text, $color) {
		if (!$this->useColor) return $text;
		$code = self::$ansiColours[$color];
		return "\x1B[$code".$text."\x1B[0m";
	}

	public function debug($msg) {
		if ($this->log_level >= self::LOG_TYPE_DEBUG) {
			$this->logMessage($msg, $this->makeColored('[DEBUG]', 'darkgray'));
		}
		return true;
	}
	public function info($msg) {
		if ($this->log_level >= self::LOG_TYPE_INFO) {
			$this->logMessage($msg, '[INFO]');
		}
		return true;
	}
	public function warning($msg) {
		if ($this->log_level >= self::LOG_TYPE_WARNING) {
			$this->logMessage($msg, $this->makeColored('[WARN]', 'yellow'));
		}
		return true;
	}
	public function error($msg) {
		if ($this->log_level >= self::LOG_TYPE_ERROR) {
			$this->logMessage($msg, $this->makeColored('[ERROR]', 'red'));
		}
		return false;
	}
	public function fatal($msg) {
		if ($this->log_level >= self::LOG_TYPE_FATAL) {
			$this->logMessage($msg, $this->makeColored('[FATAL]', 'lightred'));
		}
		return false;
	}
	
}
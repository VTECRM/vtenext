<?php

/* crmv@148761 */

/**
 * Empty class to disable logging
 */
class Logger {
	
	static $instance = null;
	
	public static function configure() {
	}
	
	public static function getLogger() {
		if (!self::$instance) {
			self::$instance = new Logger();
		}
		return self::$instance;
	}
	
	function info($message) {
	}
	
	function debug($message) {
	}
	
	function warn($message) {
	}
	
	function fatal($message) {
	}
	
	function error($message) {
	}

}

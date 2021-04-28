<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@74560 crmv@115378 */

require_once('include/utils/VTEProperties.php');

/**
 * Performance perference API
 */
class PerformancePrefs {
	
	static protected $oldFile = 'config.performance.php';
	static protected $oldTempFile = 'config.performance.temp.php';

	// prefix in the vteprop table
	static public $prefix = 'performance.';
	
	/**
	 * Migrate the old config.performance file to db
	 */
	static public function migrateConfig() {
		// old config files
		
		$VP = VTEProperties::getInstance();
		
		if (is_readable(self::$oldFile)) {
			@include(self::$oldFile);
			if(isset($PERFORMANCE_CONFIG) && is_array($PERFORMANCE_CONFIG)){
				// save
				foreach ($PERFORMANCE_CONFIG as $key => $value) {
					$VP->set(self::key2db($key), $value);
				}
				
				//save overrides
				if (is_readable(self::$oldTempFile)) {
					@include(self::$oldTempFile);
					if(isset($PERFORMANCE_CONFIG_TEMP) && is_array($PERFORMANCE_CONFIG_TEMP)){
						foreach ($PERFORMANCE_CONFIG_TEMP as $key => $value) {
							$VP->setOverride(self::key2db($key), $value);
						}
					}
				}
				// remove old files!
				@unlink(self::$oldFile);
				@unlink(self::$oldTempFile);
			}
		} 
	}
	
	static function isMigrated() {
		return !file_exists('config.performance.php');
	}
	
	// convert old style keys (uppercase in config.performance) to the db style
	static protected function key2db($key) {
		return self::$prefix . strtolower($key);
	}
	
	static protected function db2key($key) {
		$len = strlen(self::$prefix);
		return strtoupper(substr($key, $len));
	}
	
	static public function getTemp($key) {
		$VP = VTEProperties::getInstance();
		$oval = $VP->getOverride(self::key2db($key));
		return $oval;
	}
	
	static public function setTemp($key, $value, $persistence = 'db') {
		$VP = VTEProperties::getInstance();
		$VP->setOverride(self::key2db($key), $value, $persistence);
		return true;
	}
	
	static public function unsetTemp($key, $persistence = 'db') {
		$VP = VTEProperties::getInstance();
		$VP->unsetOverride(self::key2db($key), $persistence);
		return true;
	}
	
	static public function cleanTemp($persistence = 'db') {
		$VP = VTEProperties::getInstance();
		$VP->unsetAllOverrides($persistence);
		return true;
	}
	
	/**
	 * Get performance parameter configured value or default one
	 */
	static public function get($key, $defvalue=false, $noOverride = false) {
		if (!self::isMigrated()) {
			// not migrated yet! read from old file!
			return self::getLegacyValue($key, $defvalue);
		}
		
		$VP = VTEProperties::getInstance();
		$v = $VP->get(self::key2db($key), false, $noOverride);
		if ($v !== null) return $v;
		return $defvalue;
	}
	
	static protected function getLegacyValue($key, $defvalue = false) {
		global $PERFORMANCE_CONFIG;
		if (!is_array($PERFORMANCE_CONFIG)) {
			@include(self::$oldFile);
		}
		if (isset($PERFORMANCE_CONFIG[$key])) {
			return $PERFORMANCE_CONFIG[$key];
		}
		return $defvalue;
	}
	
	/** Get boolean value */
	static public function getBoolean($key, $defvalue=false, $skipTemp = false) {
		return self::get($key, $defvalue, $skipTemp);
	}
	
	/** Get Integer value */
	static public function getInteger($key, $defvalue=false, $skipTemp = false) {
		return intval(self::get($key, $defvalue, $skipTemp));
	}
	
	/**
	 * Get all configuration
	 */
	static public function getAll() {
		$VP = VTEProperties::getInstance();
		$values = $VP->getAll();
		$outvalues = array();
		// filter, get only performance prefs
		$len = strlen(self::$prefix);
		foreach ($values as $key => $val) {
			if (substr($key, 0, $len) == self::$prefix) {
				$outvalues[self::db2key($key)] = $val;
			}
		}
		return $outvalues;
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@168297 */

class SDK {

	static public $configFile = 'SDK/config.php';
	static public $baseDir = 'SDK/src/';
	static public $baseTplDir = 'SDK/';
	
	static protected $config = null;
	
	static protected function getConfigVar($var) {
		if (is_null(self::$config)) {
			self::$config = array();
			if (is_readable(self::$configFile)) {
				include('SDK/config.php');
				if (is_array($sdk_config)) {
					self::$config = $sdk_config;
				}
			}
		}
		return self::$config[$var];
	}

	/**
	 *
	 */
	static public function loadTranslations($language) {
		global $app_strings;
		
		$langs = self::getConfigVar('languages');
		if (is_array($langs[$language])) {
			foreach ($langs[$language] as $file) {
				$path = self::$baseDir . str_replace('..', '', $file);
				if (is_readable($path)) {
					include($path);
					if (is_array($sdk_strings)) {
						$app_strings = array_replace($app_strings, $sdk_strings);
					}
				}
			}
		}
	}
	
	/**
	 *
	 */
	static public function loadGlobalPhp() {
		$files = self::getConfigVar('global_php');
		
		if (is_array($files)) { 
			foreach ($files as $file) {
				$path = self::$baseDir . str_replace('..', '', $file);
				if (is_readable($path)) {
					require_once($path);
				}
			}
		}
	}
	
	/**
	 *
	 */
	static public function getTemplate($template) {
		$tpls = self::getConfigVar('templates');
		
		if (is_array($tpls) && array_key_exists($template, $tpls)) { //crmv@171574
			$path = self::$baseTplDir . str_replace('..', '', $tpls[$template]);
			return $path;
		}
		return false;
	}
	
	/**
	 *
	 */
	static public function getGlobalJs() {
		$files = self::getConfigVar('global_js');
		
		$list = array();
		if (is_array($files)) { 
			foreach ($files as $file) {
				$path = self::$baseDir . str_replace('..', '', $file);
				$stripParam = preg_replace('/\?.*$/', '', $path);
				if (is_readable($stripParam)) {
					$list[] = $path;
				}
			}
		}
		
		return $list;
	}
	
	/**
	 *
	 */
	static public function getModuleJs($module) {
		$files = self::getConfigVar('module_js');
		
		$list = array();
		if (is_array($files)) {
			if (array_key_exists($module, $files)) {
				foreach ($files[$module] as $file) {
					$path = self::$baseDir . str_replace('..', '', $file);
					$stripParam = preg_replace('/\?.*$/', '', $path);
					if (is_readable($stripParam)) {
						$list[] = $path;
					}
				}
			}
		}

		return $list;
	}
	
	/**
	 *
	 */
	static public function getGlobalCss() {
		$files = self::getConfigVar('global_css');
		
		$list = array();
		if (is_array($files)) { 
			foreach ($files as $file) {
				$path = self::$baseDir . str_replace('..', '', $file);
				$stripParam = preg_replace('/\?.*$/', '', $path);
				if (is_readable($stripParam)) {
					$list[] = $path;
				}
			}
		}
		
		return $list;
	}
	
}
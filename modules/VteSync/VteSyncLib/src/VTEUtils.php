<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
namespace VteSyncLib;

class VTEUtils {

	static protected $vteIncluded = array();
	
	/**
	 * Extract a variable from the VTE config file
	 * @param string $path The path of the VTE
	 * @param string $varname The variable name
	 */
	public static function getConfigVar($path, $varname) {
		$path = rtrim($path, '/');
		require($path.'/config.inc.php');
		
		return $$varname;
	}

	/**
	 * Include the VTE runtime to be able to use its functions.
	 * Warning: this function changes the current directory!
	 * Warning: you cannot call this function many times with different paths,
	 * since the VTE runtime cannot coexist with another one.
	 * @param string $path The path of the VTE
	 */
	public static function includeVTE($path) {
		$path = rtrim($path, '/');
		
		if (static::$vteIncluded[$path]) return;
		
		global $root_directory, $adb, $table_prefix, $dbconfig, $log;
		global $default_timezone, $default_theme, $default_language;
		global $default_decimal_separator, $default_thousands_separator, $default_decimals_num;
		
		// check if there is already a vte loaded
		$cleanRoot = rtrim($root_directory, '/');
		if ($cleanRoot) {
			if ($cleanRoot == $path) {
				// already included the same vte
				static::$vteIncluded[$path] = true;
				return;
			} else {
				throw new Exception('Another VTE has already been included.');
			}
		}
		
		require($path.'/config.inc.php');
		chdir($root_directory);
		require_once('include/utils/utils.php');
		// at this point we have all the VTE and the DB connection
		
		// include WS for basic CRUD operations
		require_once('include/Webservices/Utils.php');
	}

}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/** 
 * This function retrieves an application language file and returns the array of strings included in the $app_list_strings var.
 * If you are using the current language, do not call this function unless you are loading it for the first time 
 */
function return_app_list_strings_language($language)
{
	global $log;
	$log->debug("Entering return_app_list_strings_language(".$language.") method ...");
	global $app_list_strings, $default_language, $log, $translation_string_prefix;
	$temp_app_list_strings = $app_list_strings;
	$language_used = $language;
	$app_list_strings = get_lang_strings('APP_LIST_STRINGS',$language);
	if(!$app_list_strings)
	{
		$log->warn("Unable to find the application language file for language: ".$language);
		$app_list_strings = get_lang_strings('APP_LIST_STRINGS',$default_language);
		$language_used = $default_language;
	}
	if(!$app_list_strings)
	{
		//$log->fatal("Unable to load the application language file for the selected language($language) or the default language($default_language)"); //crmv@36556
		$log->debug("Exiting return_app_list_strings_language method ...");
		return null;
	}
	$return_value = $app_list_strings;
	$app_list_strings = $temp_app_list_strings;
	$log->debug("Exiting return_app_list_strings_language method ...");
	return $return_value;
}

/**
 * Retrieve the app_currency_strings for the required language.
 */
function return_app_currency_strings_language($language) {
	global $log;
	$log->debug("Entering return_app_currency_strings_language(".$language.") method ...");
	global $app_currency_strings, $default_language, $log, $translation_string_prefix;
	// Backup the value first
	$temp_app_currency_strings = $app_currency_strings;
	$app_currency_strings = get_lang_strings('APP_CURRENCY_STRINGS',$language);
	if(!$app_currency_strings)
	{
		$log->warn("Unable to find the application language file for language: ".$language);
		$app_currency_strings = get_lang_strings('APP_CURRENCY_STRINGS',$default_language);
		$language_used = $default_language;
	}
	if(!$app_currency_strings)
	{
		//$log->fatal("Unable to load the application language file for the selected language($language) or the default language($default_language)"); //crmv@36556
		$log->debug("Exiting return_app_currency_strings_language method ...");
		return null;
	}
	$return_value = $app_currency_strings;
	
	// Restore the value back
	$app_currency_strings = $temp_app_currency_strings;

	$log->debug("Exiting return_app_currency_strings_language method ...");
	return $return_value;
}

/** This function retrieves an application language file and returns the array of strings included.
 * If you are using the current language, do not call this function unless you are loading it for the first time
 */
function return_application_language($language)
{
	global $log;
	$log->debug("Entering return_application_language(".$language.") method ...");
	global $app_strings, $default_language, $log, $translation_string_prefix;
	$temp_app_strings = $app_strings;
	$language_used = $language;
	$app_strings = get_lang_strings('APP_STRINGS',$language);
	if(!$app_strings)
	{
		$log->warn("Unable to find the application language file for language: ".$language);
		$app_strings = get_lang_strings('APP_STRINGS',$default_language);
		$language_used = $default_language;
	}

	if(!$app_strings)
	{
		//$log->fatal("Unable to load the application language file for the selected language($language) or the default language($default_language)"); //crmv@36556
		$log->debug("Exiting return_application_language method ...");
		return null;
	}
	// If we are in debug mode for translating, turn on the prefix now!
	if($translation_string_prefix)
	{
		foreach($app_strings as $entry_key=>$entry_value)
		{
			$app_strings[$entry_key] = $language_used.' '.$entry_value;
		}
	}

	$return_value = $app_strings;
	$app_strings = $temp_app_strings;

	$log->debug("Exiting return_application_language method ...");
	return $return_value;
}

/** This function retrieves a module's language file and returns the array of strings included.
 * If you are in the current module, do not call this function unless you are loading it for the first time
 */
function return_module_language($language, $module)
{
	global $log;
	$log->debug("Entering return_module_language(".$language.",". $module.") method ...");
	global $mod_strings, $default_language, $log, $currentModule, $translation_string_prefix;
	static $cachedModuleStrings = array();
	if(isset($cachedModuleStrings[$module.$language])) { //crmv@47905
		$log->debug("Exiting return_module_language method ...");
		return $cachedModuleStrings[$module.$language];
	}
	$temp_mod_strings = $mod_strings;
	$language_used = $language;
	$mod_strings = get_lang_strings($module,$language);
	if(!$mod_strings)
	{
		$log->warn("Unable to find the module language file for language: ".$language." and module: ".$module);
		if($default_language == 'en_us') {
			$mod_strings = get_lang_strings($module,$default_language);
			$language_used = $default_language;
		} else {
			$mod_strings = get_lang_strings($module,$default_language);
			if(!$mod_strings) {
				$mod_strings = get_lang_strings($module,'en_us');
				$language_used = 'en_us';
			} else {
				$language_used = $default_language;
			}
		}
	}
	if(!$mod_strings)
	{
		//$log->fatal("Unable to load the module($module) language file for the selected language($language) or the default language($default_language)"); //crmv@36556
		$log->debug("Exiting return_module_language method ...");
		$return_value = null;
	}
	$return_value = $mod_strings;
	$mod_strings = $temp_mod_strings;
	$log->debug("Exiting return_module_language method ...");
	$cachedModuleStrings[$module.$language] = $return_value;
	return $return_value;
}

/** This function returns the mod_strings for the current language and the specified module from the db
*/
function get_lang_strings($module,$language){
	global $adb;
	if ($module=='Events') {
		$module='Calendar'; //vtc crmv@9493
	}
	if (!$adb->table_exist('sdk_language') || $module == '' || $language == '') {	//crmv@27624
		return array();
	}
	static $cache_lang_strings = array();
	if (array_key_exists($module.$language, $cache_lang_strings)) return $cache_lang_strings[$module.$language];
	$strings = SDK::getCachedLanguage($module,$language);
	if ($strings === false && (in_array($module,array('APP_CURRENCY_STRINGS','APP_LIST_STRINGS','APP_STRINGS')) || isModuleInstalled($module))) { // crmv@181165
		SDK::cacheLanguage($language, $module); // crmv@187020
		$cache_lang_strings[$module.$language] = $strings = SDK::getCachedLanguage($module,$language);
	}
	//crmv@27624e	crmv@47905bise
	return $strings;
}

function replace_version_strings($val){
	global $enterprise_current_version,$enterprise_mode,$enterprise_project;
	$patterns = Array(
		'/\$enterprise_current_version/',
		'/\$enterprise_mode/',
		'/\$enterprise_project/',
	);
	$replacements = Array(
		$enterprise_current_version,
		$enterprise_mode,
		$enterprise_project
	);
	return preg_replace($patterns,$replacements,$val);
}

/*This function returns the mod_strings for the current language and the specified module
*/

function return_specified_module_language($language, $module)
{
	global $log;
	global $default_language, $translation_string_prefix;
	if ($module=='Events') 
		$module='Calendar'; //vtc crmv@9493
	$mod_strings = get_lang_strings($module,$language);
	if(!$mod_strings)
	{
		$log->warn("Unable to find the module language file for language: ".$language." and module: ".$module);
		$mod_strings = get_lang_strings($module,$default_language);
		$language_used = $default_language;
	}

	if(!$mod_strings)
	{
		//$log->fatal("Unable to load the module($module) language file for the selected language($language) or the default language($default_language)"); //crmv@36556
		$log->debug("Exiting return_module_language method ...");
		return null;
	}
	$log->debug("Exiting return_module_language method ...");
	return $mod_strings;
}

/** This function retrieves an application language file and returns the array of strings included in the $mod_list_strings var.
 * If you are using the current language, do not call this function unless you are loading it for the first time 
 */
function return_mod_list_strings_language($language,$module)
{
	global $log;
	$log->debug("Entering return_mod_list_strings_language(".$language.",".$module.") method ...");
	global $mod_list_strings, $default_language, $log, $currentModule,$translation_string_prefix;

	$language_used = $language;
	$temp_mod_list_strings = $mod_list_strings;

	if($currentModule == $module && isset($mod_list_strings) && $mod_list_strings != null)
	{
		$log->debug("Exiting return_mod_list_strings_language method ...");
		return $mod_list_strings;
	}

	@include("modules/$module/language/$language.lang.php");

	if(!isset($mod_list_strings))
	{
		//$log->fatal("Unable to load the application list language file for the selected language($language) or the default language($default_language)"); //crmv@36556
		$log->debug("Exiting return_mod_list_strings_language method ...");
		return null;
	}

	$return_value = $mod_list_strings;
	$mod_list_strings = $temp_mod_list_strings;

	$log->debug("Exiting return_mod_list_strings_language method ...");
	return $return_value;
}

/** 
 * This function retrieves a theme's language file and returns the array of strings included.
 */
function return_theme_language($language, $theme)
{
	global $log;
	$log->debug("Entering return_theme_language(".$language.",". $theme.") method ...");
	global $mod_strings, $default_language, $log, $currentModule, $translation_string_prefix;

	$language_used = $language;

	@include("themes/$theme/language/$current_language.lang.php");
	if(!isset($theme_strings))
	{
		$log->warn("Unable to find the theme file for language: ".$language." and theme: ".$theme);
		require("themes/$theme/language/$default_language.lang.php");
		$language_used = $default_language;
	}

	if(!isset($theme_strings))
	{
		//$log->fatal("Unable to load the theme($theme) language file for the selected language($language) or the default language($default_language)"); //crmv@36556
		$log->debug("Exiting return_theme_language method ...");
		return null;
	}

	// If we are in debug mode for translating, turn on the prefix now!
	if($translation_string_prefix)
	{
		foreach($theme_strings as $entry_key=>$entry_value)
		{
			$theme_strings[$entry_key] = $language_used.' '.$entry_value;
		}
	}

	$log->debug("Exiting return_theme_language method ...");
	return $theme_strings;
}

function insert_language($module,$language,$mod_strings){
	global $adb;

	$table = 'sdk_language';
	$columns = array('languageid', 'module', 'language', 'label', 'trans_label');
	
	$limitKB = 2048;	// if the data to be inserted reaches this limit (in KB), flush it immediately!
	$limitRows = 200;	// do not store more than 200 rows in the local array
						// Note: here you could actually increase this number, but during installation
						// there are weird memory errors.
	
	$sumlength = function($sum, $item) {
		return $sum + strlen($item);
	};
	
	$inserts = array();
	$ids = $adb->getMultiUniqueID("sdk_language", count($mod_strings));
	$i = 0;
	$totLen = 0;
	foreach ($mod_strings as $label=>$trans_label){
		$row = array();
		$row[] = $ids[$i++];
		$row[] = $module;
		$row[] = $language;
		if ($module == 'ALERT_ARR') {
			$row[] = $label;
		} else {
			$row[] = correctEncoding(html_entity_decode($label, ENT_COMPAT, 'UTF-8'));
		}
		if (is_array($trans_label)){
			foreach ($trans_label as $k=>$trans_val) {
				if ($module == 'ALERT_ARR') {
					$trans_label[$k] = $trans_val;
				} else {
					$trans_label[$k] = correctEncoding(html_entity_decode($trans_val, ENT_COMPAT, 'UTF-8'));
				}
			}
			$row[] = Zend_Json::encode($trans_label);
		} else {
			if ($module == 'ALERT_ARR') {
				$row[] = $trans_label;
			} else {
				$row[] = correctEncoding(html_entity_decode($trans_label, ENT_COMPAT, 'UTF-8'));
			}
		}
		// calc the length of data (roughly)
		$rowLen = array_reduce($row, $sumlength, 0);
		$totLen += $rowLen;
		
		if ($totLen >= $limitKB*1024 || count($inserts) >= $limitRows) {
			//time to flush the previous list
			$adb->bulkInsert($table, $columns, $inserts);
			$totLen = $rowLen;
			unset($inserts);
			$inserts = array();
		}
		// add to the insert list
		$inserts[] = $row;

	}
	// fast insert the other rows!
	if (count($inserts) > 0) {
		$adb->bulkInsert($table, $columns, $inserts);
	}
	
	SDK::clearSessionValue('sdk_js_lang');
	SDK::clearSessionValue('vte_languages');
}

/*
 * Encode string $text in UTF8, regardless of input encoding
 */
function correctEncoding($text,$dest_encoding='UTF-8',$current_encoding='',&$detectedEncoding=null) { // crmv@92218
	$text .= ' ';
	if ($current_encoding == '') {
		// detect input encoding
		if (function_exists('mb_detect_encoding')) {
			// add here new encodings to check, pay attention to the order!
			$encorder = 'ASCII,UTF-8,ISO-8859-1';
			$current_encoding = mb_detect_encoding($text, $encorder);
		} else {
			// default fallback
			$current_encoding = 'ISO-8859-1';
		}
	}
	$detectedEncoding = $current_encoding; // crmv@92218
	// check if we need conversion
	if ($current_encoding != $dest_encoding) {
		// convert to new encoding
		if (function_exists('iconv')) {
			$text = iconv($current_encoding, $dest_encoding.'//IGNORE', $text);
		} elseif ($current_encoding == 'ISO-8859-1' && $dest_encoding == 'UTF-8') {
			$text = utf8_encode($text);
		}
	}
	$text = substr($text,0,-1);
	return $text;
}

<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@201442 */

// TODO:
// . translate countries in various languages

/**
 * This class will help in dealing with uitype 310 and 311
 */
class CountriesFieldUtils {

	protected static $valuesCache = [];
	protected static $fieldInfoCache = [];

	/**
	 * Get all possible values for the specified field
	 */
	public function getAllValues($fieldid) {
		
		if (isset(self::$valuesCache[$fieldid])) {
			return self::$valuesCache[$fieldid];
		}

		$countries = [];
		
		$cls = new League\ISO3166\ISO3166;
		$finfo = $this->getFieldInfo($fieldid);
		
		// show only these countries
		if ($finfo && is_array($finfo['only'])) {
			foreach ($finfo['only'] as $code) {
				$c = $cls->alpha2($code);
				$countries[$c['alpha2']] = $c['name'];
			}
		} else {
			// show all, with exclusions
			foreach ($cls as $c) {
				if ($finfo && is_array($finfo['exclude']) && in_array($c['alpha2'], $finfo['exclude'])) continue;
				$countries[$c['alpha2']] = $c['name'];
			}
		}
		
		// and sort them by name/label
		asort($countries);
		
		self::$valuesCache[$fieldid] = $countries;
		
		return $countries;
	}
	
	
	/**
	 * Add a flag before the label as a unicode emoji
	 */
	public function addUnicodeFlags(&$countries) {
		require('UnicodeFlags.php');
		foreach ($countries as $k => &$name) {
			$flag = $unicode_flags[$k];
			if ($flag) {
				$name = $flag.' '.$name;
			}
		}
	}
	
	/**
	 * Return the unicode emoji flag
	 */
	public function getUnicodeFlag($countryCode) {
		require('UnicodeFlags.php');
		$flag = $unicode_flags[$countryCode];
		return $flag;
	}
	
	/**
	 * Get the default country[ies] for the specified field
	 */
	public function getDefaultCountry($fieldid, $mode) {
		$finfo = $this->getFieldInfo($fieldid);
		if ($finfo && isset($finfo['default'])) {
			$default = $finfo['default'];
		} else {
			$default = ''; // none
		}
		return $default;
	}
	
	public function getFieldInfo($fieldid) {
		if ($fieldid > 0 && !isset(self::$fieldInfoCache[$fieldid])) {
			$field = Vtecrm_Field::getInstance($fieldid);
			self::$fieldInfoCache[$fieldid] = $field->getFieldInfo();
		} elseif (!$fieldid) {
			return false;
		}
		
		return self::$fieldInfoCache[$fieldid];
	}
	
}
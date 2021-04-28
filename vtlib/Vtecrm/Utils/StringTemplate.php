<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

/**
 * Template class will enable you to replace a merge fields defined in the String
 * with values set dynamically.
 *
 * @author Prasad
 * @package vtlib
 */
class Vtecrm_StringTemplate {
	// Template variables set dynamically
	private $tplvars = [];

	/**
	 * Identify variable with the following pattern
	 * $VARIABLE_KEY$
	 */
	private $_lookfor = '/\$([^\$]+)\$/';

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Assign replacement value for the variable.
	 */
	public function assign($key, $value) {
		$this->tplvars[$key] = $value;
	}	

	/**
	 * Get replacement value for the variable.
	 */
	public function get($key) {
		$value = false;
		if(isset($this->tplvars[$key])) {
			$value = $this->tplvars[$key];
		}
		return $value;
	}

	/**
	 * Clear all the assigned variable values.
	 * (except the once in the given list)
	 * @param bool $exceptVars
	 */
	public function clear($exceptVars=false) {
		$restoreVars = [];
		if($exceptVars && is_array($exceptVars)) {
			foreach($exceptVars as $varKey) {
                $restoreVars[$varKey] = $this->get($varKey);
			}
		}		
		unset($this->tplvars);

		$this->tplvars = [];
		foreach($restoreVars as $key=>$val) $this->assign($key, $val);
	}

	/**
	 * Clean up the input to be used as a regex
	 * @access private
	 */
	private function __formatAsRegex($value) {
		// If / is not already escaped as \/ do it now
		$value = preg_replace('/\//', '\\/', $value);
		// If $ is not already escaped as \$ do it now
		$value = preg_replace('/(?<!\\\)\$/', '\\\\$', $value);
		return $value;
	}

	/**
	 * Merge the given file with variable values assigned.
	 * @param $input input string template
	 * @param $avoidLookup should be true if only verbatim file copy needs to be done
	 * @returns merged contents
	 */
	public function merge($input, $avoidLookup=false) {
		if(empty($input)) return $input;

		if(!$avoidLookup) {
			/** Look for variables */
			$matches = [];
			preg_match_all($this->_lookfor, $input, $matches);

			/** Replace variables found with value assigned. */
			$matchcount = count($matches[1]);
			for($index = 0; $index < $matchcount; ++$index) {
				$matchstr = $matches[0][$index];
				$matchkey = $matches[1][$index];

				$matchstr_regex = $this->__formatAsRegex($matchstr);

				$replacewith = $this->get($matchkey);
				if($replacewith) {
                    $input = preg_replace(
						"/$matchstr_regex/", $replacewith, $input);
				}
			}
		}
		return $input;
	}
}

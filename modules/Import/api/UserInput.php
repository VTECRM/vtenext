<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class Import_API_UserInput {
	protected $valuemap;
	
	function __construct($values = array()) {
		$this->valuemap = $values;
	}

	function get($key) {
		if(isset($this->valuemap[$key])) {
			return $this->valuemap[$key];
		}
		return '';
	}
	
	function has($key) {
		return isset($this->valuemap[$key]);
	}
	
	function set($key, $newvalue) {
		$this->valuemap[$key]= $newvalue;
	}
}
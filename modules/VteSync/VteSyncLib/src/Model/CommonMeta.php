<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Model;

/**
 * Class representing common metadata for a module
 */
class CommonMeta {

	protected $module;
	protected $picklists = array();

	public function __construct($module) {
		$this->module = $module;
	}
	
	public function setModule($m) {
		$this->module = $m;
	}
	
	public function getModule() {
		return $this->module;
	}
	
	public function getPicklists() {
		return $this->picklists;
	}
	
	public function getFlatPicklists() {
		$list = array();
		
		foreach ($this->picklists as $pname => $values) {
			foreach ($values as $val) {
				$list[$pname][$val['value']] = $val['label'];
			}
		}
		
		return $list;
	}
	
	public function getPicklist($name) {
		return $this->picklists[$name];
	}
	
	public function setPicklist($name, $list) {
		$this->picklists[$name] = $list;
	}
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@193294 */

/**
 * This class handles the interactins with the _field table,
 * providing also a double caching layer (request and global cache)
 */
class FieldTableUtils extends VTEUniqueClass {

	const SORTFN_SEQUENCE = 1;
	const SORTFN_BLOCK_SEQUENCE = 2;

	public function __construct() {
		global $table_prefix;
		$this->tcache = new ClusteredTableCache($table_prefix.'_field', 'fieldid', 'tabid');
		$this->tcache->addIndex('fieldname', 'fieldname');
	}
	
	/**
	 * Enable caching when retrieving the fields
	 */
	public function enableCache() {
		return $this->tcache->enableCache();
	}
	
	/**
	 * Disable caching
	 */
	public function disableCache() {
		return $this->tcache->disableCache();
	}
	
	/**
	 * Get all fields for all modules
	 */
	public function getAllFields() {
		// not implemented
	}
	
	/**
	 * Get all fields for the specified module
	 */
	public function getFields($moduleOrTabid, $filterfn = null, $sortfn = null) {
		
		$tabid = $this->getTabid($moduleOrTabid);
		if (!$tabid) return false;
		
		$modfields = $this->tcache->getRows($tabid, $filterfn);
		
		if (!is_null($sortfn)) {
			if ($sortfn === self::SORTFN_SEQUENCE) {
				$sortfn = ['self', 'sortfn_sequence'];
			} elseif ($sortfn === self::SORTFN_BLOCK_SEQUENCE) {
				$sortfn = ['self', 'sortfn_block_sequence'];
			}
			
			if (is_callable($sortfn)) {
				usort($modfields, $sortfn);
			}
		}
		
		return $modfields;
	}
	
	/**
	 * Get a single field by name
	 */
	public function getField($moduleOrTabid, $fieldname) {
		$tabid = $this->getTabid($moduleOrTabid);
		if (!$tabid) return false;
		
		$row = $this->tcache->getRowByIndex($tabid, 'fieldname', $fieldname);
		return $row;
	}
	
	public function getFieldById($fieldid, $useCache = true) {
		return $this->tcache->getRowById($fieldid, $useCache);
	}
	
	public function invalidateCache($moduleOrTabid) {
		$tabid = $this->getTabid($moduleOrTabid);
		if (!$tabid) return false;
		
		return $this->tcache->invalidateCache($tabid);
	}
	
	protected function getTabid($moduleOrTabid) {
		if (is_int($moduleOrTabid) || is_numeric($moduleOrTabid)) {
			$tabid = $moduleOrTabid;
		} else {
			$tabid = getTabid2($moduleOrTabid);
		}
		return $tabid;
	}
	
	protected static function sortfn_sequence($row1, $row2) {
		return $row1['sequence'] < $row2['sequence'] ? -1 : ($row1['sequence'] > $row2['sequence'] ? +1 : 0);
	}
	
	protected static function sortfn_block_sequence($row1, $row2) {
		return $row1['block'] < $row2['block'] ? -1 : ($row1['block'] > $row2['block'] ? +1 : 
			($row1['sequence'] < $row2['sequence'] ? -1 : ($row1['sequence'] > $row2['sequence'] ? +1 : 0))
		);
	}
}


/**
 * Static class with some often used functions for quick access
 */ 
class FieldUtils {

	static public function getFields($moduleOrTabid, $filterfn = null, $sortfn = null) {
		$FU = FieldTableUtils::getInstance();
		return $FU->getFields($moduleOrTabid, $filterfn, $sortfn);
	}
	
	static public function getField($moduleOrTabid, $fieldname) {
		$FU = FieldTableUtils::getInstance();
		return $FU->getField($moduleOrTabid, $fieldname);
	}
	
	static public function getFieldById($fieldid) {
		$FU = FieldTableUtils::getInstance();
		return $FU->getFieldById($fieldid);
	}
	
	static public function invalidateCache($moduleOrTabid) {
		$FU = FieldTableUtils::getInstance();
		return $FU->invalidateCache($moduleOrTabid);
	}
	
}
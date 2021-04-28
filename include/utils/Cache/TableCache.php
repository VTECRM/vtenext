<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@193294 */

require_once(__DIR__.'/FullTableCache.php');
require_once(__DIR__.'/ClusteredTableCache.php');


abstract class TableCache {

	protected $table;
	protected $table_index = null;
	
	protected $orderBy;
	
	protected $globalCacheEnabled = true;
	protected $localCacheEnabled = true;
	
	protected $rcache = null;
	
	// indexes
	protected $indexesInfo = array();
	
	protected $indexes = array();
	protected $indexesReady = false;
	
	
	public function __construct($tablename, $tableindex) {
		$this->table = $tablename;
		$this->table_index = $tableindex;
	}
	
	
	// ---------------------------- CONFIG FUNCTIONS ----------------------------
	
	/**
	 * Enable the cache (request one and global one)
	 */
	public function enableCache() {
		$this->globalCacheEnabled = true;
		$this->localCacheEnabled = true;
	}
	
	/**
	 * Disable the cache (request one and global one)
	 */
	public function disableCache() {
		$this->globalCacheEnabled = false;
		$this->localCacheEnabled = false;
	}
	
	/**
	 * Returns whether the global cache is enabled
	 */
	public function isGlobalCacheEnabled() {
		return $this->globalCacheEnabled;
	}
	
	/**
	 * Enable only the global cache
	 */
	public function enableGlobalCache() {
		$this->globalCacheEnabled = true;
	}
	
	/**
	 * Disable only the global cache
	 */
	public function disableGlobalCache() {
		$this->globalCacheEnabled = false;
	}
	
	
	public function isLocalCacheEnabled() {
		return $this->localCacheEnabled;
	}
	
	/**
	 * Enable only the request cache
	 */
	public function enableLocalCache() {
		$this->localCacheEnabled = true;
	}
	
	/**
	 * Disable only the global cache
	 */
	public function disableLocalCache() {
		$this->localCacheEnabled = false;
	}
	
	public function setOrderBy($orderBy) {
		$this->orderBy = $orderBy;
	}
	
	//abstract public function invalidateCache();
	
	
	// ---------------------------- DB FUNCTIONS ----------------------------
	
	/**
	 * Read a single row by id from database
	 */
	public function readRowFromDbById($rowid) {
		global $adb;
		
		$res = $adb->pquery("SELECT * FROM {$this->table} WHERE {$this->table_index} = ?", array($rowid));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			return $row;
		}
		
		return null;
	}
	
	
	// ---------------------------- INDEX FUNCTIONS ----------------------------
	
	/**
	 * Add a column to be indexed
	 */
	public function addIndex($name, $column) {
		$this->indexesInfo[$name] = $column;
		$this->indexesReady = false;
	}
	
	/**
	 * Return the list of ids corresponding to the index
	 */
	public function getIdsFromIndex($indexname, $value) {
		return $this->indexes[$indexname][$value];
	}
	
	/**
	 * Return the first matching id corresponding to the index
	 */
	public function getFirstIdFromIndex($indexname, $value) {
		if (is_array($this->indexes[$indexname][$value])) {
			return reset($this->indexes[$indexname][$value]);
		}
		return null;
	}
	
	protected function fillIndexes($result) {
		
		if ($this->indexesReady) return;

		foreach ($result as $row) {
			foreach ($this->indexesInfo as $iname => $icolumn) {
				$this->indexes[$iname][$row[$icolumn]][] = $row[$this->table_index];
			}
		}
		$this->indexesReady = true;
	}
	
	protected function clearIndexes() {
		$this->indexes = array();
		$this->indexesReady = false;
	}
	
}
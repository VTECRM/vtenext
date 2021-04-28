<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@193294 */

/**
 * This class provides a cache for a table, caching the whole table in a different storage.
 * It's effective only on small tables frequently accessed
 */
class FullTableCache extends TableCache {
	
	// ---------------------------- CACHE FUNCTIONS ----------------------------
	
	protected function getCacheKey() {
		return "ftc_{$this->table}";
	}
	
	public function invalidateCache() {
		
		$ckey = $this->getCacheKey();
		$cache = Cache::getInstance($ckey);
		$cache->clear();
		
		$this->rcache = null;
		
		$this->clearIndexes();
	}
	
	
	// ---------------------------- MAIN FUNCTIONS ----------------------------
	
	/**
	 * Get all rows from table
	 */
	public function getAllRows($filterfn = null) {
		$rows = null;
		if ($this->localCacheEnabled && is_array($this->rcache)) {
			$rows = $this->rcache;
		}
		
		if (is_null($rows) && $this->globalCacheEnabled) {
			// no local cache, search in global cache
			$ckey = $this->getCacheKey();
			$cache = Cache::getInstance($ckey);
			$rows = $cache->get();
			if ($rows === false) {
				// also global cache is empty, fill it from db
				$rows = $this->readRowsFromDb();
				// fill global cache
				$cache->set($rows);
			}
			// and fill local cache if enabled
			if ($this->localCacheEnabled) {
				$this->rcache = $rows;
			}
		} elseif (is_null($rows)) {
			// no local cache, global cache disabled
			$rows = $this->readRowsFromDb();
			// and fill local cache if enabled
			if ($this->localCacheEnabled) {
				$this->rcache = $rows;
			}
		}
		
		if (is_callable($filterfn)) {
			$rows = array_filter($rows, $filterfn);
		}
		return $rows;
	}
	
	public function getRowById($rowid) {
		if ($this->localCacheEnabled && !is_array($this->rcache)) {
			return $this->rcache[$rowid];
		} else {
			// not super performant, but might be quite fast as well
			return $this->readRowFromDbById($rowid);
		}
	}
	
	public function getRowsByIndex($iname, $value) {
		$rows = $this->getAllRows();
		$this->fillIndexes($rows);
		
		// find ids
		$ids = $this->getIdsFromIndex($iname, $value);
		if (!is_array($ids)) return null;
		
		return array_intersect_key($rows, array_flip($ids));
	}
	
	// ---------------------------- DATABASE FUNCTIONS ----------------------------
	
	/**
	 * Read all rows for a module from database
	 */
	public function readRowsFromDb() {
		global $adb;
		
		$data = array();
		$sql = "SELECT * FROM {$this->table}";
		$params = array();
		if ($this->orderBy) {
			$sql .= " ORDER BY ".$this->orderBy;
		}
		$res = $adb->pquery($sql, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$data[$row[$this->table_index]] = $row;
			}
		}
		
		return $data;
	}
	
}
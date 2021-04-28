<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@193294 */

/*
 * Table cache where a single column can be used to cluster data (eg: a "module" column)
 * Useful when the table is quite big, but a single cluster is usually requested
 */
class ClusteredTableCache extends TableCache {

	
	protected $cluster_column = null;
	
	protected $cIndexes = array();
	protected $cIndexesReady = array();
	
	public function __construct($tablename, $tableindex, $clustercolumn) {
		parent::__construct($tablename, $tableindex);
		$this->cluster_column = $clustercolumn;
	}
	
	
	// ---------------------------- MAIN FUNCTIONS ----------------------------
	
	/**
	 * Get all rows from table
	 */
	/*public function getAllRows($filterfn = null) {
		// TODO
	}*/
	
	
	/**
	 * Get all fields for the specified cluster
	 */
	public function getRows($cluster, $filterfn = null) {
	
		$rows = null;	
		if ($this->localCacheEnabled && is_array($this->rcache[$cluster])) {
			$rows = $this->rcache[$cluster];
		}
		
		if (is_null($rows) && $this->globalCacheEnabled) {
			// no local cache, search in global cache
			$ckey = $this->getCacheKey($cluster);
			$cache = Cache::getInstance($ckey);
			$rows = $cache->get();
			if ($rows === false) {
				// also global cache is empty, fill it from db
				$rows = $this->readRowsFromDb($cluster);
				// fill global cache
				$cache->set($rows);
			}
			// and fill local cache if enabled
			if ($this->localCacheEnabled) {
				$this->rcache[$cluster] = $rows;
			}
		} elseif (is_null($rows)) {
			// no local cache, global cache disabled
			$rows = $this->readRowsFromDb($cluster);
			// and fill local cache if enabled
			if ($this->localCacheEnabled) {
				$this->rcache[$cluster] = $rows;
			}
		}
		
		if (!is_null($filterfn) && is_callable($filterfn)) {
			$rows = array_filter($rows, $filterfn);
		}
		
		return $rows;
	}
	
	/**
	 * Return a row by id. When calling this function a few times, it might
	 * be better to pass useCache = false to avoid reading all fields for the module
	 */
	public function getRowById($rowid, $useCache = true) {
		if ($this->localCacheEnabled && $useCache) {
			if (is_array($this->rcache)) {
				foreach ($this->rcache as $cluster => $data) {
					if (isset($data[$rowid])) {
						return $data[$rowid];
					}
				}
			}
				
			// maybe we don't have the cache it might be in a different cluster 
			$cluster = $this->readClusterFromId($rowid);
			if ($cluster) {
				if (is_array($this->rcache) && isset($this->rcache[$cluster])) {
					// we have the cache, but it wasn't found -> invalid fieldid
					return false;
				} else {
					// we don't have cache for this cluster
					$otherRows = $this->getRows($cluster);
					return $otherRows[$rowid];
				}
			} else {
				return null;
			}
			
		} else {
			// not super performant, but might be quite fast as well
			return $this->readRowFromDbById($rowid);
		}
	}
	
	/**
	 * Return the first matching row by index
	 */
	public function getRowByIndex($cluster, $iname, $value) {
		$rows = $this->getRows($cluster);
		$this->fillClusterIndexes($cluster, $rows);
		
		$idx = $this->getFirstIdFromClusterIndex($cluster, $iname, $value);
		if ($idx) {
			return $rows[$idx];
		}
		
		return null;
	}
	
	/**
	 * Return all matching rows by index
	 */
	public function getRowsByIndex($cluster, $iname, $value) {
		$rows = $this->getRows($cluster);
		$this->fillClusterIndexes($cluster, $rows);
		
		// find ids
		$ids = $this->getIdsFromClusterIndex($cluster, $iname, $value);
		if (!is_array($ids)) return null;
		
		return array_intersect_key($rows, array_flip($ids));
	}
	
	
	// ---------------------------- CACHE FUNCTIONS ----------------------------
	
	protected function getCacheKey($cluster) {
		return "ctc_{$this->table}_{$cluster}";
	}
	
	public function invalidateCache($cluster) {
		
		$ckey = $this->getCacheKey($cluster);
		$cache = Cache::getInstance($ckey);
		$cache->clear();
		
		$this->rcache = array();
		
		$this->clearIndexes();
		$this->clearClusterIndexes($cluster);
	}
	
	
	// ---------------------------- DATABASE FUNCTIONS ----------------------------
	
	/**
	 * Read all rows for a module from database
	 */
	public function readRowsFromDb($cluster) {
		global $adb;
		
		$data = array();
		$sql = "SELECT * FROM {$this->table} WHERE {$this->cluster_column} = ?";
		$params = array($cluster);
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
	
	public function readClusterFromId($rowid) {
		global $adb;
		
		$sql = "SELECT {$this->cluster_column} FROM {$this->table} WHERE {$this->table_index} = ?";
		$params = array($rowid);
		$res = $adb->pquery($sql, $params);
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			return $row[$this->cluster_column];
		}
		
		return null;
	}
	
	
	// ---------------------------- INDEX FUNCTIONS ----------------------------
	

	public function addIndex($name, $column) {
		parent::addIndex($name, $column);
		$this->cIndexesReady = array();
	}
	
	protected function clearIndexes() {
		parent::clearIndexes();
		$this->cIndexes = array();
		$this->cIndexesReady = array();
	}
	

	public function getIdsFromClusterIndex($cluster, $indexname, $value) {
		return $this->cIndexes[$cluster][$indexname][$value];
	}
	
	/**
	 * Return the first matching id corresponding to the index
	 */
	public function getFirstIdFromClusterIndex($cluster, $indexname, $value) {
		if (is_array($this->cIndexes[$cluster][$indexname][$value])) {
			return reset($this->cIndexes[$cluster][$indexname][$value]);
		}
		return null;
	}
	
	protected function fillClusterIndexes($cluster, $result) {
		
		if ($this->cIndexesReady[$cluster]) return;

		foreach ($result as $row) {
			foreach ($this->indexesInfo as $iname => $icolumn) {
				$this->cIndexes[$cluster][$iname][$row[$icolumn]][] = $row[$this->table_index];
			}
		}
		$this->cIndexesReady[$cluster] = true;
	}
	
	protected function clearClusterIndexes($cluster) {
		$this->cIndexes[$cluster] = array();
		$this->cIndexesReady[$cluster] = false;
	}
	

	
}
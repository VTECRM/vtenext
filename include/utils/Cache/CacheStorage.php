<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@105600 crmv@106294 crmv@145781 */
require_once('include/VteSession.php'); //crmv@170248

/**
 * Base class which represent a storage engine for cache
 */
abstract class CacheStorage {

	protected $readonly = false;
	
	abstract public function has($key);
	abstract public function get($key);
	abstract public function set($key, $value);
	abstract public function setMulti($values); // crmv@115378
	abstract public function clear($key);
	abstract public function clearAll();
	abstract public function clearMatching($regexp);
	
	public function isReadonly() {
		return $this->readonly;
	}
	
	public function setReadonly() {
		$this->readonly = true;
	}
	
	public function unsetReadonly() {
		$this->readonly = false;
	}
}


/**
 * Var Cache storage
 * Store everything inside a global variable
 */
class CacheStorageVar extends CacheStorage {
	
	protected $data = array();
	
	public function has($key) {
		return isset($this->data[$key]);
	}
	
	public function get($key) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}
		return null;
	}
	
	public function set($key, $value) {
		if ($this->readonly) return;
		$this->data[$key] = $value;
	}
	
	// crmv@115378
	public function getAll() {
		return $this->data;
	}
	
	public function setMulti($values) {
		if ($this->readonly) return;
		$this->data = array_merge($this->data, $values);
	}
	// crmv@115378e
	
	public function clear($key) {
		if ($this->readonly) return;
		unset($this->data[$key]);
	}
	
	public function clearAll() {
		if ($this->readonly) return;
		$this->data = array();
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		$keys = array_keys($this->data);
		foreach ($keys as $k) {
			if (preg_match($regexp, $k)) {
				$this->clear($k);
			}
		}
	}
}


/**
 * Session Cache storage
 * Store everything inside the current session
 */
class CacheStorageSession extends CacheStorage {
	
	public function has($key) {
		return VteSession::hasKeyArray(array('scache', $key));
	}
	
	public function get($key) {
		if (VteSession::hasKeyArray(array('scache', $key))) {
			return VteSession::getArray(array('scache', $key));
		}
		return null;
	}
	
	//crmv@170248
	public function getArray($key_arr) {
		$key_arr = array_merge(array('scache'), $key_arr);
		if (VteSession::hasKeyArray($key_arr)) {
			return VteSession::getArray($key_arr);
		}
		return null;
	}
	//crmv@170248e
	
	public function set($key, $value) {
		if ($this->readonly) return;
		VteSession::setArray(array('scache', $key), $value);
	}
	
	//crmv@170248
	public function setArray($key_arr, $value) {
		if ($this->readonly) return;
		$key_arr = array_merge(array('scache'), $key_arr);
		VteSession::setArray($key_arr, $value);
	}
	//crmv@170248e
	
	// crmv@115378
	public function getAll() {
		if (is_array(VteSession::get('scache'))) {
			return VteSession::get('scache');
		}
		return null;
	}
	
	public function setMulti($values) {
		if ($this->readonly) return;
		if (!is_array(VteSession::get('scache'))) VteSession::set('scache', array());
		VteSession::set('scache', array_merge(VteSession::get('scache'), $values));
	}
	// crmv@115378e
	
	public function clear($key) {
		if ($this->readonly) return;
		VteSession::removeArray(array('scache', $key));
	}
	
	//crmv@170248
	public function clearArray($key_arr) {
		if ($this->readonly) return;
		$key_arr = array_merge(array('scache'), $key_arr);
		VteSession::removeArray($key_arr);
	}
	//crmv@170248e
	
	public function clearAll() {
		if ($this->readonly) return;
		VteSession::set('scache', array());
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		if (is_array(VteSession::get('scache'))) {
			$keys = array_keys(VteSession::get('scache'));
			foreach ($keys as $k) {
				if (preg_match($regexp, $k)) {
					$this->clear($k);
				}
			}
		}
	}
	
}


/**
 * File Cache storage
 * Store everything inside a file
 * Warning, might be slow with many variables, since everything is in the same file,
 * might be better to store each var in a separate file!
 */
class CacheStorageFile extends CacheStorage {

	public $type = "json";	// one of "json", "php", "serialize" (from the fastest to the slowest)
							// decides the way variables are encoded inside the file
							
	protected $filename;
	
	public function __construct($filename, $type = null) {
		if ($type) $this->type = $type;
		$this->filename = $filename;
	}
	
	public function has($key) {
		$cache = $this->getData();
		return isset($cache[$key]);
	}
	
	public function get($key) {
		$cache = $this->getData();
		if (isset($cache[$key])) {
			return $cache[$key];
		}
		return null;
	}
	
	public function set($key, $value) {
		if ($this->readonly) return;
		$cache = $this->getData() ?: array();
		$cache[$key] = $value;
		$this->setData($cache);
	}
	
	// crmv@115378
	public function getAll() {
		$cache = $this->getData();
		if (is_array($cache)) return $cache;
		return null;
	}
	
	public function setMulti($values) {
		if ($this->readonly) return;
		$cache = $this->getData() ?: array();
		$cache = array_merge($cache, $values);
		$this->setData($cache);
	}
	// crmv@115378e
	
	public function clear($key) {
		if ($this->readonly) return;
		$cache = $this->getData();
		unset($cache[$key]);
		$this->setData($cache);
	}
	
	public function clearAll() {
		if ($this->readonly) return;
		if (is_file($this->filename)) {
			unlink($this->filename);
		}
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		$cache = $this->getData() ?: array();
		$keys = array_keys($cache);
		foreach ($keys as $k) {
			if (preg_match($regexp, $k)) {
				unset($cache[$k]);
			}
		}
		$this->setData($cache);
	}
	
	// crmv@115378
	public function isFileEmpty() {
		return !(is_readable($this->filename) && is_file($this->filename) && filesize($this->filename) > 0);
	}
	// crmv@115378e
	
	
	protected function getData() {
		$cache = array();
		if (is_readable($this->filename) && is_file($this->filename)) {
			if ($this->type == 'php') {
				// the file should contain a $cache variable
				@include($this->filename);
			} else{
				// otherwise just read the contents
				$content = @file_get_contents($this->filename);
				if ($content) {
					if ($this->type == "json") {
						$cache = json_decode($content, true);
					} elseif ($this->type == "serialize") {
						$cache = unserialize($content);
					}
				}
			}
		}
		return $cache;
	}
	
	protected function setData($cache) {
		if ($this->type == 'php') {
			$content = "<?php\n\$cache = ".var_export($cache, true).";\n";
		} elseif ($this->type == 'json') {
			$content = json_encode($cache);
		} elseif ($this->type == "serialize") {
			$content = serialize($cache);
		}
		file_put_contents($this->filename, $content);
	}
	
}


/**
 * Apc Cache storage
 * Store cache inside apc. Beware, can be read by any other php process on the same host
 * @experimental
 */
class CacheStorageApc extends CacheStorage {

	public $defaultTTL = 604800;	// 1 week
	public $minFreeMemory = 4;		// minimum free memory in MB to use APC

	public function __construct() {
		global $root_directory, $application_unique_key;
		// calculate a prefix for the var, so different VTE won't collide
		$this->prefix = md5($root_directory.'#'.$application_unique_key);
	}
	
	public static function isSupported() {
		return function_exists('apc_store');
	}
	
	public function has($key) {
		return apc_exists($this->key2apc($key));
	}
	
	public function get($key) {
		return apc_fetch($this->key2apc($key));
	}
	
	public function set($key, $value, $duration = null) {
		if ($this->readonly) return;
		apc_store($this->key2apc($key), $value, intval($duration) ?: $this->defaultTTL);
	}
	
	// crmv@115378
	public function setMulti($values, $duration = null) {
		if ($this->readonly) return;
		$values = array_combine(array_map(array($this, 'key2apc'), array_keys($values)), array_values($values));
		apc_store($values, null, intval($duration) ?: $this->defaultTTL);
	}
	// crmv@115378e
	
	public function clear($key) {
		if ($this->readonly) return;
		apc_delete($this->key2apc($key));
	}
	
	public function clearAll() {
		if ($this->readonly) return;
		apc_clear_cache("user");
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		$iter = new APCIterator('user', $regexp, APC_ITER_VALUE);
		apc_delete($iter);
	}
	
	protected function key2apc($key) {
		return $this->prefix."_".$key;
	}
	
	protected function apc2key($akey) {
		$l = strlen($this->prefix);
		return substr($akey, $l+1);
	}
	
	public function hasFreeMemory() {
		$meminfo = $this->getMemoryInfo();
		return ($meminfo['avail_mem'] >= $this->minFreeMemory * 1024 * 1024);
	}
	
	public function getMemoryInfo() {
		$info = @apc_sma_info(true); // in cli mode there's a warning
		if (!$info) {
			$info = array(
				'seg_size' => 0,
				'avail_mem' => 0,
			);
		}
		if ($info['seg_size'] > 0) {
			$info['avail_mem_perc'] = 100 * $info['avail_mem'] / $info['seg_size'];
		} else {
			$info['avail_mem_perc'] = 0;
		}
		return $info;
	}
}


// crmv@181165
/**
 * Apcu Cache storage
 * Store cache inside apcu. Beware, can be read by any other php process on the same host
 * @experimental
 */
class CacheStorageApcu extends CacheStorage {

	public $defaultTTL = 604800;	// 1 week
	public $minFreeMemory = 4;		// minimum free memory in MB to use APCu

	public function __construct() {
		global $root_directory, $application_unique_key;
		// calculate a prefix for the var, so different VTE won't collide
		$this->prefix = md5($root_directory.'#'.$application_unique_key);
	}
	
	public static function isSupported() {
		return function_exists('apcu_store');
	}
	
	public function has($key) {
		return apcu_exists($this->key2apc($key));
	}
	
	public function get($key) {
		return apcu_fetch($this->key2apc($key));
	}
	
	public function set($key, $value, $duration = null) {
		if ($this->readonly) return;
		apcu_store($this->key2apc($key), $value, intval($duration) ?: $this->defaultTTL);
	}
	
	// crmv@115378
	public function setMulti($values, $duration = null) {
		if ($this->readonly) return;
		$values = array_combine(array_map(array($this, 'key2apc'), array_keys($values)), array_values($values));
		apcu_store($values, null, intval($duration) ?: $this->defaultTTL);
	}
	// crmv@115378e
	
	public function clear($key) {
		if ($this->readonly) return;
		apcu_delete($this->key2apc($key));
	}
	
	public function clearAll() {
		if ($this->readonly) return;
		apcu_clear_cache();
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		$iter = new APCUIterator($regexp, APC_ITER_VALUE);
		apcu_delete($iter);
	}
	
	protected function key2apc($key) {
		return $this->prefix."_".$key;
	}
	
	protected function apc2key($akey) {
		$l = strlen($this->prefix);
		return substr($akey, $l+1);
	}
	
	public function hasFreeMemory() {
		$meminfo = $this->getMemoryInfo();
		return ($meminfo['avail_mem'] >= $this->minFreeMemory * 1024 * 1024);
	}
	
	public function getMemoryInfo() {
		$info = @apcu_sma_info(true); // in cli mode there's a warning
		if (!$info) {
			$info = array(
				'seg_size' => 0,
				'avail_mem' => 0,
			);
		}
		if ($info['seg_size'] > 0) {
			$info['avail_mem_perc'] = 100 * $info['avail_mem'] / $info['seg_size'];
		} else {
			$info['avail_mem_perc'] = 0;
		}
		return $info;
	}
}
// crmv@181165e


/**
 * Memcached Cache storage
 * Store cache inside memcache. Beware, can be read by any other process accessing memcache
 * @experimental
 */
class CacheStorageMemcached extends CacheStorage {

	public $defaultTTL = 604800; // 1 week
	protected $mc;

	public function __construct($servers, $options = null) {
		global $root_directory, $application_unique_key;
		// calculate the persistend id, so different VTE won't collide
		$pid = md5($root_directory.'#'.$application_unique_key);
		$this->mc = new Memcached($pid);
		// crmv@181165
		if (!count($this->mc->getServerList())) {
			$this->mc->addServers($servers);
		}
		// crmv@181165e
	}
	
	public static function isSupported() {
		return class_exists('Memcached');
	}
	
	public function has($key) {
		$v = $this->mc->get($key);
		return ($v !== false); // crmv@181165
	}
	
	public function get($key) {
		$v = $this->mc->get($key);
		if ($v !== false) { // crmv@181165
			return $v;
		}
		return null;
	}
	
	public function set($key, $value, $duration = null) {
		if ($this->readonly) return;
		$this->mc->set($key, $value, intval($duration) ?: $this->defaultTTL);
	}
	
	// crmv@115378
	public function setMulti($values, $duration = null) {
		if ($this->readonly) return;
		$this->mc->setMulti($values, intval($duration) ?: $this->defaultTTL);
	}
	// crmv@115378e
	
	public function clear($key) {
		if ($this->readonly) return;
		$this->mc->delete($key);
	}
	
	public function clearAll() {
		if ($this->readonly) return;
		$this->mc->flush();
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		$keys = $this->mc->getAllKeys();
		if (is_array($keys) && count($keys) > 0) {
			foreach ($keys as $k) {
				if (preg_match($regexp, $k)) {
					$this->clear($k);
				}
			}
		}
	}

}


// crmv@181165
/**
 * Redis Cache storage
 * Store cache inside Redis. Beware, can be read by any other process accessing redis
 * @experimental
 */
class CacheStorageRedis extends CacheStorage {

	public $defaultTTL = 604800; // 1 week
	protected $rd;

	public function __construct($server, $port = null, $options = null) {
		global $root_directory, $application_unique_key;
		// calculate a db name, so different VTE won't collide
		$prefix = md5($root_directory.'#'.$application_unique_key);
		$this->rd = new Redis();
		$this->rd->pconnect($server, $port);
		$this->rd->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
		$this->rd->setOption(Redis::OPT_PREFIX, $prefix.':');
	}
	
	public static function isSupported() {
		return class_exists('Redis');
	}
	
	public function has($key) {
		return $this->rd->exists($key);
	}
	
	public function get($key) {
		$v = $this->rd->get($key);
		if ($v !== false) {
			return $v;
		}
		
		return null;
	}
	
	public function set($key, $value, $duration = null) {
		if ($this->readonly) return;
		$this->rd->set($key, $value/* , intval($duration) ?: $this->defaultTTL */);
	}
	
	// crmv@115378
	public function setMulti($values, $duration = null) {
		if ($this->readonly) return;
		$this->rd->mSet($values /* , intval($duration) ?: $this->defaultTTL */);
	}
	// crmv@115378e
	
	public function clear($key) {
		if ($this->readonly) return;
		$this->rd->delete($key);
	}
	
	public function clearAll() {
		if ($this->readonly) return;
		$this->rd->flushDb();
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		$keys = $this->rd->keys('*'); // it's not a regexp
		foreach ($keys as $k) {
			if (preg_match($regexp, $k)) {
				$this->clear($k);
			}
		}
	}
}
// crmv@181165e

// crmv@140903
/**
 * Database Cache storage
 * Store everything inside a db table
 */
class CacheStorageDb extends CacheStorage {
	
	private $db;
	private $table;
	private $table_col_key;
	private $table_col_value;
	
	public function __construct() {
		global $adb, $table_prefix;;
		$this->db = $adb;
		$this->table = $table_prefix.'_cache';
		$this->table_col_key = 'cache_key';
		$this->table_col_value = 'cache_value';
		
		$this->checkTable();
	}
	
	protected function checkTable() {
		if (!Vtecrm_Utils::CheckTable($this->table)) {
			$schema_table =
			'<schema version="0.3">
				<table name="'.$this->table.'">
					<opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="cache_key" type="C" size="50">
						<KEY/>
					</field>
					<field name="cache_value" type="XL"/>
				</table>
			</schema>';
			$schema_obj = new adoSchema($this->db->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
	}
	
	public function has($key) {
		$res = $this->db->pquery("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE {$this->table_col_key} = ?", array($key));
		return ($res && $this->db->query_result_no_html($res,0,'cnt') > 0);
	}
	
	public function get($key) {
		$res = $this->db->pquery("SELECT {$this->table_col_value} AS val FROM {$this->table} WHERE {$this->table_col_key} = ?", array($key));
		if ($res && $this->db->num_rows($res) > 0) {
			$value = $this->db->query_result_no_html($res,0,'val');
			return $this->valueFromDb($value);
		}
		return null;
	}
	
	public function set($key, $value) {
		if ($this->readonly) return;
		$value = $this->valueToDb($value);
		// efficient way for an upsert
		// unfortunarely, it doesn't work during install... why?
		/* $res = $this->db->pquery("UPDATE {$this->table} SET {$this->table_col_value} = ? WHERE {$this->table_col_key} = ?", array($value, $key));
		if ($this->db->getAffectedRowCount($res) == 0) {
			$this->db->pquery("INSERT INTO {$this->table} ({$this->table_col_key},{$this->table_col_value}) VALUES (?,?)", array($key, $value));
		}
		*/
		// so let's use a standard approach
		if ($this->has($key)) {
			$this->db->pquery("UPDATE {$this->table} SET {$this->table_col_value} = ? WHERE {$this->table_col_key} = ?", array($value, $key));
		} else {
			$this->db->pquery("INSERT INTO {$this->table} ({$this->table_col_key},{$this->table_col_value}) VALUES (?,?)", array($key, $value));
		}
	}
	
	public function getAll() {
		$data = array();
		$res = $this->db->query("SELECT {$this->table_col_key} AS ckey, {$this->table_col_value} AS val FROM {$this->table}");
		if ($res && $this->db->num_rows($res) > 0) {
			while ($row=$this->db->fetchByAssoc($res,-1,false)) {
				$data[$row['ckey']] = $this->valueFromDb($row['val']);
			}
		}
		return $data;
	}
	
	public function getAllLike($like) {
		$data = array();
		$res = $this->db->query("SELECT {$this->table_col_key} AS ckey, {$this->table_col_value} AS val FROM {$this->table} WHERE {$this->table_col_key} LIKE '{$like}'");
		if ($res && $this->db->num_rows($res) > 0) {
			while ($row=$this->db->fetchByAssoc($res,-1,false)) {
				$data[$row['ckey']] = $this->valueFromDb($row['val']);
			}
		}
		return $data;
	}
	
	public function setMulti($values) {
		if ($this->readonly) return;
		
		$this->db->startTransaction();
		foreach($values as $key => $value) {
			$this->set($key, $value);
		}
		$this->db->completeTransaction();
	}
	
	public function clear($key) {
		if ($this->readonly) return;
		$this->db->pquery("DELETE FROM {$this->table} WHERE {$this->table_col_key} = ?", array($key));
	}
	
	public function clearAll() {
		if ($this->readonly) return;
		$this->db->query("DELETE FROM {$this->table}");
	}
	
	public function clearMatching($regexp) {
		if ($this->readonly) return;
		$keys = array();
		$res = $this->db->query("SELECT {$this->table_col_key} AS ckey FROM {$this->table}");
		if ($res && $this->db->num_rows($res) > 0) {
			while ($row=$this->db->fetchByAssoc($res,-1,false)) {
				$keys[] = $row['ckey'];
			}
		}
		foreach ($keys as $k) {
			if (preg_match($regexp, $k)) {
				$this->clear($k);
			}
		}
	}
	
	protected function valueToDb($value) {
		return json_encode($value);
	}
	
	protected function valueFromDb($value) {
		return  json_decode($value, true);
	}
}
// crmv@140903e
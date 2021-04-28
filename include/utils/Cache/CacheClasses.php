<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@105600 */ 

// Classi cache divise per persistenza


/**
 * Base class for all the caches, just call the storage backend
 */
abstract class BaseCache {

	public $supportExpiration;
	protected $storage;
	
	protected static $instances = array();
	
	// the constructor is abstract, so you are forced to define a new one!
	abstract public function __construct();

	public function has($key) {
		return $this->storage->has($key);
	}
	
	public function get($key) {
		return $this->storage->get($key);
	}
	
	public function set($key, $value, $duration=null, $options=null) {
		return $this->storage->set($key, $value);
	}
	
	// crmv@106294
	public function clearMatching($regexp) {
		return $this->storage->clearMatching($regexp);
	}
	// crmv@106294e
	
	public function clear($key) {
		return $this->storage->clear($key);
	}
	
	public function clearAll() {
		return $this->storage->clearAll();
	}
	
	// this is necessary in order not to depend on any sdk class, since the sdk uses this cache!
	public static function getInstance() {
		$classname = get_called_class();
		if (!array_key_exists($classname, self::$instances)) {
			$args = func_get_args();
			if (class_exists('ReflectionClass')) {
				$reflection = new ReflectionClass($classname);
				try {
					$const = $reflection->getConstructor();
					if ($const) {
						$classInst = $reflection->newInstanceArgs($args);
					} else {
						$classInst = $reflection->newInstance();
					}
				} catch (ReflectionException $e) {
					// try the normal way
					$classInst = new $classname($args[0], $args[1], $args[2], $args[3]);
				}
			} else {
				// use only 4 arguments
				$classInst = new $classname($args[0], $args[1], $args[2], $args[3]);
			}
			self::$instances[$classname] = $classInst;
		}
		return self::$instances[$classname];
	}
	
}


/**
 * Provide a basic wrapper to handle expiration of cache entries
 */
abstract class BaseExpirationClass extends BaseCache {
	
	public $supportExpiration = true;
	public $defaultDuration = 21600;	// seconds, set to 0 to disable, default to 6 hours
	
	/**
	 * Get an entry from the cache, checking for expiration
	 */
	public function get($key) {
		$v = $this->storage->get($key);
		if ($this->checkExpiration($v)) {
			return $v['val'];
		} else {
			$this->clear($key);
			return null;
		}
	}
	
	/**
	 * Set an entry in the cache, with an optional duration set
	 */
	public function set($key, $value, $duration=null, $options=null) {
		$v = array('val' => $value);
		if ($duration > 0) {
			$v['expire'] = time() + intval($duration);
		} elseif ($this->defaultDuration > 0) {
			$v['expire'] = time() + $this->defaultDuration;
		}
		return $this->storage->set($key, $v);
	}
	
	/**
	 * Return true if the values is valid, or false if expired
	 */
	protected function checkExpiration($v) {
		$expire = intval($v['expire']);
		if ($expire > 0 && $expire < time()) return false;
		return true;
	}
}


/**
 * Request Cache class
 * Caches data for the length of the request only
 * Especially useful to hold small data that otherwise would be read from db
 * Does not support expiration
 */
class RCache extends BaseCache {

	public $supportExpiration = false;
	
	public function __construct() {
		$this->storage = new CacheStorageVar();
	}
	
}


/**
 * Session Cache class
 * Caches data for the length of the current session
 */
class SCache extends BaseExpirationClass {
	
	public $defaultDuration = 21600;	// seconds, set to 0 to disable
	
	public function __construct() {
		$this->storage = new CacheStorageSession();
	}
	
}

/**
 * User Cache class
 * Caches data for the current user only. Persists between logins/logout, it's trasversal to all user's sessions
 */
class UCache extends BaseExpirationClass {
	
	public $defaultDuration = 7200;	// 2 hours
	
	public function __construct($userid = null) {
		global $current_user;
		if (!$userid) $userid = $current_user->id;
		
		$this->storage = new CacheStorageFile('cache/sys/ucache_'.$userid.".json");
	}
}

// crmv@181165
/**
 * Global Cache class
 * Caches data for all users/sessions. Must be manually invalidated or wait for the expiration time
 */
class GCache extends BaseCache {

	public $supportExpiration = false;
	
	public function __construct($storage = 'best', $config = array()) {
		if ($storage == 'best') {
			$storage = self::detectBestBackend();
			if (!$storage) throw new Exception('No storage backend available for Global Cache');
		}
		
		if ($storage == 'apcu') {
			$this->storage = new CacheStorageApcu();
		} elseif ($storage == 'apc') {
			$this->storage = new CacheStorageApc();
		} elseif ($storage == 'memcached') {
			$this->storage = new CacheStorageMemcached($config['servers']);
		} elseif ($storage == 'redis') {
			$this->storage = new CacheStorageRedis($config['server'], $config['port']);
		} elseif ($storage == 'file') {
			$this->storage = new CacheStorageFile($config['file'], $config['type']);
		} elseif ($storage == 'db') {
			$this->storage = new CacheStorageDb(); // crmv@140903
		} else {
			throw new Exception('Unknown storage backend specified');
		}
	}
	
	static public function detectBestBackend(&$config = array()) {
		if (CacheStorageApcu::isSupported()) {
			$storage = new CacheStorageApcu();
			if ($storage->hasFreeMemory()) {
				return 'apcu';
			}
		}
		
		if (CacheStorageApc::isSupported()) {
			$storage = new CacheStorageApc();
			if ($storage->hasFreeMemory()) {
				return 'apc';
			}
		}
		
		if (CacheStorageMemcached::isSupported()) {
			// try to retrieve parameters from session handler
			$sessHandler = ini_get('session.save_handler');
			if ($sessHandler == 'memcached') {
				$sessPath = ini_get('session.save_path');
				$servers = explode(',', $sessPath);
				$scount = count($servers);
				if ($scount > 0) {
					$servers = array_map(function($s) use ($scount) {
						list($host, $port) = explode(':', $s); 
						return array($host, $port ?: 11211, floor(100/$scount)); // set the same priority for all hosts
					}, $servers);
					$config['servers'] = $servers;
					return 'memcached';
				}
			}
		}
		return null;
	}
}
// crmv@181165
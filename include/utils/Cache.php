<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@47905bis crmv@105600 crmv@187020 */

require_once('include/utils/Cache/CacheStorage.php');
require_once('include/utils/Cache/CacheClasses.php');
require_once('include/utils/Cache/TableCache.php'); // crmv@193294

class Cache {
	
	private $enabled;	// language do not respect this value and use always the cache
	private $type;		// file, session
	private $rootFolder = 'cache/sys/';
	private $name = '';
	private $extension = '.json';	// json is faster than php
	
	protected $logging = false;
	
	protected $gcache; // crmv@181165
	protected $scache;
	protected $fstorage;
	
	// crmv@181165
	protected $gcacheType = null;
	protected $gcacheConfig = array();
	// crmv@181165e
	
	protected $creload = null;
	protected $reloadChecked = false;
	
	static $cacheInstances = array(); // crmv@134058
	
	static function getInstance($name, $extension=null, $rootFolder=null) {
		// crmv@134058
		if (!isset(self::$cacheInstances[$name])) {
			self::$cacheInstances[$name] = new self($name, $extension, $rootFolder);
		}
		return self::$cacheInstances[$name];
		// crmv@134058e
	}
	
	function __construct($name, $extension=null, $rootFolder=null) {
		$this->name = $name;
		if (!empty($extension)) $this->extension = $extension;
		if (!empty($rootFolder)) $this->rootFolder = $rootFolder;

		// crmv@115378
		$this->enabled = PerformancePrefs::getBoolean('CACHE');
		$this->type = PerformancePrefs::get('CACHE_TYPE');
		// crmv@115378e
		
		if (in_array($name,array('vteCacheHV','mIiTtC','numberUsersMorphsuit','cacheResources'))) $this->type = 'file'; //crmv@61417 crmv@94125
		
		if (basename($_SERVER['PHP_SELF']) == 'install.php') $this->enabled = false;
		
		// crmv@181165
		$gcacheType = PerformancePrefs::get('GLOBAL_CACHE');
		if ($this->enabled && $gcacheType && self::globalCacheNameMatch($name)) {
			if ($gcacheType == 'best') {
				$config = array();
				$gcacheType = GCache::detectBestBackend($config);
			} else {
				$config = PerformancePrefs::get('GLOBAL_CACHE_CONFIG') ?: array();
			}
			if ($gcacheType) {
				$this->type = 'global';
				$this->gcacheType = $gcacheType;
				$this->gcacheConfig = $config;
				$this->clearOldSessionCache();
			}
		}
		// crmv@181165e
		
		// crmv@187020
		global $current_language;
		if ($name == 'sdk_js_lang' && !empty($current_language) && $this->type == 'global') {
			$name .= "-{$current_language}";
			$this->name = $name;
		}
		// crmv@187020e
		
		if ($this->enabled) {
			$this->creload = new CacheReloader();
			if ($this->logging) $this->creload->enableLog();
			$this->enable();
		}
	}
	
	// crmv@144893
	public function isEnabled() {
		return $this->enabled;
	}
	
	public function enable() {
		$this->enabled = true;
		
		if ($this->type == 'session') {
			if (!$this->scache) {
				$this->scache = SCache::getInstance();
			}
		} elseif ($this->type == 'file') {
			if (!$this->fstorage) {
				$this->fstorage = new CacheStorageFile($this->getFile(), 'json');
			}
		// crmv@181165
		} elseif ($this->type == 'global') {
			if (!$this->gcache) {
				$this->gcache = GCache::getInstance($this->gcacheType, $this->gcacheConfig);
			}
		}
		// crmv@181165e
		
		if (!$this->creload) {
			$this->creload = new CacheReloader();
		}
		
		$this->log("[{$this->name}] enabled, type {$this->type}".($this->gcacheType ? " ({$this->gcacheType})" : ""));
	}
	
	public function disable() {
		$this->enabled = false;
		
		$this->log("[{$this->name}] disabled");
	}
	// crmv@144893e
	
	public function enableLog() {
		$this->logging = true;
		if ($this->creload) $this->creload->enableLog();
	}
	
	public function disableLog() {
		$this->logging = false;
		if ($this->creload) $this->creload->disableLog();
	}
	
	protected function log($text) {
		// very basic log, for debug only
		if ($this->logging) {
			$str = "CACHE: ".$text."\n";
			echo $str;
		}
	}
	
	// crmv@181165
	static public function globalCacheNameMatch($name) {
		$matches = PerformancePrefs::get('GLOBAL_CACHE_KEYS') ?: array();
		
		if (self::isLangCache($name) && in_array('vte_languages', $matches)) {
			return true;
		} elseif (substr($name, 0, 4) == 'sdk_' && in_array('sdk', $matches)) {
		// crmv@193294
		//} elseif (substr($name, 0, 10) == 'fields_tab' && in_array('vte_fields', $matches)) {
		//	return true;
		} elseif (in_array(substr($name, 0, 4), array('ftc_', 'ctc_')) && in_array('tablecache', $matches)) {
			return true;
		// crmv@193294e
			return true;
		} elseif (in_array($name, $matches)) {
			return true;
		}
		
		return false;
	}
	
	static public function isLangCache($name) {
		return ($name == 'vte_languages' || substr($name, 0, 11) == 'sdk_js_lang' || substr($name, 0, 18) == 'SDK/vte_languages/'); // crmv@187020
	}
	
	/**
	 * When using the global cache, this function cleasr stuff in the old session, to speed up load time
	 */
	protected function clearOldSessionCache() {
		$scache = SCache::getInstance();
		if ($scache->has($this->name)) {
			$scache->clear($this->name);
			if (self::isLangCache($this->name)) {
				$scache->clearMatching('#^SDK/vte_languages#');
				$scache->clearMatching('#^sdk_js_lang#'); // crmv@187020
				$this->log("[{$this->name}] Clear old languages from session");
			}
		}
	}
	// crmv@181165e
	
	public function getType() {
 		return $this->type;
 	}
 	
 	public function getGlobalType() {
		return $this->gcacheType;
 	}
 	
	public function getFile() {
 		return $this->rootFolder.$this->name.$this->extension;
 	}
	
	public function get() {
		if (!$this->enabled) return false;
		
		if (!$this->reloadChecked && isset($this->creload)) {
			$this->reloadChecked = true;
			// crmv@199829
			// check if the cache has to be invalidated, but only once per cache
			try {
				$this->creload->checkReload($this->name, $this->type == 'global' ? '' : $this->type, $this->gcacheType);
			} catch (Exception $e) {
				// probably missing userid, so check again later, when we have the user
				$this->log('Unable to check the cache reload: '.$e->getMessage());
				$this->reloadChecked = false;
			}
			// crmv@199829e
		}
		
		$r = false;
		if ($this->type == 'file') {
			$r = $this->fstorage->get('data');
			if ($r === null) return false;
		} elseif ($this->type == 'session') {
			$r = $this->scache->get($this->name);
			if ($r === null) return false;
		// crmv@181165
		} elseif ($this->type == 'global') {
			$r = $this->gcache->get($this->name);
			if ($r === null) return false;
		}
		// crmv@181165e
		
		$this->log("[{$this->name}] GET");
		
		return $r;
 	}
 	
 	public function set($value, $life=null) {
 		if (!$this->enabled) return false;
 		
 		if ($this->type == 'file') {
	 		$this->fstorage->set('data', $value, $life);
 		} elseif ($this->type == 'session') {
			$this->scache->set($this->name, $value, $life);
 		// crmv@181165
		} elseif ($this->type == 'global') {
			$this->gcache->set($this->name, $value, $life);
		}
		// crmv@181165e
		
		$this->log("[{$this->name}] SET");
 	}
 	
 	// crmv@187020
 	public function clear($all_users=true) {
 		if (!$this->enabled) return false;
 		
 		$this->log("[{$this->name}] Clearing...");

 		if ($this->type == 'file') {
	 		$cacheFile = $this->getFile();
	 		if (file_exists($cacheFile)) {
	 			$this->fstorage->clearAll();
	 		} elseif (is_dir($this->rootFolder.$this->name)) {	// ex. vte_languages, sdk_js_lang
	 			$files = glob($this->rootFolder.$this->name.'/*');
	 			foreach ($files as $file) {
	 				if (is_file($file)) unlink($file);
	 			}
	 		}
 		} elseif ($this->type == 'session') {
			$this->scache->clear($this->name);
			
			// crmv@106294
			if ($this->name == 'vte_languages') {
				$this->scache->clearMatching('#^SDK/vte_languages#');
			}
			// crmv@106294e
 			
 			if ($all_users) {
				$this->creload->setReloadForOthers($this->name);
 			}

 		// crmv@181165
		} elseif ($this->type == 'global') {
			$this->gcache->clear($this->name);
			// crmv@106294
			if ($this->name == 'vte_languages') {
				$this->gcache->clearMatching('#_SDK/vte_languages#'); // crmv@187020
			}
			// crmv@106294e
			// crmv@187020
			elseif (substr($this->name, 0, 11) == 'sdk_js_lang') {
				$this->gcache->clearMatching('#_sdk_js_lang#');
			}
			// crmv@187020e
			
			if ($all_users && !in_array($this->gcacheType, array('memcached', 'redis'))) { // assume memcached and redis are shared among all hosts
 				$this->creload->setReloadForOthers($this->name);
 			}
		}
		// crmv@181165e
		
		$this->log("[{$this->name}] Clear cache complete");
 	}
 	
 	/**
 	 * Rebuild the cache calling the appropriate functions, only languages and tabdata are supported now
 	 * @experimental
 	 */
 	public function rebuild() {
		$t0 = microtime(true);
		if ($this->name == 'tabdata') {
			TabdataCache::rebuildCache(false);
			$t1 = microtime(true);
			$this->log("[{$this->name}] Cache rebuilt in ".round($t1-$t0,3)."s");
		} elseif (self::isLangCache($this->name)) {
			global $current_language, $default_language;
			$lang = $current_language ?: $default_language;
			if ($lang) {
				SDK::cacheLanguage($lang);
				$t1 = microtime(true);
				$this->log("[{$this->name}] Cache rebuilt in ".round($t1-$t0,3)."s");
			}
		//crmv@170248
		} elseif ($this->name == 'vteprops') {
			$VP = VteProperties::getInstance();
			$VP->initSCache(); // reload session cache
		//crmv@170248e
		} else {
			$this->log("[{$this->name}] Unknown cache to rebuild");
		}
 	}
 	
}


/**
 * Handle cache invalidations/reload when several hosts, sessions or users are involved
 * The reload tables work by having a line when the cache doesn't need reload
 */
class CacheReloader {

	protected $logging = false;
 	protected $table_users;
 	protected $table_hosts;
 	protected $table_global;
 	
 	public function __construct() {
		global $table_prefix;
		$this->table_users = $table_prefix.'_reload_user_cache';
		$this->table_hosts = $table_prefix.'_reload_host_cache';
		$this->table_global = $table_prefix.'_reload_global_cache';
 	}
 	
 	public function enableLog() {
		$this->logging = true;
	}
	
	public function disableLog() {
		$this->logging = false;
	}
	
	protected function log($text) {
		// very basic log, for debug only
		if ($this->logging) {
			$str = "CACHE RELOADER: ".$text."\n";
			echo $str;
		}
	}
 	
 	/**
 	 * Called in index.php to check if the cache (user or host) need to be reloaded
 	 */
 	public function checkReload($varname, $type, $gtype) {
		if ($type == 'session') {
			$reloaded = $this->checkReloadForCurrentUser($varname, $type);
		} elseif ($gtype == 'apc' || $gtype == 'apcu') {
			$reloaded = $this->checkReloadForCurrentHost($varname, $gtype);
		} elseif ($gtype == 'memcached' || $gtype == 'redis') {
			$reloaded = $this->checkReloadGlobal($varname, $gtype);
		}
		
		return $reloaded;
	}
	
	public function checkReloadForCurrentUser($varname, $type = 'session') {
		global $current_user;
		if ($current_user->id > 0) {
			return $this->checkReloadForUser($current_user->id, $varname, $type);
		// crmv@199829
		} else {
			throw new Exception('No current user is set');
		}
		// crmv@199829e
		return false;
	}
	
	public function checkReloadForUser($userid, $varname, $type = 'session') {
		global $adb;
		
		$reloaded = false;
		
		//crmv@sdk	crmv@47905bis
		if (Vtecrm_Utils::CheckTable($this->table_users)) { // crmv@115378
			$VP = VteProperties::getInstance();
			$result = $adb->pquery("SELECT varname FROM {$this->table_users} WHERE userid = ? AND storage_type = ? AND varname = ?",array($userid, $type, $varname));
			if ($result && $adb->num_rows($result) == 0) {
				$cache = Cache::getInstance($varname);
				$cache->clear(false);
				$this->unsetReloadForUser($varname, $userid, $type);
				$reloaded = true;
			}
		}
		//crmv@sdke	crmv@47905bise
		
		return $reloaded;
	}
	
	public function checkReloadForCurrentHost($varname, $type = 'apcu') {
		$hostid = OSUtils::getHostId();
		return $this->checkReloadForHost($hostid, $varname, $type);
	}
	
	public function checkReloadForHost($hostid, $varname, $type = 'apcu') {
		global $adb;
		
		$reloaded = false;
		if (Vtecrm_Utils::CheckTable($this->table_hosts)) {
			// check if cache should be reloaded
			$result = $adb->pquery("SELECT varname FROM {$this->table_hosts} WHERE hostid = ? AND varname = ? AND storage_type = ?",array($hostid, $varname, $type));
			if ($result && $adb->num_rows($result) == 0) {
				$cache = Cache::getInstance($varname);
				if ($cache->getType() == 'global' && $cache->getGlobalType() == $type) {
					$where = $adb->convert2Sql("WHERE hostid = ? AND storage_type = ? AND varname = ?", array($hostid, $type, $varname));
					// this will start a transaction and lock the row until we are done with repopulating the cache
					$adb->database->RowLock($this->table_hosts, $where);
					$cache->clear(false);
					$cache->rebuild();
					$this->unsetReloadForHost($hostid, $varname, $type);
					$adb->database->CommitTrans();
					$reloaded = true;
				}
			}
		}
		
		return $reloaded;
 	}
 	
 	public function checkReloadGlobal($varname, $type = 'apcu') {
		global $adb;
		
		$reloaded = false;
		
		if (Vtecrm_Utils::CheckTable($this->table_global)) {
			// check if cache should be reloaded
			$result = $adb->pquery("SELECT varname FROM {$this->table_global} WHERE varname = ? AND storage_type = ?",array($varname, $type));
			if ($result && $adb->num_rows($result) == 0) {
				$cache = Cache::getInstance($varname);
				if ($cache->getType() == 'global' && $cache->getGlobalType() == $type) {
					$where = $adb->convert2Sql("WHERE storage_type = ? AND varname = ?", array($type, $varname));
					// this will start a transaction and lock the row until we are done with repopulating the cache
					$adb->database->RowLock($this->table_global, $where);
					$cache->clear(false);
					$cache->rebuild();
					$this->unsetReloadGlobal($varname, $type);
					$adb->database->CommitTrans();
					$reloaded = true;
				}
			}
		}
		
		return $reloaded;
 	}
 	
 	public function setReloadForOthers($varname) {
		$this->setReloadForOtherUsers($varname);
		$this->setReloadForAllHosts($varname);
		$this->setReloadGlobal($varname);
 	}
 	
 	public function setReloadForOtherUsers($varname) {
		global $adb, $current_user;
				
		if (Vtecrm_Utils::CheckTable($this->table_users)) {
			
			$query = "DELETE FROM {$this->table_users} WHERE varname = ?";
			$params = array($varname);
			if (!empty($current_user->id)) {
				$query .= " AND userid <> ?";
				$params[] = $current_user->id;
			}
			$adb->pquery($query, $params);
			
			$this->log("[{$varname}] Set reload for other users");
		}

 	}
 	
 	public function setReloadForOtherHosts($varname) {
		global $adb;
		
		if (Vtecrm_Utils::CheckTable($this->table_hosts)) {
			$hostid = OSUtils::getHostId();
			$adb->pquery("DELETE FROM {$this->table_hosts} WHERE hostid != ? AND varname = ?", array($hostid, $varname));
			if (Cache::isLangCache($varname)) {
				$adb->pquery("DELETE FROM {$this->table_hosts} WHERE hostid != ? AND varname = ?", array($hostid, 'vte_languages'));
			}
			$this->log("[{$varname}] Set reload for other hosts");
		}
 	}
 	
 	public function setReloadForAllHosts($varname) {
		global $adb;
		
		if (Vtecrm_Utils::CheckTable($this->table_hosts)) {
			$adb->pquery("DELETE FROM {$this->table_hosts} WHERE varname = ?", array($varname));
			if (Cache::isLangCache($varname)) {
				$adb->pquery("DELETE FROM {$this->table_hosts} WHERE varname = ?", array('vte_languages'));
			}
			$this->log("[{$varname}] Set reload for all hosts");
		}
 	}
 	
 	public function setReloadGlobal($varname) {
		global $adb;
		
		if (Vtecrm_Utils::CheckTable($this->table_global)) {
			$adb->pquery("DELETE FROM {$this->table_global} WHERE varname = ?", array($varname));
			if (Cache::isLangCache($varname)) {
				$adb->pquery("DELETE FROM {$this->table_global} WHERE varname = ?", array('vte_languages'));
			}
			$this->log("[{$varname}] Set global reload");
		}
 	}
 	
 	
 	public function unsetReload($varname, $type = 'session', $gtype = 'apcu') {
		$this->unsetReloadForCurrentUser($varname, $type);
		$this->unsetReloadForCurrentHost($varname, $gtype);
 	}
 	
 	public function unsetReloadForCurrentUser($varname, $type = 'session') {
		global $current_user;
		if ($current_user->id > 0) {
			return $this->unsetReloadForUser($varname, $current_user->id, $type);
		}
 	}
 	
 	public function unsetReloadForUser($varname, $userid, $type = 'session') {
		global $adb;
		if (Vtecrm_Utils::CheckTable($this->table_users)) {
			$now = date('Y-m-d H:i:s');
			if ($adb->isMysql()) {
				$adb->pquery("INSERT IGNORE INTO {$this->table_users} (userid, storage_type, varname, reload_date) VALUES (?,?,?,?)", array($userid, $type, $varname, $now));
			} else {
				$res = $adb->pquery("SELECT userid FROM {$this->table_users} WHERE userid = ? AND storage_type = ? AND varname = ?", array($userid, $type, $varname));
				if ($res && $adb->num_rows($res) == 0) {
					$adb->pquery("INSERT INTO {$this->table_users} (userid, storage_type, varname, reload_date) VALUES (?,?,?,?)", array($userid, $type, $varname, $now));
				} else {
					$adb->pquery("UPDATE {$this->table_users} SET reload_date = ? WHERE userid = ?, storage_type = ? AND varname = ?", array($now, $userid, $type, $varname));
				}
			}
			
			$this->log("[{$varname}] Unset reload for user #$userid");
		}
 	}
 	
 	public function unsetReloadForCurrentHost($varname, $type = 'apcu') {
		$hostid = OSUtils::getHostId();
		return $this->unsetReloadForHost($hostid, $varname, $type);
 	}
 	
 	public function unsetReloadForHost($hostid, $varname, $type = 'apcu') {
		global $adb;
		// I need to reset for the current host
		// This table works in the opposite way, so if a row is present, no reload is necessary
		if (Vtecrm_Utils::CheckTable($this->table_hosts)) {
			$now = date('Y-m-d H:i:s');
			if ($adb->isMysql()) {
				$adb->pquery("INSERT IGNORE INTO {$this->table_hosts} (hostid, storage_type, varname, reload_date) VALUES (?,?,?,?)", array($hostid, $type, $varname, $now));
			} else {
				$res = $adb->pquery("SELECT hostid FROM {$this->table_hosts} WHERE hostid = ? AND storage_type = ? AND varname = ?", array($hostid, $type, $varname));
				if ($res && $adb->num_rows($res) == 0) {
					$adb->pquery("INSERT INTO {$this->table_hosts} (hostid, storage_type, varname, reload_date) VALUES (?,?,?,?)", array($hostid, $type, $varname, $now));
				} else {
					$adb->pquery("UPDATE {$this->table_hosts} SET reload_date = ? WHERE hostid = ?, storage_type = ? AND varname = ?", array($now, $hostid, $type, $varname));
				}
			}
			$this->log("[{$varname}] Unset reload for host $hostid");
		}
 	}
 	
 	public function unsetReloadGlobal($varname, $type = 'memcached') {
		global $adb;

		if (Vtecrm_Utils::CheckTable($this->table_global)) {
			$now = date('Y-m-d H:i:s');
			if ($adb->isMysql()) {
				$adb->pquery("INSERT IGNORE INTO {$this->table_global} (storage_type, varname, reload_date) VALUES (?,?,?)", array($type, $varname, $now));
			} else {
				$res = $adb->pquery("SELECT varname FROM {$this->table_global} WHERE storage_type = ? AND varname = ?", array($type, $varname));
				if ($res && $adb->num_rows($res) == 0) {
					$adb->pquery("INSERT INTO {$this->table_global} (storage_type, varname, reload_date) VALUES (?,?,?)", array($type, $varname, $now));
				} else {
					$adb->pquery("UPDATE {$this->table_global} SET reload_date = ? WHERE storage_type = ? AND varname = ?", array($now, $type, $varname));
				}
			}
			$this->log("[{$varname}] Unset global reload");
		}
 	}
		
 	
}
// crmv@187020e
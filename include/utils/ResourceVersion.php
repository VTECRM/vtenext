<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@94125 */

require_once('include/BaseClasses.php');
require_once('include/utils/Cache.php');

/**
 * This class create versioned versions of resources (js, css, images...)
 * so when files are changed or after an upgrade, they can be regenerated
 * to ensure browsers will download the latest version
 */
class ResourceVersion extends SDKExtendableUniqueClass {

	public $table = '';
	public $enabled = false;
	public $autoRefresh = false;
	
	public $enableLog = false; // warning: enabling the log can impact performance
	public $logFile = 'logs/resource_version.log';
	
	// when true, the cache file can be written (usually on multi-webserver systems this is not reccomended)
	// by default, this is true when called from cli
	protected $enableCacheWrite = false; // crmv@144893
	
	// crmv@140887
	public $theme = false;
	public $resourcesCDN = false;
	
	private $cacheThemeImage = array();
	private $cacheImageFile = array();
	// crmv@140887e
	
	public function __construct() {
		global $table_prefix, $theme, $default_theme;
		
		$this->table = $table_prefix.'_resource_version';
		$this->enabled = PerformancePrefs::getBoolean('VERSION_RESOURCES', true) && Vtecrm_Utils::CheckTable($this->table);
		$this->autoRefresh = PerformancePrefs::getBoolean('VERSION_RESOURCES_AUTOREFRESH', false);
		$this->enableCacheWrite = (php_sapi_name() == 'cli'); // crmv@144893
		
		// crmv@140887
		$this->theme = $theme ?: $default_theme;
		$this->resourcesCDN = PerformancePrefs::getBoolean('VERSION_RESOURCES_CDN', '');
		// crmv@140887e
		
		$this->log('ResourceVersion initialized, cache write is '.($this->enableCacheWrite ? 'enabled' : 'disabled'));
	}
	
	// crmv@144893
	protected function log($text) {
		if ($this->enableLog) {
			file_put_contents($this->logFile, '['.date('Y-m-d H:i:s').'] '.$text."\n", FILE_APPEND | LOCK_EX);
		}
	}
	
	public function enableCacheWrite() {
		$this->enableCacheWrite = true;
		$this->log('Enable writing to cache');
	}
	
	public function disableCacheWrite() {
		$this->enableCacheWrite = false;
		$this->log('Disable writing to cache');
	}
	// crmv@144893e
	
	/**
	 * function createResources : check and recreate symliks to resources
	 * params: file
	 * step1: get resources with update_revision = 1
	 * step2: increase revision
	 * step3: check original resource if is readable and new symlink is not created yet
	 * step4: create directory containing new symlink, create symlink, update database with new symlink, delete old symlink
	 */
	public function createResources($file=''){
		global $adb,$root_directory;
		
		$cache_method = PerformancePrefs::get('VERSION_RESOURCES_METHOD','link');
		$update_date = date('Y-m-d H:i:s');
		
		if (!empty($this->resourcesCDN)) $cache_method = 'remote'; // crmv@140887
		
		$this->log("Creating resources".($file ? " (file: $file)" : ''));
		
		$columns = array('resource', 'revision', 'versioned_resource');
		$adb->format_columns($columns);
		$sql = "SELECT ".implode(', ', $columns)." FROM {$this->table} WHERE update_revision = 1";
		$params = Array();
		if (!empty($file)){
			$sql.= " AND {$columns[0]} = ?";
			$params[] = $file;
		}
		$res = $adb->pquery($sql,$params);
		if ($res){
			while($row=$adb->fetchByAssoc($res,-1,false)){
				$initial_revision = false;
				$update_cache = false;
				$revision = (int)$row['revision'];
				if ($revision == -1){
					$initial_revision = true;
				}
				$revision++; //new revision
				$sql_update = "UPDATE {$this->table} SET versioned_resource=?, update_revision=?, revision=?, filemtime=?, last_update=?, type=? WHERE {$columns[0]} = ?";
				if ($initial_revision) {
					$fileTime = file_exists($row['resource']) ? filemtime($row['resource']) : null; // crmv@140887
					$upd_params = Array($row['resource'],0,$revision,$fileTime,$update_date,'original',$row['resource']);
					$res_update = $adb->pquery($sql_update,$upd_params);
					$update_cache = true;
					$newresource = $row['resource'];				
				} elseif ($cache_method == 'link' || $cache_method == 'copy') {
					$newresource = $this->generateName($row['resource'], $revision);
					if ($this->isVersionValid($row['resource'], $newresource, $cache_method)) {
						// remove the new resource
						@unlink($newresource);
						$newresource_exists = false;
					}
					if (file_exists($row['resource'])) {
						$ok = $this->generateVersion($row['resource'], $newresource, $cache_method);
						if ($ok) {							
							$upd_params = Array($newresource,0,$revision,filemtime($row['resource']),$update_date,$cache_method,$row['resource']);
							$res_update = $adb->pquery($sql_update,$upd_params);
							if (!empty($row['versioned_resource']) && $row['versioned_resource'] != $row['resource']) {
								// remove old version
								@unlink($row['versioned_resource']);
							}
							$update_cache = true;
						} else {
							// unable to copy, use the original file
							$upd_params = Array($row['resource'],0,$revision,filemtime($row['resource']),$update_date,'original',$row['resource']);
							$res_update = $adb->pquery($sql_update,$upd_params);
							$update_cache = true;
							$newresource = $row['resource'];
						}
					} else {
						// some problem, maybe the original file doesn't exist
						if (!file_exists($row['resource'])) {
							$this->removeResource($row['resource']);
						}
					}
				}
				
				// crmv@140887
				if ($cache_method == 'remote') {
					$newresource = $this->generateName($row['resource'], $revision);
					$upd_params = Array($newresource,0,$revision,null,$update_date,$cache_method,$row['resource']);
					$res_update = $adb->pquery($sql_update,$upd_params);
					$update_cache = true;
				}
				// crmv@140887e
			}
		}
		
		if (!empty($file)) {
			if ($update_cache && !empty($newresource)){
				// crmv@144893
				if ($this->enableCacheWrite) {
					$resources_cache_obj = $this->cacheResources();
					$resources_cache = $resources_cache_obj->get();
					$fileTime = file_exists($file) ? filemtime($file) : null; // crmv@140887
					$resources_cache[$file] = array($newresource, $fileTime);
					$resources_cache_obj->set($resources_cache);
				}
				// crmv@144893e
				return $newresource;
			} 
		} else {
			//reset cache
			$resources_cache_obj = $this->cacheResources(true);
			$cache_arr = $resources_cache_obj->get();
			$resources_cache_obj->set($cache_arr);
		}
	}
	
	/**
	 * Generates the name for the versioned file (either link or real copy)
	 */
	// crmv@140887
	protected function generateName($file, $revision) {
		$isImageFile = $this->isImageFile($file);
		if ($isImageFile) {
			$file = $this->generateImageName($file, $this->theme, $revision);
		}
		
		$path_info = pathinfo($file);
		$newpath = $path_info['dirname'];
		
		if (!empty($this->resourcesCDN)) {
			$newresource = $this->resourcesCDN."/".$newpath.'/'.$path_info['filename'].".".$path_info['extension'];
		} else {
			$newresource = $newpath."/".$path_info['filename']."_v".$revision.".".$path_info['extension'];
		}
		
		return $newresource;
	}
	
	protected function generateImageName($file, $theme, $revision = 0) {
		$imagepath = $this->cacheThemeImage[$file][$theme][$revision] ?? null;
		
		if ($imagepath === null) {
			// Check in theme specific folder
			if (file_exists("themes/$theme/images/$file")) {
				$imagepath = "themes/$theme/images/$file";
			} else if (file_exists("themes/images/$file")) {
				// Search in common image folder
				$imagepath = "themes/images/$file";
			} else if (file_exists("themes/images/modulesimg/$file")) {
				$imagepath = "themes/images/modulesimg/$file";
			} else {
				// Not found anywhere? Return whatever is sent
				$imagepath = $file;
			}
			
			$this->cacheThemeImage[$file][$theme][$revision] = $imagepath;
		}
		
		return $imagepath;
	}
	
	public function isImageFile($file) {	//crmv@155337
		$isImage = $this->cacheImageFile[$file] ?? null;
		
		if ($isImage === null) {
			$imageTypes = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
			
			$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			$isImage = in_array($extension, $imageTypes);
			
			$this->cacheImageFile[$file] = $isImage;
		}
		
		return $isImage;
	}
	// crmv@140887e
	
	protected function isResourceChanged($file, $versioned, $timestamp = null) {
		$changed = (empty($timestamp) || ($timestamp < filemtime($file)) || !empty($this->resourcesCDN)); // crmv@140887
		// you can also add some kind of hash to check for changes, but might slow down things badly!
		return $changed;
	}
	
	/**
	 * Return true if the versioned file is valid
	 */
	protected function isVersionValid($file, $versioned, $type) {
		if ($type == 'link') {
			$path_info = pathinfo($file);
			$valid = (!empty($versioned) && is_link($versioned) && @readlink($versioned) && file_exists($path_info['dirname']."/".@readlink($versioned)));
		} elseif ($type == 'copy') {
			$valid = (!empty($versioned) && is_file($versioned));
		} else {
			throw new Exception("Unknown versioning type");
		}
		return $valid;
	}
	
	/**
	 * Physically create the link or copy the file
	 */
	protected function generateVersion($file, $versioned, $type) {
		$path_info = pathinfo($file);
		if ($type == 'link') {
			$ok = @symlink($path_info['basename'],$versioned);
		} elseif ($type == 'copy') {
			$ok = @copy($path_info['basename'],$versioned);
		} else {
			throw new Exception("Unknown versioning type");
		}
		$this->log("New version generated ($type): ".$versioned);
		return $ok;
	}
	
	/**
	 * function createResource : create or update cache resource
	 * params: $file: path of the original resource, $force_create: default false, force call createResources function
	 */
	public function createResource($file, $force_create = false) {
		global $adb;
		$column = "resource";
		$adb->format_columns($column);
		$sql_check = "SELECT {$column}, update_revision FROM {$this->table} WHERE {$column} = ?";
		$res_check = $adb->pquery($sql_check, [$file]);
		if ($res_check) {
			if ($adb->num_rows($res_check) > 0) {
				$upd = $adb->query_result_no_html($res_check, 0, 'update_revision');
				if ($upd != '1') {
					$adb->pquery("UPDATE {$this->table} SET update_revision = 1 WHERE {$column} = ?", [$file]);
				}
			} else {
				if ($adb->isMysql()) {
					$adb->pquery("INSERT IGNORE INTO {$this->table} ({$column}, update_revision) VALUES (?, ?)", [$file, 1]);
				} else {
					$result = $adb->pquery("SELECT 1 FROM {$this->table} WHERE {$column} = ?", [$file]);
					if ($result && $adb->num_rows($result) == 0) {
						$adb->pquery("INSERT INTO {$this->table} ({$column}, update_revision) VALUES (?, ?)", [$file, 1]);
					}
				}
			}
			$this->log("Resource set for update: " . $file);
		}
		if ($force_create) {
			$this->createResources($file);
		}
	}
	
	/**
	 * function cacheResources : create or update cached resources object
	 * params: $recreate: boolean to force rebuild cache
	 */		
	public function cacheResources($recreate=false){
		global $adb;
		
		$cache = Cache::getInstance('cacheResources');
		$resources_cache = $cache->get();

		if (($resources_cache === false || $recreate) && $this->enableCacheWrite){ // crmv@144893
			$resources_cache = Array();
			$columns = array('resource', 'versioned_resource', 'type', 'filemtime');
			$adb->format_columns($columns);
			$sql = "SELECT ".implode(', ', $columns)." FROM {$this->table}";
			$res = $adb->pquery($sql,Array());
			if ($res){
				while($row=$adb->fetchByAssoc($res,-1,false)){
					switch($row['type']){
						case 'original':
						case 'remote': // crmv@140887
							$resources_cache[$row['resource']] = array($row['versioned_resource'], $row['filemtime']);
							break;
						case 'link':
						case 'copy':
							if ($this->isVersionValid($row['resource'], $row['versioned_resource'],  $row['type'])) {
								$resources_cache[$row['resource']] = array($row['versioned_resource'], $row['filemtime']);
							}
							break;
						default:
							break;	
					}
				}
			}
			$cache->set($resources_cache);
		}
		return $cache;
	}
	
	/**
	 * function getResource : get versioned resource
	 * params: $file: path of the original resource
	 * step1: create Array with versioned resources if it is not present
	 * step2: return versioned resource or $file if it is not present
	 */		
	public function getResource($file) {
		global $adb;
		
		// crmv@140887
		if ($this->isImageFile($file)) {
			$file = $this->generateImageName($file, $this->theme, null);
		}
		// crmv@140887e
		
		if (!$this->enabled) return $file; // crmv@167753 - moved here
		
		$resources_cache = $this->cacheResources()->get();
		if (!empty($resources_cache) && isset($resources_cache[$file])) {
			$newfile = $resources_cache[$file][0];
			if ($this->autoRefresh) {
				// check for changes
				$filets = $resources_cache[$file][1];
				if ($this->isResourceChanged($file, $newfile, $filets)) {
					$newfile = $this->createResource($file,true);
				}
			}
		// crmv@144893
		// flag for update only if the cache is valid but the file is missing
		} elseif (is_array($resources_cache)) {
			$newfile = $this->createResource($file,$this->autoRefresh); // crmv@140887
		}
		// crmv@144893e
		
		//$this->log("Requested resource: $file");
		
		if (!empty($newfile)){
			return $newfile;
		} else {
			return $file;
		}
	}
	
	/**
	 * function updateResources : update versioned resources
	 * params: none
	 * step1: for every resource not in pending state control last modified date if it is newer than the versioned one put into creation queue (call createResource)
	 * step2: call createResources (create or update versioned resources that are in pending state)
	 */			
	public function updateResources() {
		global $adb;
		
		$this->log("Updating resources...");
		
		$sql = "SELECT ".$adb->format_column('resource').",versioned_resource,filemtime FROM {$this->table} WHERE update_revision = 0"; // crmv@165801
		$res = $adb->query($sql);
		if ($res){
			while($row = $adb->fetchByAssoc($res,-1,false)){
				if (file_exists($row['resource'])){
					if ($this->isResourceChanged($row['resource'], $row['versioned_resource'], $row['filemtime'])) {
						$this->createResource($row['resource']);
					}
				} else {
					// file deleted, remove the versioning line
					$this->removeResource($row['resource']);
				}
			}
		}
		$this->createResources();
	}
	
	/**
	 *
	 */
	public function removeResource($filename) {
		global $adb;
		$sqldel = "DELETE FROM {$this->table} WHERE ".$adb->format_column('resource')." = ?"; // crmv@165801
		$adb->pquery($sqldel, array($filename));
		
		$this->log("Resource removed: ".$filename);
	}
	
	/**
	 * function resetResources : reset versioned resources
	 * params: none
	 * for every resource delete entry and try to delete copy/link
	 */			
	public function resetResources(){
		global $adb;
		
		$this->log("Resetting resources...");
		
		$sql = "SELECT ".$adb->format_column('resource').",versioned_resource,type FROM {$this->table}"; // crmv@165801
		$res = $adb->query($sql);
		if ($res){
			while($row = $adb->fetchByAssoc($res,-1,false)){
				switch($row['type']){
					case 'original':
					case 'remote': // crmv@140887
						//do nothing
						break;
					case 'link':
					case 'copy':
						if ($this->isVersionValid($row['resource'], $row['versioned_resource'],  $row['type'])) {
							@unlink($row['versioned_resource']);
						}
						break;
					default:
						break;
				}
			}
		}
		$sql = "DELETE FROM {$this->table}";
		$adb->query($sql);
	}
	
	// crmv@140887
	public function getJSResources() {
		
		$resources = array('clock_bg.gif', 'close_details.png', 'open_details.png', 'btnL3Calendar.gif',
			'listview_folder.png', 'folderback.png',
			'modules/SDK/examples/uitypeSocial/img/bkico.png', 'modules/SDK/examples/uitypeSocial/img/fbico.png',
			'modules/SDK/examples/uitypeSocial/img/gpico.png', 'modules/SDK/examples/uitypeSocial/img/liico.png',
			'modules/SDK/examples/uitypeSocial/img/orico.png', 'modules/SDK/examples/uitypeSocial/img/qzico.png',
			'modules/SDK/examples/uitypeSocial/img/twico.png', 'modules/SDK/examples/uitypeSocial/img/ytico.png',
			'offstar.gif', 'onstar.gif', 'modules/Calendar/wdCalendar/css/images/calendar/task.png',
			'clear_field.gif', 'delete.gif', 'movecol_up.gif', 'movecol_down.gif', 'movecol_del.gif',
		);
		
		$resourcesJS = array();
		
		foreach ($resources as $resource) {
			$newfile = $this->getResource($resource);
			$resourcesJS[$resource] = $newfile;
		}
		
		return $resourcesJS;
	}
	// crmv@140887e
	
}

/**
 * Handy function to get the resource versioned name
 * Just a wrapper to the ResourceVersion::getResource method
 */
function resourcever($filename) {
	$RV = ResourceVersion::getInstance();
	$filename = $RV->getResource($filename);
	return $filename;
}
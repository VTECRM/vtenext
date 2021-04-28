<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
namespace VteSyncLib;

/* crmv@190016 */

class Main {

	public $version = '2.0';			// version of VteSyncLib

	public $simulate = false;			// if true, no changes will be made to servers
	public $timezone = 'Europe/Rome';	// timezone of the host running VteSync

	protected $storage = null;
	protected $log = null;

	protected $ready = false;

	protected $modules = array();
	protected $firstSyncIntervals = array('P1D', 'P1Y');

	protected $connectors = array();
	protected $connInstances = array();
	protected $userMapping = array();
	
	public function __construct($config = array()) {

		// timezone set
		//set server timezone
		if (function_exists('date_default_timezone_set')) {
			if ($config['local_timezone']) $this->timezone = $config['local_timezone'];
			date_default_timezone_set($this->timezone);
		}

		$this->log = new Logger($config['loglevel']);
		
		if ($config['simulate']) $this->simulate = true;

		$r = $this->setupStorage($config['storage']);
		if (!$r) return $this->log->error('Unable to initialize Storage class');

		$r = $this->includeConnectors();
		if (!$r) return false;

		if (is_array($config['connectors'])) {
			foreach ($config['connectors'] as $conn_name => $conn_cfg) {
				if (!array_key_exists($conn_name, $this->connectors)) {
					$this->log->warning("Unknown connector ($conn_name) in config, skipped.");
					continue;
				}
				$this->connectors[$conn_name]['config'] = $conn_cfg;
				if ($conn_cfg['enabled'] == true) $this->connectors[$conn_name]['enabled'] = true;
			}
		}

		// remove inactive connectors
		foreach ($this->connectors as $k=>$conn) {
			if (!$conn['enabled']) unset($this->connectors[$k]);
		}

		if (count($this->connectors) < 2) {
			return $this->log->fatal('Not enough connectors are enabled. Please enable at least 2.');
		}

		if ($config['user_mapping'] && $this->connectors[$config['user_mapping']]) {
			$this->connectors[$config['user_mapping']]['user_mapping'] = true;
		} else {
			return $this->log->fatal('Not connector for user mapping found.');
		}

		if ($config['firstsync_interval_past']) {
			$this->firstSyncIntervals[0] = $config['firstsync_interval_past'];
		}
		if ($config['firstsync_interval_future']) {
			$this->firstSyncIntervals[1] = $config['firstsync_interval_future'];
		}

		$this->ready = true;

	}

	public function isReady() {
		return $this->ready;
	}

	protected function setupStorage($config) {

		$type = $config['type'];
		
		if ($type === 'vte') {
			$class = '\VteSyncLib\Storage\VTE';
		} elseif ($type === 'database') {
			$class = '\VteSyncLib\Storage\Database';
		} else {
			return $this->log->error('Unknown storage type: '.$type);
		}
		
		$storage = new $class($config[$type]);
		try {
			$r = $storage->connect();
		} catch (DBException $e) {
			return $this->log->fatal($e->getMessage());
		}
		if (!$r) return false;
		
		$r = $storage->initSchema();
		if (!$r) return $this->log->error('Unable to initialize storage schema');
		
		$this->storage = $storage;
		
		return true;
	}

	protected function includeConnectors() {
		$this->log->debug('Entering '.__METHOD__);

		$files = glob(dirname(__FILE__).'/Connector/*.php');
		foreach ($files as $f) {
			if (in_array(basename($f), array('BaseConnector.php' , 'ConnectorInterface.php'))) continue;
			$class_name = 'VteSyncLib\Connector\\'.str_replace('.php', '', basename($f));
			include_once($f);
			if (class_exists($class_name)) {
				$connectorName = $class_name::$name;
				if ($connectorName) {
					$this->connectors[$connectorName] = array('name'=>$connectorName, 'class'=>$class_name, 'enabled'=>false);
				}
			}
		}

		return true;
	}

	public function getUserMapping() {
		$this->log->debug('Entering getUserMapping');

		// read from the mapping table (if users are synchronized)
		$map = $this->storage->getAllMappedIds('Users');
		$this->userMapping = $map;
		
		// find the connector with the user mapping
		// TODO -- use parameter
		/*foreach ($this->connInstances as $c) {
			if ($c['user_mapping']) {
				if (method_exits($c, 'getUserMapping')) {
					$this->userMapping = $c->getUserMapping();
				} else {
					return $this->log->fatal('Method getUserMapping not defined for connector '.$c->$name);
				}
			}
		}*/
		return true;
	}
	
	/**
	 * Return all the matching owners in format array($connector => info, conn => info, ...)
	 */
	public function getMappedOwners($connector, $id) {
		$list = array();
		foreach ($this->userMapping as $gropuid => $uinfo) {
			if (array_key_exists($connector, $uinfo)) {
				if ($uinfo[$connector]['id'] == $id) {
					$list = $uinfo;
					unset($list[$connector]);
				}
			}
		}
		return $list;
	}
	
	public function findMappedUser($connector, $id, $forConnector) {
		foreach ($this->userMapping as $gropuid => $uinfo) {
			if (array_key_exists($connector, $uinfo) && array_key_exists($forConnector, $uinfo)) {
				if ($uinfo[$connector]['id'] == $id) {
					return $uinfo[$forConnector]['id'];
				}
			}
		}
		return null;
	}

	// dates are relative to the local server
	public function setLastSyncDate($username, $connName, $module, $date = null) {
		$this->log->debug('Entering setLastSyncDate for user '.$username);

		if (empty($date)) {
			// current date + 5 sec (warning, not very reliable) TODO: verify!
			$date = date('Y-m-d H:i:s', time() + 5);
		}

		$r = $this->storage->setLastSyncDate($username, $connName, $module, $date);
		if ($r === false) {
			$this->log->warning("Error setting last sync date");
		} else {
			$this->log->debug("Set last sync date for connector $connName to $date");
		}
		
		return true;
	}

	// return local date
	public function getLastSyncDate($username, $connName, $module) {
		$this->log->debug('Entering getLastSyncDate for user '.$username);

		if (empty($username)) {
			return $this->log->error("User is empty");
		}

		$date = $this->storage->getLastSyncDate($username, $connName, $module);
		if (!$date) {
			return $this->log->error('Unable to retrieve last sync date');
		}
		
		$this->log->debug("Last sync for connector $connName was $date");
		return $date;
	}

	// reset all,so next sync is like first time sync
	public function resetAll() {
		$this->log->info('Resetting mapping and connectors');

		$this->storage->resetAll();
	}


	public function syncForUser($user) {
		$this->log->info(""); // empty line
		$this->log->info('Starting sync for user '.$user['VTE']['username']);

		if (empty($this->modules)) {
			return $this->log->fatal('No modules specified');
		}

		foreach ($this->modules as $module) {

			$this->log->info("Synchronizing module $module");
			$recordsPulled = array();
			$this->actionList = array();
			
			
			// -------------- sync contatti
			$conGoogle = $this->connInstances['Google'];
			$conVte = $this->connInstances['VTE'];
			
			$conGoogle->setModule($module);
			$conVte->setModule($module);
			
			$conuserGoogle = $user['Google'];
			if (empty($conuserGoogle)) {
				$this->log->warning("User for connector Google is empty");
				continue;
			}
			
			$conuserVte = $user['VTE'];
			if (empty($conuserVte)) {
				$this->log->warning("User for connector VTE is empty");
				continue;
			}
			
			$lastDateVTE = $this->getLastSyncDate($user['VTE']['user_name'], 'VTE', $module);
			if ($lastDateVTE == 'never') {
				// first sync, create array of dates
				$this->log->info('First syncronization for connector VTE and user '.$user['VTE']['username']);
				$conVte->firstSync = true;
				$lastDateTz = null;
			} else {
				$dt = DateTime::createFromFormat('Y-m-d H:i:s', $lastDateVTE);
				$lastDateTz = $dt->format('c');
				$conVte->firstSync = false;
			}
			
			$lastVteTime = null;
			$recs = $conVte->pull($conuserVte, $lastDateTz);
			if ($recs !== false) {
				$recordsPulled['VTE'] = $recs;
				$total = count($recs['created']) + count($recs['updated']) + count($recs['deleted']);
				$lastVteTime = $recs['lastModifiedTime'];
				$this->log->info("Connector VTE returned $total records");
			}
			
			$recs = $conGoogle->pull($conuserGoogle, $lastDateTz);
			if ($recs !== false) {
				$recordsPulled['Google'] = $recs;
				$total = count($recs['created']) + count($recs['updated']) + count($recs['deleted']) + count($recs['existing']);
				$this->log->info("Connector Google returned $total records");
			}
			
			// create the list of changes to be pushed to servers
			$r = $this->syncRecords($recordsPulled, $conVte, $conuserVte);
			if (!$r) return false;
			
			// push changes to servers
			$r = $this->pushChanges($user, $this->actionList);
			if (!$r) return $this->log->error('Unable to save changes');
			
			// this is necessary for pagination, so at the next round we process only the new records
			if ($lastVteTime) {
				$this->setLastSyncDate('admin', 'VTE', $module, $lastVteTime);
			}
			
			return true;
			
			// ----------- old sync calendario
			/*$handled = 0;
			foreach ($this->connInstances as $con) {
				$cname = $con::$name;

				if (!$con->canHandleModule($module)) {
					$this->log->warning("Connector $cname can't synchronize module $module");
					continue;
				}
				++$handled;
				$con->setModule($module);


				$conuser = $user[$cname];
				if (empty($conuser)) {
					$this->log->warning("User for connector {$cname} is empty");
					continue;
				}

				// get local time and convert to ISO format
				$lastDate = $this->getLastSyncDate($user['VTE]['user_name], $cname, $module);
				if ($lastDate == 'never') {
					// first sync, create array of dates
					$this->log->info('First syncronization for connector '.$cname.' and user '.$user['VTE']['username']);
					$con->firstSync = true;
					$date0 = new DateTime();
					$date0->sub(new DateInterval($this->firstSyncIntervals[0]));
					$date1 = new DateTime();
					$date1->add(new DateInterval($this->firstSyncIntervals[1]));
					$lastDateTz = array(
						$date0->format('c'),
						$date1->format('c'),
					);
				} else {
					$dt = DateTime::createFromFormat('Y-m-d H:i:s', $lastDate);
					$lastDateTz = $dt->format('c');
					$con->firstSync = false;
				}
				
				
				try {
					$recs = $con->pull($conuser, $lastDateTz);
				} catch (Google_Exception $e) {
					if ($e->getCode() == 410 && strpos($e->getMessage(), 'The requested minimum modification time lies too far in the past.') !== false) {
						$this->log->warning('The last syncronization was too long ago. Resetting the last check timestamps for all connectors and recheck at the next cycle');
						foreach ($this->connInstances as $con2) {
							$cname2 = $con2::$name;
							$this->setLastSyncDate($user, $cname2, $con2->module);
						}
						return true;
					} else {
						// throw again the exception
						throw $e;
					}
				}
				
				if ($recs !== false) {
					$recordsPulled[$cname] = $recs;
					$total = count($recs['created']) + count($recs['updated']) + count($recs['deleted']);
					$this->log->info("Connector $cname returned $total records");
				}
			}

			if ($handled >= 2) {

				// fill record with other connectors ids
				$r = $this->setRecordsIds($recordsPulled);
				if (!$r) return false;

				// create the list of changes to be pushed to servers
				$r = $this->syncRecords($recordsPulled);
				if (!$r) return false;

				// push changes to servers
				$r = $this->pushChanges($user, $this->actionList);
				if (!$r) return $this->log->error('Unable to save changes');
			}*/

		}


		return true;
	}
	
	
	protected function filterEtags($cname, &$records) {
		$this->log->debug('Entering '.__METHOD__);

		$totalPurged = 0;
		foreach ($records as $edittype => $list) {
			if (is_array($list)) {
				$purged = 0;
				foreach ($list as $idx => $record) {
					
					$id = $record->getId($cname);
					$etag = $record->getEtag($cname);
					if ($etag === '' || $etag === null) {
						$this->log->warning('Missing etag in record # '.$id);
						continue;
					}
					$oldEtag = $this->storage->getEtag($cname, $record->getModule(), $id);
					if (!is_null($oldEtag) && $oldEtag === $etag) {
						unset($records[$edittype][$idx]);
						$this->log->debug("Excluded record # $id");
						++$purged;
					}
				}
				$totalPurged += $purged;
				if ($purged > 0) {
					// renumber keys
					array_splice($records[$edittype], 0, 0);
				}
			}
		}
		if ($totalPurged > 0) {
			$this->log->info("Excluded $totalPurged records by etag");
		}
		
		return $totalPurged;
	}
	

	protected function setRecordsIds(&$records) {
		$this->log->debug('Entering '.__METHOD__);

		foreach ($records as $connName => &$list) {
			foreach ($list as $listtype=>$v) {
				if ($listtype == 'metadata' || !is_array($list[$listtype])) continue;
				foreach ($list[$listtype] as &$rec) {
					$id = $rec->getId($connName);
					$module = $rec->getModule();
					if (empty($id) && $module != "TicketComment" ) $this->log->warning("Record with empty id in connector $connName");
					
					$otherids = $this->getMappedIds($connName, $rec);
					unset($otherids[$connName]);
					foreach ($otherids as $cname=>$info) {
						$rec->setId($cname, $info['id']);
						$rec->setEtag($cname, $info['etag']);
					}
					
					if ($module != 'Users') {
						$this->fillOwners($connName, $rec);
						$this->fillReferenceFields($connName, $rec);
					}
				}
			}
		}

		return true;
	}
	
	protected function fillOwners($connector, &$record) {
		$owner = $record->getOwner($connector);
		$otherOwners = $this->getMappedOwners($connector, $owner);
		unset($otherOwners[$connector]);
		foreach ($otherOwners as $cname=>$uinfo) {
			$record->setOwner($cname, $uinfo['id']);
		}
	}
	
	protected function fillReferenceFields($connector, &$record) {
		
		$module = $record->getModule();
		$refFields = array();
		// set also ids for reference fields
		if (!isset($refFields[$module])) {
			$refFields[$module] = \VteSyncLib\Model\CommonRecord::getFieldsByType($module, 'reference');
		}
		
		if (!empty($refFields[$module])) {
			foreach ($refFields[$module] as $fname => $typeinfo) {
				$destmod = $typeinfo['module'];
				$value = $record->getField($fname);
				if (is_array($value)) {
					$changed = false;
					foreach ($value as $destConn => $refValue) {
						$refId = $refValue['id'];
						$refModule = $refValue['module'];
						$refMap = $this->storage->getMappedIds($destConn, $refModule, $refId);
						if (count($refMap) > 0) {
							foreach ($refMap as $refConn => $idinfo) {
								if (!isset($value[$refConn])) {
									$changed = true;
									$value[$refConn] = array('id' => $idinfo['id'], 'module' => $refModule);
								}
							}
							break;
						}
					}
					if ($changed) {
						$record->addField($fname, $value);
					} else {
						// TODO: save in a list and search again after all syncs
						// bacause might be imported later
					}
				}
			}
		}
	}

	// crmv@120777 - removed func

	protected function getMappedIds($conn, $record) {
		$this->log->debug('Entering '.__METHOD__);
		$id = $record->getId($conn);
		
		$ids = $this->storage->getMappedIds($conn, $record->getModule(), $id);
		
		if (!is_array($ids)) {
			return $this->log->error('Unable to retrieve saved ids');
		}

		return $ids;
	}

	protected function findRecordById($records, $conn, $id) {
		$this->log->debug('Entering '.__METHOD__);

		foreach ($records as $type => $list) {
			if (is_array($list)) {
				foreach ($list as $r) {
					if ($r->getId($conn) == $id) return $r;
				}
			}
		}
		return null;
	}

	public function syncRecords($module, $records, $connectors) {
		$this->log->debug('Entering '.__METHOD__);
		
		$cnt = count($records);
		if ($cnt == 0) {
			return $this->log->error('Connectors returned no results.');
		}/* elseif ($cnt <= 1) {
			return $this->log->error('Only one connector returned results, not enough.');
		}*/

		$actionList = array();

		// -------------- sync contatti
		/*$googleVteMap = array();
		
		// get record missing from VTE connector
		if (count($records['Google']['existing']) > 0) {
			$missing = $conVte->checkMissing($conuserVte, $records['Google']['existing']);
			if (is_array($missing)) {
				$actionList['Google']['delete'] = $missing;
			}
			// extract Google-VTE mapping
			foreach ($records['Google']['existing'] as $record) {
				$vteid = $record->getId('VTE');
				if ($vteid) {
					$googleVteMap[$vteid] = $record->getId('Google');
				}
				
			}
		}
		
		// do the creations (but check if already mapped, skip any date check, just create them)
		foreach ($records['VTE']['created'] as $createrec) {
			$vteid = $createrec->getId('VTE');
			$action = array_key_exists($vteid, $googleVteMap) ? 'update' : 'create';
			if ($action == 'update') $createrec->setId('Google', $googleVteMap[$vteid]);
			// list of ids, should be only 1 connector, but sometimes there are more
			$actionList['Google'][$action][] = $createrec;
		}
		
		// and updates!
		foreach ($records['VTE']['updated'] as $createrec) {
			$vteid = $createrec->getId('VTE');
			$action = array_key_exists($vteid, $googleVteMap) ? 'update' : 'create';
			if ($action == 'update') $createrec->setId('Google', $googleVteMap[$vteid]);
			// list of ids, should be only 1 connector, but sometimes there are more
			$actionList['Google'][$action][] = $createrec;
		}

		$this->actionList = $actionList;

		return true;
		*/
		
		// ------------------- old sync calendario -----------
		
		// do the creations (but check if already mapped, skip any date check, just create them)
		foreach ($records as $con=>$lists) {
			if (!is_array($lists['created'])) continue;
			foreach ($lists['created'] as $k=>$createrec) {
				// list of ids, should be only 1 connector, but sometimes there are more
				$otherids = $createrec->getIds();
				unset($otherids[$con]);
				foreach ($connectors['in'] as $cname) {
					if ($cname != $con) {
						$action = array_key_exists($cname, $otherids) ? 'update' : 'create';
						$actionList[$cname][$action][] = $createrec;
					}
				}
			}
		}

		// first check deleted records
		foreach ($records as $con=>$lists) {
			if (!is_array($lists['deleted'])) continue;
			// TODO: check for conflicts with other updates
			foreach ($lists['deleted'] as $k=>$delrec) {
				$otherids = $delrec->getIds();
				unset($otherids[$con]);
				foreach ($otherids as $conName => $id) {
					$delete = true;
					// check for conflicts with updates
					if (is_array($records[$conName]['updated'])) {
						foreach ($records[$conName]['updated'] as $k=>$updaterec) {
							$uid = $updaterec->getId($conName);
							if ($uid == $id && $updaterec->isMoreRecent($delrec)) {
								$delete = false;
								break;
							}
						}
					}
					if ($delete) {
						$actionList[$conName]['delete'][] = $delrec;
					}
				}
			}
		}

		// TODO use Last parameter

		// do the updates
		foreach ($records as $con=>$lists) {
			if (!is_array($lists['updated'])) continue;
			foreach ($lists['updated'] as $k=>$updaterec) {
				$otherids = $updaterec->getIds();
				unset($otherids[$con]);
				$useRecord = true;
				// is already mapped in another system, take the newest if conflicting
				foreach ($otherids as $con2=>$id2) {
					if (!array_key_exists($con2, $connectors['in'])) continue; // restrict to write connectors
					if (!empty($id2)) {
						$otherRec = $this->findRecordById($records[$con2], $con2, $id2);
						if ($otherRec && $otherRec->isMoreRecent($updaterec)) {
							//$winnerRecord = $otherRec;
							$useRecord = false;
							break;
						}

					}
				}
				if ($useRecord) {
					foreach ($connectors['in'] as $cname) {
						if ($cname != $con) {
							$ids = $updaterec->getIds();
							$action = (empty($ids[$cname]) ? 'create' : 'update' );
							$actionList[$cname][$action][] = $updaterec;
						}
					}
				}
			}
		}

		$this->actionList = $actionList;

		return true;
	}

	public function saveRecordMapping($list) {
		$this->log->debug('Entering '.__METHOD__);

		foreach ($list as $listtype=>$l) {
			if (!is_array($list[$listtype])) continue;
			foreach ($l as $record) {
				$ids = $record->getIds();
				$etags = $record->getEtags();
				// find first non empty id
				$ret = $record->getNonEmptyId();
				$conn = $ret['connector'];
				$matchId = $ret['id'];
				if($record->getModule() != "TicketComment") {
					if (empty($conn) || empty($matchId)) {
						$this->log->warning('No id found for record');
						continue;
					}
				}
				
				$r = $this->storage->saveMappedIds($conn, $record->getModule(), $matchId, $ids, $etags);
			}
		}
		return true;
	}
	
	public function pushMetaChanges($module, $records, $connectors) {
		$this->log->debug('Entering '.__METHOD__);

		foreach ($connectors['in'] as $cname) {
			$meta = $records[$cname]['metadata'];
			if ($meta) {
				foreach ($records as $cname2 => $recinfo) {
					if ($cname == $cname2) continue;
					$meta2 = $recinfo['metadata'];
					if ($meta2) {
						// diff the picklist
						$diff = $this->metaDiff($cname, $meta, $meta2);
						$this->applyMetaDiff($cname, $module, $diff);
					}
				}
			}
		}
		
		return true;
	}
	
	protected function metaDiff($destConn, $metaDest, $metaOther) {
		$result = array();
		
		// for now, only picklists are synchronized
		$plist1 = $metaDest->getFlatPicklists();
		$plist2 = $metaOther->getFlatPicklists();
		
		// convert to key->val
		foreach ($plist1 as $pname => $values1) {
			if (array_key_exists($pname, $plist2)) {
				$values2 = $plist2[$pname];
				// to add to dest
				$toAdd = array_diff_key(array_change_key_case($values2), array_change_key_case($values1));
				if (count($toAdd) > 0) {
					$result[$destConn]['picklist']['add'][$pname] = $toAdd;
				}
				// deletion and modification of labels not handled
			}
		}
		
		return $result;
	}
	
	protected function applyMetaDiff($destConn, $module, $metaDiff) {
		foreach ($metaDiff as $cname => $changes) {
			if (is_array($changes['picklist']['add'])) {
				$con = $this->connInstances[$cname];
				$r = $con->pushMeta($module, $changes);
				if (!$r) {
					$this->log->warning('Unable to push meta changes to connector '.$cname);
				}
			}
		}
		return true;
	}
	
	public function pushChanges($module, $list, $connectors) {
		$this->log->debug('Entering '.__METHOD__);

		foreach ($connectors['in'] as $cname) {
			// TODO: user flow!
			/*$conuser = $user[$cname];
			if (empty($conuser)) {
				$this->log->warning("User for connector {$cname} is empty");
				continue;
			}*/
			$lastSyncDate = null;
			if (is_array($list[$cname])) {
				$con = $this->connInstances[$cname];
				$results = $con->push($module, $conuser, $list[$cname]);
				$this->saveRecordMapping($list[$cname]);
				if ($results) {
					$rvalues = array();
					if ($results['created'] > 0) {
						$rvalues[] = $results['created'].' created';
					}
					if ($results['updated'] > 0) {
						$rvalues[] = $results['updated'].' updated';
					}
					if ($results['deleted'] > 0) {
						$rvalues[] = $results['deleted'].' deleted';
					}
					//if ($results['lastupdate']) $lastSyncDate = $results['lastupdate'];
					if (count($rvalues) > 0) {
						$rstring = 'Result: '.implode(', ', $rvalues).' for connector '.$cname;
					} else {
						$rstring = 'No changes made for connector '.$cname;
					}
					$this->log->info($rstring);
				}
			}

			// if the connector returns a date, use it
			// TODO:last date should be when records have been READ!
			/*if ($lastSyncDate && $lastSyncDate instanceof DateTime) {
				$lastSyncDate = date('Y-m-d H:i:s', $lastSyncDate->getTimestamp() + 1 );
				$this->setLastSyncDate($user, $cname, $con->module, $lastSyncDate);
			} else {
				$this->setLastSyncDate($user, $cname, $con->module);
			}*/
			
		}

		return true;
	}


	public function synchronize() {
		$this->log->info('Entering '.__METHOD__);

		if ($this->simulate){
			$this->log->info(' --- Simulation mode active --- ');
		}

		$time0 = microtime(true);

		if (count($this->connectors) < 2) {
			return $this->log->fatal('Not enough connectors are enabled. Please enable at least 2.');
		}

		// TODO: check flag!
		$this->getUserMapping();

		// crmv@120777
		$conns = array();
		$mapCon = null;
		
		$connectorsOn = 0;
		$allMods = array();
		foreach ($this->connectors as $name=>$conn) {
			$con = $this->connInstances[$name] = new $conn['class']($conn['config'], $this->storage);
			$con->simulate = $this->simulate;

			$res = $con->connect();
			if (!$res) {
				$this->log->error('Failed connection for '.$name);
				continue;
			} else {
				$conns[] = $name;
				++$connectorsOn;
			}
			
			if ($conn['user_mapping']) {
				$mapCon = $name;
			}
			
			$allMods = array_merge($allMods, array_keys($conn['config']['modules']));
		}
		$allMods = array_unique($allMods);
		
		if ($connectorsOn <= 1) {
			return $this->log->fatal('Not enough connectors are available.');
		}
		
		// now prepare the list of matching connectors/modules
		$syncMods = array();
		foreach ($allMods as $module) {
			$in = array();
			$out = array();
			foreach ($this->connInstances as $con) {
				$cname = $con::$name;
				$config = $this->connectors[$cname]['config'];
				if (array_key_exists($module, $config['modules'])) {
					if (!$con->canHandleModule($module)) {
						$this->log->warning("Connector $cname can't synchronize module $module. Skipped!");
						continue;
					}
					if (isset($config['modules'][$module]['direction'])) {
						$dir = $config['modules'][$module]['direction'];
					} else {
						$dir = 'Both';
					}
					if ($dir == 'In') {
						$in[] = $cname;
					} elseif ($dir == 'Out') {
						$out[] = $cname;
					} elseif ($dir == 'Both') {
						$in[] = $cname;
						$out[] = $cname;
					} else {
						$this->log->warning("Unknown sync direction for connector $cname and module $module");
					}
					if ($config['modules'][$module]['picklist'] && !$con->supportMeta()) {
						$this->log->warning("Connector $cname doesn't support metadata, it won't be propagated");
					}

				}
			}
			// if in output there is only 1, remove from input that one, idem with input
			if (count($out) == 1) {
				$in = array_diff($in, $out);
			}
			if (count($in) == 1) {
				$out = array_diff($out, $in);
			}
			// check validity
			if (count($in) == 0) {
				$this->log->warning("No input connectors for module $module. Skipped!");
			} elseif (count($out) == 0) {
				$this->log->warning("No output connectors for module $module. Skipped!");
			} else {
				$syncMods[$module] = array('in' => $in, 'out' => $out);
			}
		}
		
		if (count($syncMods) == 0) {
			return $this->log->info('No modules to be syncronized');
		} else {
			$this->log->info('Modules to be syncronized: '.implode(', ', array_keys($syncMods)));
		}
		
		foreach ($syncMods as $module => $conns) {
			try {
				$r = $this->syncForModule($module, $conns);
				if (!$r) {
					$this->log->info('Sync failed for module '.$module);
				}
			} catch (Exception $e) {
				$this->log->error('Uncaught exception: '.print_r($e->getMessage(), true));
				$this->log->error("STACK TRACE \n".$e->getTraceAsString());
				$this->log->error('Skipping user and moving on to next one');
			}
		}
		
		$time1 = microtime(true);
		$delta_m = round(($time1 - $time0)/60);
		$delta_s = round($time1 - $time0)%60;

		$this->log->info("Synchronization finished in {$delta_m}m {$delta_s}s");
		

		// crmv@120777
// TODO! ---------- old code
return;
		/*if ($mapCon) {
			$con = $this->connInstances[$mapCon];
			if ($con && method_exists($con, 'getUserMapping')) {
				$this->userMapping = $con->getUserMapping($conns);
			} else {
				return $this->log->fatal('Method getUserMapping not defined for connector '.$name);
			}		
		} else {
			return $this->log->fatal('No connector defined for user mapping');
		}
		// crmv@120777e
		
		$countUsers = (is_array($this->userMapping) ? count($this->userMapping) : 0);
		$this->log->info("Found $countUsers users for sync");

		foreach ($this->userMapping as $user) {
			try {
				$ut0 = microtime(true);
				$r = $this->syncForUser($user);
				if (!$r) {
					$this->log->info('Sync failed for user '.$user['VTE']['username']);
				} else {
					$ut1 = microtime(true);
					$delta_m = round(($ut1 - $ut0)/60);
					$delta_s = round($ut1 - $ut0)%60;
					$this->log->info("Sync done in {$delta_m}m {$delta_s}s");
				}
			} catch (Exception $e) {
				$this->log->error('Uncaught exception: '.print_r($e->getMessage(), true));
				$this->log->error("STACK TRACE \n".$e->getTraceAsString());
				$this->log->error('Skipping user and moving on to next one');
			}
		}

		$time1 = microtime(true);
		$delta_m = round(($time1 - $time0)/60);
		$delta_s = round($time1 - $time0)%60;

		$this->log->info("Synchronization finished in {$delta_m}m {$delta_s}s");
		return true;*/
	}
	
	protected function syncForModule($module, $connectors) {
		$this->log->info('Synchronizing module '.$module);
		
		$ut0 = microtime(true);
		
		$recordsPulled = array();
		$lastDates = array();
		$bigTotal = 0;
		foreach ($connectors['out'] as $cname) {
			$con = $this->connInstances[$cname];
			
			// TODO: check if I need to have user mapped first!
			
			$lastDateVTE = $this->getLastSyncDate('admin', $cname, $module); // 
			if ($lastDateVTE == 'never') {
				// first sync, create array of dates
				$this->log->info("First syncronization for connector $cname and user admin");
				$lastDateTz = null;
			} else {
				$lastDateTz = \DateTime::createFromFormat('Y-m-d H:i:s', $lastDateVTE);
			}
			
			try {
				$recs = $con->pull($module, $conuser, $lastDateTz);
			} catch (Google_Exception $e) { // TODO: put in connector and handle generic exception
				if ($e->getCode() == 410 && strpos($e->getMessage(), 'The requested minimum modification time lies too far in the past.') !== false) {
					// TODO
					/*$this->log->warning('The last syncronization was too long ago. Resetting the last check timestamps for all connectors and recheck at the next cycle');
					foreach ($this->connInstances as $con2) {
						$cname2 = $con2::$name;
						$this->setLastSyncDate($user, $cname2, $module);
					}*/
					return true;
				} else {
					// throw again the exception
					throw $e;
				}
			}
			
			if ($recs !== false) {
				if (empty($recs['last_update'])) {
					$this->log->warning("Connector $cname didn't return the last_update date. Setting current time");
					$lastDates[$cname] = new \DateTime();
				} else { 
					$lastDates[$cname] = $recs['last_update'];
				}
				
				// filter records with unchanged etags
				$filtered = 0;
				if ($con->supportEtag()) {
					$filtered = $this->filterEtags($cname, $recs);
				}
				
				$recordsPulled[$cname] = $recs;
				$total = count($recs['created']) + count($recs['updated']) + count($recs['deleted']);
				$bigTotal += $total;
				$this->log->info("Connector $cname returned $total records");
				
				if ($total == 0) {
					// this connector didn't returned anything or evetything was filtered
					$lastSyncDate = date('Y-m-d H:i:s', $lastDates[$cname]->getTimestamp() + 1);
					$this->setLastSyncDate('admin', $cname, $module, $lastSyncDate);
				}
			}
		}
		//preprint($recordsPulled);
		
		// check if nothing has to be done
		if ($bigTotal == 0) {
			$this->log->debug('Nothing to do');
			return true;
		}
		
		// fill record with other connectors ids
		$r = $this->setRecordsIds($recordsPulled);
		if (!$r) return false;

		// create the list of changes to be pushed to servers
		$r = $this->syncRecords($module, $recordsPulled, $connectors);
		if (!$r) return false;
		
		// push metadata changes
		$r = $this->pushMetaChanges($module, $recordsPulled, $connectors);
		if (!$r) return $this->log->error('Unable to propagate meta changes');
		
		// push changes to servers
		$r = $this->pushChanges($module, $this->actionList, $connectors);
		if (!$r) return $this->log->error('Unable to save changes');

		// and set the date of last update
		foreach ($lastDates as $cname => $date) {
			$lastSyncDate = date('Y-m-d H:i:s', $date->getTimestamp() + 1 );
			$this->setLastSyncDate('admin', $cname, $module, $lastSyncDate);
		}
		
		// if it was the user module, update tha user mapping
		// TODO: check flag
		if ($module == 'Users') {	
			$this->getUserMapping();
		}
		
		$ut1 = microtime(true);
		$delta_m = round(($ut1 - $ut0)/60);
		$delta_s = round($ut1 - $ut0)%60;
		$this->log->info("Module synchronized in {$delta_m}m {$delta_s}s");
		
		return true;
	}

	public function selfTest() {
		$this->log->info('Entering '.__METHOD__);

		// TODO
		return true;
	}

}
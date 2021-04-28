<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@182114 */

namespace VteSyncLib\Connector;

use VteSyncLib\Connector\SalesForce\Client as SFClient;

class SalesForce extends BaseConnector {
	
	static public $name = 'SalesForce';
	
	protected $modulesHandled = array('Users', 'Leads', 'Accounts', 'Contacts', 'Potentials', 'Campaigns', 'HelpDesk', 'Products', 'Assets');
	
	protected $classes = array(
		'Users' => array('module' => 'User', 'commonClass' => 'VteSyncLib\Model\CommonUser', 'class' => 'VteSyncLib\Connector\SalesForce\Model\User'),
		'Leads' => array('module' => 'Lead', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Lead'),
		'Accounts' => array('module' => 'Account', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Account'),
		'Contacts' => array('module' => 'Contact', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Contact'),
		'Potentials' => array('module' => 'Opportunity', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Potential'),
		'Campaigns' => array('module' => 'Campaign', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Campaign'),
		'HelpDesk' => array('module' => 'Case', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\HelpDesk'),
		'Products' => array('module' => 'Product2', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Product'),
		'Assets' => array('module' => 'Asset', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Asset'),
		// metadata
		'Meta' => array('module' => 'Meta', 'commonClass' => 'VteSyncLib\Model\CommonMeta', 'class' => 'VteSyncLib\Connector\SalesForce\Model\Meta'),
	);
	
	protected $client = null;
	
	public function __construct($config = array(), $storage = null) {
		parent::__construct($config, $storage);
		
		$this->client = new SFClient($this->storage, $this->log);
		$this->client->setSyncId($this->getSyncID());
	}
	
	public function connect() {
		$this->log->debug('Entering '.__METHOD__);
		
		$atype = $this->config['auth_type'];
		if ($atype === 'oauth2') {
			return $this->connectOAuth();
		} else {
			return $this->log->error('Unknown auth type: '.$atype);
		}
	}
	
	protected function connectOAuth() {
		$syncid = $this->getSyncID();
		
		$authInfo = $this->storage->getOAuthInfo($syncid);
		$tokenInfo = $this->storage->getTokenInfo($syncid);
		
		$this->is_connected = (!empty($authInfo['client_id']) && !empty($authInfo['client_secret']) && !empty($tokenInfo['token']));
		
		if ($this->is_connected) {
			$oauthInfo = array_replace($authInfo, $tokenInfo);
			$this->client->setOAuthInfo($oauthInfo);
		} else {
			$this->client->setOAuthInfo(null);
		}
		return $this->is_connected;
	}
	
	public function isConnected() {
		return $this->is_connected;
	}
	
	public function pull($module, $userinfo, \DateTime $date = null, $maxEntries = 100) {
		$this->log->debug('Entering '.__METHOD__);
		
		if (!in_array($module, $this->modulesHandled)) {
			return $this->log->error('Module not supported: '.$module);
		}
		
		$sobject = $this->getLocalModule($module);
		$commonClass = $this->getCommonClass($module);
		
		$records = array(
			'created' => array(),
			'updated' => array(),
			'deleted' => array(),
		);
		
		//if ($this->config['modules'][$module]['picklist']) { // TODO check...
		if ($module != 'Users') {
			// retrieve metadata and extract picklist values
			$r = $this->client->doGetRequest("sobjects/$sobject/describe/");
			if ($r) {
				$metaClass = $this->getModelClass('Meta');

				$metaModel = $metaClass::fromRawData($r);
				if (empty($metaModel)) {
					$this->log->warning('Unable to convert object from raw data');
				} else {
					$class = $this->getModelClass($module);
					$metaModel->setRecordClass($class);
					$cmModel = $metaModel->toCommonMeta();
					$records['metadata'] = $cmModel;
				}
			} else {
				$this->log->warning("Unable to describe object, metadata won't be propagated");
			}
		}
		
		$now = new \DateTime();
		
		// only updated
		if (empty($date)) {
			// first sync, return all records
			// TODO: PERFORMANCE: retrieve only the required fields instead of the id + full records
			$params = array(
				'q' => "SELECT Id, LastModifiedDate FROM {$sobject} ORDER BY LastModifiedDate ASC LIMIT ".($maxEntries+1),
			);
			$r = $this->client->doGetRequest('query/', $params);
			if (!$r) return false;
			
			$cnt = 0;
			foreach ($r['records'] as $rinfo) {
				$id = $rinfo['Id'];
				$object = $this->getObject($module, $id);
				if ($object) {
					$records['created'][] = $object;
				}
				++$cnt;
				if ($cnt >= $maxEntries) break;
			}
			
			if ($cnt < $maxEntries) {
				// nothing returned
				$records['last_update'] = $now;
			} else {
				// take the last
				$last = end($records['created']);
				$records['last_update'] = $last->getModifiedTime();
			}
		
		} else {
		
			// incremental sync!
			$params = array(
				'start' => $date->format('c'),
				'end' => $now->format('c'),
			);
			$r = $this->client->doGetRequest("sobjects/$sobject/updated/", $params);
			if (!$r) {
				$err = $this->client->getLastError();
				$msg = $this->client->getLastErrorMessage();
				if ($err == 'INVALID_REPLICATION_DATE') {
					if (strpos($msg, 'before org replication enabled') !== false) {
						$this->log->warning('SF was activated early in the past, trying to find the first valid date');
						$replDate = $this->findActivationDate($sobject, $now);
						if (!$replDate) {
							$this->log->error('Unable to find activation date');
							return false;
						}
					} else {
						// try again, but with a more recent date (some changes might be missing!!)
						$this->log->warning('Moving the last sync date forward. Some changes might be missing!');
						$replDate = time()-3600*24*29;
					}
					$params['start'] = date('c', $replDate);
					$r = $this->client->doGetRequest("sobjects/$sobject/updated/", $params);
					if (!$r) return false;
				} else {
					return false;
				}
			}
			
			// TODO: if older than 30days -> full sync!
			
			foreach ($r['ids'] as $id) {
				$object = $this->getObject($module, $id);
				if ($object) {
					$ctime = $object->getCreatedTime();
					$mtime = $object->getModifiedTime();
					$t1 = $ctime->getTimestamp();
					$t2 = $mtime->getTimestamp();
					// if difference is less than 2 secs -> consider it as created
					if (abs($t2 - $t1) < 2.0) {
						$olist = 'created';
					} else {	
						$olist = 'updated';
					}
					$records[$olist][] = $object;
				}
			}
			if ($r['latestDateCovered']) $records['last_update'] = new \DateTime($r['latestDateCovered']);
			
			// now get deleted items!
			$r = $this->client->doGetRequest("sobjects/$sobject/deleted/", $params);
			if (!$r) return false;
			
			foreach ($r['deletedRecords'] as $rinfo) {
				$id = $rinfo['id'];
				$delDate = new \DateTime($rinfo['deletedDate']);
				//create void object
				$delRecord = new $commonClass(static::$name, $module, $id, array(), null, $delDate);
				$records['deleted'][] = $delRecord;
			}

			if ($r['latestDateCovered']) {
				// TODO: take the minimum between the 2
			}
		}

		return $records;
	}
	
	public function push($module, $userinfo, &$records) {
		$this->log->debug('Entering '.__METHOD__);
		
		$results = array(
			'deleted' => 0,
			'created' => 0,
			'updated' => 0,
		);

		$actionsFunc = array(
			'create' => 'createRecord',
			'update' => 'updateRecord',
			'delete' => 'deleteObject',
		);
		
		$delPermission = $this->config['modules'][$module]['delete'];

		foreach ($records as $type => &$list) {
			if ($delPermission === false && $type == 'delete') {
				$this->log->debug('Not deleting records for this module');
				continue;
			}
			$func = $actionsFunc[$type];
			if (is_array($list) && $func) {
				foreach ($list as &$rec) {
					$res = $this->$func($module, $userinfo, $rec);
					if (!empty($res['id'])) {
						if ($res['action'] == 'created') $rec->setId(static::$name, $res['id']);
						if (!empty($res['etag'])) {
							$rec->setEtag(static::$name, $res['etag']);
						} elseif ($res['action'] != 'deleted') {
							$this->log->warning("Etag not returned for record # ".$res['id']);
						}
						$results[$res['action']]++;
					}
				}
			}
		}

		return $results;
	}
	
	public function pushMeta($module, $metaDiff) {
		// TODO: not supported
		return true;
	}
	
	// find the activation date by bisection
	protected function findActivationDate($sobject, \DateTime $now) {
		$tolerance = 60; // 1 min tolerance
		
		$start = time()-3600*24*29;
		$end = $now->getTimestamp();
		
		$steps = 0;
		while ($end - $start > $tolerance) {
			$mid = floor(($start + $end) / 2);
			$params = array(
				'start' => date('c', $mid),
				'end' => date('c', $end),
			);
			$r = $this->client->doGetRequest("sobjects/$sobject/updated/", $params);
			usleep(100000); // wait 100ms
			if ($r) {
				$end = $mid;
			} else {
				$err = $this->client->getLastError();
				if ($err == 'INVALID_REPLICATION_DATE') {
					$start = $mid;
				} else {
					return false; // some other error
				}
			}
			++$steps;
		}
		
		$this->log->info("Installation date found in $steps steps");
		
		return $end + 300; // plus 5 min
	}
	
	public function getObject($module, $id) {
		
		$sobject = $this->getLocalModule($module);
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}
		
		$r = $this->client->doGetRequest("sobjects/$sobject/".$id);
		if (!$r) return false;
		
		// create the internal object
		$class = $this->getModelClass($module);

		$sfModel = $class::fromRawData($r);
		if (empty($sfModel)) {
			$this->log->warning('Unable to convert object from raw data');
			return false;
		}

		// convert to common object
		if ($module == 'Users') {
			$cModel = $sfModel->toCommonUser();
		} else {
			$cModel = $sfModel->toCommonRecord();
		}
		
		if (empty($cModel)) {
			$this->log->warning('Unable to convert object to common model');
			return false;
		}
		
		return $cModel;
	}
	
	public function setObject($module, $id, $object) {
		if ($id) {
			return $this->updateRecord($module, null, $object);
		} else {
			return $this->createRecord($module, null, $object);
		}
	}
	
	public function deleteObject($module, $id) {
	
		$sobject = $this->getLocalModule($module);
		
		if ($this->simulate) {
			$this->log->info('RECORD DELETED (ID: '.$id.')');
			return array('action' => 'deleted', 'id'=>$id);
		}
		
		$r = $this->client->doDeleteRequest("sobjects/$sobject/$id");
		if (!$r) {
			$this->log->error('Unable to delete SalesForce record # '.$id);
			return false;
		}
		
		$this->log->debug('Deleted SalesForce record with id '.$id);
		return array('action' => 'deleted', 'id'=>$id);
	}
	
	public function objectExists($module, $id) {
		$retid = $this->getObjectField($module, $id, 'Id');
		return ($retid && $retid == $id);
	}
	
	protected function getObjectFields($module, $id, $fields = array()) {
		if (!is_array($fields)) $fields = array($fields);
		
		$sobject = $this->getLocalModule($module);
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}
		
		$params = array('fields' => implode(',', $fields));
		$r = $this->client->doGetRequest("sobjects/$sobject/".$id, $params);
		if (!$r) return false;
		
		$fields = array_intersect_key($r, array_flip($fields));
		
		return $fields;
	}
	
	protected function getObjectField($module, $id, $field) {
		$data = $this->getObjectFields($module, $id, array($field));
		if (!$data) return false;
		return $data[$field];
	}
	
	protected function createRecord($module, $userinfo, $record) {
		if ($module == 'Users') throw new Exception('User creation not supported in SalesForce');
		
		$this->log->debug('Entering '.__METHOD__);
		
		$sobject = $this->getLocalModule($module);
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}
		
		// clear the id in case it was wrongly set in the mapping table
		$record->clearId(static::$name);
		
		$class = $this->getModelClass($module);
		
		$localRecord = $class::fromCommonRecord($record);
		$this->mergeDefaults($localRecord->getModule(), $localRecord);
		$this->mergeForcedFields($localRecord->getModule(), $localRecord); // crmv@190016
		$fields = $localRecord->toRawData('create');
		
		// remove null values
		$fields = array_filter($fields, function($var) { return !is_null($var); } );
		
		// TODO: generalize
		if (empty($fields['Birthdate'])) {
			unset($fields['Birthdate']);
		}
		
		if ($this->simulate) {
			$this->log->info('RECORD CREATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'created', 'id'=>100, 'etag' => '0000');
		}
		
		$r = $this->client->doPostRequest("sobjects/$sobject/", $fields);
		if (!$r) {
			// TODO: handle duplicate message:
			/*
			(
    [0] => Array
        (
            [message] => Use one of these records?
            [errorCode] => DUPLICATES_DETECTED
            [fields] => Array
                (
                )
        )
)
			*/
			return false;
			//return $this->log->error('Request failed: '.print_r($r, true));
		}
		
		$id = $r['id'];
		$ldate = $this->getObjectField($module, $id, 'LastModifiedDate');
		$fakedata = array('LastModifiedDate' => $ldate);
		$etag = $class::extractEtag($fakedata);
		
		$this->log->debug('Created SalesForce record with id '.$id);
		return array('action' => 'created', 'id'=>$id, 'etag' => $etag);
	}
	
	protected function updateRecord($module, $userinfo, $record) {
		if ($module == 'Users') throw new Exception('User update not supported in SalesForce');
		
		$this->log->debug('Entering '.__METHOD__);
		
		$exists = $this->objectExists($module, $record->getId(static::$name));
		if (!$exists) {
			return $this->createRecord($module, $userinfo, $record);
		}
		
		$sobject = $this->getLocalModule($module);
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}
		
		$class = $this->getModelClass($module);
		
		$localRecord = $class::fromCommonRecord($record);
		$id = $localRecord->getId();
		$this->mergeDefaults($localRecord->getModule(), $localRecord);
		$this->mergeForcedFields($localRecord->getModule(), $localRecord); // crmv@190016
		$fields = $localRecord->toRawData('update');
		
		// TODO: owner??

		if ($this->simulate) {
			$this->log->info('RECORD UPDATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'updated', 'id'=>$id, 'etag' => '0000');
		}

		$r = $this->client->doPatchRequest("sobjects/$sobject/".$id, $fields);
		if (!$r) return false;
		
		$ldate = $this->getObjectField($module, $id, 'LastModifiedDate');
		$fakedata = array('LastModifiedDate' => $ldate);
		$etag = $class::extractEtag($fakedata);

		$this->log->debug('Updated SalesForce record with id '.$id);
		return array('action' => 'updated', 'id'=>$id, 'etag' => $etag);
	}
	
	
}
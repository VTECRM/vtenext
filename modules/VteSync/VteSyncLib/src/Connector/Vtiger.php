<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@197423 */

namespace VteSyncLib\Connector;

use \VteSyncLib\VTEUtils;
use \VteSyncLib\Model\CommonUser;
use VteSyncLib\Connector\Vtiger\Client as VtigerClient;

class Vtiger extends BaseConnector {
	
	static public $name = 'Vtiger';
	
	protected $modulesHandled = array('Users', 'Contacts', 'Accounts', 'HelpDesk', 'Products','Services','Assets','Leads','Potentials','Vendors');
	
	protected $classes = array(
		'Users' => array('module' => 'Users', 'commonClass' => 'VteSyncLib\Model\CommonUser', 'class' => 'VteSyncLib\Connector\Vtiger\Model\User'),
		'Contacts' => array('module' => 'Contacts', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Contacts'),
		'Accounts' => array('module' => 'Accounts', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Accounts'),
		'HelpDesk' => array('module' => 'Cases', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\HelpDesk'),
		'Products' => array('module' => 'Products', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Products'),
		'Services' => array('module' => 'Services', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Services'),
		'Assets' => array('module' => 'Assets', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Assets'),
		'Leads' => array('module' => 'Leads', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Leads'),
		'Potentials' => array('module' => 'Potentials', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Potentials'),
		'Vendors' => array('module' => 'Vendors', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Vendors'),
		// metadata
		'Meta' => array('module' => 'Meta', 'commonClass' => 'VteSyncLib\Model\CommonMeta', 'class' => 'VteSyncLib\Connector\Vtiger\Model\Meta'),
	);
	
	protected $client = null;
	
	public function __construct($config = array(), $storage = null) {
		parent::__construct($config, $storage);
		
		$this->client = new VtigerClient($this->storage, $this->log);
		$this->client->setSyncId($this->getSyncID());
	}
	
	protected $is_connected = false;
	
	public function connect() {
		$this->log->debug('Entering '.__METHOD__);
		
		$syncid = $this->getSyncID();
		
		
		$atype = $this->config['auth_type'];
		if ($atype === 'oauth2') {
			//return $this->connectOAuth();
			// TODO
		} elseif ($atype === 'http') {
			return $this->connectHttp();
		} else {
			return $this->log->error('Unknown auth type: '.$atype);
		}
	}
	
	
	protected function connectHttp() {
		$syncid = $this->getSyncID();
		
		$authInfo = $this->storage->getAuthInfo($syncid);
			
		$this->is_connected = (!empty($authInfo['username']) && !empty($authInfo['password']));
			
		if ($this->is_connected) {
			
		}
		else{
			return $this->log->error('Login failed');
		}
		return $this->is_connected;
		
	}
	
	public function isConnected() {
		return $this->is_connected;
	}
	
	protected function get_web_page($url, $type, $key,$post,$data,$coding) {
		$options = array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => false,  // don't return headers
        CURLOPT_FOLLOWLOCATION => true,   // follow redirects
        CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
        CURLOPT_ENCODING       => "",     // handle compressed
        CURLOPT_USERAGENT      => "test", // name of client
        CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
		CURLOPT_CUSTOMREQUEST  => "{$type}",  // custom request method to use instead of "GET"  
		CURLOPT_POST		   =>	$post,
		CURLOPT_POSTFIELDS     => $data,
        CURLOPT_TIMEOUT        => 120,    // time-out on response
		CURLOPT_HTTPHEADER => array(
			"Content-type:" . $coding ,
			"Authorization: Basic " . $key
			),
		); 

		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$content  = curl_exec($ch);
		curl_close($ch);

		return $content;
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
		
		$syncid = $this->getSyncID();
		$authInfo = $this->storage->getAuthInfo($syncid);
		$auth = base64_encode($authInfo['username'] .":" . $authInfo['password']);
		$urlMain = $this->config['instance_url'];
		
		if($module != 'Users'){
			
			if($module == "HelpDesk")
			{
				$module = "Cases";
			}
			
			$paramsMeta = array(
				'elementType' => $module,
			);
			
			$rm = $this->client->doGetRequest($urlMain,"describe",$auth,$paramsMeta);
			
			$meta_res = $rm["result"]["fields"];
			
			if($module == "Cases")
			{
				$module = "HelpDesk";
			}
			
			if ($meta_res) {	
				$meta_res['module'] = $module;			 
				$metaClass = $this->getModelClass('Meta');
				$metaModel = $metaClass::fromRawData($meta_res);  
				
				if (empty($metaModel)) 
				{
					$this->log->warning('Unable to convert object from raw data');
				} 
				else {					
					$class = $this->getModelClass($module);	
					$metaModel->setRecordClass($class);					
					$cmModel = $metaModel->toCommonMeta();
								
					$records['metadata'] = $cmModel; 
				}				
			}else {
				$this->log->warning("Unable to describe object, metadata won't be propagated");
			}
			
			if($module == "HelpDesk")
			{
				$module = "Cases";
			}
		
			if($date == null)
			{
				$params = array(
				'modifiedTime' => 0,
				'elementType'=> $module,
				'syncType'=> 'application'
				);
			}
			else
			{
				$params = array(
				'modifiedTime' => $date->getTimestamp(),
				'elementType'=> $module,
				'syncType'=> 'application'
				);
			}

			$r = $this->client->doGetRequest($urlMain,"sync",$auth,$params);	
		}
		else{
			$r = $this->client->doGetRequest($urlMain,"me",$auth);		
		}
		
		if ($module == 'Users') {
			$res = array( $r['result'] );
		}
		else{
			$res = $r['result']['updated'] ;
		}
		
		if($module == "Cases")
		{
			$module = "HelpDesk";
		}
		
		if($res != null)
		{
			foreach($res as $rinfo){					
				$m = $this->getLocalModule($module);
				$class = $this->getModelClass($module);	
							
				$object = $class::fromRawData($rinfo);	
				
			
				// convert to common object
				if ($module == 'Users') {
					$cModel = $object->toCommonUser();
					
					$records['updated'][] = $cModel;
				} 
				else {
					$cModel = $object->toCommonRecord();
					
					$ctime = $cModel->getCreatedTime();
					$mtime = $cModel->getModifiedTime();
					$t1 = $ctime->getTimestamp();
					$t2 = $mtime->getTimestamp();
					if (abs($t2 - $t1) < 2) {
						$records['created'][] = $cModel;	 	
					}
                    else{
						$records['updated'][] = $cModel; 
					}	
				}            				 
			}
		}
		
		$records['last_update'] = new \DateTime();
		
	
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
			'delete' => 'deleteRecord',
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
	protected function findActivationDate($sobject, \DateTime $now) {	
	}
	
	public function getObject($module, $id) {
	}
	public function setObject($module, $id, $object) {}
	
	public function deleteObject($module, $id) {
		$this->log->debug('Entering '.__METHOD__);
		$id = $record->getId(static::$name);
	    return $this->deleteRecord($module, $id);
	}
	protected function getObjectFields($module, $id, $fields = array()) {}
	
	protected function getObjectField($module, $id, $field) {}
	

	
	public function objectExists($module, $id) {
		
		$sobject = $this->getLocalModule($module);
		
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}
		
		$syncid = $this->getSyncID();
		$authInfo = $this->storage->getAuthInfo($syncid);
		$auth = base64_encode($authInfo['username'] .":" . $authInfo['password']);
		
		$urlMain = $this->config['instance_url'];
		$params = array(
				'elementType'=> $module,
		);
		$rs = $this->client->doGetRequest($urlMain,"describe",$auth,$params);
			
		if (!$rs) {return false;}
		else{return true;}
	}
	
	protected function createRecord($module, $userinfo, $record) {	
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
		$wsowner = $localRecord->getOwner();
		$fields = $localRecord->toRawData('create');
		$urlMain = $this->config['instance_url'];
		 
		$syncid = $this->getSyncID();
		$authInfo = $this->storage->getAuthInfo($syncid);
		$auth = base64_encode($authInfo['username'] .":" . $authInfo['password']);
		$username = $authInfo['username'];
		$password = $authInfo['password'];
		 
		// remove null values
		$fields = array_filter($fields, function($var) { return !is_null($var); } );

		if ($this->simulate) {
			$this->log->info('RECORD CREATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'created', 'id'=>100, 'etag' => '0000');
		}
		
		 
		$user = $this->client->doGetRequest($urlMain,"me",$auth);
		 
		$fields['assigned_user_id'] = $user["result"]["id"];
		
		if($module == "Contacts")
		{
			$fields['contacttype'] = "Others";
		}
		
		if($module != 'HelpDesk')
		{
			$params = array("elementType"=> $module,"element"=>json_encode($fields));
		}
		else
		{
			$params = array("elementType"=> "Cases","element"=>json_encode($fields));
		}
		
		$rs = $this->client->doPostRequestCreate($username,$password,$urlMain,'create',$auth,$params);
		
		   
		if (!$rs) {
			return false;
		}
		
		$etag = $record->getEtag("VTE");
		$this->log->debug('Created record');
		return array('action' => 'created', 'id'=>$rs["result"]["id"], 'etag' => $etag);
		
		
	}
	
	protected function updateRecord($module, $userinfo, $record) {
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
		
		$urlMain = $this->config['instance_url'];
		
		$syncid = $this->getSyncID();
		$authInfo = $this->storage->getAuthInfo($syncid);
		$auth = base64_encode($authInfo['username'] .":" . $authInfo['password']);
		$username = $authInfo['username'];
		$password = $authInfo['password'];
		
		
		$id = $localRecord->getId();
		$this->mergeDefaults($localRecord->getModule(), $localRecord);
		$this->mergeForcedFields($localRecord->getModule(), $localRecord);
		$fields = $localRecord->toRawData('update');

		

		if ($this->simulate) {
			$this->log->info('RECORD UPDATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'updated', 'id'=>$id, 'etag' => '0000');
		}
		
		$fields["id"] = $id;
		$user = $this->client->doGetRequest($urlMain,"me",$auth);
		$fields['assigned_user_id'] = $user["result"]["id"];
		
		if($module == "Contacts")
		{
			$fields['contacttype'] = "Others";
		}
		
		$params = array("element"=> json_encode($fields));
		
		$rs = $this->client->doPostRequestUpdate($username,$password,$urlMain,'update',$auth,$params);
		
		
		if (!$rs) {
			return false;
		}
		
		$etag = $record->getEtag("VTE");
		$this->log->debug('updated record');
		return array('action' => 'updated', 'id'=>$id, 'etag' => $etag);
	}
	
	protected function deleteRecord($module, $userinfo, $record) {
		$sobject = $this->getLocalModule($module);
		$urlMain = $this->config['instance_url'];
		
		$syncid = $this->getSyncID();
		$authInfo = $this->storage->getAuthInfo($syncid);
		$auth = base64_encode($authInfo['username'] .":" . $authInfo['password']);
		$username = $authInfo['username'];
		$password = $authInfo['password'];
		
		$class = $this->getModelClass($module);			
		$localRecord = $class::fromCommonRecord($record);
		$id = $localRecord->getId();
		
		if ($this->simulate) {
			$this->log->info('RECORD DELETED (ID: '.$id.')');
			return array('action' => 'deleted', 'id'=>$id);
		}
		
		$params = array("id"=> $id);
		
		$rs = $this->client->doPostRequestDelete($username,$password,$urlMain,'delete',$auth,$params);
		
		
		if (!$rs) {
			$this->log->error('Unable to delete record # '.$id);
			return false;
		}
		
		return array('action' => 'deleted', 'id'=>$id);
	}

}
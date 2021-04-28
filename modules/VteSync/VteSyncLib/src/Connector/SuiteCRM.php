<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@196666 */
namespace VteSyncLib\Connector;

require __DIR__.'/../../../vendor/autoload.php';

use VteSyncLib\Connector\SuiteCRM\Client as SuiteClient;

class SuiteCRM extends BaseConnector {
	
	static public $name = 'SuiteCRM';
	
	protected $modulesHandled = array('Users', 'Leads', 'Accounts', 'Contacts', 'Potentials', 'Campaigns', 'HelpDesk', 'Products');

    	protected $classes = array(
		'Users' => array('module' => 'User', 'commonClass' => 'VteSyncLib\Model\CommonUser', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\User'),
		'Leads' => array('module' => 'Lead', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\Lead'),
		'Accounts' => array('module' => 'Account', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\Account'),
		'Contacts' => array('module' => 'Contact', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\Contact'),
		'Potentials' => array('module' => 'Opportunity', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\Potential'),
		'Campaigns' => array('module' => 'Campaign', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\Campaign'),
		'HelpDesk' => array('module' => 'Case', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\HelpDesk'),
		'Products' => array('module' => 'Product2', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\SuiteCRM\Model\Product'),
	);
	
	protected $client = null;
	
	public function __construct($config = array(), $storage = null) {
		parent::__construct($config, $storage);
		
		$this->client = new SuiteClient($this->storage, $this->log);
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
		}
		else{
			return $this->log->error('Login failed');
		}
		return $this->is_connected;
	}
	
	public function isConnected() {
		return $this->is_connected;
	}
	
	protected function get_web_page($url, $type, $token,$post,$data,$coding) {
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
			"Authorization: Bearer " . $token
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
		
		$client_id = $this->storage->getOAuthInfo($this->getSyncID())['client_id'];
		$client_secret = $this->storage->getOAuthInfo($this->getSyncID())['client_secret'];
		$refresh_token = $this->storage->getTokenInfo($this->getSyncID())['refresh_token'];
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];
		$urlMain = $this->storage->getTokenInfo($this->getSyncID())['instance_url'];
		
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
		$url = "module/" . $module;
		if($date == null)
		{
		$params = array(
			'page[size]' => 10000000,
		);
		}
		else
		{
		$timestampModify = $date->getTimestamp();
		$timeFiltr = gmdate(DATE_W3C, $timestampModify);
		
		$params = array(
			'page[size]' => 10000000,
			'filter[date_modified][GTE]'=> $timeFiltr,
			
		);
		}
		if($module == "Products")
		{	
			$url = "module/AOS_Products" ;
			$r = $this->client->doGetRequest($urlMain,$token,$url,$params);
			$meta_res = $r;
		}
		elseif($module == "HelpDesk"){
			$url = "module/Cases" ;
			$r = $this->client->doGetRequest($urlMain,$token,$url,$params);
			$meta_res = $r;
		}
		elseif($module == "Potentials"){
			$url = "module/Opportunities" ;
			$r = $this->client->doGetRequest($urlMain,$token,$url,$params);
			$meta_res = $r;
		}
		else{
			$r = $this->client->doGetRequest($urlMain,$token,$url,$params);
			$meta_res = $r;
		}
		
		
		
		
		
		$res = $r['data'];
		
		
		foreach($res as $rinfo){					
				$m = $this->getLocalModule($module);
				$class = $this->getModelClass($module);			
				$object = $class::fromRawData($rinfo);	
				
				// convert to common object
				if ($module == 'Users') {
					$cModel = $object->toCommonUser();
				} 
				else {
					$cModel = $object->toCommonRecord();
					
				
				}
					 $owner = $cModel->getOwner("SuiteCRM");
					 $cModel->setOwner("VTE",$owner);
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
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];
		$urlMain = $this->storage->getTokenInfo($this->getSyncID())['instance_url'];
		$sobject = $this->getLocalModule($module);
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}
		
		if($module == "Products")
		{	
			$module = "AOS_Products" ;
			
		}
		if($module == "HelpDesk"){
			$module = "Cases" ;
			
		}
		if($module == "Potentials"){
			$module = "Opportunities" ;
			
		}
		
		$url = "module/" . $module . "/" .$id;
		$rs = $this->client->doGetRequest($urlMain,$token,$url);
		
			
	
		if (!$rs) {return false;}
		else{return true;}
	}
	
	protected function createRecord($module, $userinfo, $record) {
		
		$this->log->debug('Entering '.__METHOD__);
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];    
		$urlMain = $this->storage->getTokenInfo($this->getSyncID())['instance_url'];
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
		 $fields['assigned_user_id'] = $wsowner;
		 
		 // remove null values
		 $fields = array_filter($fields, function($var) { return !is_null($var); } );

		 if ($this->simulate) {
			$this->log->info('RECORD CREATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'created', 'id'=>100, 'etag' => '0000');
		 }
		 
		if($module == "Products")
		{	
			$module = "AOS_Products" ;
			
		}
		if($module == "HelpDesk"){
			$module = "Cases" ;
			
		}
		if($module == "Potentials"){
			$module = "Opportunities" ;
			
		}
		
		$params = array("data" => array("type"=> $module,"attributes"=>$fields));
			
		$rs = $this->client->doPostRequest($urlMain,$token,'module',$params);
		
		   
		if (!$rs) {
			return false;
		}
		$etag = $record->getEtag("VTE");
		$this->log->debug('Created record');
		return array('action' => 'created', 'id'=>$rs["data"]["id"], 'etag' => $etag);
		
	}
	
	protected function updateRecord($module, $userinfo, $record) {
		$this->log->debug('Entering '.__METHOD__);
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];
		$urlMain = $this->storage->getTokenInfo($this->getSyncID())['instance_url'];
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
		$fields = $localRecord->toRawData('update');
				
		if ($this->simulate) {
			$this->log->info('RECORD UPDATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'updated', 'id'=>$id, 'etag' => '0000');
		}
		
		if($module == "Products")
		{	
			$module = "AOS_Products" ;
			
		}
		if($module == "HelpDesk"){
			$module = "Cases" ;
			
		}
		if($module == "Potentials"){
			$module = "Opportunities" ;
			
		}
		
		$fields = array("data" => array("type"=> $module,"id" => $id, "attributes"=>$fields));
		
		$rs = $this->client->doPatchRequest($urlMain,$token,"module", $fields);
		
		
		if (!$rs) {
			return false;
		}
		$etag = $record->getEtag("VTE");
		$this->log->debug('updated record');
		return array('action' => 'updated', 'id'=>$rs["data"]["id"], 'etag' => $etag);
		
	}
	
	public function deleteRecord($module, $userinfo, $record){
		
		$sobject = $this->getLocalModule($module);
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];
        $urlMain = $this->storage->getTokenInfo($this->getSyncID())['instance_url'];
		$class = $this->getModelClass($module);			
		$localRecord = $class::fromCommonRecord($record);
		$id = $localRecord->getId();
		if ($this->simulate) {
			$this->log->info('RECORD DELETED (ID: '.$id.')');
			return array('action' => 'deleted', 'id'=>$id);
		}
       	
		if($module == "Products")
		{	
			$module = "AOS_Products" ;
			
		}
		if($module == "HelpDesk"){
			$module = "Cases" ;
			
		}
		if($module == "Potentials"){
			$module = "Opportunities" ;
			
		}
		
		$url = 'module/' . $module . '/' . $id;
		$rs = $this->client->doDeleteRequest($urlMain,$token,$url);
	
		
	
		
		if (!$rs) {
			$this->log->error('Unable to delete record # '.$id);
			return false;
		}	
		return array('action' => 'deleted', 'id'=>$rs["data"]["id"]);
	}
	
	
	}
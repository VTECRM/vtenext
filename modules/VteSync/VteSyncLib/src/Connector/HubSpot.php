<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
//crmv@195073
namespace VteSyncLib\Connector;

require __DIR__.'/../../../vendor/autoload.php';

class HubSpot extends BaseConnector {
	
	static public $name = 'HubSpot';
	
	protected $modulesHandled = array('Users', 'Accounts', 'Contacts', 'Potentials','HelpDesk', 'Tasks', 'Targets','Targets_Contacts');

    	protected $classes = array(
		'Users' => array('module' => 'Owners', 'commonClass' => 'VteSyncLib\Model\CommonUser', 'class' => 'VteSyncLib\Connector\HubSpot\Model\User'),
		'Accounts' => array('module' => 'Companies', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\HubSpot\Model\Account'),
		'Contacts' => array('module' => 'Contacts', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\HubSpot\Model\Contact'),
		'Potentials' => array('module' => 'Deals', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\HubSpot\Model\Potential'),
		'HelpDesk' => array('module' => 'Tickets', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\HubSpot\Model\HelpDesk'),
		//'Calendar' => array('module' => 'Engagements', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\HubSpot\Model\Calendar'),
		'Targets' => array('module' => 'Lists', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\HubSpot\Model\Target'),
		'Targets_Contacts'=>array('module' => 'contacts', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\HubSpot\Model\Targets_Contacts'),
		// metadata
		'Meta' => array('module' => 'Meta', 'commonClass' => 'VteSyncLib\Model\CommonMeta', 'class' => 'VteSyncLib\Connector\HubSpot\Model\Meta'),
	);
	
	
	public function __construct($config = array(), $storage = null) {
		parent::__construct($config, $storage);
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
	protected function get_web_page($url, $type) {
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
        CURLOPT_TIMEOUT        => 120,    // time-out on response
		CURLOPT_HTTPHEADER => array(
			"Content-type: application/x-www-form-urlencoded;charset=utf-8"
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
		//$module = strtolower($module);
		/*
		$page = $this->storage->getLastSyncPage('admin', static::$name, $module);
		...	
		// --- get the $page
		$this->storage->setLastSyncPage('admin', static::$name, $module, 'NEWPAGE');
		*/
		
		$get_expiration = $this->get_web_page("https://api.hubapi.com/oauth/v1/access-tokens/".$token , 'GET');
		$expiration_time = array();
		$expiration_time = json_decode($get_expiration);
		
		if(strpos($expiration_time->message, 'token is expired') !== false){
			$get_new_token= $this->get_web_page("https://api.hubapi.com/oauth/v1/token?grant_type=refresh_token&client_id=".$client_id."&client_secret=".$client_secret."&refresh_token=".$refresh_token , 'POST');
			$newToken = array();
			$newToken = json_decode($get_new_token, true);
			$token = $newToken['access_token'];
			if ($newToken['access_token']) {
				$this->storage->setTokenInfo($this->getSyncID(), $newToken);
				$this->oauthInfo['token'] = $newToken['access_token'];
				}
		}
		$hubspot = \SevenShores\Hubspot\Factory::createWithOAuth2Token($token);	
					
		if($module !== 'Users' and $module !== 'Targets'){
	      $r = $hubspot->objectProperties($sobject)->all();
		  $meta_res = json_decode(json_encode($r), true);			  
		}			
		
		if($sobject == 'Deals' || $sobject == 'Tickets'){
		 $p = $hubspot->CrmPipelines()->all($sobject); 
		 $pipe_res = json_decode(json_encode($p), true);    	 
		 // search the right field
		 foreach ($meta_res['data'] as $k=>$field) {
			if ($field['name'] == 'dealstage') {
				$meta_res['data'][$k]['options'] = $pipe_res['data']['results'][0]['stages'];
				break;
			}
			if ($field['name'] == 'hs_pipeline_stage') {
				$meta_res['data'][$k]['options'] = $pipe_res['data']['results'][0]['stages'];
				break;
			}
		 }
		}			
		//if ($this->config['modules'][$module]['picklist']) { // TODO check...
		if ($module !== 'Users' and $module !== 'Targets' and $module !== 'Targets_Contacts') {				
		// retrieve metadata and extract picklist values											
		if ($meta_res) {			
			 $meta_res['module']= $module;			 
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
		} else {
			$this->log->warning("Unable to describe object, metadata won't be propagated");
		}
		}	
		//default values for most endpoints
		$properties['count'] = 100;
		$offset = $this->storage->getLastSyncPage('admin', static::$name, $module);
		if(!is_null($offset) and $offset > 0){
		   $properties['offset'] = $offset;
		   if($module == 'Contacts'){
					$properties['vidOffset'] = $offset; 
		   }  
		}
		
		if($module == 'Users'){
			$response = $hubspot->{strtolower($sobject)}()->all();			
		}
		elseif($module == 'HelpDesk'){
			$properties['properties'] = array_column($meta_res['data'], 'name');
			$response = $hubspot->{strtolower($sobject)}()->all($properties);								
		}
		elseif($module == 'Targets'){			
				$response = $hubspot->ContactLists()->all((array)$porperties);				
				$r = json_decode(json_encode($response), true);
				$rs = $r['data']['lists'];   			
				$this->targets = $rs;									
		}
		else
		{
			if(empty($date) || $date < date('Y-m-d', strtotime('-28 days')))
			{			
				if($module == 'Targets_Contacts')
					{
							$class = $this->getModelClass($module);					
							foreach($this->targets as $data)
							{
								$response = $hubspot->ContactLists()->contacts($data['listId']);
								$r = json_decode(json_encode($response), true);	
								if($r['has-more'] == 'true'){
									//adding offset value do database
									$this->storage->setLastSyncPage('admin', static::$name, $module, $r['vid-offset']);							
								}
								else{
									$this->storage->setLastSyncPage('admin', static::$name, $module, 0);
								}
								$res = $r['data']['contacts'];                    					
								foreach($res as $target_con){
									$target_con['listId'] = $data['listId']; 					
									$object = $class::fromRawData($target_con);	                 					
									// convert to common object
									$cModel = $object->toCommonRecord();
									$records['created'][] = $cModel;
									
								}			 							 		
							}
							$records['last_update'] = new \DateTime();	
							return   $records;			
					}
			        else
					{
						if($module == 'Contacts')
						{
							$properties['property'] = array_column($meta_res['data'], 'name');					
							$response = $hubspot->{strtolower($sobject)}()->recent($properties);																																	
						}
						else
						{
							$result = $date->format('Y-m-d H:i:s.u');
							$stamp = strtotime($result); // get unix timestamp
							$since= $stamp*1000;
							$properties['since'] = $since;
							$properties['properties'] = array_column($meta_res['data'], 'name');
							$response = $hubspot->{strtolower($sobject)}()->getRecentlyModified($properties);	
						}																		
					}			
			}			
			else 
		    {						
				if($module == 'Targets_Contacts')
					{
						$class = $this->getModelClass($module);					
						foreach($this->targets as $data){
							$response = $hubspot->ContactLists()->recentContacts($data['listId']);
							$r = json_decode(json_encode($response), true);	
							if($r['has-more'] == 'true'){
								//adding offset value do database
								$this->storage->setLastSyncPage('admin', static::$name, $module, $r['vid-offset']);							
							}
							else{
								$this->storage->setLastSyncPage('admin', static::$name, $module, 0);
							}
							$res = $r['data']['contacts'];                    					
							foreach($res as $target_con){
								$target_con['listId'] = $data['listId']; 					
								$object = $class::fromRawData($target_con);	                 					
								// convert to common object
								$cModel = $object->toCommonRecord();
								$records['created'][] = $cModel;
								
							}			 							 		
						}
						$records['last_update'] = new \DateTime();	
						return   $records;			
					}
				else
					{
						if($module == 'Contacts'){
							$properties['property'] = array_column($meta_res['data'], 'name');					
							$response = $hubspot->{strtolower($sobject)}()->all($properties);																																	
						}
						else{				
						$properties['properties'] = array_column($meta_res['data'], 'name');
						$response = $hubspot->{strtolower($sobject)}()->all($properties);	
						}								
					}		
			}
		}
		//converting response to php array
		$r = json_decode(json_encode($response), true);			
		//checking if there is more data to sync and setting pagination
        if($r['has-more'] == 'true'){
			//adding offset value do database
			if($module == 'Contacts'){
				 $this->storage->setLastSyncPage('admin', static::$name, $module, $r['vid-offset']);					
			}
			else{
				$this->storage->setLastSyncPage('admin', static::$name, $module, $r['offset']);	
			}						
		 }			
		 elseif($r['hasMore'] == 'true'){
			//adding offset value do database
			$this->storage->setLastSyncPage('admin', static::$name, $module, $r['offset']);										
		 }
		 else{
			$this->storage->setLastSyncPage('admin', static::$name, $module, 0);
		 }	
			
		if($module == 'Users'){
            $res = $r['data'];
		}
		elseif($module == 'HelpDesk'){
			$res = $r['data']['objects'];
		}
		else{
			$res = $r['data'][strtolower($sobject)];
		}		
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
		// I have to connect with the user otherwise the timezone will be wrong
		/*if (!$this->userConnect($userinfo)) {
			return $this->log->error('Unable to connect to VTE with user '.$userinfo['username']);
		}*/

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
	
	public function pushMeta($module,$changes) {
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
	
	public function deleteRecord($module, $userinfo, $record){		
		$sobject = $this->getLocalModule($module);
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];
        
		$class = $this->getModelClass($module);			
		$localRecord = $class::fromCommonRecord($record);
		$id = $localRecord->getId();
		if ($this->simulate) {
			$this->log->info('RECORD DELETED (ID: '.$id.')');
			return array('action' => 'deleted', 'id'=>$id);
		}
        $hubspot = \SevenShores\Hubspot\Factory::createWithOAuth2Token($token); 
		if($module == 'Targets'){			
				$response = $hubspot->ContactLists()->delete($id);				
				$rs = json_decode(json_encode($response), true); 
                $rs = $rs['data'];				
			}
		else{
		$response = $hubspot->{strtolower($sobject)}()->delete($id);
		$rs = $response[strtolower($sobject)];
		}
		if (!$rs) {
			$this->log->error('Unable to delete record # '.$id);
			return false;
		}	
		return $rs;
	}
	
	public function objectExists($module, $id) {
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];
		$sobject = $this->getLocalModule($module);
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}
		$hubspot = \SevenShores\Hubspot\Factory::createWithOAuth2Token($token); 
        if($module == 'Targets'){
			$response = $hubspot->ContactLists()->getById($id);
			$rs = json_decode(json_encode($response), true);				
		}
		else{
			$response = $hubspot->{strtolower($sobject)}()->getById($id);	
			$rs = json_decode(json_encode($response), true);
		}
		if (!$rs) {return false;}
		else{return true;}    
	}
	
	protected function getObjectFields($module, $id, $fields = array()) {}
	
	protected function getObjectField($module, $id, $field) {}
	
	protected function createRecord($module, $userinfo, $record) {
		$this->log->debug('Entering '.__METHOD__);
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];    
		$sobject = $this->getLocalModule($module);
		if (empty($sobject)) {
			return $this->log->error('Unknown module: '.$module);
		}	
		// clear the id in case it was wrongly set in the mapping table
		 $record->clearId(static::$name);
		 $class = $this->getModelClass($module);
		 $localRecord = $class::fromCommonRecord($record);	
		 $this->mergeDefaults($localRecord->getModule(), $localRecord);
		 $fields = $localRecord->toRawData('create');
		 // remove null values
		 $fields = array_filter($fields, function($var) { return !is_null($var); } );

		 if ($this->simulate) {
			$this->log->info('RECORD CREATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'created', 'id'=>100, 'etag' => '0000');
		 }	
		 if($sobject == 'Contacts'){
			 $properties = [];
			foreach ($fields as $key => $value) {
				$properties[] = [
				'property' => $key,
				'value' => $value,
		        ];
		    }
		 }
		 if($sobject == 'Deals'){	 
		  //replace  fields array with correct values
		  $dealtype_replace = array('dealtype'=>strtolower(str_replace(" ", "",$fields['dealtype'])));
		  $dealstage_replace = array('dealstage'=>strtolower(str_replace(" ", "",$fields['dealstage'])));
		  $date_replace = array('closedate'=>strtotime($fields['closedate'])); 
		  $new_fields = array_replace($fields, $date_replace,$dealstage_replace,$dealtype_replace);	 	  
		  //unset associatedCompanyIds value from properties array
		  unset($new_fields["associatedCompanyIds"]); 
		 
		  foreach ($new_fields as $key => $value){		              
			$options[] = array(	
			    'name' => $key,
			    'value' => $value);		         			
			}
		  //add associations do properties array
		  $accos_field = $fields['associatedCompanyIds'];	
		  $associations = array('associatedCompanyIds'=>[$accos_field]);  
		  $properties = array('properties'=> $options,'associations'=>$associations); 
		  }
			if($sobject == 'Tickets'){	
					if($fields['hs_ticket_priority'] = 'Normal'){
						$fields['hs_ticket_priority'] = 'Medium';
					}
					$new_ticket_prio = strtoupper($fields['hs_ticket_priority']);				
					$fields['hs_ticket_priority'] = $new_ticket_prio;														    
					if($fields['hs_pipeline_stage'] == 'new'){
						$fields['hs_pipeline_stage'] = '1';
					}
					if($fields['hs_pipeline_stage'] == 'waiting on contact'){
						$fields['hs_pipeline_stage'] = '2';
					}
					if($fields['hs_pipeline_stage'] == 'waiting on us'){
						$fields['hs_pipeline_stage'] = '3';
					}
					if($fields['hs_pipeline_stage'] == 'Closed'){
						$fields['hs_pipeline_stage'] = '4';
					}	
					unset($new_fields["hs_num_associated_companies"]); 
					foreach ($fields as $key => $value) {		 
						$properties[] = [
							'name' => $key,
							'value' => $value,
						];
				}
			}	
            if($module == 'Accounts'){
               $properties = [];			
					foreach ($fields as $key => $value) {		 
						$properties[] = [
							'name' => $key,
							'value' => $value,
				];
			}
		}		
		   $hubspot = \SevenShores\Hubspot\Factory::createWithOAuth2Token($token); 
			if($module == 'Targets'){			
				$response = $hubspot->ContactLists()->create($fields);				
				$res = json_decode(json_encode($response), true);							
				$rs = $res['data'];				
			}
			elseif($module == 'Targets_Contacts'){
                 $target_id = $localRecord->getFields()['listId'];
				 $contact_ids = $localRecord->getFields()['vid'];	
				 $response = $hubspot->ContactLists()->addContact($target_id,(array)$contact_ids);  								 
				 $res = json_decode(json_encode($response), true);							
				 $rs = $res['data'];		 
			}
			elseif($module == 'Contacts'){	
				$response = $hubspot->{strtolower($sobject)}()->create($properties);		
				$rs = $response[strtolower($sobject)];
			}
			else{	
				$response = $hubspot->{strtolower($sobject)}()->create($properties);		
				$rs = $response[strtolower($sobject)];
			}
			if (!$rs) {
				return false;
			}
			
		$this->log->debug('Created record');
		return $rs;
	}
	
	protected function updateRecord($module, $userinfo, $record) {
		$this->log->debug('Entering '.__METHOD__);
		$token = $this->storage->getTokenInfo($this->getSyncID())['token'];
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
	 
		if($module == 'Contacts'){
			$properties = [];
			foreach ($fields as $key => $value) {
				$properties[] = [
				'property' => $key,
				'value' => $value,
				];
		}
		}
		if($sobject == 'Deals'){			 
		  //replace  fields array with correct values
		  $dealtype_replace = array('dealtype'=>strtolower(str_replace(" ", "",$fields['dealtype'])));
		  $dealstage_replace = array('dealstage'=>strtolower(str_replace(" ", "",$fields['dealstage'])));
		  $date_replace = array('closedate'=>strtotime($fields['closedate'])); 
		  $new_fields = array_replace($fields, $date_replace,$dealstage_replace,$dealtype_replace);	 	  
		  //unset associatedCompanyIds value from properties array
		  unset($new_fields["associatedCompanyIds"]); 
		 
		  foreach ($new_fields as $key => $value){		              
			$options[] = array(	
			    'name' => $key,
			    'value' => $value);		         			
			}
		  //add associations do properties array
		  $accos_field = $fields['associatedCompanyIds'];	
		  $associations = array('associatedCompanyIds'=>[$accos_field]);  
		  $properties = array('properties'=> $options,'associations'=>$associations); 
		}
		if($sobject == 'Tickets'){	
					if($fields['hs_ticket_priority'] = 'Normal'){
						$fields['hs_ticket_priority'] = 'Medium';
					}
					$new_ticket_prio = strtoupper($fields['hs_ticket_priority']);
					$fields['hs_ticket_priority'] = $new_ticket_prio;						
					if($fields['hs_pipeline_stage'] == 'new'){
						$fields['hs_pipeline_stage'] = '1';
					}
					if($fields['hs_pipeline_stage'] == 'waiting on contact'){
						$fields['hs_pipeline_stage'] = '2';
					}
					if($fields['hs_pipeline_stage'] == 'waiting on us'){
						$fields['hs_pipeline_stage'] = '3';
					}
					if($fields['hs_pipeline_stage'] == 'Closed'){
						$fields['hs_pipeline_stage'] = '4';
					}				
					foreach ($fields as $key => $value) {		 
							$properties[] = [
								'name' => $key,
								'value' => $value,
							];
			}
		}
		if($module == 'Accounts'){
		$properties = [];
		foreach ($fields as $key => $value) {		 
				$properties[] = [
				'name' => $key,
				'value' => $value,
		 ];
		}}
		$hubspot = \SevenShores\Hubspot\Factory::createWithOAuth2Token($token); 
		if($module == 'Targets'){			
				$response = $hubspot->ContactLists()->update($id,$fields);					
				$rs = json_decode(json_encode($response), true);				
		}
		else{
		$response = $hubspot->{strtolower($sobject)}()->update($id,$properties);
		$rs = $response[strtolower($sobject)];
		}
		if (!$rs) {
			return false;
		}
			
		$this->log->debug('updated record');
		return $rs;
	}
	}
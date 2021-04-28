<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@182114 crmv@190016 crmv@195073 */

namespace VteSyncLib\Connector;

use \VteSyncLib\VTEUtils;

use \VteSyncLib\Model\CommonUser;
use \VteSyncLib\Connector\VTE\LocalWSClient;
use \VteSyncLib\Connector\VTE\Model\User as VTEUser;

class VTE extends BaseConnector {
	
	static public $name = 'VTE';
	
	protected $modulesHandled = array(
		'Users', 'Leads', 'Accounts', 'Contacts', 'Vendors', // crmv@197423
		'Potentials', 'Campaigns', 'HelpDesk', 'Products', 'Services', 'Assets', 'ProjectPlan', 'ProjectTask','TicketComments', // crmv@190016 crmv@197423
		'Targets', 'Targets_Contacts',
	); 
	
	protected $classes = array(
		'Users' => array('module' => 'Users', 'commonClass' => 'VteSyncLib\Model\CommonUser', 'class' => 'VteSyncLib\Connector\VTE\Model\User'),
		'Leads' => array('module' => 'Leads', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Lead'),
		'Accounts' => array('module' => 'Accounts', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Account'),
		'Contacts' => array('module' => 'Contacts', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Contact'),
		'Potentials' => array('module' => 'Potentials', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Potential'),
		'Campaigns' => array('module' => 'Campaigns', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Campaign'),
		'HelpDesk' => array('module' => 'HelpDesk', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\HelpDesk'),
		'Products' => array('module' => 'Products', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Product'),
		'Assets' => array('module' => 'Assets', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Asset'),
		'ProjectPlan' => array('module' => 'ProjectPlan', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Project'), // crmv@190016
		'ProjectTask' => array('module' => 'ProjectTask', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\ProjectTask'), // crmv@190016
		'TicketComments' => array('module' => 'TicketComments', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\TicketComment'), // crmv@190016
		'Targets' => array('module' => 'Targets', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Target'),
		'Targets_Contacts'=>array('module' => 'Contacts', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Targets_Contacts'),
		'Services'=>array('module' => 'Services', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Services'), // crmv@197423
		'Vendors'=>array('module' => 'Vendors', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\VTE\Model\Vendors'), // crmv@197423
		//'Tasks' => array('module' => 'Calendar'),
		// metadata
		'Meta' => array('module' => 'Meta', 'commonClass' => 'VteSyncLib\Model\CommonMeta', 'class' => 'VteSyncLib\Connector\VTE\Model\Meta'),
	);
	
	protected $vtws = null;
	protected $is_connected = false;
	
	// loaded dynamically
	protected $ws_prefixes = array();
	
	/*public function __construct($config = array()) {
		parent::__construct($config);
	}*/
	
	public function connect($force = false) {
		$this->log->debug('Entering '.__METHOD__);
		
		$atype = $this->config['auth_type'];
		if ($atype === 'local') {
			return $this->connectVTE();
		} elseif ($atype === 'webservice') {
			return $this->connectWS($force);
		} else {
			return $this->log->error('Unknown auth type: '.$atype);
		}
	}
	
	protected function connectVTE() {
		$this->vtws = new LocalWSClient($this->config['vte_path']);
		
		$adminId = \Users::getActiveAdminId();
		$res = $this->vtws->doLogin($adminId);
		if (!$res) {
			$this->is_connected = false;

			$l = $this->vtws->lastError();
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error('Login failed: '.$l);
		}
		
		$this->is_connected = true;
		$this->log->debug('VTE included succesfully!');
		
		return true;
	}
	
	protected function connectWS($force = false) {
		
		if (!$force && $this->isConnected()) return true;
		
		require_once dirname(__FILE__).'/VTE/vtwsclib/WSClient.php'; // crmv@190016
		$this->vtws = new Vte_WSClient($this->config['vte_url']);////crmv@207871

		$res = $this->vtws->doLogin($this->config['vte_admin_user'], $this->config['vte_admin_accesskey']);
		if (!$res) {
			$this->is_connected = false;

			$l = $this->vtws->lastError();
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error('Login failed: '.$l);
		}

		$this->is_connected = true;
		$this->log->debug('Login succesful!');
		
		return true;
	}
	
	public function isConnected() {
		return $this->is_connected;
	}
	
	public function pull($module, $userinfo, \DateTime $date = null, $maxEntries = 100) {
		
		if ($module == 'Users') {
			return $this->pullUsers();
		} elseif ($module == 'Targets_Contacts') {
			return $this->pullRelation($module);
		}
		
		$this->log->debug('Entering '.__METHOD__);

		$vteModule = $this->getLocalModule($module);
		
		if (empty($date)) {
			$deleted = 'false';
		} else {
			$deleted = $this->config['modules'][$module]['delete'] ? 'true':'false';
		}
	
		$params = array(
			'module'=>$module,
			'maxEntries'=>$maxEntries,
			//'delegatedUser'=>$userinfo['username'],
			'showDeleted' => $deleted
		);
		
		// for calendar
		/*
		if ($this->firstSync) {
			$params['dateField'] = 'time_start';
			$params['showDeleted'] = 'false';
			$params['maxEntries'] = 1000;
		}*/
		
		
		$now = new \DateTime();
		
		// only updated
		if (empty($date)) {
			$params['maxEntries'] = $maxEntries;
		} else {
			$params['dateFrom'] = $date->format('c');
			$params['dateTo'] = $now->format('c');
		}

		// connect again to use admin user
		/*if (!$this->connect(true)) {
			return $this->log->fatal("Can't connect to VTE");
		}*/
		
		$res = $this->vtws->doInvoke('vtesync.get_changes', $params, 'GET');
		
		$l = $this->vtws->lastError();
		if (!empty($l)) {
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error('Unable to get last changes from VTE : '.$l);
		}

		if (empty($date)) {
			// discard deletion and consider all creations
			$res['created'] = array_merge($res['created'], $res['updated']);
			$res['updated'] = array();
			$res['deleted'] = array();
		}
		
		
		/*if ($res['local_timezone']) $this->vte_timezone = $res['local_timezone'];
		if ($res['user_timezone']) $this->user_timezone = $res['user_timezone'];
		if ($userinfo['date_format']) $this->user_date_format = $userinfo['date_format'];

		if (empty($this->vte_timezone)) return $this->log->error('Unable to retrieve VTE timezone');
		*/
		
		if ($module != "Users" && $module != 'Targets' && $module != 'Targets_Contacts') {
			// retrieve metadata and extract picklist values
			$mres = $this->vtws->doDescribe($vteModule);
			$l = $this->vtws->lastError();
			if (!empty($l)) {
				if (is_array($l)) $l = implode("\n", $l);
				$this->log->warning("Unable to describe object, metadata won't be propagated: ".$l);
			} else {
				$metaClass = $this->getModelClass('Meta');
				
				$metaModel = $metaClass::fromRawData($mres);
				if (empty($metaModel)) {
					$this->log->warning('Unable to convert object from raw data');
				} else {
					$class = $this->getModelClass($module);
					$metaModel->setRecordClass($class);
					$cmModel = $metaModel->toCommonMeta();
					$res['metadata'] = $cmModel;
				}
			}
		}
		
		$class = $this->getModelClass($module);
		
		$ids = array();
		
		
		// crmv@67836 - check for duplicates (in case of reminder + recurring)
		foreach ($res['created'] as $k => &$record) {
			if (in_array($record['id'], $ids)) { unset($res['created'][$k]); continue; }	// it is a duplicate
			
			$ids[] = $record['id'];
			$vteModel = $class::fromRawData($record);
			if (empty($vteModel)) {
				$this->log->warning('Unable to convert object from raw data, record skipped');
				unset($res['created'][$k]);
				continue;
			}
			$record = $vteModel->toCommonRecord();
		}
		foreach ($res['updated'] as $k => &$record) {
			if (in_array($record['id'], $ids)) { unset($res['updated'][$k]); continue; }	// it is a duplicate
			
			$ids[] = $record['id'];
			
			if($module == "HelpDesk")
			{
				if($record['id'] != null)
				{
				$id = explode('x',$record['id']);
				$idTicket = $id[1];
				}
				$_SESSION["idTicket"] = $idTicket;
			}
		 
			$vteModel = $class::fromRawData($record);
			if (empty($vteModel)) {
				$this->log->warning('Unable to convert object from raw data, record skipped');
				unset($res['updated'][$k]);
				continue;
			}
			$record = $vteModel->toCommonRecord();
		}
		foreach ($res['deleted'] as $k => &$record) {
			if (in_array($record['id'], $ids)) { unset($res['deleted'][$k]); continue; }	// it is a duplicate
			$ids[] = $record['id'];
			$vteModel = $class::fromRawData($record); // crmv@190016
			if (empty($vteModel)) {
				$this->log->warning('Unable to convert object from raw data, record skipped');
				unset($res['deleted'][$k]);
				continue;
			}
			$record = $vteModel->toCommonRecord();
		}
		// crmv@67836e
		
		if($module == "TicketComments")
		{
			global $adb;
			$ticketid = $_SESSION["idTicket"];
		    $resComments = $adb->pquery("select * from vte_ticketcomments WHERE ticketid = ?", array($ticketid));
		  
			while($row = $adb->fetchByAssoc($resComments)){
				$row["ticketid"] = $this->getWSId('HelpDesk', $row["ticketid"]);
				$commentVte[] = $row;
			}
		   
			$res['created'] = $commentVte;
			unset($commentVte);
			unset($_SESSION["idTicket"]);
			
			if($res['created'] != null)
			{
				foreach ($res['created'] as $k => &$record) {
				
					if (in_array($record['commentid'], $ids)) { unset($res['created'][$k]); continue; }	// it is a duplicate
			
					$ids[] = $record['commentid'];
			
					$vteModel = $class::fromRawData($record);
			
					if (empty($vteModel)) {
						$this->log->warning('Unable to convert object from raw data, record skipped');
						unset($res['created'][$k]);
						continue;
					}
					$record = $vteModel->toCommonRecord();
				}
			}
			else{
				$res['created'] = array();
			}
			
			
			foreach ($res['updated'] as $k => &$record) {
				if (in_array($record['id'], $ids)) { unset($res['updated'][$k]); continue; }	// it is a duplicate
				$ids[] = $record['id'];
			
				$vteModel = $class::fromRawData($record);
			
				if (empty($vteModel)) {
					$this->log->warning('Unable to convert object from raw data, record skipped');
					unset($res['updated'][$k]);
					continue;
				}
				$record = $vteModel->toCommonRecord();
		
			}
		   
		   
			foreach ($res['deleted'] as $k => &$record) {
				if (in_array($record['id'], $ids)) { unset($res['deleted'][$k]); continue; }	// it is a duplicate
				$ids[] = $record['id'];
				$vteModel = $class::fromRawData($record); 
				if (empty($vteModel)) {
					$this->log->warning('Unable to convert object from raw data, record skipped');
					unset($res['deleted'][$k]);
					continue;
				}
				$record = $vteModel->toCommonRecord();
			}
		}
	
		if ($res['last_update']) {
			$res['last_update'] = new \DateTime('@'.$res['last_update']);
		}
	
		return $res;
	}
	
	protected function pullUsers() {
		$this->log->debug('Entering '.__METHOD__);
		
		return $this->log->error("Reading users from VTE is not supported");
		
		return array();
	}
	
	//crmv@195073
	protected function pullRelation($module) {
	
		$this->log->debug('Entering '.__METHOD__);
		
		if ($module == 'Targets_Contacts') {
			$targ_cont = array();
			$targ_cont['created'] = array();
			$targ_cont['updated'] = array();
			$targ_cont['deleted'] = array();
			
			$params = [
				'firstModule' => 'Targets',
				'secondModule' => 'Contacts',
			];
			$res = $this->vtws->doInvoke('vtesync.get_all_related_ids', $params, 'GET');
			$l = $this->vtws->lastError();
			if (!empty($l)) {
				if (is_array($l)) $l = implode("\n", $l);
				return $this->log->error('Unable to get last changes from VTE : '.$l);
			}
			
			$class = $this->getModelClass($module);
			foreach ($res['records'] as $targetid => $contactlist) {
				foreach ($contactlist as $contactid) {
					$vteModel = $class::fromRawData([
						'targetid' => $targetid,
						'contactid' => $contactid
					]);
					$record = $vteModel->toCommonRecord();
					$targ_cont['updated'][] = $record;
				}
			}
			
			return $targ_cont;
		} else {
			return $this->log->error("Unknown relation: ".$module);
		}

	}
	//crmv@195073e
	
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
		
		
		if($module == 'TicketComments')
		{
			
			global $adb;
			foreach($records["create"] as $record)
			{
				$ticketName = $record->getField("subject");
				
				$resTicket = $adb->pquery("select ticketid from vte_troubletickets where title = '$ticketName'", array($ticketName));
				$resTicketId = $adb->query_result($resTicket,0,'ticketid');
				$arrayIdTicket[] = $resTicketId;
			}
			$arrayLisTicketId = array_unique($arrayIdTicket);
			
			foreach($arrayLisTicketId as $IdTicket)
			{
				$params = array($IdTicket);
				$sql = "Delete From vte_ticketcomments WHERE ticketid = ?";
				$adb->pquery($sql, $params);
			}
		}
			
		
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
	
	public function pushMeta($module, $changes) {
		$this->log->debug('Entering '.__METHOD__);
		
		$atype = $this->config['auth_type'];
		if ($atype === 'webservice') {
			return $this->log->error('Meta propagation not supported via webservices');
		}
		
		$this->convertMetaChanges($module, $changes);
		
		$vteModule = $this->getLocalModule($module);
		
		if (is_array($changes['picklist']['add'])) {
			foreach ($changes['picklist']['add'] as $pname => $addValues) {
				$this->vtws->addPicklistValues($vteModule, $pname, $addValues);
			}
		}
		// other changes not supported yet
		
		return true;
	}
	
	public function objectExists($module, $id) {
		//$this->log->debug('Entering '.__METHOD__);

		$vteModule = $this->getLocalModule($module);

		$atype = $this->config['auth_type'];
		if ($atype === 'local') {
			// I can't use the query, because for users, inactive ones are not returned by the query
			global $adb;
			$focus = \CRMEntity::getInstance($vteModule);
			list($xx, $crmid) = explode('x', $id);
			$res = $adb->pquery("SELECT {$focus->table_index} FROM {$focus->table_name} WHERE {$focus->table_index} = ?", array($crmid));
			$exists = ($res && $adb->num_rows($res) > 0);
		} elseif ($atype === 'webservice') {
			
			$vtwsid = $id;

			$res = $this->vtws->doQuery("SELECT id FROM $vteModule WHERE id = '$vtwsid';");
			$l = $this->vtws->lastError();
			if (!empty($l)) {
				if (is_array($l)) $l = implode("\n", $l);
				return $this->log->error("Unable to check record existence in CRM (ID: $vtwsid): $l");
			}

			$exists = ($res && $res[0] && $res[0]['id'] == $vtwsid);
		}
		
		return $exists;
	}
	
	public function getObject($module, $id) {
		// TODO!
		//return $this->re
	}
	
	public function setObject($module, $id, $object) {
		if ($id) {
			return $this->updateRecord($module, null, $object);
		} else {
			return $this->createRecord($module, null, $object);
		}
	}
	
	public function deleteObject($module, $id) {
		return $this->deleteRecord($module, $id);
	}
	
	protected function createRecord($module, $userinfo, $record) {
		if ($module == 'Users') {
			return $this->createUser($record);
		}
		
		$this->log->debug('Entering '.__METHOD__);
		
		global $adb, $table_prefix;
		
		$class = $this->getModelClass($module);
		
		// clear the id in case it was wrongly set in the mapping table
		$record->clearId(static::$name);
		
		if($module == 'TicketComments')
		{
			
			$owner = $record->getOwner("Jira");
			$commentId = $record->getField("commentId");
			$comment = $record->getField("comment");
			
			$ticketName = $record->getField("subject");
			
			$resTicket = $adb->pquery("select ticketid from {$table_prefix}_troubletickets where title = ?", array($ticketName));
			$resTicketId = $adb->query_result($resTicket,0,'ticketid');
			
			$createdTime = $record->getCreatedTime();
			$id = $record->getId("Jira");
			$etag = $record->getEtag("Jira");
			
			$createdTimeFormat = $createdTime->format('Y-m-d H:i:s');
			
			$res = $adb->pquery("select id from {$table_prefix}_users where last_name = ?", array($owner));
				
			$resresult = $adb->query_result($res,0,'id');
			$ownerId = $resresult;

			$res2 = $adb->pquery("select commentid from {$table_prefix}_ticketcomments where commentid = ?", array($commentId));
				
			$resresult2 = $adb->query_result($res2,0,'commentid');

			
			if( $resresult2 == null )
			{
			
				$params = array($commentId,$resTicketId, $comment, $ownerId,'user',$createdTimeFormat);	//crmv@157490
				$sql = "INSERT INTO {$table_prefix}_ticketcomments (commentid,ticketid, comments, ownerid,ownertype, createdtime) VALUES (".generateQuestionMarks($params).")";
				
				$adb->pquery($sql, $params);
			}
			else{
				$params = array($comment,$commentId);
				$sql = "UPDATE {$table_prefix}_ticketcomments SET comments = ? WHERE commentid = ?";
				$adb->pquery($sql, $params);
				
			}
		
			return array($arrayCommentId);
			
		} else {			
			
			$localRecord = $class::fromCommonRecord($record);
			
			$this->mergeDefaults($localRecord->getModule(), $localRecord);
			$this->mergeForcedFields($localRecord->getModule(), $localRecord); // crmv@190016
			
			// crmv@195073
			if ($module == 'Targets_Contacts') {
				$target_id = $localRecord->getFields()['targetid'];
				$contact_id = $localRecord->getFields()['contactid'];
				$params = [
					'id' => $target_id,
					'relatelist' => json_encode([$contact_id]),
				];
			
				return $res = $this->vtws->doInvoke('relate', $params, 'POST');
			}
			// crmv@195073e
		
			if($module != "HelpDesk" &&  $module != "ProjectTask")
			{
				
				$localRecord = $class::fromCommonRecord($record);
				
				
				$wsowner = $localRecord->getOwner();
			
		
				$fields = $localRecord->toRawData('create');
			
				$fields['assigned_user_id'] = $wsowner;

				//crmv@196666 crmv@197423
				if($fields['assigned_user_id'] == "19x" AND $wsowner != null)
				{
					$fullname = explode(" ",$wsowner);
					
					$responseOwner = $adb->pquery("select id from {$table_prefix}_users where user_name = ? ", array($fullname[0]));
					
					$responseresultOwner = $adb->query_result($responseOwner,0,'id');
					
					$fields = $localRecord->toRawData('create');
		
					$fields['assigned_user_id'] = $this->getWSId('Users', $responseresultOwner);
				}
				//crmv@196666e crmv@197423e
			}
			
			if($module == "ProjectTask") {
		
				$nameOwner = $record->getOwner("Jira")->displayName;
				if($nameOwner != null)
				{
					$responseOwner = $adb->pquery("select id from {$table_prefix}_users where last_name = ?", array($nameOwner));
				
					$responseresultOwner = $adb->query_result($responseOwner,0,'id');
			
					$fields = $localRecord->toRawData('create');
		
					$fields['assigned_user_id'] = $this->getWSId('Users', $responseresultOwner);
				}
				else{
					$wsowner = $localRecord->getOwner();
		
					$fields = $localRecord->toRawData('create');
		
					$fields['assigned_user_id'] = $wsowner;
				}
				
				$fields['enddate'] = $record->getField("end_date");
				$fields['startdate'] = $record->getField("start_date");
				
			} elseif($module == "HelpDesk") {
			
				$nameAssignee = $record->getField("assignee")->displayName;
				//crmv@196666
				$localRecord = $class::fromCommonRecord($record);	
				$wsowner = $localRecord->getOwner();
				//crmv@197423
				$fields = $localRecord->toRawData('create');
		
				$fields['assigned_user_id'] = $wsowner;
				//crmv@196666e
				if($nameAssignee != null)
				{
					$response = $adb->pquery("select id from {$table_prefix}_users where last_name = ?", array($nameAssignee));
				
					$responseresult = $adb->query_result($response,0,'id');
			
					$fields = $localRecord->toRawData('create');
			
					$fields['assigned_user_id'] = $this->getWSId('Users', $responseresult);
				
				}elseif($fields['assigned_user_id'] == "19x" AND $wsowner != null){
					//crmv@196666
					$fullname = explode(" ",$wsowner);
					$responseOwner = $adb->pquery("select id from {$table_prefix}_users where user_name = ? ", array($fullname[0]));
				
					$responseresultOwner = $adb->query_result($responseOwner,0,'id');
					
					$fields = $localRecord->toRawData('create');
		
					$fields['assigned_user_id'] = $this->getWSId('Users', $responseresultOwner);
					//crmv@196666e
				}
				//crmv@197423e
				
			}
			
		}
		
		// TODO: check if using delegation or single-user access
		if (empty($fields['assigned_user_id'])) {
			$this->log->warning('Missing assigned user, using userid = 1');
			$fields['assigned_user_id'] = $this->getWSId('Users', 1);
		}

		$vteModule = $this->getLocalModule($module);

		if ($this->simulate) {
			$this->log->info('RECORD CREATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'created', 'id'=>100, 'etag' => '0000');
		}
	
		$res = $this->vtws->doCreate($vteModule, $fields);
		$l = $this->vtws->lastError();
		if (!empty($l)) {
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error('Unable to create record in CRM : '.$l);
		}

		if (empty($res)) {
			return $this->log->error('Unknown error while creating record in CRM');
		}
		$etag = $class::extractEtag($res);

		$this->log->debug('Created VTE record with id '.$res['id']);
		return array('action' => 'created', 'id'=>$res['id'], 'etag' => $etag);
	}
	
	
	protected function createUser($record) {
		$this->log->debug('Entering '.__METHOD__);
		
		$atype = $this->config['auth_type'];
		if ($atype === 'webservice') {
			return $this->log->error('User creation not supported via webservices');
		}
		
		// clear the id in case it was wrongly set in the mapping table
		$record->clearId(static::$name);
		
		$vteuser = VTEUser::fromCommonUser($record);
		
		if (!$vteuser) return $this->log->error('Unable to convert user to VTE model');
		
		$this->mergeDefaults($vteuser->getModule(), $vteuser);
		$this->mergeForcedFields($vteuser->getModule(), $vteuser); // crmv@190016
		
		$fields = $vteuser->getFields();
		
		//crmv@197423
		if ($fields["currency_id"]) {
			$currency = explode("x", $fields["currency_id"]);
			$fields["currency_id"] = $this->getModuleWSId("Currency") ."x" . $currency[1];
		}
		//crmv@197423e
	
		// crmv@190016
		// check for mandatory fields (TODO: should be done in the model)
		if (empty($fields['user_name'])) {
			return $this->log->error('User has no username');
		}
		if (empty($fields['email1'])) {
			return $this->log->error('User has no email address');
		}
		// crmv@190016e
		
		if ($fields['user_password'] === ':RANDOM:') {
			// generate random password
			$fields['user_password'] = $this->randomString(16);
		}

		// specific fields and request stuff!
		$fields['confirm_password'] = $fields['user_password'];
		$_REQUEST['CRMVNEWS'] = 'on';
		$_REQUEST['HELPVTE'] = 'on';
		$_REQUEST['MODCOMMENTS'] = 'on';
		$_REQUEST['My files'] = 'on';

		$r = $this->vtws->doCreate('Users', $fields);

		// and remove them!
		unset($_REQUEST['CRMVNEWS'], $_REQUEST['HELPVTE']);
		unset($_REQUEST['MODCOMMENTS'], $_REQUEST['My files']);
		
		$l = $this->vtws->lastError();
		if (!empty($l)) {
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error("Unable to create user in CRM: $l");
		}
		
		list($xx, $userid) = explode('x', $r['id']);
		
		// the only way to have the modified time
		global $adb, $table_prefix;
		$res = $adb->pquery("SELECT date_entered, date_modified FROM {$table_prefix}_users WHERE id = ?", array($userid));
		if ($res && $adb->num_rows($res) > 0) {
			$date1 = strtotime($adb->query_result_no_html($res, 0, 'date_entered'));
			$date2 = strtotime($adb->query_result_no_html($res, 0, 'date_modified'));
			$etag = max($date1, $date2);
		}
		
		$this->log->debug('Created VTE user with id '.$r['id']);
		return array('action' => 'created', 'id'=>$r['id'], 'etag' => $etag);
	}
	
	protected function randomString($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_') {
		$str = '';
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$str .= $keyspace[random_int(0, $max)];
		}
		return $str;
	}

	protected function updateRecord($module, $userinfo, $record) {
	
		if ($module == 'Users') {
			return $this->updateUser($record);
		}
	
		
		$this->log->debug('Entering '.__METHOD__);

		$exists = $this->objectExists($module, $record->getId(static::$name));
		if (!$exists) {
			return $this->createRecord($module, $userinfo, $record);
		}
		
		$class = $this->getModelClass($module);
		
		$localRecord = $class::fromCommonRecord($record);
		
		$vtwsid = $localRecord->getId();
		$wsowner = $localRecord->getOwner();
		$fields = $localRecord->toRawData('update');
		
		$fields['id'] = $vtwsid;
		
		if ($this->config['update_owners']) {
			$fields['assigned_user_id'] = $wsowner;
		} else {
			unset($fields['assigned_user_id']);
		}

		if ($this->simulate) {
			$this->log->info('RECORD UPDATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'updated', 'id'=>$vtwsid, 'etag' => '0000');
		}

		$res = $this->vtws->doRevise($fields);	// revise, so update only passed fields
		$l = $this->vtws->lastError();
		if (!empty($l)) {
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error("Unable to update record in CRM (ID: $vtwsid): $l");
		}

		if (empty($res)) {
			return $this->log->error('Unknown error while updating record in CRM');
		}
		//list ($modid, $crmid) = explode('x', $res['id']);
		
		$etag = $class::extractEtag($res);

		$this->log->debug('Updated VTE record with id '.$vtwsid);
		return array('action' => 'updated', 'id'=>$res['id'], 'etag' => $etag);
	}
	
	protected function updateUser($record) {
		$this->log->debug('Entering '.__METHOD__);
		
		$exists = $this->objectExists('Users', $record->getId(static::$name));
		if (!$exists) {
			$this->log->debug('User does not exist for update, creating it.');
			return $this->createUser($record);
		}
		
		$atype = $this->config['auth_type'];
		if ($atype !== 'local') {
			// TODO: via ws!
			return $this->log->error('User update not supported via webservices');
		}
		
		// TODO: simulate
		
		$vteuser = VTEUser::fromCommonUser($record);
		
		if (!$vteuser) return $this->log->error('Unable to convert user to VTE model');
		
		$this->mergeDefaults($vteuser->getModule(), $vteuser);
		$this->mergeForcedFields($vteuser->getModule(), $vteuser); // crmv@190016
		
		$fields = $vteuser->getFields();
		$vtwsid = $vteuser->getId();
		list($xx, $userid) = explode('x', $vtwsid);
		
		// specific fields and request stuff!
		$fields['id'] = $vtwsid;

		// clean empty fields
		$fields = array_filter($fields);
		
		// if the user is not active, it won't be found, so i have to flip the status flag!
		global $adb, $table_prefix;
		$res = $adb->pquery("SELECT status FROM {$table_prefix}_users WHERE id = ?", array($userid));
		if ($res && $adb->num_rows($res) > 0) {
			$status = $adb->query_result_no_html($res, 0, 'status');
			if ($status == 'Inactive') {
				$adb->pquery("UPDATE {$table_prefix}_users SET status = ? WHERE id = ?", array('Active', $userid));
			}
		}
		
		// "ws" call!
		$res = $this->vtws->doRevise($fields);	// revise, so update only passed fields
		
		// and restore the status if it wasn't changed -> restore anyway, no status changes are allowed!
		//if ($status == 'Inactive' && $fields['status'] != 'Active') {
		if ($status == 'Inactive') {
			$adb->pquery("UPDATE {$table_prefix}_users SET status = ? WHERE id = ?", array($status, $userid));
		}
		
		// check for errors
		$l = $this->vtws->lastError();
		if (!empty($l)) {
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error("Unable to update user in CRM (ID: {$vtwsid}): $l");
		}

		if (empty($res)) {
			return $this->log->error('Unknown error while updating record in CRM');
		}
		
		// the only way to have the modified time
		$resdate = $adb->pquery("SELECT date_entered, date_modified FROM {$table_prefix}_users WHERE id = ?", array($userid));
		if ($resdate && $adb->num_rows($resdate) > 0) {
			$date1 = strtotime($adb->query_result_no_html($resdate, 0, 'date_entered'));
			$date2 = strtotime($adb->query_result_no_html($resdate, 0, 'date_modified'));
			$etag = max($date1, $date2);
		}
		
		$this->log->debug('Updated VTE user with id '.$res['id']);
		return array('action' => 'updated', 'id'=>$res['id'], 'etag' => $etag);
	}

	protected function deleteRecord($module, $userinfo, $record) {
		
		if ($module == 'Users') {
			return $this->deleteUser($record);
		}
		
		$this->log->debug('Entering '.__METHOD__);
		
		$vtwsid = $record->getId(static::$name);
		
		if ($this->simulate) {
			$this->log->info('RECORD DELETED (ID: '.$vtwsid.')');
			return array('action' => 'deleted', 'id'=>$vtwsid);
		}
		
		$res = $this->vtws->doDelete($vtwsid);
		$l = $this->vtws->lastError();
		if (!empty($l)) {
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error('Unable to delete record in CRM : '.$l);
		}
		
		$this->log->debug('Deleted VTE record with id '.$vtwsid);
		return array('action' => 'deleted', 'id'=>$vtwsid);
	}
	
	// Untested! in SF users cannot be deleted!
	protected function deleteUser(CommonUser $record) {
		$this->log->debug('Entering '.__METHOD__);
		
		$atype = $this->config['auth_type'];
		if ($atype === 'webservice') {
			return $this->log->error('User delete not supported via webservices');
		}
		
		$vtwsid = $record->getId(static::$name);
		
		if ($this->simulate) {
			$this->log->info('USER DELETED (ID: '.$vtwsid.')');
			return array('action' => 'deleted', 'id'=>$vtwsid);
		}
		
		$transferTo = \Users::getActiveAdminId();
		$transferToWs = $this->getWSId('Users', $transferTo);
		$res = $this->vtws->doDeleteUser($vtwsid, $transferToWs);
		$l = $this->vtws->lastError();
		if (!empty($l)) {
			if (is_array($l)) $l = implode("\n", $l);
			return $this->log->error('Unable to delete user in CRM : '.$l);
		}
			
		$this->log->debug('Deleted VTE user with id '.$vtwsid);
		return true;
	}
	
	protected function getModuleWSId($vtemodule) {
		$res = $this->vtws->doDescribe($vtemodule);
		return $res['idPrefix'];
	}
	
	protected function getWSId($module, $crmid) {
		$vteModule = $this->getLocalModule($module);
		if (!array_key_exists($vteModule, $this->ws_prefixes)) {
			$this->ws_prefixes[$vteModule] = $this->getModuleWSId($vteModule);
		}
		$vtwsid = $this->ws_prefixes[$vteModule].'x'.$crmid; // TODO: load ws prefixes from VTE
		return $vtwsid;
	}
	
	/**
	 * $record is a CommonObject
	 */
	protected function getRecordWSId($record) {
		return $this->getWSId($record->getModule(), $record->getId(static::$name));
	}
	
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@190016 */

namespace VteSyncLib\Connector;

use \VteSyncLib\VTEUtils;

use \VteSyncLib\Model\CommonUser;
//use \VteSyncLib\Connector\VTE\LocalWSClient;
//use \VteSyncLib\Connector\VTE\Model\User as VTEUser;

use \JiraRestApi\JiraException;
use \JiraRestApi\Configuration\ArrayConfiguration;
use \JiraRestApi\User\UserService;
use \JiraRestApi\Project\ProjectService;
use \JiraRestApi\Issue\IssueService;


class Jira extends BaseConnector {
	
	static public $name = 'Jira';
	
	protected $modulesHandled = array('Users', 'ProjectPlan', 'ProjectTask', 'HelpDesk', 'TicketComments');
	
	protected $classes = array(
		'Users' => array('module' => 'Users', 'commonClass' => 'VteSyncLib\Model\CommonUser', 'class' => 'VteSyncLib\Connector\Jira\Model\User'),
		'ProjectPlan' => array('module' => 'ProjectPlan', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Jira\Model\Project'),
		'ProjectTask' => array('module' => 'ProjectTask', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Jira\Model\Epic'),
		'HelpDesk' => array('module' => 'HelpDesk', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Jira\Model\Ticket'),
		'TicketComments' => array('module' => 'TicketComments', 'commonClass' => 'VteSyncLib\Model\CommonRecord', 'class' => 'VteSyncLib\Connector\Jira\Model\TicketComment'),
		// metadata
		'Meta' => array('module' => 'Meta', 'commonClass' => 'VteSyncLib\Model\CommonMeta', 'class' => 'VteSyncLib\Connector\Jira\Model\Meta'),
	);
	
	protected $userService = null;
	protected $projectService = null;
	protected $issueService = null;
	
	protected $is_connected = false;
	
	public function connect() {
		$this->log->debug('Entering '.__METHOD__);
		
		$syncid = $this->getSyncID();
		
		require_once dirname(__FILE__).'/Jira/vendor/autoload.php';
		
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
	
	/*protected function connectOAuth() {
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
	}*/
	
	protected function connectHttp() {
		$syncid = $this->getSyncID();
		
		$authInfo = $this->storage->getAuthInfo($syncid);
			
		$this->is_connected = (!empty($authInfo['username']) && !empty($authInfo['password']));
			
		if ($this->is_connected) {
			$config = new ArrayConfiguration(array(
				'jiraHost' => rtrim($this->config['instance_url'], '/'), // crmv@206329
				// for basic authorization:
				'jiraUser' => $authInfo['username'],
				'jiraPassword' => $authInfo['password'], // can also be API token
				// to enable session cookie authorization (with basic authorization only)
				'cookieAuthEnabled' => false,
				//'cookieFile' => storage_path('jira-cookie.txt'),
				'jiraLogEnabled' => false,
				'jiraLogFile' => 'php://stdout',
				'jiraLogLevel' => $this->log->getMonologLevel(),
			));
			$this->userService = new UserService($config);
			$this->projectService = new ProjectService($config);
			$this->issueService = new IssueService($config);
			try {
				$this->myself = $this->userService->getMyself();
			} catch (JiraException $e) {
				$this->is_connected = false;
				$this->log->error('Unable to retrieve info about the current user');
			}
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
		
		if ($module == 'Users') {
			return $this->pullUsers($userinfo, $date, $maxEntries);
		}
	
		$sobject = $this->getLocalModule($module);
		
		$commonClass = $this->getCommonClass($module);
	
		$records = array(
			'created' => array(),
			'updated' => array(),
			'deleted' => array(),
		);
		
		//if ($this->config['modules'][$module]['picklist']) { // TODO check...
		
		if ($module != 'Users' && $module != 'TicketComments') {
			// retrieve metadata and extract picklist values
			
			$priority = $this->issueService->getAllPriorities();
			$statuses = $this->issueService->getAllStatuses();
			$obj_merged = array_merge((array) $priority, (array) $statuses);
			
			$metaClass = $this->getModelClass('Meta');

			$metaModelAll = $metaClass::fromRawData($obj_merged);

			$class = $this->getModelClass($module);

			$metaModelAll->setRecordClass($class);

			$cmModelAll = $metaModelAll->toCommonMeta();
	
			$records['metadata'] = $cmModelAll;
		}
		
		$class = $this->getModelClass($module);
		
		try {
			if ($module == 'ProjectPlan') {
				
				$list = $this->projectService->getAllProjects(['expand' => 'description,url,lead']);
				$lastUpdate = new \DateTime(); // no timestamps available for projects :(
			
			} elseif ($module == 'HelpDesk' || $module == 'ProjectTask' || $module == 'TicketComments') {
			
				if ($module == 'ProjectTask') {
					$typeFilter = "issuetype = 'Epic'";
				} else {
					$typeFilter = "issuetype != 'Epic'";
				}
				if (empty($date)) {
					$jql = "$typeFilter order by updated asc";
				} else {
					$date->setTimezone(new \DateTimeZone($this->myself->timeZone));
					$jql = "$typeFilter and updated >= '".$date->format('Y/m/d H:i')."' order by updated asc";
				}
				
				if ($module == 'TicketComments') {
					$list = $this->issueService->search($jql, 0, $maxEntries, ['*all'], ['assignee','description']);
				} else {
					$list = $this->issueService->search($jql, 0, $maxEntries, ['*all','-comment'], ['assignee','description']);
				}
				
				$list = $list->issues;
				if ($list->total <= $maxEntries) {
					// ok, got all
					$lastUpdate = new \DateTime(); // no timestamps available for projects :(
				} else {
					// take the last time
					$lastUpdate = $class::extractModifiedTime($list[count($list)-1]);
				}
			} else {
				throw new \Exception("Module $module is not supported");
			}
		} catch (JiraException $e) {
			return $this->log->error('Unable to retrieve records: '.$e->getMessage());
		}

		foreach ($list as $p) {
			// create the internal object
			
			$jiraModel = $class::fromRawData($p);
			
			if (empty($jiraModel)) {
				if($module != "TicketComments")
				{
				$this->log->warning('Unable to convert object from raw data');
				}
				continue;
			}
			else
			{
			
				if($module != "TicketComments")
				{
					$cModel = $jiraModel->toCommonRecord();
		
					if (empty($cModel)) {
						$this->log->warning('Unable to convert object to common model');
						continue;
					}
					$records['updated'][] = $cModel;
				}
				if($module == "TicketComments")
				{
					foreach($jiraModel as $jiraMod)
					{
						$cModel = $jiraMod->toCommonRecord();
						if (empty($cModel)) {
						$this->log->warning('Unable to convert object to common model');
						continue;
						}
						$records['updated'][] = $cModel;
					}
				}
				
			}
		}

		$records['last_update'] = $lastUpdate;
		
		return $records;
	}
	
	public function push($module, $userinfo, &$records) {
		
		if($module == "TicketComments") {
			foreach($records["create"] as $record) {
				$vteIDTicket = $record->getField("ticketid");
				$ids = $this->storage->getMappedIds("VTE","HelpDesk",$vteIDTicket);
				$record->addField('taskid',$ids["Jira"]["id"]);
			}
		}
		
		
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
	
	protected function pullUsers($userinfo, \DateTime $date = null, $maxEntries = 100) {
		
		$module = 'Users';
		
		$paramArray = array(
			'username' => '', // get all users
			'startAt' => 0,
			'maxResults' => $maxEntries,
			'includeInactive' => true,
		);
		
		try {
			$list = $this->userService->findUsers($paramArray);
		} catch (JiraException $e) {
			$this->log->error('Unable to retrieve users: '.$e->getMessage());
			return false;
		}
		
		$sobject = $this->getLocalModule($module);
		$commonClass = $this->getCommonClass($module);
		$class = $this->getModelClass($module);
		
		$records = array(
			'created' => array(),
			'updated' => array(),
			'deleted' => array(),
		);
		
		// explude "app" users
		$list = array_filter($list, function($juser) {
			return $juser->accountType == 'atlassian';
		});
		
		// create the objects
		foreach ($list as $juser) {
			$vteModel = $class::fromRawData($juser);
			if (empty($vteModel)) {
				$this->log->warning('Unable to convert object from raw data, record skipped');
				continue;
			}
			$record = $vteModel->toCommonUser();
			if ($juser->active) {
				$records['updated'][] = $record;
			} else {
				$records['deleted'][] = $record;
			}
		}
		
		$records['last_update'] = new \DateTime();
		
		return $records;
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
		if($module == 'TicketComments' && empty($sfModel))
		{
			return false;
		}
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
		if (!$id) return $this->log->error("Empty ID");
		
		try {
			if ($module == 'ProjectPlan') {
				
				$r = $this->projectService->deleteProject($id);
				
			} elseif ($module == 'HelpDesk') {
				$r = $this->issueService->deleteIssue($id);
			}elseif	($module == 'ProjectTask')
			{
				$r = $this->issueService->deleteIssue($id,array('deleteSubtasks' => 'true'));
			}
			else {
				throw new \Exception("Module $module not supported yet");
			}
		} catch (JiraException $e) {
			if ($e->getCode() == 404) {
				$this->log->warning("The record with #$id didn't exist in Jira but it's fine, since we have to delete it.");
				return array('action' => 'deleted', 'id'=>$id);
			}
			return $this->log->error("Unable to delete project #{$id} ".$e->getMessage());
		}
		
		$this->log->debug('Deleted Jira record with id '.$id);
		return array('action' => 'deleted', 'id'=>$id);
	}
	
	public function objectExists($module, $id) {
		try {
			if ($module == 'ProjectPlan') {
				$r = $this->projectService->get($id);
			} elseif ($module == 'HelpDesk' || $module == 'ProjectTask') {
				$r = $this->issueService->get($id, ['fields' => 'id']);
			} else {
				throw new \Exception("Module $module not supported yet");
			}
		} catch (JiraException $e) {
			if ($e->getCode() == 404) return false;
			return $this->log->error("Unable to retrieve project #{$id} ".$e->getMessage());
		}
		
		return true;
	}
	
	protected function createRecord($module, $userinfo, $record) {
		
		if ($module == 'Users') throw new \Exception('User creation not supported in Jira');
		if($module == "TicketComments")
		{
			$nameComment = $record->getField("comment");
			$nameComment = html_entity_decode($nameComment, ENT_COMPAT, 'UTF-8');
			$idTask = $record->getField("taskid"); 
			$commentId	= $record->getField("commentId");
		}
		
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
		$this->mergeForcedFields($localRecord->getModule(), $localRecord);
		$fields = $localRecord->toRawData('create');
		
		if ($this->simulate) {
			$this->log->info('RECORD CREATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'created', 'id'=>100, 'etag' => '0000');
		}
		
		try {
			if ($module == 'ProjectPlan') {
				// mandatory lead, use connecting user if missing
				// TODO: wrong place to do this!
				if (!$fields->leadAccountId) {
					$fields->setLeadAccountId($this->myself->accountId);
					
				}
				$r = $this->projectService->createProject($fields);
			} elseif ($module == 'HelpDesk' || $module == 'ProjectTask' ) {
				$r = $this->issueService->create($fields);
			} elseif ($module == 'TicketComments' )
			{	
				if($idTask != null)
				{
					
					$listCom = $this->issueService->getComments($idTask);
					$flagNoDuplicate = 1;
					
					foreach($listCom->comments as $com)
					{
						
						if($com->body == $nameComment)
						{
							$flagNoDuplicate = 0;
						}
						
					}
					if($flagNoDuplicate)
					{
						$r = $this->issueService->addComment($idTask, array("body" => $nameComment));
					}
				}
			}
			else {
				return $this->log->error('Unsupported module: '.$module);
			}
		} catch (JiraException $e) {
			return $this->log->error('Unable to create record: '.$e->getMessage());
		}
	

		if (!$r) {
			return false;
			//return $this->log->error('Request failed: '.print_r($r, true));
		}
		
		$id = $class::extractId($r);
		$etag = $class::extractEtag($r);
		
		$this->log->debug('Created Jira record with id '.$id);
		return array('action' => 'created', 'id'=>$id, 'etag' => $etag);
	}
	
	protected function updateRecord($module, $userinfo, $record) {
		if ($module == 'Users') throw new \Exception('User update not supported in Jira');
	
	
	
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
		$this->mergeForcedFields($localRecord->getModule(), $localRecord);
		$fields = $localRecord->toRawData('update');

		// preprint($fields);
		// preprint($id);
		// TODO: owner??

		if ($this->simulate) {
			$this->log->info('RECORD UPDATED');
			$this->log->info(print_r($fields, true));
			return array('action' => 'updated', 'id'=>$id, 'etag' => '0000');
		}

		try {
			if ($module == 'ProjectPlan') {
				$r = $this->projectService->updateProject($fields, $id);
			} elseif ($module == 'HelpDesk' || $module == 'ProjectTask') {
				$ok = $this->issueService->update($id, $fields, ['notifyUsers' => false]);
				if (!$ok) return $this->log->error("Unable to update record");
				// now I have to retrieve the record to have the dates
				$r = $this->issueService->get($id, ['fields' => 'id,created,updated']);
			} else {
				return $this->log->error('Unsupported module: '.$module);
			}
		} catch (JiraException $e) {
			return $this->log->error('Unable to update record: '.$e->getMessage());
		}
		
		$etag = $class::extractEtag($r);

		$this->log->debug('Updated Jira record with id '.$id);
		return array('action' => 'updated', 'id'=>$id, 'etag' => $etag);
	}
	
	protected function deleteRecord($module, $userinfo, $record) {
		if ($module == 'Users') throw new \Exception('User deletion is not supported in Jira');
		$localId = $record->getId(static::$name);
		return $this->deleteObject($module, $localId);
	}

}
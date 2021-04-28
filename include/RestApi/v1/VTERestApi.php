<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@170283 */
require_once('include/RestApi/Exceptions.php');

class VTERestApi extends SDKExtendableClass {
	
	protected $user;
	protected $operationManager;
	protected $operationParameters = [];
	protected $restOperations = array(
		'retrieve' => 'retrieve',
		'create' => 'create',
		'delete' => 'delete',
		'query' => 'query',
		'listtypes' => 'listtypes',
		'describe' => 'describe',
		'convertlead' => 'convertlead',
		'updateRecord' => 'update',
		'retrieveInventory' => 'retrieveinventory',
		'revise' => 'revise',
		'get_labels' => 'getlabels',
		'get_langs' => 'getlangs',
		'login_pwd' => 'loginpwd',
		'getmenulist' => 'getmenulist',
		'retrieveExtra' => 'retrieveextra',
		'queryExtra' => 'queryextra',
		'describeExtra' => 'describeextra',
		'listtypesExtra' => 'listtypesextra',
		'getRelationsExtra' => 'getrelationsextra',
		'ol_get_filters' => 'olgetfilters',
		'ol_clientsearch' => 'olclientsearch',
		'ol_is_sdk' => 'olissdk',
		'ol_doquery' => 'oldoquery',
		'describe_all' => 'describeall',
		'dynaform_describe' => 'dynaformdescribe',
	);
	// crmv@171524
	private $systemOperations = array( // system methods: written here and not in external files
		'processhandler' => array('id'=>'string'),
	);
	// crmv@171524e
	protected static $performanceLogger = null; //crmv@173186
	
	function __construct() {
		//crmv@173186
		$this->log('URL',$_SERVER['REQUEST_URI'],true);
		$this->log('METHOD',$_SERVER['REQUEST_METHOD']);
		$this->log('REQUEST',Zend_Json::encode($_REQUEST));
		//crmv@173186e
	}
	
	public function enableRestOperations() {
		global $adb, $table_prefix;
		foreach($this->restOperations as $name => $rest_name) {
			$adb->pquery("update {$table_prefix}_ws_operation set rest_name = ? where name = ?", array($rest_name, $name));
		}
	}
	
	protected function validateToken() {
		if (!isset($_SERVER['PHP_AUTH_USER']) || trim($_SERVER['PHP_AUTH_USER']) == "") {
			throw new InvalidLoginException('Authentication failed - reason: no username supplied');
		} elseif (!isset($_SERVER['PHP_AUTH_PW']) || trim($_SERVER['PHP_AUTH_PW']) == "") {
			throw new InvalidLoginException('Authentication failed - reason: no password supplied');
		} else {
			//crmv@186555
			global $adb, $table_prefix, $current_user;
			$user_name = vtlib_purify($_SERVER['PHP_AUTH_USER']);
			$token = vtlib_purify($_SERVER['PHP_AUTH_PW']);
			
			$userInstance = CRMEntity::getInstance('Users');
			$userId = $userInstance->retrieve_user_id($user_name);
			$userInstance->id = $userId;
			
			$result = $adb->pquery("select id from {$table_prefix}_users where user_name = ? and accesskey = ? and status = ?", array($user_name, $token, 'Active'));
			if (!($result && $adb->num_rows($result) > 0)) {
				$userInstance->trackErrorLogin();
				throw new InvalidLoginException('Authentication failed - reason: incorrect username and password combination');
			} else {
				$userInstance->trackSuccessLogin();
			}
			
			$success = true;
			$userInstance->checkTrackingLogin($success);
			if (!$success) {
				throw new InvalidLoginException('Authentication failed - reason: too many unsuccessfully logins');
			}
			
			$current_user = CRMEntity::getInstance('Users');
			$this->user = $current_user->retrieveCurrentUserInfoFromFile($userId);
			//crmv@186555e
		}
	}
	
	//crmv@173186
	public function log($title,$str='',$new=false) {
		VTESystemLogger::log('restapi', $title, $str, $new); // crmv@176614
	}
	//crmv@173186e
	
	public function getMethods() {
		global $adb, $table_prefix;
		static $methods = array();
		if (empty($methods)) {
			$result = $adb->query("select operationid, name, type, rest_name, prelogin from {$table_prefix}_ws_operation where rest_name is not null and rest_name <> ''"); // crmv@179081
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$methods[$row['rest_name']] = $row;
				}
			}
		}
		// crmv@171524
		if (!empty($this->systemOperations)) {
			foreach($this->systemOperations as $op => $params) {
				$methods[$op] = array('name'=>$op,'rest_name'=>$op,'type'=>'POST','prelogin'=>0,'parameters'=>$params);
			}
		}
		// crmv@171524e
		return $methods;
	}
	
	public function getParameters($method) {
		global $adb;
		if (!isset($this->operationParameters[$method])) {
			$this->operationParameters[$method] = [];
			$methods = $this->getMethods();
			// crmv@171524
			if (isset($methods[$method]['parameters'])) {
				$this->operationParameters[$method] = array_keys($methods[$method]['parameters']);
			} else {
			// crmv@171524e
				if (isset($methods[$method])) {
					$operation = strtolower($methods[$method]['name']);
				}
				require_once("include/Webservices/OperationManager.php");
				require_once("include/Webservices/SessionManager.php");
				$sessionManager = new SessionManager();
				$format = vtws_getParameter($_REQUEST, "format", "json");
				$this->operationManager = new OperationManager($adb,$operation,$format,$sessionManager);
				$tmp_params = $this->operationManager->getOperationParams();
				if (!empty($tmp_params)) {
					foreach($tmp_params as $param) {
						$name = array_keys($param); $name = $name[0];
						$this->operationParameters[$method][] = $name;
					}
				}
			} // crmv@171524
		}
		return $this->operationParameters[$method];
	}
	
	/*
	 * some examples:
	 * query query:"SELECT * FROM Accounts WHERE accountname like '%vte%';"
	 * listtypes fieldTypeList(optional):"[\"Accounts\",\"Contacts\"]"
	 * revise element:"{\"id\":\"9x222\",\"ticket_title\":\"aaa\",\"description\":\"aaa\"}"
	 */
	public function __call($method, $args) {
		$methods = $this->getMethods();
		// crmv@179081
		if (!array_key_exists($method,$methods)) {
			throw new MethodNotFoundException(sprintf('Method %s in class %s not found.', $method, get_class($this)));
		} else {
			$args = array_combine($this->getParameters($method), $args);
			$validate = !$methods[$method]['prelogin'];
			return $this->invoke($method, Zend_Json::encode($args), $validate);
		}
		// crmv@179081e
	}
	
	public function create($elementType, $element) {
		$element = Zend_Json::decode($element);
		if(!isset($element['assigned_user_id'])) {
			$element['assigned_user_id'] = vtws_getWebserviceEntityId('Users',getUserId_Ol(vtlib_purify($_SERVER['PHP_AUTH_USER'])));
		}
		return $this->invoke(__FUNCTION__, Zend_Json::encode(array('elementType'=>$elementType,'element'=>Zend_Json::encode($element))));
	}
	
	private function invoke($method, $params='', $validate = true) { // crmv@179081
		if ($validate) $this->validateToken(); // crmv@179081
		
		$operationInput = $this->operationManager->sanitizeOperation(Zend_Json::decode($params));
		$includes = $this->operationManager->getOperationIncludes();
		foreach($includes as $ind=>$path){
			require_once($path);
		}
		$rawOutput = $this->operationManager->runOperation($operationInput, $this->user);
		return $rawOutput;
	}
	
	// crmv@171524 crmv@178291 crmv@199641
	function processhandler($id) {
		global $adb, $table_prefix;
		
		$this->validateToken();
		
		$start_time = microtime(true);
		$allok = true;
		
		require_once('include/utils/TriggerQueueManager.php');
		$triggerQueueManager = \TriggerQueueManager::getInstance();
		$triggerQueueManager->activateConsumer();
		
		$VTEP = \VTEProperties::getInstance();
		$rabbitmqConnection = $VTEP->getProperty('performance.mq_rabbitmq_connection');
		$max_attempts_exhausted = false;
		
		// catch fatal errors
		$oldDieOnError = ini_get('display_errors');
		ini_set('display_errors', 0);
		register_shutdown_function(array($this, 'errorHandler'), $id);
		
		// sleep(2);
		
		// disable die on error, enable exception
		$oldDieOnError = $adb->dieOnError;
		$adb->setDieOnError(false);
		$adb->setExceptOnError(true);
		
		try {
			$result = $adb->pquery("SELECT * FROM {$table_prefix}_trigger_queue WHERE id = ?", array($id));
			if ($result && $adb->num_rows($result) > 0) {
				
				$crmid = $adb->query_result($result, 0, 'crmid');
				$logtitle = "crmid:{{$crmid}} queueid:{$id}";
				$this->log("{$logtitle} info", "Start processing");
				
				// check and lock working
				$result_upd = $adb->pquery("UPDATE {$table_prefix}_trigger_queue SET working = ? WHERE id = ?", array(1,intval($id)));
				if ($adb->getAffectedRowCount($result_upd) == 0) {
					$this->log("{$logtitle} info", "End processing, already working");
					throw new \Exception("Queue element $id (dependent_on: {$adb->query_result($result, 0, 'dependent_on')}, master_dependent_on: {$adb->query_result($result, 0, 'master_dependent_on')}) is already working.");
				}
				$triggerQueueManager->lockDependents($id);
				
				// check if the previous element has been executed
				$dependent_on = $adb->query_result($result, 0, 'dependent_on');
				if (!empty($dependent_on)) {
					// if the dependent_on failed set failed also this row
					$result1 = $adb->pquery("SELECT * FROM {$table_prefix}_trigger_queue_failed WHERE id = ?", array($dependent_on));
					if ($result1 && $adb->num_rows($result1) > 0) {
						$this->log("{$logtitle} error", "Queue element {$id} depends on the failed {$dependent_on}.");
						$triggerQueueManager->move2Failed($id,'DEPENDENT_ON_FAILED');
						$triggerQueueManager->unlockDependents($id);
						$this->log("{$logtitle} info", "End processing element, moved to failed");
						return $id;
					}
					$result1 = $adb->pquery("SELECT * FROM {$table_prefix}_trigger_queue WHERE id = ?", array($dependent_on));
					if ($result1 && $adb->num_rows($result1) > 0) {
						$this->log("{$logtitle} error", "Queue element {$id} depends on {$dependent_on}.");
						$this->log("{$logtitle} info", "End processing element, exception thrown");
						throw new \Exception("Queue element $id depends on $dependent_on.");
					}
				}
				
				// increment and check attempts
				$adb->pquery("UPDATE {$table_prefix}_trigger_queue set attempts = attempts+1 WHERE id = ?", array($id));
				$attempts = $adb->query_result($result, 0, 'attempts');
				if ($attempts < $rabbitmqConnection['max_trigger_attempts']) {
					
					$crmid = $adb->query_result($result, 0, 'crmid');
					$module = $adb->query_result($result, 0, 'module');
					$mode = $adb->query_result($result, 0, 'mode');
					$info = \Zend_Json::decode($adb->query_result_no_html($result, 0, 'info'));
					
					$focus = \CRMEntity::getInstance($module);
					$err = $focus->retrieve_entity_info_no_html($crmid, $module, false);
					if (empty($err)) {
						require_once('data/VTEntityDelta.php');
						$entityDelta = new \VTEntityDelta();
						
						if (!empty($info['old_column_fields'])) {
							$entity = \VTEntityData::fromEntityId($adb, $crmid);
							$entity->focus->column_fields = $info['old_column_fields'];
							$entityDelta->setOldEntity($module, $crmid, $entity);
						}
						$entity = \VTEntityData::fromEntityId($adb, $crmid);
						$entity->focus->column_fields = $info['new_column_fields'];
						$entityDelta->setNewEntity($module, $crmid, $entity);
						$entityDelta->computeDelta($module, $crmid);
						
						$focus->column_fields = $info['new_column_fields'];
						$focus->mode = $mode;
						
						require_once('include/events/include.inc');
						require_once('modules/Settings/ProcessMaker/ProcessMakerHandler.php');
						$em = new \VTEventsManager($adb);
						// Initialize Event trigger cache
						$em->initTriggerCache();
						$entityData = \VTEntityData::fromCRMEntity($focus);
						
						if ($mode == '') $entityData->setNew(true);
						$processMakerHandler = new \ProcessMakerHandler();
						$processMakerHandler->handleEvent('vte.entity.aftersave', $entityData);//crmv@207852
					} else{
						// skip row and set failed if the crmid is deleted
						$this->log("{$logtitle} error", " [{$err}] retrieve failed");
						$this->log("{$logtitle} info", "End processing element, moved to failed");
						$allok = false;
						$triggerQueueManager->move2Failed($id,$error);
					}
				} else {
					$max_attempts_exhausted = true;
				}
			}
		} catch (\Exception $e) {
			// catches exceptions inside the try{}
			$triggerQueueManager->unlockDependents($id);
			$this->log("{$logtitle} error", "Exception: ".$e->getMessage());
			$this->log("{$logtitle} info", "End processing element, exception thrown");
			throw new \Exception($e->getMessage()); //."\n".$e->getTraceAsString());
		}
		
		// restore die on error
		$adb->setDieOnError($oldDieOnError);
		
		// restore display errors
		ini_set('display_errors', $oldDieOnError);
		
		$triggerQueueManager->unlockDependents($id);
		if (!$max_attempts_exhausted) {
			$triggerQueueManager->deleteFromTriggerQueue($id);
		} else {
			$this->log("{$logtitle} error", "max attempts exhausted ({$rabbitmqConnection['max_trigger_attempts']})");
			$this->log("{$logtitle} info", "End processing element, moved to failed");
			$triggerQueueManager->move2Failed($id,'MAX_ATTEMPTS_EXHAUSTED');
		}
		
		$rabbitmqConnection = $triggerQueueManager->getConnectionParams('rabbitmq');
		
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		$PMUtils = \ProcessMakerUtils::getInstance();
		
		$triggerQueueManager->execute($rabbitmqConnection['defreeze_topic_name'], \Zend_Json::encode(array('id' => $id, 'action' => __FUNCTION__, 'module' => $module, 'record' => $crmid)), true);
		
		$brothers = $PMUtils->getRecordsBrothers($crmid);
		foreach ($brothers as $bid) {
			$triggerQueueManager->execute($rabbitmqConnection['defreeze_topic_name'], \Zend_Json::encode(array('id' => $id, 'action' => __FUNCTION__, 'module' => $module, 'record' => $bid)), true);
		}
		
		if ($allok){
			$end_time = microtime(true);
			$time_consumed = round(($end_time-$start_time),2);
			$this->log("{$logtitle} info", "End processing element, OK, took {$time_consumed}s");
		}
		
		return $id;
	}
	
	// catches fatal errors and exceptions
	static function errorHandler($id) {
		global $server;
		
		$error = error_get_last();
		$catchTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
		
		// NOTICE: in case of die() or exit(), it's not possible to detect if there was an error or not, so the next code won't be executed
		if ($error !== null && in_array($error['type'], $catchTypes)) {
			require_once('include/utils/TriggerQueueManager.php');
			$triggerQueueManager = \TriggerQueueManager::getInstance();
			$triggerQueueManager->unlockDependents($id);
			$server->sendException(new \Exception($error['message'].' in '.$error['file'].'('.$error['line'].')'));
		}
	}
	// crmv@171524e crmv@178291e crmv@199641e
}
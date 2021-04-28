<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@171524 crmv@178291 crmv@199641 */

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class TriggerQueueManager extends SDKExtendableUniqueClass {
	
	protected $connection = null;
	protected static $dependent_on = null;
	protected static $master_dependent_on = array();
	protected static $triggerQueueConsumer;
	
	protected $enabled_modules = array(); // ha priorità su disabled_modules e un array esclude l'altro
	protected $disabled_modules = array();
	
	private $restPath = 'restapi/v1/vtews';
	protected $siteURL;
	protected $restURL;
	
	public function __construct() {
		global $site_URL;
		$this->siteURL = $site_URL;
		$this->restURL = $site_URL.'/'.$this->restPath;
	}
	
	public function __destruct() {
		if ($this->connection) {
			$this->connection->close();
			$this->connection = null;
		}
	}
	
	public function isEnabled($module, $crmid='') {
		$VTEP = VTEProperties::getInstance();
		if ($VTEP->getProperty('performance.mq_enabled')) {
			if (!empty($this->enabled_modules)) {
				$r = in_array($module,$this->enabled_modules);
				if (!$r) $this->activateConsumer(); // simulate the consumer
				return $r;
			}
			if (!empty($this->disabled_modules)) {
				$r = (!in_array($module,$this->disabled_modules));
				if (!$r) $this->activateConsumer(); // simulate the consumer
				return $r;
			}
			return true;
		}
		$this->activateConsumer(); // simulate the consumer
		return false;
	}
	
	// crmv@196871
	public function isStompEnabled() {
		$VTEP = VTEProperties::getInstance();
		return $VTEP->getProperty('performance.mq_webstomp_enabled');
	}
	// crmv@196871e
	
	protected function connect() {
		if (!$this->connection) {
			$rabbitmqConnection = $this->getConnectionParams('rabbitmq');

			$connection = new AMQPConnection(
				$rabbitmqConnection['host'],		// host - host name where the RabbitMQ server is runing
				$rabbitmqConnection['port'],		// port - port number of the service, 5672 is the default
				$rabbitmqConnection['user'],		// user - username to connect to server
				$rabbitmqConnection['password']		// password
			);

			$this->connection = $connection;
		}
		
		return $this->connection;
	}
	
	public function execute($routingKey, $message, $topic = false) {
		try {
			if ($topic) {
				$this->sendMessageToTopic($routingKey, $message);
			} else {
				$this->sendMessageToQueue($routingKey, $message);
			}
			return true;
		} catch (Exception $e) {
			//echo $e->getMessage(); die();
			return false;
		}
	}
	
	public function sendMessageToQueue($routingKey, $message) {
		try {
			$connection = $this->connect();
			
			$channel = $connection->channel();
			
			$queueName = $routingKey;
			
			$channel->queue_declare(
				$queueName,		// queue name - Queue names may be up to 255 bytes of UTF-8 characters
				false,			// passive - can use this to check whether an exchange exists without modifying the server state
				true,			// durable - make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
				false,			// exclusive - used by only one connection and the queue will be deleted when that connection closes
				false			// autodelete - queue is deleted when last consumer unsubscribes
			);
			
			$msg = new AMQPMessage(
				$message,
				array(
					'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,	// make message persistent, so it is not lost if server crashes or quits
				)
			);
			
			$channel->basic_publish(
				$msg,			// message
				'',				// exchange
				$queueName		// routing key
			);
			
			$channel->close();
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function sendMessageToTopic($routingKey, $message) {
		try {
			$connection = $this->connect();
			
			$channel = $connection->channel();
			
			$msg = new AMQPMessage(
				$message,
				array(
					'delivery_mode' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT,	// make message non persistent
				)
			);
			
			$channel->basic_publish(
				$msg,			// message
				'amq.topic',	// exchange
				$routingKey		// routing key
			);
			
			$channel->close();
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function enqueue($focus, $action, $info, $freeze = false) {
		global $current_user;
		
		if (empty($current_user->user_name) && empty($current_user->accesskey)) return false;
		
		$rabbitmqConnection = $this->getConnectionParams('rabbitmq');
		
		$triggerQueueId = $this->insertIntoTriggerQueue($focus, $action, $info, $freeze);
		
		$data = Zend_Json::encode(array(
			'site_url' => $this->siteURL,
			'rest_url' => $this->restURL,
			'action' => $action,
			'username' => $current_user->user_name,
			'password' => $current_user->accesskey,
			'method' => 'post',
			'params' => array('id' => $triggerQueueId),
		));
		
		$enqueued = $this->execute($rabbitmqConnection['queue_name'], $data);
		if (!$enqueued) {
			$this->deleteFromTriggerQueue($triggerQueueId);
		}
		
		return $enqueued;
	}
	
	protected function insertIntoTriggerQueue($focus, $action, $info, $freeze = false) {
		global $adb, $table_prefix, $current_user;

		$id = $adb->getUniqueID($table_prefix."_trigger_queue");

		$dependent_on = (empty($this->getDependentOn())) ? 0 : $this->getDependentOn(); // try to use the static dependent_on
		if (empty($dependent_on)) {
			// select previous element in queue with the same crmid
			$result = $adb->pquery("select max(id) as \"previous_id\" from {$table_prefix}_trigger_queue where id < ? and crmid = ?", array(intval($id),intval($focus->id)));
			if ($result && $adb->num_rows($result)) {
				$previous_id = $adb->query_result($result,0,'previous_id');
				// select the master_dependent_on of that element
				$result = $adb->pquery("select master_dependent_on from {$table_prefix}_trigger_queue where id = ?", array(intval($previous_id)));
				if ($result && $adb->num_rows($result)) {
					$master_dependent_on = $adb->query_result($result,0,'master_dependent_on');
					// select last element with the same master_dependent_on that will become the dependent_on of the new element
					$result = $adb->pquery("select max(id) as \"dependent_on\" from {$table_prefix}_trigger_queue where master_dependent_on = ?", array(intval($master_dependent_on)));
					if ($result && $adb->num_rows($result)) {
						$dependent_on = $adb->query_result($result,0,'dependent_on');						
					}
				}
			}
		}
		// calc master_dependent_on if empty
		if (empty($master_dependent_on)) {
			if (empty($dependent_on)) {
				$master_dependent_on = $id;
			} else {
				$master_dependent_on = $this->getMaster($dependent_on);
			}
		}
		$this->setMasterDependentOn($id,$master_dependent_on);
		
		$params = array(
			$id,
			$focus->id,
			$focus->modulename,
			$focus->mode,
			$adb->formatDate(date('Y-m-d H:i:s'), true),
			$current_user->id,
			$action,
			$dependent_on,
			$master_dependent_on,
			Zend_Json::encode($info),
			intval($freeze)
		);
		
		$this->setDependentOn($id); // id become the new dependent_on
		
		$adb->pquery("INSERT INTO {$table_prefix}_trigger_queue (id, crmid, module, mode, queue_time, userid, action, dependent_on, master_dependent_on, info, freeze) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $params);
		
		return $id;
	}
	
	public function checkFreezed($crmid) {
		global $adb, $table_prefix;
		static $check = array();
		
		if (!isset($check[$crmid])) {
			$check[$crmid] = false;
			$result = $adb->pquery("SELECT id FROM {$table_prefix}_trigger_queue WHERE crmid = ? and freeze = ?", array($crmid, 1));
			if ($result && $adb->num_rows($result) > 0) {
				$check[$crmid] = true;
			} else {
				require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
				$PMUtils = ProcessMakerUtils::getInstance();
				$brothers = $PMUtils->getRecordsBrothers($crmid, 'running', true);
				$check[$crmid] = !empty($brothers);
			}
		}
		return $check[$crmid];
	}
	
	public function deleteFromTriggerQueue($id) {
		global $adb, $table_prefix;
		$rabbitmqConnection = $this->getConnectionParams('rabbitmq');
		if ($rabbitmqConnection['keep_history']) {
			$adb->pquery("insert into {$table_prefix}_trigger_queue_history select * from {$table_prefix}_trigger_queue WHERE id = ?", array(intval($id)));
		}
		$adb->pquery("DELETE FROM {$table_prefix}_trigger_queue WHERE id = ?", array(intval($id)));
	}
	
	public function lockDependents($id) {
		global $adb, $table_prefix;
		$adb->pquery("UPDATE {$table_prefix}_trigger_queue SET working = ? WHERE master_dependent_on = ?", array(1,intval($this->getMaster($id))));
	}
	
	public function unlockDependents($id) {
		global $adb, $table_prefix;
		$adb->pquery("UPDATE {$table_prefix}_trigger_queue SET working = ? WHERE master_dependent_on = ?", array(0,intval($this->getMaster($id))));		
	}
	
	public function getMaster($id) {
		if ($this->getMasterDependentOn($id) == null) {
			global $adb, $table_prefix;
			$result = $adb->pquery("SELECT master_dependent_on FROM {$table_prefix}_trigger_queue WHERE id = ?", array(intval($id)));
			if ($result && $adb->num_rows($result) > 0) {
				$this->setMasterDependentOn($id,$adb->query_result($result,0,'master_dependent_on'));
			}
		}
		return $this->getMasterDependentOn($id);
	}
	
	public function move2Failed($id, $error='') {
		global $adb, $table_prefix;
		$adb->pquery("insert into {$table_prefix}_trigger_queue_failed select *, '{$error}' from {$table_prefix}_trigger_queue where id = ?", array(intval($id)));
		$this->deleteFromTriggerQueue($id);
	}
	
	public function getConnectionParams($connection = '') {
		if ($connection === 'rabbitmq') {
			return $this->getRabbitmqConnectionParams();
		} elseif ($connection === 'stomp') {
			return $this->getStompConnectionParams();
		} else {
			return array('rabbitmq' => $this->getRabbitmqConnectionParams(), 'stomp' => $this->getStompConnectionParams());
		}
	}
	
	protected function getRabbitmqConnectionParams() {
		$VTEP = VTEProperties::getInstance();
		$rabbitmqConnection = $VTEP->getProperty('performance.mq_rabbitmq_connection');
		
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$rabbitmqConnection['password'] = $encryption->decrypt($rabbitmqConnection['password']);
		
		return $rabbitmqConnection;
	}
	
	protected function getStompConnectionParams() {
		$VTEP = VTEProperties::getInstance();
		$rabbitmqConnection = $VTEP->getProperty('performance.mq_rabbitmq_connection');
		$stompConnection = $VTEP->getProperty('performance.mq_webstomp_connection');
		
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$stompConnection['password'] = $encryption->decrypt($stompConnection['password']);
		
		$stompConnection['defreeze_topic_name'] = $rabbitmqConnection['defreeze_topic_name'];
		$stompConnection['freeze_topic_name'] = $rabbitmqConnection['freeze_topic_name'];
		
		return $stompConnection;
	}
	
	private static function getTriggerQueueConsumer(){
		return self::$triggerQueueConsumer;
	}
	private static function setTriggerQueueConsumer($value){
		self::$triggerQueueConsumer = $value;
	}
	private static function getDependentOn(){
		return self::$dependent_on;
	}
	private static function setDependentOn($value){
		self::$dependent_on = $value;
	}
	private static function getMasterDependentOn($id){
		return self::$master_dependent_on[$id];
	}
	private static function setMasterDependentOn($id,$value){
		self::$master_dependent_on[$id] = $value;
	}
	/*
	 * this function reset consumer status and dependent on flag:
	 * this can be used when multiple save are called and they're not related each other (import/massedit/masscreate/script)
	 */
	public static function activateBatchSave(){
		self::setTriggerQueueConsumer(false);
		self::setDependentOn(null);
	}
	/*
	 * this function set consumer ON:
	 * processes are launched directly, useful for launching processes without queueing them
	 */
	public static function activateConsumer(){
		self::setTriggerQueueConsumer(true);
	}
	/*
	 * this function is useful to check if the consumer is active
	 * true: processes are launched
	 * false: processes are queued
	 */
	public static function isConsumerActive(){
		if (self::$triggerQueueConsumer === true){
			return true;
		}
		return false;
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/WSAPP/synclib/models/SyncStateModel.php';
require_once 'modules/WSAPP/synclib/models/VtenextModel.php';//crmv@208112
require_once 'modules/WSAPP/synclib/models/TargetModel.php';

require_once 'modules/WSAPP/synclib/connectors/VtenextConnector.php';//crmv@208112

require_once 'include/database/PearDatabase.php';

require_once 'modules/WSAPP/api/ws/Register.php';

abstract class WSAPP_SynchronizeController {
	
	const WSAPP_SYNCHRONIZECONTROLLER_USER_SYNCTYPE = 'user';
	const WSAPP_SYNCHRONIZECONTROLLER_APP_SYNCTYPE = 'app';

	const WSAPP_SYNCHRONIZECONTROLLER_PULL_EVENT = 'pull';
	const WSAPP_SYNCHRONIZECONTROLLER_PUSH_EVENT = 'push';
	
	public $user;


	abstract function getTargetConnector();
	abstract function getSourceType();
	abstract function getSyncType();

	function __construct($user) {
		$this->targetConnector = $this->getTargetConnector();
		$this->sourceConnector = $this->getSourceConnector();
		$this->db = PearDatabase::getInstance();
		$this->user = $user;
	}
	
	function getSourceConnector() {
		$connector =  new WSAPP_VtenextConnector();//crmv@208112
		$connector->setSynchronizeController($this);
		$targetName = $this->targetConnector->getName();
		if(empty ($targetName)){
			throw new Exception('Target Name cannot be empty');
		}
		return $connector->setName('Vtenext_'.$targetName);//crmv@208112
	}

	function getTargetRecordModel($data) {
		return new WSAPP_TargetModel($data);
	}

	function getSourceRecordModel($data) {
		return new WSAPP_VtenextModel($data);//crmv@208112
	}

	function getSyncStateModel($connector) {
		return $connector->getSyncState($this->getSourceType())->setType($this->getSourceType());
	}

	function updateSyncStateModel($connector,WSAPP_SyncStateModel $syncStateModel){
		return $connector->updateSyncState($syncStateModel);
	}
	
	public function synchronizePull() {
		$synchronizedRecords = array();
		$sourceType = $this->getSourceType();

		$this->sourceConnector->preEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PULL_EVENT);
		$this->targetConnector->preEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PUSH_EVENT);
		
		$syncStateModel = $this->getSyncStateModel($this->sourceConnector);
		$sourceRecords = $this->sourceConnector->pull($syncStateModel);
		
		foreach($sourceRecords as $record){
			$record->setSyncIdentificationKey(uniqid());
		}
		$transformedRecords = $this->targetConnector->transformToTargetRecord($sourceRecords);
		$targetRecords = $this->targetConnector->push($transformedRecords);
		$targetSyncStateModel = $this->getSyncStateModel($this->targetConnector);
		
		foreach($sourceRecords as $sourceRecord){
			$sourceId = $sourceRecord->getId();
			foreach($targetRecords as $targetRecord){
				if($sourceRecord->getSyncIdentificationKey() == $targetRecord->getSyncIdentificationKey()){
					$sychronizeRecord = array();
					$sychronizeRecord['source'] = $sourceRecord;
					$sychronizeRecord['target'] = $targetRecord;
					$synchronizedRecords[] = $sychronizeRecord;
					break;
				}
			}
		}
		$this->sourceConnector->postEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PULL_EVENT, $synchronizedRecords, $syncStateModel);
		$this->targetConnector->postEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PUSH_EVENT, $synchronizedRecords, $targetSyncStateModel);		
	}

	function synchronizePush(){
		$synchronizedRecords = array();
		$sourceType = $this->getSourceType();

		$this->sourceConnector->preEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PUSH_EVENT);
		$this->targetConnector->preEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PULL_EVENT);
		
		$syncStateModel = $this->getSyncStateModel($this->targetConnector);
		$targetRecords = $this->targetConnector->pull($syncStateModel);
		foreach($targetRecords as $record){
			$record->setSyncIdentificationKey(uniqid());
		}
		$transformedRecords = $this->targetConnector->transformToSourceRecord($targetRecords);
	
		$sourceSyncStateModel = $this->getSyncStateModel($this->sourceConnector);
		$sourceRecords = $this->sourceConnector->push($transformedRecords,$sourceSyncStateModel);

		foreach($targetRecords as $targetRecord){
			$targetId = $targetRecord->getId();
			foreach($sourceRecords as $sourceRecord){
				if($sourceRecord->getSyncIdentificationKey() == $targetRecord->getSyncIdentificationKey()){
					$sychronizeRecord = array();
					$sychronizeRecord['source'] = $sourceRecord;
					$sychronizeRecord['target'] = $targetRecord;
					$synchronizedRecords[] = $sychronizeRecord;
					break;
				}
			}
		}
		
		$this->targetConnector->postEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PULL_EVENT, $synchronizedRecords,$syncStateModel);
		$this->sourceConnector->postEvent(self::WSAPP_SYNCHRONIZECONTROLLER_PUSH_EVENT, $synchronizedRecords, $sourceSyncStateModel);
		$this->updateSyncStateModel($this->sourceConnector,$sourceSyncStateModel);
	}

	public function synchronize($pullTargetFirst = true){
		if($pullTargetFirst){
			$this->synchronizePush();
			$this->synchronizePull();
		}
		else{
			$this->synchronizePull();
			$this->synchronizePush();
		}
	}

}
?>
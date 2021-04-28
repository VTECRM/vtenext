<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/WSAPP/synclib/models/BaseModel.php';

class WSAPP_SyncStateModel extends WSAPP_BaseModel{

	// Represents the module which will the synchronize will happen
	protected $type;

	public function getLastSyncTime(){
		return $this->get('lastSyncTime');
	}

	public function setLastSyncTime($lastSyncTime){
		return $this->set('lastSyncTime',$lastSyncTime);
	}

	public function setMoreRecords($more){
		return $this->set('more',$more);
	}

	public function hasMoreRecords(){
		return ($this->get('more')==1) ? true : false;
	}

	public function getSyncTrackerId(){
		return $this->get('synctrackerid');
	}

	public function setSyncTrackerId($value){
		return $this->set('synctrackerid',$value);
	}

	public function getSyncToken(){
		return $this->get('synctoken');
	}

	public function setSyncToken($syncToken){
		return $this->set('synctoken',$syncToken);
	}

	public function setType($type){
		$this->type = $type;
		return $this;
	}

	public function getType(){
		return $this->type;
	}

	public function getInstanceFromSyncResult($syncResult){
		$model = new self();
		return $model->setLastSyncTime($syncResult['lastModifiedTime'])->setMoreRecords($syncResult['more']);
	}

	public function getInstanceFromQueryResult($rowData){
		$model = new self();
		return $model->setSyncTrackerId($rowData['synctrackerid'])->setSyncToken($rowData['synctoken']);
	}
	
}

?>
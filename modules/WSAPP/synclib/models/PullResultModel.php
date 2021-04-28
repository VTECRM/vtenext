<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/WSAPP/synclib/models/BaseModel.php';

class WSAPP_PullResultModel extends WSAPP_BaseModel{

	public function setPulledRecords($records){
		return $this->set('pulledrecords',$records);
	}

	public function getPulledRecords(){
		return $this->get('pulledrecords');
	}

	public function setNextSyncState(WSAPP_SyncStateModel $syncStateModel){
		return $this->set('nextsyncstate',$syncStateModel);
	}

	public function getNextSyncState(){
		return $this->get('nextsyncstate');
	}

	public function setPrevSyncState(WSAPP_SyncStateModel $syncStateModel){
		return $this->set('prevsyncstate',$syncStateModel);
	}

	public function getPrevSyncState(){
		return $this->get('prevsyncstate');
	}
}

?>
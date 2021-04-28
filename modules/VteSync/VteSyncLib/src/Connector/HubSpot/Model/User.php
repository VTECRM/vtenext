<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@195073
namespace VteSyncLib\Connector\HubSpot\Model;

use VteSyncLib\Model\GenericUser;
use VteSyncLib\Model\CommonUser;

class User extends GenericUser {

	protected $createdTime;
	protected $modifiedTime;

	public static function fromRawData($data) {	
		$id = $data['ownerId'];
		//changing last updated time to readable format
		$date_data = $data['updatedAt'];
		$modTime = new \DateTime();
		$modTime->format('U = Y-m-d H:i:s.u');
		$modTime->setTimestamp($date_data/1000);
		//changing last creation time to readable format
		$cDate = $data['createdAt'];
		$creationTime = new \DateTime();
		$creationTime->format('U = Y-m-d H:i:s.u');
		$creationTime->setTimestamp($cDate/1000);	
		$etag = strval($modTime->getTimestamp());	
		$fields = array(
		    'username' => $data['email'],
			'lastname' => $data['lastName'],
			'firstname' => $data['firstName'],
			'email' => $data['email'],
		);
		$user = new self($id, $etag, $fields);
		$user->rawData = $data;
		$user->createdTime = $creationTime;
		$user->modifiedTime = $modTime;
		return $user;
	}
	
	public static function fromCommonUser(CommonUser $cuser) {
	}
	
	public function toCommonUser() {
		if (empty($this->modifiedTime)) {
			return false;
		}
		$cuser = new CommonUser('HubSpot', 'Users', $this->id, $this->etag, $this->fields, $this->createdTime, $this->modifiedTime);
		return $cuser;
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@196666 */
namespace VteSyncLib\Connector\SuiteCRM\Model;

use VteSyncLib\Model\GenericUser;
use VteSyncLib\Model\CommonUser;

class User extends GenericUser {

	protected $createdTime;
	protected $modifiedTime;

	public static function fromRawData($data) {
		
		$id = $data['id'];
		//changing last updated time to readable format
		$date_data = $data['attributes']['date_modified'];
		$date_data = strtotime($date_data);
		$modTime = new \DateTime();
		$modTime->format('U = Y-m-d H:i:s.u');
		$modTime->setTimestamp($date_data);
	
		//changing last creation time to readable format
		$cDate = $data['attributes']['date_entered'];
		$cDate = strtotime($date_data);
		$creationTime = new \DateTime();
		$creationTime->format('U = Y-m-d H:i:s.u');
		$creationTime->setTimestamp($cDate);	
		$etag = strval($modTime->getTimestamp());	
		$fields = array(
		    'username' => $data['attributes']['user_name'],
			'lastname' => $data['attributes']['last_name'],
			'firstname' => $data['attributes']['first_name'],
			'email' => $data['attributes']['email1'],
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
		$cuser = new CommonUser('SuiteCRM', 'Users', $this->id, $this->etag, $this->fields, $this->createdTime, $this->modifiedTime);
		return $cuser;
	}
}
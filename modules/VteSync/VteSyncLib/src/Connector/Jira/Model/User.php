<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\Jira\Model;

use VteSyncLib\Model\GenericUser;
use VteSyncLib\Model\CommonUser;

class User extends GenericUser {

	protected $createdTime;
	protected $modifiedTime;

	public static function fromRawData($data) {
		
		$id = $data->accountId;
		
		// no modification time is available for users... :(
		$modTime = new \DateTime();
		
		// map the fields
		$fields = array(
			'username' => 'jira.'.$data->name, // there might be duplicates then
			'lastname' => $data->displayName,
			'firstname' => '',
			'email' => $data->emailAddress ?: 'nojiraemail@example.com',
			'active' => $data->active,
			'timezone' => $data->timeZone,
			'language' => $data->locale,
		);
		
		// just make an hash of the common fields
		$etag = md5($fields['lastname'].$fields['email'].$fields['active'].$fields['timezone'].$fields['language']);
		
		$user = new self($id, $etag, $fields);
		$user->rawData = $data;
		//$user->createdTime = new \DateTime($data['CreatedDate']);
		$user->modifiedTime = $modTime;
		return $user;
	}
	
	public static function fromCommonUser(CommonUser $cuser) {
	}
	
	public function toCommonUser() {
		if (empty($this->modifiedTime)) {
			return false;
		}
		$cuser = new CommonUser('Jira', 'Users', $this->id, $this->etag, $this->fields, $this->createdTime, $this->modifiedTime);
		return $cuser;
	}
}

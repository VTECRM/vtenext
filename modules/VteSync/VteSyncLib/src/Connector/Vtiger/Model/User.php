<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@197423 */

namespace VteSyncLib\Connector\Vtiger\Model;

use VteSyncLib\Model\GenericUser;
use VteSyncLib\Model\CommonUser;

class User extends GenericUser {

	protected $createdTime;
	protected $modifiedTime;

	public static function fromRawData($data) {
		
		$id = $data['id'];
		$id = explode("x", $id);
		$id = $id[1];
		
		$modTime = new \DateTime();
		
		// map the fields
		$fields = array(
			'username' => $data["user_name"], 
			'lastname' => $data["last_name"],
			'firstname' => $data["first_name"],
			'email' => $data["email1"] ,
			'timezone' => $data["time_zone"],
		);
		
		// just make an hash of the common fields
		$etag = md5($fields['lastname'].$fields['email'].$fields['timezone']);
		
		$user = new self($id, $etag, $fields);
		$user->rawData = $data;

		$user->modifiedTime = $modTime;
		return $user;
	}
	
	public static function fromCommonUser(CommonUser $cuser) {
	}
	
	public function toCommonUser() {
		if (empty($this->modifiedTime)) {
			return false;
		}
		$cuser = new CommonUser('Vtiger', 'Users', $this->id, $this->etag, $this->fields, $this->createdTime, $this->modifiedTime);
		return $cuser;
	}
}

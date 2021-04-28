<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\VTE\Model;

use VteSyncLib\Model\GenericUser;
use VteSyncLib\Model\CommonUser;

class User extends GenericUser {

	// TODO
	/*public static function fromRawData($data) {
		$id = $data['Id'];
		$fields = array(
			'language' => $data['LanguageLocaleKey'],

		);
		$user = new static($id, $fields);
		$user->rawData = $data;
		return $user;
	}*/
	
	public static function fromCommonUser(CommonUser $cuser) {
		$id = $cuser->getId('VTE');
		$etag = $cuser->getEtag('VTE');
		$cfields = $cuser->getFields();
		$fields = array(
			'user_name' => $cfields['username'],
			'first_name' => $cfields['firstname'],
			'last_name' => $cfields['lastname'],
			'email1' => $cfields['email'],
			//'status' => $cfields['active'] ? 'Active' : 'Inactive', // not mapped, to force inactive state!
			'title' => $cfields['title'],
			'phone_work' => $cfields['phone'],
			'phone_mobile' => $cfields['mobile'],
			'phone_fax' => $cfields['fax'],
			'department' => $cfields['department'],
			'user_timezone' => $cfields['timezone'],
			'default_language' => strtolower($cfields['language']),
			// address
			'address_street' => $cfields['street'],
			'address_city' => $cfields['city'],
			'address_state' => $cfields['state'],
			'address_postalcode' => $cfields['postalcode'],
			'address_country' => $cfields['country'],
		);
		$user = new self($id, $etag, $fields);
		return $user;
	}
	
	public function toCommonUser() {
	}
}

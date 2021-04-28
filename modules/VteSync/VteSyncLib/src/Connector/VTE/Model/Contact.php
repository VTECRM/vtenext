<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\VTE\Model;


class Contact extends GenericVTERecord {

	protected static $staticModule = 'Contacts';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'salutationtype' => 'salutation',
		'lastname' => 'lastname',
		'firstname' => 'firstname',
		'account_id' => 'accountid',
		'phone' => 'phone',
		'fax' => 'fax',
		'mobile' => 'mobile',
		'homephone' => 'homephone',
		'otherphone' => 'otherphone',
		'assistantphone' => 'assistantphone',
		'email' => 'email',
		'title' => 'title',
		'department' => 'department',
		'assistant' => 'assistant',
		'birthday' => 'birthday',
		'leadsource' => 'leadsource',
		'description' => 'description',
		
		// mailing address
		'mailingstreet' => 'mailingstreet',
		'mailingcity' => 'mailingcity',
		'mailingzip' => 'mailingpostalcode',
		'mailingstate' => 'mailingstate',
		'mailingcountry' => 'mailingcountry',
		// other address
		'otherstreet' => 'otherstreet',
		'othercity' => 'othercity',
		'otherzip' => 'otherpostalcode',
		'otherstate' => 'otherstate',
		'othercountry' => 'othercountry',
	);

	/*public static function fromRawData($data) {
		$record = parent::fromRawData($data);
		
		// convert local format to generic format
		if (!empty($record->fields['birthday'])) {
			$bday = $record->fields['birthday'];
			preprint($bday, true);
		}
		
		return $record;
	}*/
}
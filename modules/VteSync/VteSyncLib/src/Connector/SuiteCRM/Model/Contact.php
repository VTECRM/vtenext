<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@196666 */
namespace VteSyncLib\Connector\SuiteCRM\Model;

class Contact extends GenericSuiteRecord {

	protected static $staticModule = 'Contacts';
	
	protected static $fieldMap = array(
		// Suite => CommonRecord
		'salutation' => 'salutationtype',
		'last_name' => 'lastname',
		'first_name' => 'firstname',
		'account_id' => 'accountid',
		'phone_work' => 'phone',
		'phone_fax' => 'fax',
		'phone_mobile' => 'mobile',
		'phone_home' => 'homephone',
		'phone_other' => 'otherphone',
		'assistant_phone' => 'assistantphone',
		'email_addresses' => 'email',
		'title' => 'title',
		'department' => 'department',
		'assistant' => 'assistant',
		'birthdate' => 'birthday',
		'lead_source' => 'leadsource',
		'description' => 'description',
		
		// mailing address
		'primary_address_street' => 'mailingstreet',
		'primary_address_city' => 'mailingcity',
		'primary_address_postalcode' => 'mailingpostalcode',
		'primary_address_state' => 'mailingstate',
		'primary_address_country' => 'mailingcountry',
		// other address
		'alt_address_street' => 'otherstreet',
		'alt_address_city' => 'othercity',
		'alt_address_postalcode' => 'otherpostalcode',
		'alt_address_state' => 'otherstate',
		'alt_address_country' => 'othercountry',
	);
	public static function extractId($data) {
		return $data['id'];
	}
	
	public static function extractOwner($data) {
		return $data['attributes']['assigned_user_name'];
		
	}

	public static function extractCreatedTime($data) {
		$cDate = $data['attributes']['date_entered'];
		$cDate = strtotime($cDate);
		$creationTime = new \DateTime();
		$creationTime->format('U = Y-m-d H:i:s.u');
		$creationTime->setTimestamp($cDate);
		return $creationTime;
	}
	
	public static function extractModifiedTime($data) {
		$date_data = $data['attributes']['date_modified'];
		$date_data = strtotime($date_data);
		$modTime = new \DateTime();
		$modTime->format('U = Y-m-d H:i:s.u');
		$modTime->setTimestamp($date_data);
		return $modTime;
	}
	
	public static function extractEtag($data) {
		$lastmod = static::extractModifiedTime($data);
		$etag = strval($lastmod->getTimestamp().$lastmod->format('u'));
		return $etag;
	}
	
	public static function fromRawData($data) {
	
		$id = static::extractId($data);
		$ownerid = static::extractOwner($data);
		$creatTime = static::extractCreatedTime($data);
		$modTime = static::extractModifiedTime($data);
		$etag = static::extractEtag($data);	
				
		$arr_fields = [];
			
		foreach ($data['attributes'] as $key => $value)
		{   
			$arr_fields[$key] = $value;
		}	
			
		$fields = array_intersect_key($arr_fields, static::$fieldMap);
		
		
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		return $record;
	}
	
	// if needed, you can override methods and change fields/behaviour
}


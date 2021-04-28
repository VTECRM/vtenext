<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@196666 */
namespace VteSyncLib\Connector\SuiteCRM\Model;

class Lead extends GenericSuiteRecord {

	protected static $staticModule = 'Leads';
	
	protected static $fieldMap = array(
		// Suite => CommonRecord
		'title' => 'title',
		'salutation' => 'salutation',
		'last_name' => 'lastname',
		'first_name' => 'firstname',
		'account_name' => 'company',
		'email1' => 'email',
		'phone_work' => 'phone',
		'phone_mobile' => 'mobile',
		'phone_fax' => 'fax',
		'status' => 'status',
		'website' => 'website',
		'lead_source' => 'source',
		'description' => 'description',
		// address
		'primary_address_street' => 'street',
		'primary_address_city' => 'city',
		'primary_address_postalcode' => 'postalcode',
		'primary_address_state' => 'state',
		'primary_address_country' => 'country',
		
		
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
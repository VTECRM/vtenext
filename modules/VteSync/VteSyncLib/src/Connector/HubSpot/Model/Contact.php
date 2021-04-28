<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@195073
namespace VteSyncLib\Connector\HubSpot\Model;


class Contact extends GenericHSRecord {
    
	protected static $staticModule = 'Contacts';

		protected static $fieldMap = array(
		// HS => CommonRecord
		'lastname' => 'lastname',
		'firstname' => 'firstname',
		'phone' => 'phone',
		'fax' => 'fax',
		'mobilephone'=>'mobile',
		'email' => 'email',
		'jobtitle' => 'title',
		'lifecyclestage'=>'lifecyclestage',
		'hs_lead_status'=>'leadstatus',
		);

	
	public static function extractId($data) {
		return $data['vid'];
	}
	
	public static function extractOwner($data) {
		return $data['properties']['hubspot_owner_id']['value'];
	}

	public static function extractCreatedTime($data) {
		$cDate = $data['properties']['createdate']['value'];
		$creationTime = new \DateTime();
		$creationTime->format('U = Y-m-d H:i:s.u');
		$creationTime->setTimestamp($cDate/1000);
		return $creationTime;
	}
	
	public static function extractModifiedTime($data) {
		$date_data = $data['properties']['lastmodifieddate']['value'];
		$modTime = new \DateTime();
		$modTime->format('U = Y-m-d H:i:s.u');
		$modTime->setTimestamp($date_data/1000);
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
		$email_val = $data['properties']['email']['value'];
		$data['properties']['email'] = $email_val;
		$fields = array_intersect_key($data['properties'], static::$fieldMap);
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		return $record;
	}
}

<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@195073
namespace VteSyncLib\Connector\HubSpot\Model;

class Potential extends GenericHSRecord {

	protected static $staticModule = 'Potentials';
	
	protected static $fieldMap = array(
		// HS => CommonRecord
		'dealname' => 'name',
		'amount' => 'amount',
        'dealstage' => 'sales_stage',
        'closedate' => 'closingdate',
		'dealtype' => 'type',
		'associatedCompanyIds'=>'related_to'
	);

	public static function extractId($data) {
		return $data['dealId'];
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
		$date_data = $data['properties']['hs_lastmodifieddate']['value'];
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
		$arr_fields = [];
		foreach ($data['associations']['associatedCompanyIds'] as $key =>$value)
		{   
			$arr_fields['associatedCompanyIds'] = $value;			
		}
		foreach ($data['properties'] as $key => $value)
		{   
			$arr_fields[$key] = $value['value'];
		}	  
		$fields = array_intersect_key($arr_fields, static::$fieldMap);	
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		return $record;
	}
}
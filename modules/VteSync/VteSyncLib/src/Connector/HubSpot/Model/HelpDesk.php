<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@195073
namespace VteSyncLib\Connector\HubSpot\Model;

class HelpDesk extends GenericHSRecord {

	protected static $staticModule = 'HelpDesk';
	
	protected static $fieldMap = array(
		// HS => CommonRecord
		'subject' => 'subject',
		'hs_pipeline_stage' => 'status',
		'hs_ticket_priority' => 'priority',
		'content' => 'description',
		'hs_num_associated_companies'=>'related_to'
	);

	public static function extractId($data) {		
		return $data['objectId'];
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
		//convert piple stages to more readable format
			if($data['properties']['hs_pipeline_stage']['value'] == '1'){
					$data['properties']['hs_pipeline_stage']['value'] = 'New ';
			}
			if($data['properties']['hs_pipeline_stage']['value'] == '2'){
					$data['properties']['hs_pipeline_stage']['value'] = 'Waiting on contact';
			}
			if($data['properties']['hs_pipeline_stage']['value'] == '3'){
					$data['properties']['hs_pipeline_stage']['value'] = 'Waiting on us';
			}
			if($data['properties']['hs_pipeline_stage']['value'] == '4'){
					$data['properties']['hs_pipeline_stage']['value'] = 'Closed';
			}		
		$id = static::extractId($data);
		$ownerid = static::extractOwner($data);
		$creatTime = static::extractCreatedTime($data);
		$modTime = static::extractModifiedTime($data);
		$etag = static::extractEtag($data);
		$arr_keys = [];
		foreach ($data['properties'] as $key => $value)
		{
			$arr_keys[$key] = $value['value'];
		}
		$fields = array_intersect_key($arr_keys, static::$fieldMap);
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		return $record;
	}
}
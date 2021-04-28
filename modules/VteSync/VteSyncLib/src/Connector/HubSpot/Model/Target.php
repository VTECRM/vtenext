<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@195073

namespace VteSyncLib\Connector\HubSpot\Model;

class Target extends GenericHSRecord {

	protected static $staticModule = 'Targets';
	
	protected static $fieldMap = array(
		// HS => CommonRecord
		'name' => 'name'
	);

	public static function extractId($data) {
		return $data['listId'];
	}
	
	public static function extractOwner($data) {
		return $data['listId'];
	}

	public static function extractCreatedTime($data) {
		$cDate = $data['createdAt'];
		$creationTime = new \DateTime();
		$creationTime->format('U = Y-m-d H:i:s.u');
		$creationTime->setTimestamp($cDate/1000);
		return $creationTime;
	}
	
	public static function extractModifiedTime($data) {
		$date_data = $data['updatedAt'];
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
		$fields = array_intersect_key($data, static::$fieldMap);
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		return $record;
	}
}
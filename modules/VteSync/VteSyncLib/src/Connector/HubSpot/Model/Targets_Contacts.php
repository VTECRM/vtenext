<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*crmv@195073*/
namespace VteSyncLib\Connector\HubSpot\Model;

class Targets_Contacts extends GenericHSRecord {

	protected static $staticModule = 'Targets_Contacts';
	
	protected static $fieldMap = array(
		'listId' => 'targetid',
		'vid' => 'contactid',
	);

public static function extractId($data) {
	return $data['vid'];
	}
	
	public static function extractOwner($data) {
		return false;
	}

	public static function extractCreatedTime($data) {
		$ceatedTime = new \DateTime();
		return $ceatedTime;
	}
	
	public static function extractModifiedTime($data) {
		$modTime = new \DateTime();
		return $modTime;
	}
	
	public static function extractEtag($data) {
		return false;
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
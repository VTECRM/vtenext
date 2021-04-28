<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@196666 */
namespace VteSyncLib\Connector\SuiteCRM\Model;

class HelpDesk extends GenericSuiteRecord {

	protected static $staticModule = 'HelpDesk';
	
	protected static $fieldMap = array(
		// Suite => CommonRecord
		'name' => 'subject',
		'contact_created_by_id' => 'contactid',
		'account_id' => 'accountid',
		'state' => 'status',
		'type' => 'type',
		'priority' => 'priority',
		'description' => 'description',
		'resolution' => 'solution',
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
		if($fields["priority"] == "P1")
		{
			$fields["priority"] = "High";
		}
		if($fields["priority"] == "P2")
		{
			$fields["priority"] = "Medium";
		}
		if($fields["priority"] == "P3")
		{
			$fields["priority"] = "Low";
		}
		if($fields["status"] == "Open_New")
		{
			$fields["status"] = "New";
		}
		if($fields["status"] == "Open_Assigned")
		{
			$fields["status"] = "Assigned";
		}
		if($fields["status"] == "Open_Pending Input")
		{
			$fields["status"] = "Pending Input";
		}
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		
		return $record;
	}
	
	public function toRawData($mode) {
		if($this->fields["priority"] == "High")
		{
			$this->fields["priority"] = "P1";
		}
		if($this->fields["priority"] == "Medium")
		{
			$this->fields["priority"] = "P2";
		}
		if($this->fields["priority"] == "Low")
		{
			$this->fields["priority"] = "P3";
		}
		if($this->fields["status"] == "New")
		{
			$this->fields["status"] = "Open_New";
		}
		if($this->fields["status"] == "Assigned")
		{
			$this->fields["status"] = "Open_Assigned";
		}
		if($this->fields["status"] == "Pending Input")
		{
			$this->fields["status"] = "Open_Pending Input";
		}
		
		return $this->fields;
	}

	// if needed, you can override methods and change fields/behaviour
}
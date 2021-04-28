<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\Jira\Model;

class Project extends GenericJiraRecord {

	protected static $staticModule = 'ProjectPlan';
	
	protected static $fieldMap = array(
		// Jira => CommonRecord
		'Name' => 'name',
		'StartDate' => 'start_date',
		'TargetEndDate' => 'target_end_date',
		'ActualEndDate' => 'actual_end_date',
		'Status' => 'status',
		'Type' => 'type',
		'Url' => 'url',
		'Description' => 'description',
		
	);

	public static function extractOwner($data) {
		return $data->lead->accountId;
		
	}
	public static function extractAssignee($data) {
		
		return $data->owner;
	}
	public static function extractEtag($data) {
		// no native etag or timestamp... just join togheter various fields and hash them
		$string = $data->key.'#'.$data->name.'#'.$data->url.'#'.$data->description;
		$etag = md5($string);
		return $etag;
	}
	
	public static function fromRawData($data) {
		$id = static::extractId($data);
		$ownerid = static::extractOwner($data);
		
		$creatTime = static::extractCreatedTime($data);
		$modTime = static::extractModifiedTime($data);
		$etag = static::extractEtag($data);
		
		$fields = array(
			'Name' => $data->name,
			'Key' => $data->key,
			'Description' => $data->description,
			'Type' => $data->projectTypeKey,
			'Url' => $data->url,
			
		);
		
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		
		return $record;
	}
	
	public function toRawData($mode) {
		$p = new \JiraRestApi\Project\Project();

		$values = $this->fields;
		
		
		$owner = static::extractAssignee($this);
		//setLead
		if ($values['Name']) $p->setName($values['Name']);
		if ($values['Key']) $p->setKey($values['Key']);
		if ($values['Type']) $p->setProjectTypeKey($values['Type']);
		if ($values['AssigneeType']) $p->setAssigneeType($values['AssigneeType']);
		
		if ($owner) $p->setLeadAccountId($owner);
		if ($values['Url']) {
			// url must begin with the schema
			if (!preg_match('/^http/', $values['Url'])) {
				$values['Url'] = 'http://'.$values['Url'];
			}
			$p->setUrl($values['Url']);
		}
		if ($values['Description']) $p->setDescription($values['Description']);
		
		return $p;
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\Jira\Model;

class Epic extends GenericJiraRecord {

	protected static $staticModule = 'ProjectTask';
	
	protected static $fieldMap = array(
		// Jira => CommonRecord
		'Summary' => 'subject',
		'Status' => 'status',
		'Priority' => 'priority',
		'Project' => 'projectid',
		'Description' => 'description',
		'StartDate' => 'start_date', // custom field
		'DueDate' => 'end_date',
	);
	
	public static function extractOwner($data) {
		return $data->fields->assignee; // TODO:
		
	}

	public static function extractCreatedTime($data) {
		return $data->fields->created;
	}
	
	//public static function extractModifiedTime($data) {
	//	return $data->fields->updated;
	//}
	
	
	public static function fromRawData($data) {
		$id = static::extractId($data);
		$ownerid = static::extractOwner($data);
		$creatTime = static::extractCreatedTime($data);
		$modTime = static::extractModifiedTime($data);
		$etag = static::extractEtag($data);
		
		$jiraFields = $data->fields;
		$fields = array(
			'Summary' => $jiraFields->summary,
			'StartDate' => $jiraFields->customfield_10015,
			//'Type' => $jiraFields->issuetype->name,
			'Status' => $jiraFields->status->name,
			'Priority' => $jiraFields->priority->name,
			'Project' => $jiraFields->project->id,
			'DueDate' => $jiraFields->duedate, // Y-m-d
			'Description' => $jiraFields->description,
		);
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		
		return $record;
	}
	
	public function toRawData($mode) {
		
		$p = new \JiraRestApi\Issue\IssueField($mode == 'update');
		
		$values = $this->fields;

		if ($values['Summary']) $p->setSummary($values['Summary']);
		if ($values['Project']) @$p->setProjectId($values['Project']);
		//if ($values['Priority']) $p->setPriorityName($values['Priority']); // TODO handle this!
		if ($values['DueDate']) $p->setDueDate($values['DueDate']);
		if ($values['Description']) $p->setDescription($values['Description']);
		
		$p->setIssueType('Epic');
		
		return $p;
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\Jira\Model;

class Ticket extends GenericJiraRecord {

	protected static $staticModule = 'HelpDesk';
	
	protected static $fieldMap = array(
		// Jira => CommonRecord
		'Summary' => 'subject',
		'Type' => 'type',
		'Status' => 'status',
		'Priority' => 'priority',
		'Project' => 'projectid',
		'Parent' => 'projecttaskid',
		'Description' => 'description',
		'Assignee' => 'assignee',
		'Comment' => 'comment',
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
		$owner = static::extractOwner($data);
		$etag = static::extractEtag($data);
		
		$jiraFields = $data->fields;
		
		$fields = array(
			'Summary' => $jiraFields->summary,
			'Type' => $jiraFields->issuetype->name,
			'Status' => $jiraFields->status->name,
			'Priority' => $jiraFields->priority->name,
			'Project' => $jiraFields->project->id,
			'Parent' => $jiraFields->parent ? $jiraFields->parent->id : null,
			'Description' => $jiraFields->description,
			'Assignee' => $jiraFields->assignee,
			
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
		if ($values['Type']) $p->setIssueType($values['Type']);
		if ($values['Project']) @$p->setProjectId($values['Project']);
		if ($values['Parent']) $p->setParentKeyOrId($values['Parent']);
		//if ($values['Comment']) $p->setComment($values['Comment']);
		//if ($values['Priority']) $p->setPriorityName($values['Priority']); // TODO handle this!
		if ($values['Description']) $p->setDescription($values['Description']);
		
		return $p;
	}
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\Jira\Model;

class TicketComment extends GenericJiraRecord {

	protected static $staticModule = 'TicketComment';
	
	protected static $fieldMap = array(
		'Comment' => 'comment',
		'CommentId' => 'commentId',
		'Summary' => 'subject',
		
	);

	public static function extractOwner($data) {
		return $data->author->displayName; 
	}
	public static function extractCreatedTime($data) {
		return $data->created;
	}
	
	public static function extractCommentsText($data) {
		
		return $data->body;
	}
	
	
	public static function fromRawData($data) {
		
		$id = static::extractId($data);
		
		
		$modTime = static::extractModifiedTime($data);
		
		$etag = static::extractEtag($data);
		
	
		$jiraFields = $data->fields;
		
		$i=0;
		foreach($jiraFields->comment->comments as $comment)
		{
			
			$ownerid = static::extractOwner($comment);
			$creatTime = static::extractCreatedTime($comment);
			
		$fields = array(
			'Comment' => $comment->body,
			'CommentId' => $comment->id,
			'Summary' => $jiraFields->summary,
			
		);
		
		$record[] = new static(static::$staticModule, $id, $etag, $fields);
		
		$record[$i]->owner = $ownerid;
		$record[$i]->rawData = $data;
		$record[$i]->createdTime = $creatTime;
		$record[$i]->modifiedTime = $modTime;
		$i++;
		}
		return $record;
	}
	
	public function toRawData($mode) {
		
		$p = new \JiraRestApi\Issue\IssueField($mode == 'update');
		
		$values = $this->fields;

		if ($values['Summary']) $p->setSummary($values['Summary']);
		if ($values['Type']) $p->setIssueType($values['Type']);
		if ($values['Project']) @$p->setProjectId($values['Project']);
		if ($values['Parent']) $p->setParentKeyOrId($values['Parent']);
		//if ($values['Priority']) $p->setPriorityName($values['Priority']); // TODO handle this!
		if ($values['Description']) $p->setDescription($values['Description']);
			
		return $p;
	}
}
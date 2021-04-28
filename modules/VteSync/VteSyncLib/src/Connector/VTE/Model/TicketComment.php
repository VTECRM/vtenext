<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\VTE\Model;

class TicketComment extends GenericVTERecord {

	protected static $staticModule = 'TicketComment';
	
	protected static $fieldMap = array(
		'comments'=>'comment',
		'commentid' => 'commentId',
		'projectid' => 'projectid',
		'projecttaskid'	=> 'projecttaskid'
		// VTE => CommonRecord
		
	);
	
	/* public static function extractId($data) {
		return $data['commentid'];
	}
	
	public static function extractOwner($data) {
		return $data['ownerid'];
	}
	
	public static function fromRawData($data) {
		
		$id = static::extractId($data);
		$ownerid = static::extractOwner($data);
		$creatTime = static::extractCreatedTime($data);
		$etag = static::extractEtag($data);
		$fields = array(
			'TicketId' => $data->ticketid,
			'comments' => $data->comments,	
		);
		
		
		
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		return $record;
	} */
	


	// if needed, you can override methods and change fields/behaviour	
}
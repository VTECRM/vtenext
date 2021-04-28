<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@197423 */

namespace VteSyncLib\Connector\Vtiger\Model;

class HelpDesk extends GenericVtigerRecord {

	protected static $staticModule = 'Cases';
	
	protected static $fieldMap = array(
		// Vtiger => CommonRecord
		'title' => 'subject',
		'contact_id' => 'contactid',
		'casestatus' => 'status',
		'casepriority' => 'priority',
		'description' => 'description',
		'contact_id' => 'parent_id', // crmv@197423
	);

	public static function extractId($data) {
		
		return $data['id'];
	}
	
	public static function extractOwner($data) {
		return $data['assigned_user_id'];
		
	}

	public static function extractCreatedTime($data) {
		$cDate = $data['createdtime'];
		$cDate = strtotime($cDate);
		$creationTime = new \DateTime();
		$creationTime->format('U = Y-m-d H:i:s.u');
		$creationTime->setTimestamp($cDate);
		return $creationTime;
	}
	
	public static function extractModifiedTime($data) {
		$date_data = $data['modifiedtime'];
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
		global $adb;
		
		$id = static::extractId($data);
		$ownerid = static::extractOwner($data);
		$creatTime = static::extractCreatedTime($data);
		$modTime = static::extractModifiedTime($data);
		$etag = static::extractEtag($data);	
				
			
		$owner = explode('x',$ownerid);
			
		$fields = array_intersect_key($data, static::$fieldMap);
		//crmv@197423 
		$res = $adb->pquery('SELECT groupid  FROM vte_vtesync_vsl_mapping where conn_id = ? AND module = ? AND connector = ?',array($fields["contact_id"],"Contacts","Vtiger"));
		$groupid = $adb->query_result_no_html($res,0,'groupid');
		
		$res = $adb->pquery('SELECT conn_id FROM vte_vtesync_vsl_mapping where groupid = ? AND module = ? AND connector = ?',array($groupid,"Contacts","VTE"));
		$idContact = $adb->query_result_no_html($res,0,'conn_id');
		
		$fields["contact_id"] = $idContact;
		$fields["description"] = strip_tags($fields["description"]);
		//crmv@197423e
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $owner[1];
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
	
		return $record;
	}
	// if needed, you can override methods and change fields/behaviour
}
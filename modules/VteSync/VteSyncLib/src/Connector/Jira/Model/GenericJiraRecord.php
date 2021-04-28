<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@190016 */

namespace VteSyncLib\Connector\Jira\Model;

use VteSyncLib\Model\GenericRecord;
use VteSyncLib\Model\CommonRecord;

class GenericJiraRecord extends GenericRecord {

	protected static $connector = 'Jira';
	
	public static function extractId($data) {
		return $data->id;
	}
	
	public static function extractOwner($data) {
		return null; // No generic owner available
	}
	
	public static function extractCreatedTime($data) {
		return new \DateTime(); // No generic timestamp available
	}
	
	public static function extractModifiedTime($data) {
		return new \DateTime(); // No generic timestamp available
	}
	
	public static function extractEtag($data) {
		$lastmod = static::extractModifiedTime($data);
		
		$etag = strval($lastmod->getTimestamp().$lastmod->format('u'));
		return $etag;
	}
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\VTE\Model;

use VteSyncLib\Model\GenericRecord;
use VteSyncLib\Model\CommonRecord;

class GenericVTERecord extends GenericRecord {

	protected static $connector = 'VTE';
	
	public static function extractId($data) {
		return $data['id'];
	}
	
	public static function extractOwner($data) {
		return $data['assigned_user_id'];
	}
	
	public static function extractCreatedTime($data) {
		return new \DateTime($data['createdtime']);
	}
	
	public static function extractModifiedTime($data) {
		return new \DateTime($data['modifiedtime']);
	}
	
	public static function extractEtag($data) {
		$lastmod = static::extractModifiedTime($data);
		$etag = strval($lastmod->getTimestamp());
		return $etag;
	}
	
}
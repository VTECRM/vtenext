<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Storage;

interface StorageInterface {

	public function __construct($config = array());
	
	public function connect();
	
	public function initSchema();

	public function resetAll();
	public function resetConnector($connector);
	
	public function getLastSyncDate($vteuser, $connector, $module);
	public function setLastSyncDate($user, $connName, $module, $date = null);
	
	public function getGroupId($connector, $module, $id);
	public function getMappedIds($connector, $module, $id);
	public function saveMappedIds($connector, $module, $id, $otherids = array(), $etags = array());
	
	public function getEtag($connector, $module, $id);
	public function setEtag($connector, $module, $id, $etag);
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector;

interface ConnectorInterface {

	public function __construct($config = array(), $storage = null);
	
	public function connect();
	public function isConnected();
	
	public function pull($module, $userinfo, \DateTime $date = null, $maxEntries = 100);
	public function push($module, $userinfo, &$records);
	public function pushMeta($module, $metaDiff);
	
	public function getObject($module, $id);
	public function objectExists($module, $id);
	public function setObject($module, $id, $object);
	public function deleteObject($module, $id);
	
	public function getStorage();
	public function setStorage(\VteSyncLib\Storage\StorageInterface $storage);

	public function canHandleModule($module);
	
}
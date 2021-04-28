<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('BackendBase.php');
require_once('include/utils/FileStorage.php');

/**
 * This is the backend for local file storage.
 * This class doesn't do much, it's mostly a wrapper around FileStorage class,
 * but it conforms to the Backend interface
 */
class BackendFile extends VTEBackendBase {

	public $name = 'file';
	public $hasMetadata = false;
	
	/**
	 *
	 */
	public function saveFile($parentfocus, $options = array(), &$attid = null) {
		$ekey = null;
		
		$FS = FileStorage::getInstance();
		
		if ($options && $options['storage_path']) {
			$FS->changeStorageFolder($options['storage_path']);
			$pathChanged = true;
		}
		
		$saved= $FS->insertIntoAttachment($parentfocus->id, $parentfocus->modulename, $parentfocus);
		$contentid = $FS->getLastInsertedId();
		
		if ($pathChanged) {
			$FS->restoreStorageFolder();
		}
		
		if ($saved && $contentid) {
			// retrieve the key
			$ekey = $this->generateKey($contentid);
			$attid = $contentid;
		}
		
		return $ekey;
	}
	
	/**
	 *
	 */
	public function saveFileRevision($oldkey, $parentfocus, $options = array(), &$attid = null) {
		return $this->saveFile($parentfocus, $options, $attid);
	}
	
	/**
	 * Download a file
	 */
	public function retrieveFile($attid, $key, $options = array()) {
		$FS = FileStorage::getInstance();
		return $FS->downloadFile($attid, $options); // crmv@189246
	}
	
	/**
	 * Remove a file from the storage
	 */
	public function deleteFile($attid, $key, $options = array()) {
		$FS = FileStorage::getInstance();
		
		$info = $FS->getFileInfo($attid);
		$path = $info['local_path'];
		
		if (!empty($path) && file_exists($path)) {
			// delete the file
			return unlink($path);
		}
		
		return true;
	}
	
	/**
	 * Generate a fake key for the file backend: crmid:fullpath
	 */
	public function generateKey($attid) {
		
		$FS = FileStorage::getInstance();
		
		$key = null;
		$info = $FS->getFileInfo($attid);
		if ($info) {
			$key = $info['attachmentsid'].':'.$info['path'].$info['attachmentsid'].'_'.$info['name'];
		}
		
		return $key;
	}
	
	/**
	 *
	 */
	public function checkIntegrity($attid, $key, $options = array()) {
		$FS = FileStorage::getInstance();
		
		return $FS->checkIntegrity($attid);
	}
	
	/**
	 * Not supported
	 */
	public function readMetadata($attid, $key, $options = array()) {
		return array();
	}
	
	/**
	 * Not supported
	 */
	public function updateMetadata($attid, $key, $data, $options = array()) {
		return true;
	}
	
}
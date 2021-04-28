<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

interface VTEStorageBackend {

	public function getLabel();
	
	public function saveFile($parentfocus, $options = array(), &$attid = null);
	
	public function saveFileRevision($oldkey, $parentfocus, $options = array(), &$attid = null);
	
	public function retrieveFile($attid, $key, $options = array());
	
	public function deleteFile($attid, $key, $options = array());
	
	public function checkIntegrity($attid, $key, $options = array());
	
	public function incrementDownloadCount($key);
	
	public function readMetadata($attid, $key, $options = array());
	
	public function updateMetadata($attid, $key, $data, $options = array());

}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('BackendInterface.php');

abstract class VTEBackendBase extends SDKExtendableUniqueClass implements VTEStorageBackend {

	public $name;
	public $hasMetadata = true;
	
	public function getLabel() {
		$key = 'LBL_STORAGE_BACKEND_'.strtoupper($this->name);
		return getTranslatedString($key, 'Documents');
	}
	
	public function incrementDownloadCount($key) {
		// do nothing by default
		return true;
	}
	
}
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@24153 crmv@95157 */

require_once('modules/Documents/storage/StorageBackendUtils.php');

function upload_files_ws($record,$module,$userid,$files,$email_id,$zimbra_url,$zimbra_user) {
	//throw new WebServiceException('DEBUG','record: '.$record.', module: '.$module.', userid: '.$userid.', files: '.print_r($files,true).', email_id: '.$email_id.', zimbra_url: '.$zimbra_url.', zimbra_user: '.$zimbra_user);
	if (is_array($files) && !empty($files)) {
		foreach($files as $file => $part) {
			$url = "$zimbra_url/service/home/$zimbra_user/$file?auth=co&loc=it&id=$email_id&part=$part";
			upload_file_ws($record,$module,$userid,$url);
		}
	}
}

function upload_file_ws($record,$module,$userid,$path) {
	global $adb, $table_prefix;
	$SBU = StorageBackendUtils::getInstance();

	$backend = $SBU->defaultBackend;
	
	$focus = CRMEntity::getInstance($module);
	
	// update the backend
	if (array_key_exists('backend_name', $focus->column_fields)) {
		$adb->pquery("UPDATE {$focus->table_name} SET filelocationtype = ?, backend_name = ? WHERE {$focus->table_index} = ?", array('B', $backend, $record));
	}
	
	$focus->retrieve_entity_info($record, $module, false);
	$focus->id = $record;
	
	$fname = explode('?auth',substr($path,strrpos($path,'/')+1));
	$fname = $fname[0];
	
	// fake file request
	$_FILES = array();
	$_FILES[0] = array(
		'tmp_name' => $path,
		'name' => $fname,
		'size' => filesize($path),
		'error' => 0,
	);
	
	// do the upload
	$r = $SBU->uploadFile($backend, $module, $focus);
	
}
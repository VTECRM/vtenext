<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Storage;

use \VteSyncLib\VTEUtils;

class VTE extends Database {

	public function __construct($config = array()) {
		parent::__construct($config);
		
		$table_prefix = VTEUtils::getConfigVar($config['path'], 'table_prefix');
		$this->tables = array(
			'version' => $table_prefix.'_vtesync_vsl_version',
			'mapping' => $table_prefix.'_vtesync_vsl_mapping',
			'lastsync' => $table_prefix.'_vtesync_vsl_lastsync',
			'oauth2' => $table_prefix.'_vtesync_auth', // crmv@190016
			'auth' => $table_prefix.'_vtesync_auth', // crmv@190016
			'tokens' => $table_prefix.'_vtesync_tokens',
		);
	}
	
	public function connect() {
		
		global $adb;
		VTEUtils::includeVTE($this->config['path']);
		if ($adb && $adb->database) {
			$this->db = $adb->database;
			$this->db->setFetchMode(ADODB_FETCH_ASSOC);
			$this->dbu = new DatabaseUtils($this->db);
		}
		
		return ($this->db && $this->db->IsConnected());
	}
	
	// crmv@190016
	public function getOAuthInfo($syncid) {
		$row = parent::getOAuthInfo($syncid);
		if ($row) {
			unset($row['username']);
			unset($row['password']);
		}
		return $row;
	}
	
	public function getAuthInfo($syncid) {
		$row = parent::getAuthInfo($syncid);
		
		if ($row) {
			unset($row['client_id']);
			unset($row['client_secret']);
			unset($row['scope']);
			// decode pwd
			if ($row['password']) {
				global $current_user;
				if ($current_user) {
					$userObj = $current_user;
				} else {
					$userObj = \CRMEntity::getInstance('Users');
				}
				$row['password'] = $userObj->de_cryption($row['password']);
			}
		}
		
		return $row;
	}
	// crmv@190016e
	
}
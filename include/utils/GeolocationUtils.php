<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@59610 - Initial Geolocation support */
/* Add here all Geolocation stuff */

require_once('include/BaseClasses.php');

class GeolocationUtils extends SDKExtendableUniqueClass {

	public $users_table = '_geolocation_users';
	
	public function __construct() {
		global $table_prefix;
		
		$this->users_table = $table_prefix.'_geolocation_users';
	}
	
	public function getUserLocation($userid) {
		global $adb;
		
		$r = $adb->pquery("SELECT * FROM {$this->users_table} WHERE userid = ?", array($userid));
		if ($r && $adb->num_rows($r) > 0) {
			$info = $adb->FetchByAssoc($r, -1, false);
			if ($info['data']) $info['data'] = Zend_Json::decode($info['data']);
			return $info;
		}
		
		return null;
	}
	
	public function updateUserLocation($userid, $latitude, $longitude, $timestamp = null, $otherinfo = null) {
		global $adb;
		
		if (is_null($timestamp)) $timestamp = date('Y-m-d H:i:s');
		if (!is_null($otherinfo)) $otherinfo = Zend_Json::encode($otherinfo);
		
		// check if exists
		$r = $adb->pquery("SELECT userid FROM {$this->users_table} WHERE userid = ?", array($userid));
		if ($r && $adb->num_rows($r) > 0) {
			// update
			$params = array($timestamp, $latitude, $longitude, ($otherinfo ? $otherinfo : ''), $userid);
			$adb->pquery("UPDATE {$this->users_table} SET timestamp = ?, latitude = ?, longitude = ?, data = ? WHERE userid = ?", $params);
		} else {
			// insert
			$params = array($userid, $timestamp, $latitude, $longitude, ($otherinfo ? $otherinfo : ''));
			$adb->pquery("INSERT INTO {$this->users_table} (userid, timestamp, latitude, longitude, data) VALUES (?,?,?,?,?)", $params);
		}
	}
	
	public function removeUserLocation($userid) {
		global $adb;
		
		$adb->pquery("DELETE FROM {$this->users_table} WHERE userid = ?", array($userid));
	}
	
}
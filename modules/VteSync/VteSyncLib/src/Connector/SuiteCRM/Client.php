<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@196666 */
namespace VteSyncLib\Connector\SuiteCRM;

// TODO: unifiy all the duplicate code!
class Client {

	public $apiVersion = '8';
	public $loginUrl = 'http://192.168.8.21/suitecrm/Api/access_token';
	public $apiUrl = 'http://192.168.8.21/suitecrm/Api';
	
	protected $log;
	protected $storage;
	protected $oauthInfo = null;
	protected $syncId = null;
	
	protected $lastError = null;
	protected $lastErrorMsg = null;
	
	public function __construct($storage, $logger) {
		$this->storage = $storage;
		$this->log = $logger;
	}
	
	public function setSyncId($syncid) {
		$this->syncId = $syncid;
	}
	
	public function setOAuthInfo($info) {
		$this->oauthInfo = $info;
	}
	
	public function getLastError() {
		return $this->lastError;
	}
	
	public function getLastErrorMessage() {
		return $this->lastErrorMsg;
	}

	public function doGetRequest($token,$resource, $params = array(), $retry = true) {
		global $adb;
		$this->setOAuthInfo(array('instance_url' => $this->apiUrl));
		$url = $this->oauthInfo['instance_url']."/V{$this->apiVersion}/".$resource;
		
		
		if (count($params) > 0) {
			$url .= '?'.http_build_query($params);
		}
		
		$this->log->debug("Request: GET $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$token));
		
		$r = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($r, true);
	
		if ($retry && $decoded['error'] === 'access_denied') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			
			$r = $this->refreshToken();
			
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			$ress = $adb->pquery("select token from vte_vtesync_tokens WHERE syncid = ?", array(4));
			$token = $adb->query_result($ress,0,'token');
			// and try again
			return $this->doGetRequest($token,$resource, $params, false);
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function doPostRequest($token, $resource, $params = array(), $retry = true) {
		global $adb;
		$this->setOAuthInfo(array('instance_url' => $this->apiUrl));
		$url = $this->oauthInfo['instance_url']."/V{$this->apiVersion}/".$resource;
		
		
		$this->log->debug("Request: POST $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$jsonParams = json_encode($params, JSON_PRETTY_PRINT);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/vnd.api+json',
		    'Content-Length: ' . strlen($jsonParams),
		    'Authorization: Bearer '.$token
		));  

		$r = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			$r = $this->refreshToken();
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			$ress = $adb->pquery("select token from vte_vtesync_tokens WHERE syncid = ?", array(4));
			$token = $adb->query_result($ress,0,'token');
			// and try again
			return $this->doPostRequest($token,$resource, $params, false);
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function doPatchRequest($token, $resource, $params = array(), $retry = true ) {
		global $adb;
		$this->setOAuthInfo(array('instance_url' => $this->apiUrl));
		$url = $this->oauthInfo['instance_url']."/V{$this->apiVersion}/".$resource;
		
		
		$this->log->debug("Request: PATCH $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$jsonParams = json_encode($params);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/vnd.api+json',
		    'Authorization: Bearer '.$token
		));
		
		$r = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			$r = $this->refreshToken();
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			$ress = $adb->pquery("select token from vte_vtesync_tokens WHERE syncid = ?", array(4));
			$token = $adb->query_result($ress,0,'token');
			// and try again
			return $this->doPatchRequest($token,$resource, $params, false);
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		}
		// no answer is expected... 
		
		return true;
	}
	
	public function doDeleteRequest($token, $resource, $retry = true) {
		global $adb;
		$this->setOAuthInfo(array('instance_url' => $this->apiUrl));
		$url = $this->oauthInfo['instance_url']."/V{$this->apiVersion}/".$resource;
		
		
		$this->log->debug("Request: DELETE $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$token));
		
		$r = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			$r = $this->refreshToken();
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			$ress = $adb->pquery("select token from vte_vtesync_tokens WHERE syncid = ?", array(4));
			$token = $adb->query_result($ress,0,'token');
			// and try again
			return $this->doDeleteRequest($token, $resource, false);
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function refreshToken() {
		$url = $this->loginUrl;
		global $adb;
		$res = $adb->pquery("select client_id,client_secret from vte_vtesync_auth WHERE syncid = ?", array(4));
		$resClientId = $adb->query_result($res,0,'client_id');
		$resClientSecret = $adb->query_result($res,0,'client_secret');
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,array(
			'grant_type' => 'client_credentials',
			'client_id' => $resClientId,
			'client_secret' => $resClientSecret,
		));
		
		$r = curl_exec($ch);
		curl_close($ch);
		
		$decoded = json_decode($r, true);
		
		if ($decoded['access_token']) {
			// save the token
			$this->storage->setTokenInfo($this->syncId, $decoded);
			// save the local cache
			$this->oauthInfo['token'] = $decoded['access_token'];
			$res = $adb->pquery("Update vte_vtesync_tokens set token = ? WHERE syncid = ?", array($decoded['access_token'],4));
			if ($decoded['instance_url']) $this->oauthInfo['instance_url'] = $decoded['instance_url'];
		} else {
			return $this->log->error("Unable to refresh token: ".print_r($decoded, true));
		}
		
		return true;
	}
}
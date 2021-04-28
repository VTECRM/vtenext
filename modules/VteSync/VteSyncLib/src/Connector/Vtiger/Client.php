<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@197423 */
namespace VteSyncLib\Connector\Vtiger;

// TODO: unifiy all the duplicate code!
class Client {

	
	
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

	public function doGetRequest($urlMain,$resource,$key, $params = array(), $retry = true) {
		global $adb;
		
		$url = $urlMain ."restapi/v1/vtiger/default/" . $resource;
		
	
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
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/x-www-form-urlencoded',
		'Authorization: Basic '.$key
		));
		
		$r = curl_exec($ch);
		
		
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function doPostRequestCreate($username,$password,$urlMain,$resource,$key, $params = array(), $retry = true) {
		global $adb;
		$url = $urlMain ."restapi/v1/vtiger/default/" . $resource;
		
		
		$this->log->debug("Request: POST $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$jsonParams = $params;
		
	
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
		curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
		/* curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Authorization: Basic '.$key
		));   */
		
		$r = curl_exec($ch);
	
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function doPostRequestUpdate($username,$password,$urlMain,$resource,$key, $params = array(), $retry = true) {
		global $adb;
		$url = $urlMain ."restapi/v1/vtiger/default/" . $resource;
		
		
		$this->log->debug("Request: POST $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$jsonParams = $params;
		
		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
		curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
		/* curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Authorization: Basic '.$key
		));   */
		
		$r = curl_exec($ch);
	
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function doPostRequestDelete($username,$password,$urlMain,$resource,$key, $params = array(), $retry = true) {
		global $adb;
		$url = $urlMain ."restapi/v1/vtiger/default/" . $resource;
		
		
		$this->log->debug("Request: POST $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$jsonParams = $params;
		
		 if (count($params) > 0) {
			$url .= '?'.http_build_query($params);
		} 
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
		curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
		/* curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Authorization: Basic '.$key
		));   */
		
		$r = curl_exec($ch);
	
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	
	/* public function doPatchRequest($urlMain,$resource,$key, $params = array(), $retry = true ) {
		global $adb;
		$url = $urlMain ."restapi/v1/vtiger/default/" . $resource;
		
		
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
		     'Content-Type: application/x-www-form-urlencoded',
		    'Authorization: Basic '.$key
		));
		
		$r = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded['error'] === 'access_denied') {
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		}
		// no answer is expected... 
		
		return true;
	}
	
	public function doDeleteRequest($urlMain,$resource,$key, $params = array(), $retry = true) {
		global $adb;
		$url = $urlMain ."restapi/v1/vtiger/default/" . $resource;
		
		
		$this->log->debug("Request: DELETE $url");
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$key));
		
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
	} */
	
	
}
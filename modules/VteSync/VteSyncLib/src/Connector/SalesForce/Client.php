<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\SalesForce;

// TODO: unifiy all the duplicate code!
class Client {

	public $apiVersion = '45.0';
	public $loginUrl = 'https://login.salesforce.com/services/oauth2';
	
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

	public function doGetRequest($resource, $params = array(), $retry = true) {
		
		$url = $this->oauthInfo['instance_url']."/services/data/v{$this->apiVersion}/".$resource;
		$token = $this->oauthInfo['token'];
		
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
		
		if ($retry && $decoded[0]['errorCode'] === 'INVALID_SESSION_ID') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			$r = $this->refreshToken();
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			
			// and try again
			return $this->doGetRequest($resource, $params, false);
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function doPostRequest($resource, $params = array(), $retry = true) {
		
		$url = $this->oauthInfo['instance_url']."/services/data/v{$this->apiVersion}/".$resource;
		$token = $this->oauthInfo['token'];
		
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
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($jsonParams),
		    'Authorization: Bearer '.$token
		));  

		$r = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded[0]['errorCode'] === 'INVALID_SESSION_ID') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			$r = $this->refreshToken();
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			
			// and try again
			return $this->doPostRequest($resource, $params, false);
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		} elseif (empty($decoded)) {
			return $this->log->error("Request failed for $resource: empty answer");
		}
		
		return $decoded;
	}
	
	public function doPatchRequest($resource, $params = array(), $retry = true) {
		
		$url = $this->oauthInfo['instance_url']."/services/data/v{$this->apiVersion}/".$resource;
		$token = $this->oauthInfo['token'];
		
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
		    'Content-Type: application/json',
		    'Authorization: Bearer '.$token
		));
		
		$r = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($r, true);
		
		if ($retry && $decoded[0]['errorCode'] === 'INVALID_SESSION_ID') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			$r = $this->refreshToken();
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			
			// and try again
			return $this->doPatchRequest($resource, $params, false);
		} elseif ($decoded[0]['errorCode']) {
			$this->lastError = $decoded[0]['errorCode'];
			$this->lastErrorMsg = $decoded[0]['message'];
			return $this->log->error("Request failed: ".print_r($decoded, true));
		}
		// no answer is expected... 
		
		return true;
	}
	
	public function doDeleteRequest($resource, $retry = true) {
		
		$url = $this->oauthInfo['instance_url']."/services/data/v{$this->apiVersion}/".$resource;
		$token = $this->oauthInfo['token'];
		
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
		
		if ($retry && $decoded[0]['errorCode'] === 'INVALID_SESSION_ID') {
			$this->log->debug("Session expired, trying to get a new token");
			// try to refresh the token
			$r = $this->refreshToken();
			if (!$r) {
				return $this->log->error("Request failed for ".$resource);
			}
			
			// and try again
			return $this->doDeleteRequest($resource, false);
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
		$url = $this->loginUrl.'/token';
		$token = $this->oauthInfo['refresh_token'];
		
		if (!$token) {
			return $this->log->error("Missing refresh token, unable to refresh!");
		}
		
		$this->lastError = null;
		$this->lastErrorMsg = null;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,array(
			'grant_type' => 'refresh_token',
			'client_id' => $this->oauthInfo['client_id'],
			'client_secret' => $this->oauthInfo['client_secret'],
			'refresh_token' => $token,
		));
		
		$r = curl_exec($ch);
		curl_close($ch);
		
		$decoded = json_decode($r, true);
		
		if ($decoded['access_token']) {
			// save the token
			$this->storage->setTokenInfo($this->syncId, $decoded);
			// save the local cache
			$this->oauthInfo['token'] = $decoded['access_token'];
			if ($decoded['instance_url']) $this->oauthInfo['instance_url'] = $decoded['instance_url'];
		} else {
			return $this->log->error("Unable to refresh token: ".print_r($decoded, true));
		}
		
		return true;
	}
}
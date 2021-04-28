<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

/* crmv@208173 */
include 'vtlib/thirdparty/network/Request.php';

/**
 * Provides API to work with HTTP Connection.
 * @package vtlib
 */
class Vtecrm_Net_Client {
	private $client;
	private $url;
	private $response;

	/**
	 * Constructor
	 * @param String URL of the site
	 * Example:
	 * $client = new Vtenext_New_Client('http://vtenext.com');
	 */
	public function __construct($url) {
		$this->setURL($url);
	}

	/**
	 * Set another url for this instance
	 * @param String URL to use go forward
	 */
	public function setURL($url) {
		$this->url = $url;
		$this->client = new HTTP_Request();
		$this->response = false;
	}

	/**
	 * Set custom HTTP Headers
	 * @param Map HTTP Header and Value Pairs
	 */
	public function setHeaders($values) {
		foreach($values as $key=>$value) {
			$this->client->addHeader($key, $value);
		}
	}

	/**
	 * Did last request resulted in error?
	 */
	public function wasError() {
		return PEAR::isError($this->response);
	}

	/**
	 * Disconnect this instance
	 */
	public function disconnect() {
		$this->client->disconnect();
	}

	/**
	 * Perform a GET request
	 * @param Map key-value pair or false
	 * @param Integer timeout value
	 */
	public function doGet($params=false, $timeout=null) {
		if($timeout) $this->client->_timeout = $timeout;
		$this->client->setURL($this->url);
		$this->client->setMethod(HTTP_REQUEST_METHOD_GET);

		if($params && is_array($params)) {
			foreach($params as $key=>$value){
				$this->client->addQueryString($key, $value);
			}
		}
		$this->response = $this->client->sendRequest();

		$content = false;
		if(!$this->wasError()) {
			$content = $this->client->getResponseBody();
		}
		$this->disconnect();
		return $content;
	}

	/**
	 * Perform a POST request
	 * @param Map key-value pair or false
	 * @param Integer timeout value
	 */
	public function doPost($params=false, $timeout=null) {
		if($timeout) $this->client->_timeout = $timeout;
		$this->client->setURL($this->url);
		$this->client->setMethod(HTTP_REQUEST_METHOD_POST);

		if($params) {
			if(is_string($params)) {
				$this->client->addRawPostData($params);
			}
			else if(is_array($params)) {
				foreach($params as $key=>$value){
					$this->client->addPostData($key, $value);
				}
			}
		}
		$this->response = $this->client->sendRequest();

		$content = false;
		if(!$this->wasError()) {
			$content = $this->client->getResponseBody();
		}
		$this->disconnect();

		return $content;
	}
}

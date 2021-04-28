<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@80883 */

class Vtecrm_Net_Client extends Curl_HTTP_Client {//crmv@207871
	public $_serviceurl = '';
	
	protected $rawResponse = null;

	function __construct($url) {
		if (!function_exists('curl_exec')) {
			die('Vtecrm_Net_Client: Curl extension not enabled!');
		}
		parent::__construct();
		$this->_serviceurl = $url;
		$useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
		$this->set_user_agent($useragent);
	}
	
	function getLastRawResponse() {
		return $this->rawResponse;
	}
	
	function doPost($postdata=false, $decodeResponseJSON=false, $timeout=20) {
		if($postdata === false) $postdata = Array();
		$this->rawResponse = null;
		$resdata = $this->send_post_data($this->_serviceurl, $postdata, null, $timeout);
		$this->rawResponse = $resdata;
		if($resdata && $decodeResponseJSON) $resdata = $this->__jsondecode($resdata);
		return $resdata;
	}

	function doGet($getdata=false, $decodeResponseJSON=false, $timeout=20) {
		if($getdata === false) $getdata = Array();
		$queryString = '';
		foreach($getdata as $key=>$value) {
			$queryString .= '&' . urlencode($key)."=".urlencode($value);
		}
		$this->rawResponse = null;
		$resdata = $this->fetch_url("$this->_serviceurl?$queryString", null, $timeout);
		$this->rawResponse = $resdata;
		if($resdata && $decodeResponseJSON) $resdata = $this->__jsondecode($resdata);
		return $resdata;
	}

	function __jsondecode($indata) {
		return Zend_Json::decode($indata);
	}

	function __jsonencode($indata) {
		return Zend_Json::encode($indata);
	}
}

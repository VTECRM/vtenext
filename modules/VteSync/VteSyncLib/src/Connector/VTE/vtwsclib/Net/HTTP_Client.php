<?php

class Vtecrm_Net_Client extends Curl_HTTP_Client {//crmv@207871
	var $_serviceurl = '';

	function __construct($url) {
		if(!function_exists('curl_exec')) {
			die('Vtecrm_Net_Client: Curl extension not enabled!');
		}
		parent::__construct();
		$this->_serviceurl = $url;
		$useragent = "VteSync/1.0 (VTE Connector)";
		$this->set_user_agent($useragent);

		// Escape SSL certificate hostname verification
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	function doPost($postdata=false, $decodeResponseJSON=false, $timeout=20) {
		if($postdata === false) $postdata = Array();
		$resdata = $this->send_post_data($this->_serviceurl, $postdata, null, $timeout);
		if($resdata && $decodeResponseJSON) $resdata = $this->__jsondecode($resdata);
		return $resdata;
	}

	function doGet($getdata=false, $decodeResponseJSON=false, $timeout=20) {
		if($getdata === false) $getdata = Array();
		$queryString = '';
		foreach($getdata as $key=>$value) {
			$queryString .= '&' . urlencode($key)."=".urlencode($value);
		}
		$resdata = $this->fetch_url("$this->_serviceurl?$queryString", null, $timeout);
		if($resdata && $decodeResponseJSON) $resdata = $this->__jsondecode($resdata);
		return $resdata;
	}

	function __jsondecode($indata) {
		$out = Zend_Json::decode($indata);
		if ($indata != '' && $out === null) throw new Exception("Invalid data to decode: ".$indata);
		return $out;
	}

	function __jsonencode($indata) {
		return Zend_Json::encode($indata);
	}
}

?>

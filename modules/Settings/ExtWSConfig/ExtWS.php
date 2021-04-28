<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146670 */

/**
 * Class to call external WS
 */
class ExtWS {

	public $default_useragent = 'VTECRM/ExtWS 1.0';
	public $default_timeout = 30;

	public function call($data) {

		$client = new Zend\Http\Client();
		
		$client->setOptions(array(
			'useragent' => $this->default_useragent,
			'timeout' => $this->default_timeout,
			'adapter' => 'Zend\Http\Client\Adapter\Curl'
		));
		
		$client->setUri($data['wsurl']);
		$client->setMethod($data['method']);
		
		if ($data['authinfo'] && $data['authinfo']['username']) {
			$client->setAuth($data['authinfo']['username'], $data['authinfo']['password']);
		}
		
		if ($data['headers'] && count($data['headers']) > 0) {
			$hlist = array();
			foreach ($data['headers'] as $hinfo) {
				$hlist[$hinfo['name']] = $hinfo['value'];
			}
			$client->setHeaders($hlist);
		}
		
		if ($data['params'] && count($data['params']) > 0) { // crmv@190014 crmv@192144
			$plist = array();
			foreach ($data['params'] as $pinfo) {
				$plist[$pinfo['name']] = $pinfo['value'];
			}
			if ($data['method'] == 'POST' || $data['method'] == 'PUT' || $data['method'] == 'PATCH') {
				$client->setParameterPost($plist);
			} else {
				$client->setParameterGet($plist);
			}
		}
		
		// crmv@190014
		if ($data['encoding']) {
			$client->setEncType($data['encoding']);
		}

		if (isset($data['rawbody'])) {
			$client->setRawBody($data['rawbody']);
		}

		if ($data['files'] && count($data['files']) > 0) {
			foreach ($data['files'] as $finfo) {
				$client->setFileUpload($finfo['path'], $finfo['name'] ?: 'file', null, $finfo['mimetype'] ?: null);
			}
		}
		// crmv@190014e
		
		$response = $client->send();
		
		// enable to see the raw request
		//echo $client->getLastRawRequest();
		
		$result = array(
			'success' => $response->isSuccess(),
			'code' => $response->getStatusCode(),
			'message' => $response->getReasonPhrase(),
			'headers' => $response->getHeaders()->toArray(),
			'body' => $response->getBody(),
		);
		
		return $result;
	}
	
	/**
	 * @deprecated
	 */
	public function loadZendFramework() {
		// crmv@196384 - moved into the vendor folder and already autoloaded
	}
	
}
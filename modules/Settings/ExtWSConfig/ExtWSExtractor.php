<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146671 */

/**
 * Class to extract data from the result of a WS
 * Exceptions are thrown in case of errors
 */
class ExtWSExtractor {

	private $json_child_operator = '.'; //crmv@182555
	
	public function extractFields($result, $fields) {
	
		$parsedBody = $this->parseBody($result['body']);
		
		$values = array();
		
		// now extract what I want
		if ($result['success']) {
			foreach ($fields as $rinfo) {
				$key = $rinfo['name'];
				$expression = $rinfo['value'];
				$value = $this->extractValue($parsedBody, $expression);
				if ($value !== false) {
					$values[$key] = $value;
				}
			}
		}
		
		// add some standard values
		$values['extws_success'] = intval($result['success']);
		$values['extws_returncode'] = intval($result['code']);
		$values['extws_returnmessage'] = $result['message']; //crmv@166569
		
		return $values;
	}
	
	// for now, only simple flat objects are supported
	//crmv@182555
	public function extractValue($data, $expression) {
		if (strpos($expression, $this->json_child_operator) !== false) {
			$jsonPath = explode($this->json_child_operator, $expression);
			if (!empty($jsonPath)) {
				foreach($jsonPath as $jsonPar) {
					$data = $data[$jsonPar];
				}
				if (!empty($data)) {
					if (is_array($data))
						return Zend_Json::encode($data);
					else
						return $data;
				}
			}
		} elseif (array_key_exists($expression, $data)) {
			if (is_array($data[$expression]))
				return Zend_Json::encode($data[$expression]);
			else
				return $data[$expression];
		}
		return false;
	}
	//crmv@182555e
	
	public function parseBody($rawbody) {
		$rawbody = trim($rawbody); //crmv@182555
		if (empty($rawbody)) throw new Exception('No data returned');
		
		if ($rawbody[0] == '[' || $rawbody[0] == '{') {
			// try with json
			$decoded = Zend_Json::decode($rawbody);
			if (!is_array($decoded)) {
				throw new Exception(getTranslatedString('LBL_NO_VALID_JSON', 'Settings'));
			}
		} elseif ($rawbody[0] == '<') {
			// try with xml
			$xml = @simplexml_load_string($rawbody);
			if (!is_object($xml)) {
				throw new Exception(getTranslatedString('LBL_NO_VALID_XML', 'Settings'));
			}
			$decoded = array();
			foreach ($xml as $name => $val) {
				if (is_object($val)) {
					$decoded[$name] = (string)$val;
				}
			}
		} else {
			throw new Exception(getTranslatedString('LBL_NO_VALID_DATA_FORMAT', 'Settings'));
		}
		
		return $decoded;
	}
	
}
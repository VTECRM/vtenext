<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150748 */

class RequestHandler {

	// crmv@177677
	/**
	 * Return a "unique" id for each requests
	 * Remember: not guaranteed to be 100% unique, but enough for common use
	 */
	static public function getId() {
		static $requestId = null;
		if (is_null($requestId)) {
			global $application_unique_key;
			$cliflag = (php_sapi_name() == 'cli' ? 'C' : 'W');
			$prefix = substr($application_unique_key, 0, 2) . $cliflag;
			$requestId = uniqid($prefix, true);
		}
		return $requestId;
	}
	// crmv@177677e

	static public function processCompressedRequest() {
		$compressedData = $_REQUEST['compressedData'] ?? '';
		
		if ($compressedData === 'true' && isset($_FILES['payload'])) {
			if ($_FILES['payload']['error'] != 0) throw new Exception('File upload error');
			
			$fmt = $_REQUEST['compressFormat'];
			$serial = $_REQUEST['serializeFormat'];

			// uncompress
			if ($fmt === 'gzip') {
				$zp = gzopen($_FILES['payload']['tmp_name'], 'rb');
				if ($zp) {
					$rawdata = '';
					while (!gzeof($zp)) {
						$rawdata .= gzread($zp, 10000);
					}
					gzclose($zp);
				} else {
					throw new Exception('Unable to open compressed data');
				}
			} else {
				throw new Exception('Unknown compression format');
			}
			
			// decode
			$payload = null;
			if ($serial === 'serialize') {
				// beware, this is still subjected to max_input_vars :(
				// see http://php.net/manual/en/function.parse-str.php#108642
				parse_str($rawdata, $payload);
			} elseif ($serial === 'json') {
				$payload = json_decode($rawdata, true);
			} else {
				throw new Exception('Unknown serialization format');
			}
			
			// merge with request
			if (is_array($payload)) {
				// crmv@162674
				// Using replace to keep numeric keys
				$_REQUEST = array_replace($_REQUEST, $payload);
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$_POST = array_replace($_POST, $payload);
				}
				// crmv@162674e
			}
		}
	}
	
	static public function outputRedirect($url, $rformat = null) {
		if (!$rformat) $rformat = $_REQUEST['responseFormat'];
		
		if ($rformat === 'json') {
			$result = array('success' => true, 'redirect' => $url);
			header('Content-type: application/json');
			echo json_encode($result);
			exit();
		}
		
		header("Location: $url");
	}
	
	// crmv@171581
	static public function getCSRFToken() {
		$VP = VTEProperties::getInstance();
		if ($VP->getProperty('security.csrf.enabled')) {
			$VTECSRF = new VteCsrf();
			return $VTECSRF->csrf_get_tokens();
		} else {
			return '';
		}
	}

	static public function validateCSRFToken() {
		$VP = VTEProperties::getInstance();
		if ($VP->getProperty('security.csrf.enabled')) {
			$VTECSRF = new VteCsrf();
			return $VTECSRF->csrf_check();
		} else {
			return true;
		}
	}
	// crmv@171581e
	
}
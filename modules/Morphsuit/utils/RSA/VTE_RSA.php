<?php

/* crmv@18670 crmv@146653 */

require_once('modules/Morphsuit/utils/RSA/Math/BigInteger.php');
require_once('modules/Morphsuit/utils/RSA/Crypt/Random.php');
require_once('modules/Morphsuit/utils/RSA/Crypt/Hash.php');
require_once('modules/Morphsuit/utils/RSA/Crypt/RSA.php');

class VTE_Crypt_RSA extends Crypt_RSA {
	
	function createKey($bits = 1024, $timeout = false, $partial = array()) {
		$keys = parent::createKey($bits, $timeout, $partial);
		$keys['publickey'] = preg_replace('/^-----.*?-----(\\r\\n)?/m', '', $keys['publickey']);
		$keys['privatekey'] = preg_replace('/^-----.*?-----(\\r\\n)?/m', '', $keys['privatekey']);
		return $keys;
	}
}

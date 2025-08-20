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

    //crmv@211287
    static public function paramGet($name){
        return $_GET[$name];
    }

    static public function paramPost($name){
        return $_POST[$name];
    }

    static public function param($name){
        return $_REQUEST[$name];
    }

    static public function filterIntParam($value){
        return intval($value);
    }

    static public function filterStringParam($value, $maxLength=100){
        return substr(strip_tags($value), 0, $maxLength);
    }

    static public function filterHtmlParam($value){
        return vtlib_purify($value);
    }

    static public function filterFloatParam($value){
        return floatval($value);
    }

    static public function filterBoolParam($value){
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    static public function filterModuleParam($value){
        return preg_replace("/[^a-zA-Z0-9_\-\s]/", '', $value);//allow only letters, numbers and '-' '_' ' '
    }

    static public function paramInt($name){
        return self::filterIntParam(self::param($name));
    }

    static public function paramString($name, $maxLength = 100){
        return self::filterStringParam(self::param($name), $maxLength);
    }

    static public function paramFloat($name){
        return self::filterFloatParam(self::param($name));
    }

    static public function paramHtml($name){
        return self::filterHtmlParam(self::param($name));
    }

    static public function paramBool($name){
        return self::filterBoolParam(self::param($name));
    }

    static public function paramGetInt($name){
        return self::filterIntParam(self::paramGet($name));
    }

    static public function paramGetString($name, $maxLength = 100){
        return self::filterStringParam(self::paramGet($name), $maxLength);
    }

    static public function paramGetFloat($name){
        return self::filterFloatParam(self::paramGet($name));
    }

    static public function paramGetHtml($name){
        return self::filterHtmlParam(self::paramGet($name));
    }

    static public function paramGetBool($name){
        return self::filterBoolParam(self::paramGet($name));
    }

    static public function paramPostInt($name){
        return self::filterIntParam(self::paramPost($name));
    }

    static public function paramPostString($name, $maxLength = 100){
        return self::filterStringParam(self::paramPost($name), $maxLength);
    }

    static public function paramPostFloat($name){
        return self::filterFloatParam(self::paramPost($name));
    }

    static public function paramPostHtml($name){
        return self::filterHtmlParam(self::paramPost($name));
    }

    static public function paramPostBool($name){
        return self::filterBoolParam(self::paramPost($name));
    }

    static public function paramModule($name){
        return self::filterModuleParam(self::param($name));
    }

    static public function paramAction($name){
        return self::filterModuleParam(self::param($name));
    }

    static public function paramField($name){
        return self::filterModuleParam(self::param($name));
    }

    static public function paramParentTab($name){
        return self::filterModuleParam(self::param($name));
    }
    //crmv@211287

}

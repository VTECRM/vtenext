<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@80883 crmv@208028 */

if (!defined('VTWSC_BASEDIR')) {
    define('VTWSC_BASEDIR', dirname(__FILE__));
}

require_once(VTWSC_BASEDIR . '/lib/curl_http_client.php');
require_once(VTWSC_BASEDIR . '/lib/Zend/Json.php');

require_once(VTWSC_BASEDIR . '/lib/HTTP_Client.php');
require_once(VTWSC_BASEDIR . '/WSVersion.php');

/**
 * VTE Webservice Client
 */
class VTE_WSClient {
    // Webserice file
    var $_servicebase = 'webservice.php';

    // HTTP Client instance
    var $_client = false;
    // Service URL to which client connects to
    var $_serviceurl = false;

    // Webservice user credentials
    var $_serviceuser= false;
    var $_servicekey = false;

    // Webservice login validity
    var $_servertime = false;
    var $_expiretime = false;
    var $_servicetoken=false;

    // Webservice login credentials
    var $_sessionid  = false;
    var $_userid     = false;

    // Last operation error information
    var $_lasterror  = false;

    /**
     * Constructor.
     */
    function __construct($url) {
        $this->_serviceurl = $this->getWebServiceURL($url);
        $this->_client = new Vtecrm_Net_Client($this->_serviceurl);//crmv@207871
    }

    /**
     * Return the client library version.
     */
    function version() {
        global $wsclient_version;
        return $wsclient_version;
    }

    /**
     * Reinitialize the client.
     */
    function reinitalize() {
        $this->_client = new Vtecrm_Net_Client($this->_serviceurl);//crmv@207871
    }

    /**
     * Get the URL for sending webservice request.
     */
    function getWebServiceURL($url) {
        if(stripos($url, $this->_servicebase) === false) {
            if(strripos($url, '/') != (strlen($url)-1)) {
                $url .= '/';
            }
            $url .= $this->_servicebase;
        }
        return $url;
    }

    /**
     * Get actual record id from the response id.
     */
    function getRecordId($id) {
        $ex = explode('x', $id);
        return $ex[1];
    }

    /**
     * Check if result has any error.
     */
    function hasError($result) {
        if (is_array($result) && isset($result['success']) && $result['success'] === true) {
            $this->_lasterror = false;
            return false;
        }
        if (is_array($result)) {
            $this->_lasterror = $result['error'];
        } else {
            $this->_lasterror = array('code' => -1, 'message' => "SERVER ERROR. RAW RESPONSE: \n".$this->_client->getLastRawResponse()."\n");
        }
        return true;
    }

    /**
     * Get last operation error
     */
    function lastError() {
        return $this->_lasterror;
    }

    /**
     * Perform the challenge
     * @access private
     */
    function __doChallenge($username) {
        $getdata = Array(
            'operation' => 'getchallenge',
            'username'  => $username
        );
        $resultdata = $this->_client->doGet($getdata, true);

        if($this->hasError($resultdata)) {
            return false;
        }

        $this->_servertime   = $resultdata['result']['serverTime'];
        $this->_expiretime   = $resultdata['result']['expireTime'];
        $this->_servicetoken = $resultdata['result']['token'];
        return true;
    }

    /**
     * Check and perform login if requried.
     */
    function __checkLogin() {
        /*if($this->_expiretime || (time() > $this->_expiretime)) {
            $this->doLogin($this->_serviceuser, $this->_servicepwd);
        }*/
    }

    /**
     * JSONify input data.
     */
    function toJSON($input) {
        return $this->_client->__jsondecode($input);
    }

    /**
     * Convert input data to JSON String.
     */
    function toJSONString($input) {
        return $this->_client->__jsonencode($input);
    }

    /**
     * Do Login Operation
     */
    function doLogin($username, $vtenextUserAccesskey) {
        // Do the challenge before login
        if($this->__doChallenge($username) === false) return false;

        $postdata = Array(
            'operation' => 'login',
            'username'  => $username,
            'accessKey' => md5($this->_servicetoken.$vtenextUserAccesskey)
        );
        $resultdata = $this->_client->doPost($postdata, true);

        if($this->hasError($resultdata)) {
            return false;
        }
        $this->_serviceuser = $username;
        $this->_servicekey  = $vtenextUserAccesskey;

        $this->_sessionid = $resultdata['result']['sessionName'];
        $this->_userid    = $resultdata['result']['userId'];
        return true;
    }

    /**
     * Do Query Operation.
     */
    function doQuery($query) {
        // Perform re-login if required.
        $this->__checkLogin();

        // Make sure the query ends with ;
        $query = trim($query);
        if(strripos($query, ';') != strlen($query)-1) $query .= ';';

        $getdata = Array(
            'operation' => 'query',
            'sessionName'  => $this->_sessionid,
            'query'  => $query
        );
        $resultdata = $this->_client->doGet($getdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }

    /**
     * Get Result Column Names.
     */
    function getResultColumns($result) {
        $columns = Array();
        if(!empty($result)) {
            $firstrow= $result[0];
            foreach($firstrow as $key=>$value) $columns[] = $key;
        }
        return $columns;
    }

    /**
     * List types available Modules.
     */
    function doListTypes() {
        // Perform re-login if required.
        $this->__checkLogin();

        $getdata = Array(
            'operation' => 'listtypes',
            'sessionName'  => $this->_sessionid
        );
        $resultdata = $this->_client->doGet($getdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        $modulenames = $resultdata['result']['types'];

        $returnvalue = Array();
        foreach($modulenames as $modulename) {
            $returnvalue[$modulename] =
                Array ( 'name' => $modulename );
        }
        return $returnvalue;
    }

    /**
     * Describe Module Fields.
     */
    function doDescribe($module) {
        // Perform re-login if required.
        $this->__checkLogin();

        $getdata = Array(
            'operation' => 'describe',
            'sessionName'  => $this->_sessionid,
            'elementType' => $module
        );
        $resultdata = $this->_client->doGet($getdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }

    /**
     * Retrieve details of record.
     */
    function doRetrieve($record) {
        // Perform re-login if required.
        $this->__checkLogin();

        $getdata = Array(
            'operation' => 'retrieve',
            'sessionName'  => $this->_sessionid,
            'id' => $record
        );
        $resultdata = $this->_client->doGet($getdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }

    protected function checkUploads($uploads = array()) {
        if (is_array($uploads) && count($uploads) > 0) {
            $postFiles = array();
            foreach ($uploads as $name => $filename) {
                if (!is_readable($filename)) {
                    $this->_lasterror = "File not readable: $filename";
                    return false;
                } else {
                    $postFiles[$name] = '@'.realpath($filename);
                }
            }
            if (count($postFiles) > 0) {
                // keep this order, so the postfiles won't override the parameters
                return $postFiles;
            }
        }
        return null;
    }

    /**
     * Do Create Operation
     */
    function doCreate($module, $valuemap, $uploads = array()) {
        // Perform re-login if required.
        $this->__checkLogin();

        // Assign record to logged in user if not specified
        if(!isset($valuemap['assigned_user_id'])) {
            $valuemap['assigned_user_id'] = $this->_userid;
        }

        $postdata = Array(
            'operation'   => 'create',
            'sessionName' => $this->_sessionid,
            'elementType' => $module,
            'element'     => $this->toJSONString($valuemap)
        );

        // check uploads
        $up = $this->checkUploads($uploads);
        if (is_array($up)) {
            $postdata = array_merge($up, $postdata);
        }

        $resultdata = $this->_client->doPost($postdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }

    function doUpdate($id, $valuemap, $uploads = array()) {
        // Perform re-login if required.
        $this->__checkLogin();

        // Assign record to logged in user if not specified
        if(!isset($valuemap['assigned_user_id'])) {
            $valuemap['assigned_user_id'] = $this->_userid;
        }

        $postdata = Array(
            'operation' => 'updateRecord',
            'sessionName' => $this->_sessionid,
            'id' => $id,
            'columns' => $this->toJSONString($valuemap)
        );

        // check uploads
        $up = $this->checkUploads($uploads);
        if (is_array($up)) {
            $postdata = array_merge($up, $postdata);
        }

        $resultdata = $this->_client->doPost($postdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }

    function doUpdateOld($valuemap, $uploads = array()) {
        // Perform re-login if required.
        $this->__checkLogin();

        // Assign record to logged in user if not specified
        if(!isset($valuemap['assigned_user_id'])) {
            $valuemap['assigned_user_id'] = $this->_userid;
        }

        $postdata = Array(
            'operation'   => 'update',
            'sessionName' => $this->_sessionid,
            'element'     => $this->toJSONString($valuemap),
            'ajxaction'	  => 'DETAILVIEW',	// serve per non farlo entrare nell'if che fa la saveInventoryProductDetails()
        );

        // check uploads
        $up = $this->checkUploads($uploads);
        if (is_array($up)) {
            $postdata = array_merge($up, $postdata);
        }

        $resultdata = $this->_client->doPost($postdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }

    function doDelete($valuemap) {
        // Perform re-login if required.
        $this->__checkLogin();

        $postdata = Array(
            'operation'   => 'delete',
            'sessionName' => $this->_sessionid,
            'id' => $valuemap
        );
        $resultdata = $this->_client->doPost($postdata, true);
        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }

    /**
     * Invoke custom operation
     *
     * @param String $method Name of the webservice to invoke
     * @param Object $type null or parameter values to method
     * @param String $params optional (POST/GET)
     */
    function doInvoke($method, $params = null, $type = 'POST', $uploads = array()) {
        // Perform re-login if required
        $this->__checkLogin();

        $senddata = Array(
            'operation' => $method,
            'sessionName' => $this->_sessionid
        );
        if(!empty($params)) {
            foreach($params as $k=>$v) {
                if(!isset($senddata[$k])) {
                    $senddata[$k] = $v;
                }
            }
        }

        $resultdata = false;
        if(strtoupper($type) == "POST") {
            // check uploads
            $up = $this->checkUploads($uploads);
            if (is_array($up)) {
                $senddata = array_merge($up, $senddata);
            }
            // send the request
            $resultdata = $this->_client->doPost($senddata, true);
        } else {
            $resultdata = $this->_client->doGet($senddata, true);
        }

        if($this->hasError($resultdata)) {
            return false;
        }
        return $resultdata['result'];
    }
}
?>
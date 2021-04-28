<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

require_once("include/HTTP_Session/Session.php");
// Later may we can move this to config file.

global $maxWebServiceSessionLifeSpan, $maxWebServiceSessionIdleTime;

$maxWebServiceSessionLifeSpan = 86400; //Max life span of a session is a day.
$maxWebServiceSessionIdleTime = 1800; //Max life span session should be kept alive after the last transaction.

// Till Here.

class SessionManager{
    private $maxLife;
    private $idleLife;
    //Note: the url lookup part of http_session will have String null or this be used as id instead of ignoring it.
    //private $sessionName = "sessionName";
    private $sessionVar = "__SessionExists";
    private $error;

    public function __construct(){
        global $maxWebServiceSessionLifeSpan, $maxWebServiceSessionIdleTime;

        $now = time();
        $this->maxLife = $now + $maxWebServiceSessionLifeSpan;
        $this->idleLife = $now + $maxWebServiceSessionIdleTime;

        (new HTTP_Session)->useCookies(false); //disable cookie usage. may this could be moved out constructor?
        // only first invocation of following method, which is setExpire
        //have an effect and any further invocation will be have no effect.
        (new HTTP_Session)->setExpire($this->maxLife);
        // this method replaces the new with old time if second params is true
        //otherwise it subtracts the time from previous time
        (new HTTP_Session)->setIdle($this->idleLife, true);
    }

    public function startSession($sid = null,$adoptSession=false){
        if(!$sid || strlen($sid) === 0){
            $sid = null;
        }

        //session name is used for guessing the session id by http_session so pass null.
        (new HTTP_Session)->start(null, $sid);

        $newSID = (new HTTP_Session)->id();

        if(!$sid || $adoptSession==true){
            $this->set($this->sessionVar,"true");
        }else{
            if(!$this->get($this->sessionVar)){
                (new HTTP_Session)->destroy();
                throw new WebServiceException(WebServiceErrorCode::$SESSIONIDINVALID,"Session Identifier provided is Invalid");
            }
        }

        try {
            if (!$this->isValid()) {
                $newSID = null;
            }
        } catch (WebServiceException $e) {
        }

        $sid = $newSID;
        return $sid;
    }

    public function isValid(){
        $valid = true;
        // expired
        if ((new HTTP_Session)->isExpired()) {
            $valid = false;
            (new HTTP_Session)->destroy();
            throw new WebServiceException(WebServiceErrorCode::$SESSLIFEOVER,"Session has life span over please login again");
        }

        // idled
        if ((new HTTP_Session)->isIdle()) {
            $valid = false;
            (new HTTP_Session)->destroy();
            throw new WebServiceException(WebServiceErrorCode::$SESSIONIDLE,"Session has been invalidated to due lack activity");
        }
        //echo "<br>is new: ", HTTP_Session::isNew();
        //invalid sessionId provided.
        //echo "<br>get: ",$this->get($this->sessionVar);
        if(!$this->get($this->sessionVar) && !(new HTTP_Session)->isNew()){
            $valid = false;
            (new HTTP_Session)->destroy();
            throw new WebServiceException(WebServiceErrorCode::$SESSIONIDINVALID,"Session Identifier provided is Invalid");
        }

        return $valid;
    }

    public function get($name){
        return (new HTTP_Session)->get($name);
    }

    public function getError(){
        return $this->error;
    }

    public function destroy(){
        (new HTTP_Session)->destroy();
    }

    public function getSessionId(){
        return (new HTTP_Session)->id();
    }

    public function set($var_name, $var_value){
        //TODO test setRef and getRef combination
        (new HTTP_Session)->set($var_name, $var_value);
    }
}

?>
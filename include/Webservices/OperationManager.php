<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

function setBuiltIn($json){
    $json->useBuiltinEncoderDecoder = true;
}

class OperationManager{
    private $format;
    private $formatsData=array(
        "json"=>array(
            "includePath"=>"include/Zend/Json.php",
            "class"=>"Zend_Json",
            "encodeMethod"=>"encode",
            "decodeMethod"=>"decode",
            "postCreate"=>"setBuiltIn"
        )
    );
    private $formatObjects;
    private $inParamProcess;
    private $sessionManager;
    private $pearDB;
    private $operationName;
    private $type;
    private $handlerPath;
    private $handlerMethod;
    private $preLogin;
    private $operationId;
    private $operationParams;

    public function __construct($adb, $operationName, $format, $sessionManager){
        $this->format = strtolower($format);
        $this->sessionManager = $sessionManager;
        $this->formatObjects = [];

        foreach($this->formatsData as $frmt=>$frmtData){
            require_once($frmtData["includePath"]);
            $instance = new $frmtData["class"]();
            $this->formatObjects[$frmt]["encode"] = [&$instance,$frmtData["encodeMethod"]];
            $this->formatObjects[$frmt]["decode"] = [&$instance,$frmtData["decodeMethod"]];
            if($frmtData["postCreate"]){
                call_user_func($frmtData["postCreate"],$instance);
            }
        }

        $this->pearDB = $adb;
        $this->operationName = $operationName;
        $this->inParamProcess = [];
        $this->inParamProcess["encoded"] = &$this->formatObjects[$this->format]["decode"];
        $this->fillOperationDetails($operationName);
    }

    public function isPreLoginOperation(){
        return $this->preLogin == 1;
    }

    private function fillOperationDetails($operationName){
        global $table_prefix;
        $sql = "select type, handler_method, handler_path, prelogin, name, operationid from {$table_prefix}_ws_operation where name=?";
        $result = $this->pearDB->pquery($sql,array($operationName));
        if($result){
            $rowCount = $this->pearDB->num_rows($result);
            if($rowCount > 0){
                $row = $this->pearDB->query_result_rowdata($result,0);
                $this->type = $row['type'];
                $this->handlerMethod = $row['handler_method'];
                $this->handlerPath = $row['handler_path'];
                $this->preLogin = $row['prelogin'];
                $this->operationName = $row['name'];
                $this->operationId = $row['operationid'];
                $this->fillOperationParameters();
                return;
            }
        }
        throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION,"Unknown operation requested");
    }

    public function sanitizeOperation($input){
        return $this->sanitizeInputForType($input);
    }

    public function sanitizeInputForType($input){
        $sanitizedInput = [];
        foreach($this->operationParams as $ind=>$columnDetails){
            foreach ($columnDetails as $columnName => $type) {
                $sanitizedInput[$columnName] = $this->handleType($type,vtws_getParameter($input,$columnName));;
            }
        }
        return $sanitizedInput;
    }

    private function fillOperationParameters(){
        global $table_prefix;
        $sql = "select name, type from {$table_prefix}_ws_operation_parameters where operationid=? order by sequence";
        $result = $this->pearDB->pquery($sql,array($this->operationId));
        $this->operationParams = array();
        if($result){
            $rowCount = $this->pearDB->num_rows($result);
            if($rowCount > 0){
                for ($i=0;$i<$rowCount;++$i){
                    $row = $this->pearDB->query_result_rowdata($result,$i);
                    array_push($this->operationParams,[$row['name']=>$row['type']]);
                }
            }
        }
    }

    public function getOperationInput(){
        $type = strtolower($this->type);
        switch($type){
            case 'post': $input = &$_POST;
                return $input;
            case 'get': $input = &$_GET;
                return $input;
            default: $input = &$_REQUEST;
                return $input;
        }
    }

    public function handleType($type,$value){
        $value = stripslashes($value);
        $type = strtolower($type);
        if($this->inParamProcess[$type]){
            $result = call_user_func($this->inParamProcess[$type],$value);
        }else{
            $result = $value;
        }
        return $result;
    }

    public function runOperation($params,$user){
        global $API_VERSION;
        try{
            $operation = strtolower($this->operationName);

            //auditing
            // crmv@202301
            require_once('modules/Settings/AuditTrail.php');
            $AuditTrail = new AuditTrail();
            $AuditTrail->processWS($operation, $params, $user);
            // crmv@202301e

            if(!$this->preLogin){
                $params[] = $user;
                return call_user_func_array($this->handlerMethod,$params);
            }else{
                $userDetails = call_user_func_array($this->handlerMethod,$params);
                if(!is_array($userDetails)){
                    $this->sessionManager->set("authenticatedUserId", $userDetails->id);
                    global $adb;
                    $webserviceObject = VtenextWebserviceObject::fromName($adb,"Users");//crmv@207871
                    $userId = vtws_getId($webserviceObject->getEntityId(),$userDetails->id);
                    $vteVersion = vtws_getVteVersion();
                    //crmv@2390m
                    return [
                        "sessionName"=>$this->sessionManager->getSessionId(),
                        "userId"=>$userId,
                        "version"=>$API_VERSION,
                        "vteVersion"=>$vteVersion,
                        "timezone"=>$userDetails->timezonediff
                    ];
                }else{
                    return $userDetails;
                }
            }
        }catch(WebServiceException $e){
            throw $e;
        }catch(Exception $e){
            throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR,"Unknown Error while processing request");
        }

    }

    public function encode($param){
        return call_user_func($this->formatObjects[$this->format]["encode"],$param);
    }

    public function getOperationIncludes(){
        $includes = [];
        array_push($includes,$this->handlerPath);
        return $includes;
    }

    //crmv@170283
    public function getOperationParams() {
        return $this->operationParams;
    }
    //crmv@170283e
}
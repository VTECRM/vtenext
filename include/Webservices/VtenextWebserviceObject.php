<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class VtenextWebserviceObject{

    private $id;
    private $name;
    private $handlerPath;
    private $handlerClass;

    private function __construct($entityId,$entityName,$handler_path,$handler_class){
        $this->id = $entityId;
        $this->name = $entityName;
        $this->handlerPath = $handler_path;
        $this->handlerClass = $handler_class;
    }

    // Cache variables to enable result re-use
    private static $_fromNameCache = array();

    static function fromName($adb,$entityName){
        global $table_prefix;
        //crmv@sdk-24185
        $sdkClass = SDK::getClasses('_parent');
        if ($sdkClass[$entityName] != '') {
            $entityName = $sdkClass[$entityName];
        }
        //crmv@sdk-24185 e

        $rowData = false;

        // If the information not available in cache?
        if(!isset(self::$_fromNameCache[$entityName])) {
            $result = $adb->pquery("select * from ".$table_prefix."_ws_entity where name=?",array($entityName));
            if($result){
                $rowCount = $adb->num_rows($result);
                if($rowCount === 1){
                    $rowData = $adb->query_result_rowdata($result,0);
                    self::$_fromNameCache[$entityName] = $rowData;
                }
            }
        }

        $rowData = self::$_fromNameCache[$entityName];

        if($rowData) {
            return new VtenextWebserviceObject($rowData['id'],$rowData['name'],
                $rowData['handler_path'],$rowData['handler_class']);
        }
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied for name");
    }

    // Cache variables to enable result re-use
    private static $_fromIdCache = array();

    static function fromId($adb,$entityId){
        $rowData = false;
        global $table_prefix;
        // If the information not available in cache?
        if(!isset(self::$_fromIdCache[$entityId])) {
            //crmv@fix save
            $idComponents = vtws_getIdComponents($entityId);
            $id=$idComponents[0];
            //crmv@fix save end
            $result = $adb->pquery("select * from ".$table_prefix."_ws_entity where id=?",array($id));
            if($result){
                $rowCount = $adb->num_rows($result);
                if($rowCount === 1){
                    $rowData = $adb->query_result_rowdata($result,0);
                    self::$_fromIdCache[$entityId] = $rowData;
                }
            }
        }

        $rowData = self::$_fromIdCache[$entityId];

        if($rowData) {
            return new VtenextWebserviceObject($rowData['id'],$rowData['name'],
                $rowData['handler_path'],$rowData['handler_class']);
        }

        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied for id");
    }

    static function fromQuery($adb,$query){
        $moduleRegex = "/[fF][rR][Oo][Mm]\s+([^\s;]+)/";
        $matches = array();
        $found = preg_match($moduleRegex,$query,$matches);
        if($found === 1){
            return VtenextWebserviceObject::fromName($adb,trim($matches[1]));//crmv@207871
        }
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied for query");
    }

    public function getEntityName(){
        return $this->name;
    }

    public function getEntityId(){
        return $this->id;
    }

    public function getHandlerPath(){
        return $this->handlerPath;
    }

    public function getHandlerClass(){
        return $this->handlerClass;
    }

}
?>
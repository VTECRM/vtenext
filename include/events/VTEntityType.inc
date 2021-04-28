<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

require_once('include/events/SqlResultIterator.inc');

class VTEntityType{
    public function __construct($adb, $setype){
        global $table_prefix;
        $this->moduleName=$setype;
        require_once("modules/".$setype."/".$setype.".php");
        $result = $adb->pquery("select tabid from ".$table_prefix."_tab where name=?", array($setype));
        $tabId = $adb->query_result($result,0,"tabid");
        $this->tabId = $tabId;
        $this->adb = $adb;
    }

    public function getTabId(){
        return $this->tabId;
    }

    public function getModuleName(){
        return $this->moduleName;
    }

    public function getFieldType($fieldName){
        global $table_prefix;
        $adb = $this->adb;
        $result = $adb->pquery("select uitype from {$table_prefix}_field where fieldname=? and tabid=? and presence in (0,2)",
            array($fieldName, $this->tabId));
        $uitype = $adb->query_result($result,0,"uitype");
        $fieldType = new VTFieldType();
        if(in_array($uitype, [50, 51, 73])){
            $fieldType->type = "Related";
            $fieldType->relatedTo = "Accounts";
        }else if($uitype == 71){
            $fieldType->type = "Number";
        }else{
            $fieldType->type = "String";
        }
        return $fieldType;
    }

    public function getFieldTypes(){
        $fieldNames = $this->getFieldNames();
        $fieldTypes = [];
        foreach($fieldNames as $fieldName){
            $fieldTypes[$fieldName]=$this->getFieldType($fieldName);
        }
        return $fieldTypes;
    }

    public function getFieldNames(){
        global $table_prefix;
        $adb = $this->adb;
        $arr = array();
        $result = $adb->pquery("select fieldname from {$table_prefix}_field where tabid=? and presence in (0,2)",
            array($this->getTabId()));
        $it = new SQLResultIterator($adb, $result);
        foreach($it as $row){
            $arr[] = $row->fieldname;
        }
        return $arr;
    }
}

class VTFieldType{
    public function toArray(){
        $ro = new ReflectionObject($this);
        $data = [];
        $props = $ro->getProperties();
        foreach($props as $prop){
            $data[$prop->getName()]=$prop->getValue($this);
        }
        return $data;
    }

}
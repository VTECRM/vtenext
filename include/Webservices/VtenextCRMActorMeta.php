<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

class VtenextCRMActorMeta extends EntityMeta {
    protected $pearDB;
    protected static $fieldTypeMapping = [];

    public function __construct($tableName,$webserviceObject,$adb,$user){
        parent::__construct($webserviceObject,$user);
        $this->baseTable = $tableName;
        $this->idColumn = null;
        $this->pearDB = $adb;

        $fieldList = $this->getTableFieldList($tableName);
        $this->moduleFields = [];
        foreach ($fieldList as $field) {
            $this->moduleFields[$field->getFieldName()] = $field;
        }

        $this->pearDB = $adb;
        $this->tableList = [$this->baseTable];
        $this->tableIndexList = [$this->baseTable=>$this->idColumn];
        $this->defaultTableList = [];
    }

    protected function getTableFieldList($tableName){
        //crmv@fix index column
        $tableFieldList = [];
        $factory = WebserviceField::fromArray($this->pearDB,['tablename'=>$tableName]);
        $index_field = WebserviceField::getIndexColumn($this->pearDB,$tableName);
        $dbTableFields = $factory->getTableFields();
        foreach ($dbTableFields as $dbField) {
            if ($dbField->name == $index_field){
                $this->idColumn = $dbField->name;
            }
            $field = $this->getFieldArrayFromDBField($dbField,$tableName);
            $webserviceField = WebserviceField::fromArray($this->pearDB,$field);
            $fieldDataType = $this->getFieldType($dbField,$tableName);
            if($fieldDataType === null){
                $fieldDataType = $this->getFieldDataTypeFromDBType($dbField->type);
            }
            $webserviceField->setFieldDataType($fieldDataType);
            if(strcasecmp($fieldDataType,'reference') === 0){
                $webserviceField->setReferenceList($this->getReferenceList($dbField,$tableName));
            }
            array_push($tableFieldList,$webserviceField);
        }
        if ($this->idColumn === null){
            throw new WebServiceException(WebServiceErrorCode::$UNKOWNENTITY,
                "No Index column for $tableName!!! ");
        }
        //crmv@fix index column end
        return $tableFieldList;
    }

    protected function getFieldArrayFromDBField($dbField,$tableName){
        $field = [];
        $field['fieldlabel'] = str_replace('_', ' ',$dbField->name);
        $field['displaytype'] = 1;
        $field['fieldname'] = $dbField->name;
        $field['columnname'] = $dbField->name;
        $field['tablename'] = $tableName;
        $field['uitype'] = 1;
        $fieldDataType = $this->getFieldType($dbField,$tableName);
        if($fieldDataType !== null){
            $fieldType = $this->getTypeOfDataForType($fieldDataType);
        }else{
            $fieldType = $this->getTypeOfDataForType($dbField->type);
        }
        $typeOfData = null;
        if(($dbField->not_null && !$dbField->primary_key) || $dbField->unique_key == 1){
            $typeOfData = $fieldType.'~M';
        }else{
            $typeOfData = $fieldType.'~O';
        }
        $field['typeofdata'] = $typeOfData;
        $field['tabid'] = null;
        $field['fieldid'] = null;
        $field['masseditable'] = 0;
        $field['presence'] = '0';
        return $field;
    }

    protected function getReferenceList($dbField, $tableName){
        global $table_prefix;
        static $referenceList = [];
        if(isset($referenceList[$dbField->name])){
            return $referenceList[$dbField->name];
        }
        if(!isset(VtenextCRMActorMeta::$fieldTypeMapping[$tableName][$dbField->name])){//crmv@207871
            $this->getFieldType($dbField, $tableName);
        }
        $fieldTypeData = VtenextCRMActorMeta::$fieldTypeMapping[$tableName][$dbField->name];//crmv@207871
        $referenceTypes = [];
        $sql = "select type from ".$table_prefix."_ws_entity_referencetype where fieldtypeid=?";
        $result = $this->pearDB->pquery($sql,array($fieldTypeData['fieldtypeid']));
        $numRows = $this->pearDB->num_rows($result);
        for($i=0;$i<$numRows;++$i){
            array_push($referenceTypes,$this->pearDB->query_result($result,$i,"type"));
        }
        $referenceList[$dbField->name] = $referenceTypes;
        return $referenceTypes;
    }

    protected function getFieldType($dbField,$tableName){
        global $table_prefix;
        if(isset(VtenextCRMActorMeta::$fieldTypeMapping[$tableName][$dbField->name])){//crmv@207871
            if(VtenextCRMActorMeta::$fieldTypeMapping[$tableName][$dbField->name] === 'null'){//crmv@207871
                return null;
            }
            $row = VtenextCRMActorMeta::$fieldTypeMapping[$tableName][$dbField->name];//crmv@207871
            return $row['fieldtype'];
        }
        $sql = "select * from ".$table_prefix."_ws_entity_fieldtype where table_name=? and field_name=?";
        $result = $this->pearDB->pquery($sql,array($tableName,$dbField->name));
        $rowCount = $this->pearDB->num_rows($result);
        if($rowCount > 0){
            $row = $this->pearDB->query_result_rowdata($result,0);
            VtenextCRMActorMeta::$fieldTypeMapping[$tableName][$dbField->name] = $row;//crmv@207871
            return $row['fieldtype'];
        }else{
            VtenextCRMActorMeta::$fieldTypeMapping[$tableName][$dbField->name] = 'null';//crmv@207871
            return null;
        }
    }

    protected function getTypeOfDataForType($type){
        switch($type){
            case 'email': return 'E';
            case 'password': return 'P';
            case 'date': return 'D';
            case 'datetime': return 'DT';
            case 'timestamp': return 'T';
            case 'int':
            case 'integer': return 'I';
            case 'decimal':
            case 'numeric': return 'N';
            case 'varchar':
            case 'text':
            default: return 'V';
        }
    }

    protected function getFieldDataTypeFromDBType($type){
        switch($type){
            case 'date': return 'date';
            case 'datetime': return 'datetime';
            case 'timestamp': return 'time';
            case 'int':
            case 'integer': return 'integer';
            case 'real':
            case 'decimal':
            case 'numeric': return 'double';
            case 'text': return 'text';
            case 'varchar': return 'string';
            default: return $type;
        }
    }

    public function hasPermission($operation,$webserviceId){
        if(is_admin($this->user)){
            return true;
        }else{
            if(strcmp($operation,EntityMeta::$RETRIEVE)===0){
                return true;
            }
            return false;
        }
    }

    public function hasAccess(){
        return true;
    }

    public function hasReadAccess(){
        return true;
    }

    public function hasWriteAccess(){
        if(!is_admin($this->user)){
            return false;
        }else{
            return true;
        }
    }

    //crmv@180123
    public function hasAssignPrivilege($webserviceId){
        $idComponents = vtws_getIdComponents($webserviceId);
        $userId=$idComponents[1];
        $ownerTypeId = $idComponents[0];

        if($userId == null || $userId =='' || $ownerTypeId === null || $ownerTypeId ==''){
            return false;
        }

        // administrator's have assign privilege
        if(is_admin($this->user)) return true;

        if($this->user->id === $userId){
            return true;
        }
        return false;
    }
    //crmv@180123e

    public function hasDeleteAccess(){
        if(!is_admin($this->user)){
            return false;
        }else{
            return true;
        }
    }

    public function getEntityName(){
        return $this->webserviceObject->getEntityName();
    }
    public function getEntityId(){
        return $this->webserviceObject->getEntityId();
    }

    public function getObjectEntityName($webserviceId){
        $idComponents = vtws_getIdComponents($webserviceId);
        $id=$idComponents[1];

        if($this->exists($id)){
            return $this->webserviceObject->getEntityName();
        }
        return null;
    }

    public function exists($recordId){
        $exists = false;
        $sql = 'select * from '.$this->baseTable.' where '.$this->getObectIndexColumn().'=?';
        $result = $this->pearDB->pquery($sql , array($recordId));
        if($result != null && isset($result)){
            if($this->pearDB->num_rows($result)>0){
                $exists = true;
            }
        }
        return $exists;
    }

    public function getNameFields(){
        global $table_prefix;
        $query = "select name_fields from ".$table_prefix."_ws_entity_name where entity_id = ?";
        $result = $this->pearDB->pquery($query, array($this->objectId));
        $fieldNames = '';
        if($result){
            $rowCount = $this->pearDB->num_rows($result);
            if($rowCount > 0){
                $fieldNames = $this->pearDB->query_result($result,0,'name_fields');
            }
        }
        return $fieldNames;
    }

    public function getName($webserviceId){
        $idComponents = vtws_getIdComponents($webserviceId);
        $entityId = $idComponents[0];
        $id=$idComponents[1];

        $nameList = vtws_getActorEntityNameById($entityId, array($id));
        return $nameList[$id];
    }

    public function getEntityAccessControlQuery() {
        return '';
    }

    public function getEntityDeletedQuery() {
        global $table_prefix;
        if($this->getEntityName() == 'Currency'){
            return $table_prefix.'_currency_info.deleted=0';
        }

        return '';
    }

    public function isModuleEntity() {
        return false;
    }
}
?>
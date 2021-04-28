<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

class VtenextModuleOperation extends WebserviceEntityOperation {
    protected $tabId;
    protected $isEntity = true;

    public function __construct($webserviceObject,$user,$adb,$log){
        parent::__construct($webserviceObject,$user,$adb,$log);
        $this->meta = $this->getMetaInstance();
        $this->tabId = $this->meta->getTabId();
    }

    protected function getMetaInstance(){
        if(empty(WebserviceEntityOperation::$metaCache[$this->webserviceObject->getEntityName()][$this->user->id])){
            WebserviceEntityOperation::$metaCache[$this->webserviceObject->getEntityName()][$this->user->id]  = new VtenextCRMObjectMeta($this->webserviceObject,$this->user);//crmv@207871
        }
        return WebserviceEntityOperation::$metaCache[$this->webserviceObject->getEntityName()][$this->user->id];
    }

    public function create($elementType,$element){
        $crmObject = new VtenextCRMObject($elementType, false);//crmv@207871

        $element = DataTransform::sanitizeForInsert($element,$this->meta);

        $error = $crmObject->create($element);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        $id = $crmObject->getObjectId();

        // Bulk Save Mode
        if(CRMEntity::isBulkSaveMode()) {
            // Avoiding complete read, as during bulk save mode, $result['id'] is enough
            return ['id' => vtws_getId($this->meta->getEntityId(), $id)];
        }

        $error = $crmObject->read($id);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
    }

    public function revise($element){
        $ids = vtws_getIdComponents($element["id"]);
        $element = DataTransform::sanitizeForInsert($element,$this->meta);

        $crmObject = new VtenextCRMObject($this->tabId, true);//crmv@207871
        $crmObject->setObjectId($ids[1]);
        $error = $crmObject->revise($element);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        $id = $crmObject->getObjectId();

        $error = $crmObject->read($id);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
    }

    public function delete($id){
        $ids = vtws_getIdComponents($id);
        $elemid = $ids[1];

        $crmObject = new VtenextCRMObject($this->tabId, true);//crmv@207871

        $error = $crmObject->delete($elemid);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }
        return ["status"=>"successful"];
    }

    // crmv@57042 - extra check for some modules
    protected function sanitizeSqlQuery($query) {
        global $table_prefix;

        $module = $this->meta->getTabName();
        if (in_array($module, ['Messages', 'MyNotes'])) {
            // force to retrieve only owned records
            // the where is always present (id > 0)
            $cond = "{$table_prefix}_crmentity.smownerid = {$this->user->id}";
            // crmv@189338
            $instance = CRMEntity::getInstance($module);
            if (!in_array($table_prefix.'_crmentity',$instance->tab_name)) {
                $cond = $instance->table_name.'.smownerid='.$this->user->id;
            }
            // crmv@189338e
            $query = preg_replace("/where\s+/i", "WHERE $cond AND ", $query);
        }

        return $query;
    }
    // crmv@57042e

    public function retrieve($id){
        $ids = vtws_getIdComponents($id);
        $elemid = $ids[1];

        $crmObject = new VtenextCRMObject($this->tabId, true);//crmv@207871
        $error = $crmObject->read($elemid);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
    }

    public function update($element){
        $ids = vtws_getIdComponents($element["id"]);
        $element = DataTransform::sanitizeForInsert($element,$this->meta);

        $crmObject = new VtenextCRMObject($this->tabId, true);//crmv@207871
        $crmObject->setObjectId($ids[1]);
        $error = $crmObject->update($element);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        $id = $crmObject->getObjectId();

        $error = $crmObject->read($id);
        if(!$error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
    }

    public function query($q,$limit=false){
        $parser = new Parser($this->user, $q);
        $error = $parser->parse();

        if($error){
            return $parser->getError();
        }

        $mysql_query = $parser->getSql();
        $mysql_query = $this->sanitizeSqlQuery($mysql_query);	// crmv@57042

        //crmv@55311
        if($limit === false){
            $limit = $parser->getLimit();
        }
        //crmv@55311e

        $meta = $parser->getObjectMetaData();
        $this->pearDB->startTransaction();
        if ($limit){
            list($start,$stop) = $limit;
            $result = $this->pearDB->limitQuery($mysql_query,$start,$stop);
        }
        else{
            $result = $this->pearDB->pquery($mysql_query, []);
        }
        //crmv@9426
        global $listQueryResult;
        $listQueryResult = $result;
        //crmv@9426 end
        $error = $this->pearDB->hasFailedTransaction();
        $this->pearDB->completeTransaction();

        if($error){
            throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
                vtws_getWebserviceTranslatedString('LBL_'.
                    WebServiceErrorCode::$DATABASEQUERYERROR));
        }

        $noofrows = $this->pearDB->num_rows($result);
        $output = [];
        for($i=0; $i<$noofrows; $i++){
            $row = $this->pearDB->fetchByAssoc($result,$i);
            if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$row["crmid"])){
                continue;
            }
            $output[] = DataTransform::sanitizeDataWithColumn($row,$meta);
        }

        return $output;
    }

    public function describe($elementType){
        $app_strings = VTWS_PreserveGlobal::getGlobal('app_strings');

        $label = (isset($app_strings[$elementType]))? $app_strings[$elementType]:$elementType;
        $createable = strcasecmp(isPermitted($elementType,EntityMeta::$CREATE),'yes')===0;
        $updateable = strcasecmp(isPermitted($elementType,EntityMeta::$UPDATE),'yes')===0;
        $deleteable = $this->meta->hasDeleteAccess();
        $retrieveable = $this->meta->hasReadAccess();
        $fields = $this->getModuleFields();
        // crmv@195745
        return [
            "label"=>$label,
            "name"=>$elementType,
            "fields"=>$fields,
            "idPrefix"=>$this->meta->getEntityId(),
            'isEntity'=>$this->isEntity,
            "createable"=>$createable,
            "updateable"=>$updateable,
            "deleteable"=>$deleteable,
            "retrieveable"=>$retrieveable,
            'isInventory' => isInventoryModule($elementType),
            'isProduct' => isProductModule($elementType),
            'labelFields'=>$this->meta->getNameFields()
        ];
        // crmv@195745e
    }

    public function getModuleFields(){
        $fields = [];
        $moduleFields = $this->meta->getModuleFields();
        foreach ($moduleFields as $fieldName=>$webserviceField) {
            if(!$this->meta->show_hidden_fields && ((int)$webserviceField->getPresence()) == 1) {	//crmv@120039
                continue;
            }
            array_push($fields,$this->getDescribeFieldArray($webserviceField));
        }
        array_push($fields,$this->getIdField($this->meta->getObectIndexColumn()));

        return $fields;
    }

    public function getDescribeFieldArray($webserviceField){
        $default_language = VTWS_PreserveGlobal::getGlobal('default_language');

        require 'modules/'.$this->meta->getTabName()."/language/$default_language.lang.php";
        $fieldLabel = $webserviceField->getFieldLabelKey();
        $fieldLabel = getTranslatedString($fieldLabel, $this->meta->getTabName()); // crmv@39110
        $typeDetails = $this->getFieldTypeDetails($webserviceField);

        //set type name, in the type details array.
        $typeDetails['name'] = $webserviceField->getFieldDataType();
        $editable = $this->isEditable($webserviceField);

        //crmv@31780
        $describeArray = [
            'name'=>$webserviceField->getFieldName(),
            'label'=>$fieldLabel,
            'type'=>$typeDetails,
            'nullable'=>$webserviceField->isNullable(),
            'editable'=>$editable,
            'mandatory'=>$webserviceField->isMandatory($this->user),	//crmv@49510
            // added properties
            'fieldid'=>$webserviceField->getFieldId(),
            'uitype'=>$webserviceField->getUitype(),
            'blockid'=>$webserviceField->getBlockId(),
            'panelid'=>$webserviceField->getPanelId(), // crmv@104568
            'sequence'=>$webserviceField->getSequence(),
        ];
        //crmv@31780e
        if($webserviceField->hasDefault()){
            $describeArray['default'] = $webserviceField->getDefault();
        }
        return $describeArray;
    }

    public function getMeta(){
        return $this->meta;
    }

    public function getField($fieldName){
        $moduleFields = $this->meta->getModuleFields();
        return $this->getDescribeFieldArray($moduleFields[$fieldName]);
    }
}
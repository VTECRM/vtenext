<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

abstract class EntityMeta{
	
	public static $RETRIEVE = "DetailView";
	public static $CREATE = "Save";
	public static $UPDATE = "EditView";
	public static $DELETE = "Delete";
	
	protected $webserviceObject;
	protected $objectName;
	protected $objectId;
	protected $user;
	protected $baseTable;
	protected $tableList;
	protected $tableIndexList;
	protected $defaultTableList;
	protected $idColumn;
	
	protected $userAccessibleColumns;
	protected $columnTableMapping;
	protected $fieldColumnMapping;
	protected $mandatoryFields;
	protected $referenceFieldDetails;
	protected $emailFields;
	protected $ownerFields;
	protected $dataFields;	//crmv@120769
	protected $hourFields;	//crmv@128159
	protected $moduleFields;
	
	protected function __construct($webserviceObject,$user){
		$this->webserviceObject = $webserviceObject;
		$this->objectName = $this->webserviceObject->getEntityName();
		$this->objectId = $this->webserviceObject->getEntityId();
		
		$this->user = $user;
	}
	
	public function getEmailFields(){
		if($this->emailFields === null){
			$this->emailFields =  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				if(strcasecmp($webserviceField->getFieldType(),'e') === 0){
					array_push($this->emailFields, $fieldName);
				}
			}
		}
		
		return $this->emailFields;
	}
	
	public function getFieldColumnMapping(){
		if($this->fieldColumnMapping === null){
			$this->fieldColumnMapping =  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				$this->fieldColumnMapping[$fieldName] = $webserviceField->getColumnName();
			}
			$this->fieldColumnMapping['id'] = $this->idColumn;
		}
		return $this->fieldColumnMapping;
	}
	
	public function getMandatoryFields(){
		if($this->mandatoryFields === null){
			$this->mandatoryFields =  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				if($webserviceField->isMandatory($this->user) === true){	//crmv@49510
					array_push($this->mandatoryFields,$fieldName);
				}
			}
		}
		return $this->mandatoryFields;
	}
	
	public function getReferenceFieldDetails(){
		if($this->referenceFieldDetails === null){
			$this->referenceFieldDetails =  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				if(strcasecmp($webserviceField->getFieldDataType(),'reference') === 0){
					$this->referenceFieldDetails[$fieldName] = $webserviceField->getReferenceList();
				}
			}
		}
		return $this->referenceFieldDetails;
	}
	
	public function getOwnerFields(){
		if($this->ownerFields === null){
			$this->ownerFields =  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				if(strcasecmp($webserviceField->getFieldDataType(),'owner') === 0){
					array_push($this->ownerFields, $fieldName);
				}
			}
		}
		return $this->ownerFields;
	}

	//crmv@120769
	public function getDataFields(){
		if($this->dataFields=== null){
			$this->dataFields=  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				if(strcasecmp($webserviceField->getFieldDataType(),'date') === 0){
					array_push($this->dataFields, $fieldName);
				}
			}
		}
		return $this->dataFields;
	}
	//crmv@120769e
	//crmv@128159
	public function getHourFields(){
		if($this->hourFields=== null){
			$this->hourFields=  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				if ($webserviceField->getUIType() == '73') {
					array_push($this->hourFields, $fieldName);
				}
			}
		}
		return $this->hourFields;
	}
	//crmv@128159e
	
	public function getObectIndexColumn(){
		return $this->idColumn;
	}
	
	public function getUserAccessibleColumns(){
		if($this->userAccessibleColumns === null){
			$this->userAccessibleColumns =  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				array_push($this->userAccessibleColumns,$webserviceField->getColumnName());
			}
			array_push($this->userAccessibleColumns,$this->idColumn);
		}
		return $this->userAccessibleColumns;
	}

	public function getFieldByColumnName($column){
		$fields = $this->getModuleFields();
		foreach ($fields as $fieldName=>$webserviceField) {
			if($column == $webserviceField->getColumnName()) {
				return $webserviceField;
			}
		}
		return null;
	}
	
	public function getColumnTableMapping(){
		if($this->columnTableMapping === null){
			$this->columnTableMapping =  array();
			foreach ($this->moduleFields as $fieldName=>$webserviceField) {
				$this->columnTableMapping[$webserviceField->getColumnName()] = $webserviceField->getTableName();
			}
			$this->columnTableMapping[$this->idColumn] = $this->baseTable;
		}
		return $this->columnTableMapping;
	}
	
	function getUser(){
		return $this->user;
	}
	
	function hasMandatoryFields($row){
		
		$mandatoryFields = $this->getMandatoryFields();
		$hasMandatory = true;
		foreach($mandatoryFields as $ind=>$field){
			// dont use empty API as '0'(zero) is a valid value.
			if( !isset($row[$field]) || $row[$field] === "" || $row[$field] === null ){
				throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING,
						"$field does not have a value");
			}
		}
		return $hasMandatory;
		
	}
	public function isUpdateMandatoryFields($element){
		if(!is_array($element)){
			throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING,
							"Mandatory field does not have a value");
		}
		$mandatoryFields = $this->getMandatoryFields();
		$updateFields = array_keys($element);
		$hasMandatory = true;
		$updateMandatoryFields = array_intersect($updateFields, $mandatoryFields);
		if(!empty($updateMandatoryFields)){
			foreach($updateMandatoryFields as $ind=>$field){
				// dont use empty API as '0'(zero) is a valid value.
				if( !isset($element[$field]) || $element[$field] === "" || $element[$field] === null ){
					throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING,
							"$field does not have a value");
				}
			}
		}
		return $hasMandatory;
	}
	
	public function getModuleFields(){
		return $this->moduleFields;
	}

	public function getFieldNameListByType($type) { 
		$type = strtolower($type); 
		$typeList = array(); 
		$moduleFields = $this->getModuleFields(); 
		foreach ($moduleFields as $fieldName=>$webserviceField) { 
			if(strcmp($webserviceField->getFieldDataType(),$type) === 0){ 
				array_push($typeList, $fieldName); 
			} 
		} 
		return $typeList; 
	}

	public function getFieldListByType($type) {
		$type = strtolower($type);
		$typeList = array();
		$moduleFields = $this->getModuleFields();
		foreach ($moduleFields as $fieldName=>$webserviceField) {
			if(strcmp($webserviceField->getFieldDataType(),$type) === 0){
				array_push($typeList, $webserviceField);
			}
		}
		return $typeList;
	}
	
	public function getIdColumn(){
		return $this->idColumn;
	}

	public function getEntityBaseTable() {
		return $this->baseTable;
	}

	public function getEntityTableIndexList() {
		return $this->tableIndexList;
	}

	public function getEntityDefaultTableList() {
		return $this->defaultTableList;
	}

	public function getEntityTableList() {
		return $this->tableList;
	}

	public function getEntityAccessControlQuery(){
		$accessControlQuery = '';
		return $accessControlQuery;
	}

	public function getEntityDeletedQuery(){
		global $table_prefix;
		if($this->getEntityName() == 'Leads') {
			return $table_prefix."_crmentity.deleted=0 and ".$table_prefix."_leaddetails.converted=0";
		}
		if($this->getEntityName() == 'Calendar') {
			return $table_prefix."_crmentity.deleted=0 and ".$table_prefix."_activity.activitytype not in ('Emails')"; // crmv@152701
		}
		// crmv@152701 - removed code
		if($this->getEntityName() == "Users"){
			return $table_prefix."_users.status='Active'";
		}
		//crmv@171021
		$module = $this->getEntityName();
		$instance = CRMEntity::getInstance($module);
		if (!in_array($table_prefix.'_crmentity',$instance->tab_name)) {
			return $instance->table_name.'.deleted=0';
		}
		//crmv@171021e
		return $table_prefix."_crmentity.deleted=0";
	}

	// crmv@205306
	public function getWebserviceObject() {
		return $this->webserviceObject;
	}
	// crmv@205306e

	abstract function hasPermission($operation,$webserviceId);
	abstract function hasAssignPrivilege($ownerWebserviceId);
	abstract function hasDeleteAccess();
	abstract function hasAccess();
	abstract function hasReadAccess();
	abstract function hasWriteAccess();
	abstract function getEntityName();
	abstract function getEntityId();
	abstract function exists($recordId);
	abstract function getObjectEntityName($webserviceId);
	abstract public function getNameFields();
	abstract public function getName($webserviceId);
	abstract public function isModuleEntity();
}
?>
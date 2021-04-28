<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	//crmv@26244
	function vtws_update2($id,$columns,$user){

		global $log,$adb;
		$idList = vtws_getIdComponents($id);
		$webserviceObject = VtenextWebserviceObject::fromId($adb,$idList[0]);//crmv@207871
		$handlerPath = $webserviceObject->getHandlerPath();
		$handlerClass = $webserviceObject->getHandlerClass();

		require_once $handlerPath;
		
		$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
		$meta = $handler->getMeta();
		$entityName = $meta->getObjectEntityName($id);
		
		$types = vtws_listtypes(null,$user); //crmv@outlook
		if(!in_array($entityName,$types['types'])){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
		}
		
		if($entityName !== $webserviceObject->getEntityName()){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
		}
		
		if(!$meta->hasPermission(EntityMeta::$UPDATE,$id)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
		}
		
		if(!$meta->exists($idList[1])){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
		}
		
		if($meta->hasWriteAccess()!==true){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
		}
		
		$tmp_columns = $handler->retrieve($id);
		$columns = array_merge($tmp_columns,$columns);
		
		$_REQUEST['ajxaction'] = 'DETAILVIEW';
		if ($entityName == 'SalesOrder' && $columns['enable_recurring'] == 0 && $columns['invoicestatus'] == '') {
			$columns['invoicestatus'] = 'Created';
		}

		$referenceFields = $meta->getReferenceFieldDetails();
		foreach($referenceFields as $fieldName=>$details){
			if(isset($columns[$fieldName]) && strlen($columns[$fieldName]) > 0){
				$ids = vtws_getIdComponents($columns[$fieldName]);
				$elemTypeId = $ids[0];
				$elemId = $ids[1];
				$referenceObject = VtenextWebserviceObject::fromId($adb,$elemTypeId);//crmv@207871
				if(!in_array($referenceObject->getEntityName(),$details)){
					throw new WebServiceException(WebServiceErrorCode::$REFERENCEINVALID,
						"Invalid reference specified for $fieldName");
				}
				if(!in_array($referenceObject->getEntityName(),$types['types'])){
					throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,
						"Permission to access reference type is denied ".$referenceObject->getEntityName());
				}
			}else if($columns[$fieldName] !== NULL){
				unset($columns[$fieldName]);
			}
		}

		$meta->hasMandatoryFields($columns);

		$ownerFields = $meta->getOwnerFields();
		if(is_array($ownerFields) && sizeof($ownerFields) >0){
			foreach($ownerFields as $ownerField){
				if(isset($columns[$ownerField]) && $columns[$ownerField]!==null && 
					!$meta->hasAssignPrivilege($columns[$ownerField])){
					throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Cannot assign record to the given user");
				}
			}
		}
		
		$entity = $handler->update($columns);
		VTWS_PreserveGlobal::flush();
		return $entity;
	}
	//crmv@26244e
?>
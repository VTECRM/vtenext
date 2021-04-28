<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * @author MAK
 */

function vtws_deleteUser($id, $newOwnerId,$user){
		global $log,$adb,$table_prefix;
		$webserviceObject = VtenextWebserviceObject::fromId($adb,$id);//crmv@207871
		$handlerPath = $webserviceObject->getHandlerPath();
		$handlerClass = $webserviceObject->getHandlerClass();

		require_once $handlerPath;

		$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
		$meta = $handler->getMeta();
		$entityName = $meta->getObjectEntityName($id);

		$types = vtws_listtypes($user);
		if(!in_array($entityName,$types['types'])){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,
					"Permission to perform the operation is denied, EntityName = ".$entityName);
		}

		if($entityName !== $webserviceObject->getEntityName()){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID,
					"Id specified is incorrect");
		}

		if(!$meta->hasPermission(EntityMeta::$DELETE,$id)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,
					"Permission to read given object is denied");
		}

		$idComponents = vtws_getIdComponents($id);
		if(!$meta->exists($idComponents[1])){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,
					"Record you are trying to access is not found, idComponent = ".$idComponents);
		}

		if($meta->hasWriteAccess()!==true){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,
					"Permission to write is denied");
		}

		$newIdComponents = vtws_getIdComponents($newOwnerId);
		if(empty($newIdComponents[1])) {
			//force the default user to be the default admin user.
			//added cause eazybusiness team is sending this value empty
			$newIdComponents[1] = Users::getActiveAdminId(); // crmv@177071
		}
		
		vtws_transferOwnership($idComponents[1], $newIdComponents[1], true); // crmv@177071

		VTWS_PreserveGlobal::flush();
		return  array("status"=>"successful");
	}

?>
<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@195835 */

/**
 * Relate the record $id with all the records in $relatelist array
 * If relationid is empty, the first relation with the matching module will be used,
 * otherwise the specified relation will be used. In this case, all records in relatelist
 * must be of the same module.
 * NOTE: at the moment only N to N relations are supported
 * NOTE: at the moment the relationid is not used, but its validity is checked
 */
function vtws_relate($id, $relatelist, $relationid, $user) {

	global $log,$adb;
	
	if (!is_array($relatelist)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Relatelist must be an array");
	}
	
	$idList = vtws_getIdComponents($id);
	$sourceId = $idList[1];
	$webserviceObject = VtenextWebserviceObject::fromId($adb,$idList[0]);//crmv@207871
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();
	
	require_once $handlerPath;

	$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
	$meta = $handler->getMeta();
	$entityName = $meta->getObjectEntityName($id);

	$types = vtws_listtypes(null, $user);
	if (!in_array($entityName,$types['types'])) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}

	if ($entityName !== $webserviceObject->getEntityName()) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
	}
	
	if (!$meta->hasPermission(EntityMeta::$UPDATE,$id)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
	}
	
	if (!$meta->exists($sourceId)) {
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
	}
		
	if ($meta->hasWriteAccess()!==true) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
	}
	
	// get relations for the first module
	$RM = RelationManager::getInstance();
	$rels = $RM->getRelations($entityName);
	
	$listrel = array();
	$relmods = array();
	foreach ($rels as $rel) {
		$secmod = $rel->getSecondModule();
		
		if (in_array($secmod,$types['types'])) {
			$listrel[$rel->relationid] = array(
				'first_module' => $rel->getFirstModule(),
				'second_module' => $secmod,
				'type' => $rel->getType(),
				'fieldid' => $rel->getFieldId(),
			);
			$relmods[$secmod][] = $rel->relationid;
		}
	}
	
	// split ids by module
	$relids = array();
	foreach ($relatelist as $relid) {
		list($modid, $crmid) = vtws_getIdComponents($relid);
		$module = $meta->getObjectEntityName($relid);
		if (!array_key_exists($module, $relmods)) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"There is no relation with module $module");
		}
		if ($crmid) {
			$relids[$module][] = intval($crmid);
		}
	}
	
	// check the relationid passed
	if (!empty($relationid)) {
		if (count($relids) > 0) {
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"You cannot specify a relationid and pass multiple modules to relate");
		}

		if (!array_key_exists($relationid, $listrel)) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"The relationid passed is not valid");
		}
		$hasRelation = true;
		$relation = $listrel[$relationid];
	}
	
	foreach ($relids as $module=>$relids)  {
		if (!$hasRelation) {
			// get the first matching relation for that module
			$relationid = $relmods[$module][0];
			$relation = $listrel[$relationid];
		}
		// TODO: use the relation somehow!
		// TODO: support 1-N relations
		$modInstance = CRMEntity::getInstance($entityName); // crmv@205993
		$modInstance->save_related_module($entityName, $sourceId, $module, $relids, true);
	}


}
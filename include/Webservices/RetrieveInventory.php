<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	//crmv@26244
	function vtws_retrieve_inventory($id, $user){

		global $log,$adb;

		$webserviceObject = VtenextWebserviceObject::fromId($adb,$id);//crmv@207871
		$handlerPath = $webserviceObject->getHandlerPath();
		$handlerClass = $webserviceObject->getHandlerClass();

		require_once $handlerPath;

		$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
		$meta = $handler->getMeta();
		$entityName = $meta->getObjectEntityName($id);
		$types = vtws_listtypes(null,$user);
		if(!in_array($entityName,$types['types'])){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
		}
		if($meta->hasReadAccess()!==true){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
		}

		if($entityName !== $webserviceObject->getEntityName()){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
		}

		if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$id)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
		}

		$idComponents = vtws_getIdComponents($id);
		if(!$meta->exists($idComponents[1])){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
		}

		$entity = $handler->retrieve($id);
		VTWS_PreserveGlobal::flush();

		$product_block = vtws_retrieve_inventory_products($idComponents[1],$entityName);
		if (!empty($product_block['products'][0])) {
			$entity['product_block'] = $product_block;
		}

		return $entity;
	}

	function vtws_retrieve_inventory_products($record_id, $module) {
		global $adb;

		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($record_id,$module);
		$focus->id = $record_id;

		$tmp_product_block = $InventoryUtils->getAssociatedProducts($module,$focus);
		// crmv@31780
		$final_details = $InventoryUtils->getFinalDetails($module, $focus, $record_id);
		if (is_array($final_details) && is_array($final_details[1]['final_details'])) $final_details = $final_details[1]['final_details'];
		// crmv@31780e
		unset($tmp_product_block[1]['final_details']);

		$product_block = array();
		$j = 0;
		foreach($tmp_product_block as $i => $product) {
			unset($product['delRow'.$i]);
			foreach($product as $k => $v) {
				// crmv@31780
				// rimuovo numeri alla fine
				$newk = preg_replace('/(.*\D)([0-9]+)$/', '\1', $k);
				$product_block[$j][$newk] = $v;
				// crmv@31780e
			}
			$j++;
		}

		return array('products'=>$product_block,'final_details'=>$final_details);
	}
	//crmv@26244e
?>
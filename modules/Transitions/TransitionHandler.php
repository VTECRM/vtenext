<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TransitionHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb, $table_prefix;
		$moduleName = $entityData->getModuleName();
		//crmv@31357
		$trans_obj = CRMEntity::getInstance('Transitions');
		$trans_obj->Initialize($moduleName);
		//crmv@31357e
		if($eventName == 'vte.entity.beforesave') {//crmv@207852
			if ($trans_obj->ismanaged_global()){
				$objId = $entityData->getId();
				$oldStatus = '';
				if(!empty($objId)) {
					$sql = "select columnname,tablename from ".$table_prefix."_field where tabid = ? and fieldname = ?";
					$params = Array(getTabId($moduleName),$trans_obj->status_field);
					$res = $adb->pquery($sql,$params);
					if ($res && $adb->num_rows($res)>0){
						$columnname = $adb->query_result($res,0,'columnname');
						$tablename = $adb->query_result($res,0,'tablename');
						$entity_obj = CRMEntity::getInstance($moduleName);
						$primary_key = $entity_obj->tab_name_index[$tablename];
						$query = "select $columnname from $tablename where $primary_key = ?";
						$res2=$adb->pquery($query,Array($objId));
						if ($res2 && $adb->num_rows($res2)>0){
							$oldStatus = $adb->query_result_no_html($res2,0,$columnname); //crmv@31357
						}
					}
				}
				$entityData->oldStatus = $oldStatus;
			}
		}
		if($eventName == 'vte.entity.aftersave') {//crmv@207852
			if ($trans_obj->ismanaged_global()){
				$objId = $entityData->getId();		
				$objData = $entityData->getData();
				if ($objData[$trans_obj->status_field] != $entityData->oldStatus){	//crmv@16600
					$trans_obj->insertIntoHistoryTable($entityData->oldStatus,$objData[$trans_obj->status_field],$objId,$_REQUEST['motivation']);//crmv@204903
				}
			}
		}
	}
}

?>
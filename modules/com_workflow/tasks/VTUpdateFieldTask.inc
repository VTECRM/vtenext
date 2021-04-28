<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@18199
class VTUpdateFieldTask extends VTTask{
	public $executeImmediately = false;	//crmv@32366
	
	public function getFieldNames(){return array('fieldName','fieldValue');}
	public function doTask($entity){
		global $adb,$current_user;
		$moduleName = $entity->getModuleName();
		list($moduleId,$id) = vtws_getIdComponents($entity->getId());
		
		$handler = vtws_getModuleHandlerFromName($moduleName, $current_user);
		$meta = $handler->getMeta();
		$indexList = $meta->getEntityTableIndexList();
		$referenceList = $meta->getReferenceFieldDetails();
		$moduleFields = $meta->getModuleFields();
		
		// crmv@172616
		$doLog = false;
		$logger = VTESystemLogger::getLogger('workflow');
		// crmv@172616e

		$tmp = explode(' ',$this->fieldName);
		$fieldname = $tmp[0];
		if (in_array($fieldname,array_keys($referenceList))) {		//moduli relazionati
			$fieldname_real = $tmp[count($tmp)-1];
			if (!in_array($entity->data[$fieldname],array('',0))) {
				list($moduleId,$id) = vtws_getIdComponents($entity->data[$fieldname]);
				$moduleName = getSalesEntityType($id);
				
				$handler = vtws_getModuleHandlerFromName($moduleName, $current_user);
				$meta = $handler->getMeta();
				$indexList = $meta->getEntityTableIndexList();
				$referenceList = $meta->getReferenceFieldDetails();
				$moduleFields = $meta->getModuleFields();
	
				$fieldObj = $moduleFields[$fieldname_real];
				if (!$fieldObj) return;
				
				// crmv@172616
				if ($logger) {
					$this->oldFieldValue = getSingleFieldValue($fieldObj->getTableName(), $fieldObj->getColumnName(), $indexList[$fieldObj->getTableName()], $id);
					$doLog = true;
				}
				// crmv@172616e
				
				$adb->pquery('update '.$fieldObj->getTableName().' set '.$fieldObj->getColumnName().' = ? where '.$indexList[$fieldObj->getTableName()].' = '.$id, array($this->fieldValue));
				
				$this->setChangedData(); // crmv@193294
			}
		}
		else {		//modulo corrente
			$fieldObj = $moduleFields[$this->fieldName];
			if (!$fieldObj) return; //crmv@107238
			
			// crmv@172616
			if ($logger) {
				$this->oldFieldValue = getSingleFieldValue($fieldObj->getTableName(), $fieldObj->getColumnName(), $indexList[$fieldObj->getTableName()], $id);
				$doLog = true;
			}
			// crmv@172616e
			
			$adb->pquery('update '.$fieldObj->getTableName().' set '.$fieldObj->getColumnName().' = ? where '.$indexList[$fieldObj->getTableName()].' = '.$id, array($this->fieldValue));
			$this->setChangedData(); // crmv@193294
		}
		
		// crmv@172616
		if ($doLog) {
			$logger->info(
				"WORKFLOW #{$this->workflowId} TASK #{$this->id}: [UPDATE] [$moduleName #$id] Field ".
				$fieldObj->getFieldName().' from "'.$this->oldFieldValue.'" to "'.$this->fieldValue.'"'
			);
		}
		// crmv@172616e
	}
}
//crmv@18199e
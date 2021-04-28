<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/com_workflow/VTEntityMethodManager.inc');//crmv@207901

class VTEntityMethodTask extends VTTask{
	
	public $executeImmediately = true;
	
	public function getFieldNames(){return array('methodName');}
	
	public function doTask($entityData){
		global $adb;
		$emm = new VTEntityMethodManager($adb);
		$executed = $emm->executeMethod($entityData, $this->methodName); // crmv@172616
		
		// crmv@172616
		if ($executed) {
			$logger = VTESystemLogger::getLogger('workflow');
			list($modid, $crmid) = explode('x', $entityData->getId());
			if ($logger) $logger->warning("WORKFLOW #{$this->workflowId} TASK #{$this->id}: [EXECUTE] [".$entityData->getModuleName()." #$crmid] Method {$this->methodName} executed"); 
		}
		// crmv@172616e
	}
}
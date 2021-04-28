<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@19400 crmv@96226 crmv@130458 */

class ServiceContractsHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb, $table_prefix, $current_user;
		
		if($eventName == 'vte.entity.beforesave') {		//crmv@207852
			$moduleName = $entityData->getModuleName();
			if ($moduleName == 'ServiceContracts') {
				$contractId = $entityData->getId();
				$data = $entityData->getData();
				
				$oldTrackingUnit = '';
				if(!empty($contractId)) {
					$contractResult = $adb->pquery('SELECT tracking_unit FROM '.$table_prefix.'_servicecontracts WHERE servicecontractsid = ?', array($contractId));
					if($adb->num_rows($contractResult) > 0) {
						$oldTrackingUnit = $adb->query_result($contractResult,0,'tracking_unit');
					}
				}
				
				$scFocus = CRMEntity::getInstance('ServiceContracts');
				$scFocus->id = $contractId;
				$scFocus->column_fields = $data;
				
				// convert values in db format
				if ($entityData->focus->formatFieldsForSave) {
					// crmv@174253 - removed code
					$scFocus->column_fields['start_date'] = getValidDBInsertDateValue($scFocus->column_fields['start_date']);
					$scFocus->column_fields['end_date'] = getValidDBInsertDateValue($scFocus->column_fields['end_date']);
					$scFocus->column_fields['due_date'] = getValidDBInsertDateValue($scFocus->column_fields['due_date']);
				}
				// Update the Planned Duration, Actual Duration, End Date and Progress based on other field values.		
				if($data['tracking_unit'] != $oldTrackingUnit) { // Need to recompute used_units based when tracking_unit changes.
					$scFocus->updateServiceContractState($saveMode);
				} else {
					$scFocus->calculateProgress($saveMode);
					$scFocus->updateResidualUnits($saveMode);
				}
				// convert values in user format
				if ($entityData->focus->formatFieldsForSave) {
					// crmv@174253 - removed code
					if (!empty($scFocus->column_fields['start_date'])) $scFocus->column_fields['start_date'] = getDisplayDate(substr($scFocus->column_fields['start_date'],0,10));
					if (!empty($scFocus->column_fields['end_date'])) $scFocus->column_fields['end_date'] = getDisplayDate(substr($scFocus->column_fields['end_date'],0,10));
					if (!empty($scFocus->column_fields['due_date'])) $scFocus->column_fields['due_date'] = getDisplayDate(substr($scFocus->column_fields['due_date'],0,10));
				}
				$entityData->set('used_units',$scFocus->column_fields['used_units']);
				$entityData->set('residual_units',$scFocus->column_fields['residual_units']);
				$entityData->set('planned_duration',$scFocus->column_fields['planned_duration']);
				$entityData->set('actual_duration',$scFocus->column_fields['actual_duration']);
				$entityData->set('end_date',$scFocus->column_fields['end_date']);
				$entityData->set('progress',$scFocus->column_fields['progress']);
			}
		}

		if($eventName == 'vte.entity.aftersave') {//crmv@207852
			$moduleName = $entityData->getModuleName();
			
			// Update Used Units for the Service Contract, everytime the status of a ticket related to the Service Contract changes
			if ($moduleName == 'HelpDesk') {
				$ticketId = $entityData->getId();
				$data = $entityData->getData();
				
				if(strtolower($data['ticketstatus']) == 'closed') {
					$ticketId = intval($ticketId);
					$sql = 
						"SELECT {$table_prefix}_crmentityrel.crmid as relcrmid FROM {$table_prefix}_crmentityrel
						INNER JOIN {$table_prefix}_crmentity crm1 ON crm1.crmid = {$table_prefix}_crmentityrel.crmid
						INNER JOIN {$table_prefix}_crmentity crm2 ON crm2.crmid = {$table_prefix}_crmentityrel.relcrmid
						INNER JOIN {$table_prefix}_troubletickets ON {$table_prefix}_troubletickets.ticketid = crm2.crmid
						WHERE module = 'ServiceContracts' AND {$table_prefix}_crmentityrel.relcrmid = ?
							AND relmodule = 'HelpDesk' AND crm1.deleted = 0 AND crm2.deleted = 0
						UNION ALL
						SELECT {$table_prefix}_crmentityrel.relcrmid FROM {$table_prefix}_crmentityrel
						INNER JOIN {$table_prefix}_crmentity crm1 ON crm1.crmid = {$table_prefix}_crmentityrel.crmid
						INNER JOIN {$table_prefix}_crmentity crm2 ON crm2.crmid = {$table_prefix}_crmentityrel.relcrmid
						INNER JOIN {$table_prefix}_troubletickets ON {$table_prefix}_troubletickets.ticketid = crm1.crmid
						WHERE relmodule = 'ServiceContracts' AND {$table_prefix}_crmentityrel.crmid = ?
							AND module = 'HelpDesk' AND crm1.deleted = 0 AND crm2.deleted = 0";
		
					$contract_tktresult = $adb->pquery($sql,Array($ticketId,$ticketId));
					if ($contract_tktresult) {
						while($row = $adb->fetchByAssoc($contract_tktresult,-1,false)){
							$_tmp_REQUEST = $_REQUEST; unset($_REQUEST);
							$scFocus = CRMEntity::getInstance('ServiceContracts');
							$scFocus->retrieve_entity_info_no_html($row['relcrmid'],'ServiceContracts');
							$scFocus->updateServiceContractState();
							$scFocus->mode = 'edit';
							$scFocus->formatFieldsForSave = false;
							$scFocus->save('ServiceContracts');
							$_REQUEST = $_tmp_REQUEST;
						}
					}
				}
			}
		}
	}
}